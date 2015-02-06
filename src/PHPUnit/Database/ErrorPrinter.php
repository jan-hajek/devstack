<?php
namespace Jelito\DevStack\PHPUnit\Database;

interface ErrorPrinter
{
	/**
	 * @param string $tableName
	 * @param array $errors
	 * @param array $primaryKeys
	 * @return string
	 */
	public function getString($tableName, array $errors, array $primaryKeys);
}