<?php
namespace Jelito\DevStack\PHPUnit\Database;

use PDO;
use PHPUnit_Extensions_Database_DB_DefaultDatabaseConnection;
use PHPUnit_Extensions_Database_DB_MetaData_MySQL;
use PHPUnit_Extensions_Database_DefaultTester;
use PHPUnit_Extensions_Database_Operation_Factory;

class Functions
{
	/**
	 * @var \PDO
	 */
	private $pdo;
	/**
	 * @var \PHPUnit_Extensions_Database_ITester
	 */
	private $databaseTester;
	/**
	 * @var \PHPUnit_Extensions_Database_TestCase
	 */
	private $testCase;
	/**
	 * @var Comparator
	 */
	private $comparator;
	/**
	 * @var ErrorPrinter
	 */
	private $errorPrinter;

	/** @var array */
	private static $messages = array(
		JSON_ERROR_DEPTH => 'The maximum stack depth has been exceeded',
		JSON_ERROR_STATE_MISMATCH => 'Syntax error, malformed JSON',
		JSON_ERROR_CTRL_CHAR => 'Unexpected control character found',
		JSON_ERROR_SYNTAX => 'Syntax error, malformed JSON',
		5 /*JSON_ERROR_UTF8*/ => 'Invalid UTF-8 sequence', // exists since 5.3.3, but is returned since 5.3.1
	);

	/**
	 * @param \PHPUnit_Framework_Test $testCase
	 * @param PDO $pdo
	 */
	public function __construct(\PHPUnit_Framework_Test $testCase, PDO $pdo)
	{
		$this->pdo = $pdo;
		$this->testCase = $testCase;

		$this->databaseTester = new PHPUnit_Extensions_Database_DefaultTester(
			new PHPUnit_Extensions_Database_DB_DefaultDatabaseConnection($this->pdo)
		);
		$this->comparator = new DefaultComparator();
		$this->errorPrinter = new DefaultErrorPrinter();
	}

	/**
	 * @param string $filePath
	 */
	public function setupFromSqlFile($filePath)
	{
		ob_start();
		require $filePath;
		$data = ob_get_clean();

		$this->pdo->query('SET FOREIGN_KEY_CHECKS=0');
		$this->pdo->query($data);
		$this->pdo->query('SET FOREIGN_KEY_CHECKS=1');
	}

	/**
	 * nastavi vstup
	 *
	 * @param string $jsonFilePath
	 * @throws \Exception
	 */
	public function setupFromJsonFile($jsonFilePath)
	{
		if (!file_exists($jsonFilePath)) {
			throw new \Exception("file '$jsonFilePath' does not found");
		}
		$json = file_get_contents($jsonFilePath);
		$data = $this->jsonDecode($json);

		$this->pdo->query('SET FOREIGN_KEY_CHECKS=0');
		$this->databaseTester->setSetUpOperation(PHPUnit_Extensions_Database_Operation_Factory::CLEAN_INSERT());
		$this->databaseTester->setDataSet(new JsonDataSet($data));
		$this->databaseTester->onSetUp();
		$this->pdo->query('SET FOREIGN_KEY_CHECKS=1');
	}

	/**
	 * zkontroluje vystup
	 *
	 * @param string $jsonFilePath
	 * @throws \Exception
	 */
	public function assertJsonFileEqual($jsonFilePath)
	{
		if (!file_exists($jsonFilePath)) {
			throw new \Exception("file '$jsonFilePath' does not found");
		}
		$json = file_get_contents($jsonFilePath);

		$data = $this->jsonDecode($json);

		foreach ($data as $tableName => $info) {
			$primaryKeys = $this->getPrimaryKeys($tableName, $info);
			$expected = array_key_exists('items', $info) ? $info['items'] : $info;
			$actual = $this->pdo->query("SELECT " . $this->getColumnString($expected) . " FROM $tableName")
				->fetchAll(PDO::FETCH_ASSOC);

			$errors = $this->comparator->compare($expected, $actual, $primaryKeys);
			$this->testCase->addToAssertionCount(max(count($expected), count($actual)));
			if (count($errors)) {
				$this->testCase->fail($this->errorPrinter->getString($tableName, $errors, $primaryKeys));
			}
		}
	}

	/**
	 * @param string $tableName
	 * @param array $info
	 * @return array
	 */
	private function getPrimaryKeys($tableName, array $info)
	{
		if (array_key_exists('primaryKeys', $info)) {
			return $info['primaryKeys'];
		} else {
			return (new PHPUnit_Extensions_Database_DB_MetaData_MySQL($this->pdo))->getTablePrimaryKeys($tableName);
		}
	}

	/**
	 * @param array $items
	 * @return string
	 */
	private function getColumnString(array $items)
	{
		foreach ($items as $item) {
			return implode(',', array_keys($item));
		}
		return '*';
	}

	/**
	 * @param string $json
	 * @return array
	 */
	private function jsonDecode($json)
	{
		$json = (string) $json;
		if (!preg_match('##u', $json)) {
			throw new \RuntimeException('Invalid UTF-8 sequence', 5); // workaround for PHP < 5.3.3 & PECL JSON-C
		}

		$args = array($json, true);
		$args[] = 512;
		if (PHP_VERSION_ID >= 50400 && !(defined('JSON_C_VERSION') && PHP_INT_SIZE > 4)) { // not implemented in PECL JSON-C 1.3.2 for 64bit systems
			$args[] = JSON_BIGINT_AS_STRING;
		}
		$value = call_user_func_array('json_decode', $args);

		if ($value === NULL && $json !== '' && strcasecmp($json, 'null')) { // '' is not clearing json_last_error
			$error = json_last_error();
			throw new \RuntimeException(isset(static::$messages[$error]) ? static::$messages[$error] : 'Unknown error', $error);
		}
		return $value;
	}
}