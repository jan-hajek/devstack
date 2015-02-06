<?php
namespace Jelito\DevStack\PHPUnit;

use Jelito\DevStack\Mocker\Mocker;
use Jelito\DevStack\PHPUnit\Database\DatabaseNotSetException;
use Jelito\DevStack\PHPUnit\Database\Functions;
use PDO;

abstract class TestCase extends \PHPUnit_Framework_TestCase
{
	/**
	 * @var Functions
	 */
	private $databaseFunctions;

	/**
	 * @param string $dsn
	 * @param $username
	 * @param $pass
	 */
	protected function setTestDatabase($dsn, $username, $pass)
	{
		$this->databaseFunctions = new Functions($this, new PDO($dsn, $username, $pass));
	}

	/**
	 * @throws DatabaseNotSetException
	 * @return Functions
	 */
	protected function database()
	{
		if (!$this->databaseFunctions) {
			throw new DatabaseNotSetException("database doesn't init, use setTestDatabase() first");
		}
		return $this->databaseFunctions;
	}

	/**
	 * @param $className
	 * @return Mocker
	 */
	protected function createMocker($className)
	{
		return new Mocker($className, $this);
	}
}