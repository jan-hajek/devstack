<?php
namespace Jelito\DevStack\PHPUnit\Database;

use InvalidArgumentException;
use PHPUnit_Extensions_Database_DataSet_AbstractDataSet;
use PHPUnit_Extensions_Database_DataSet_DefaultTable;
use PHPUnit_Extensions_Database_DataSet_DefaultTableIterator;
use PHPUnit_Extensions_Database_DataSet_DefaultTableMetaData;

class JsonDataSet extends PHPUnit_Extensions_Database_DataSet_AbstractDataSet
{
	/**
	 * @var PHPUnit_Extensions_Database_DataSet_DefaultTable[]
	 */
	protected $tables = array();

	/**
	 * @param array $data
	 */
	public function __construct($data)
	{
		foreach ($data AS $tableName => $rows) {
			$columns = array();
			if (isset($rows[0])) {
				$columns = array_keys($rows[0]);
			}

			$table = new PHPUnit_Extensions_Database_DataSet_DefaultTable(
				new PHPUnit_Extensions_Database_DataSet_DefaultTableMetaData($tableName, $columns)
			);

			foreach ($rows AS $row) {
				$table->addRow($row);
			}
			$this->tables[$tableName] = $table;
		}
	}

	/**
	 * @param bool $reverse
	 * @return PHPUnit_Extensions_Database_DataSet_DefaultTableIterator
	 */
	protected function createIterator($reverse = FALSE)
	{
		return new PHPUnit_Extensions_Database_DataSet_DefaultTableIterator($this->tables, $reverse);
	}

	/**
	 * @param string $tableName
	 * @return PHPUnit_Extensions_Database_DataSet_DefaultTable
	 * @throws InvalidArgumentException
	 */
	public function getTable($tableName)
	{
		if (!isset($this->tables[$tableName])) {
			throw new InvalidArgumentException("$tableName is not a table in the current database.");
		}

		return $this->tables[$tableName];
	}
}