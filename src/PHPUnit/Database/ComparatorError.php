<?php
namespace Jelito\DevStack\PHPUnit\Database;

class ComparatorError
{
	const EXPECTED_ROW_NOT_FOUND = 1;
	const ACTUAL_ROW_NOT_FOUND = 2;
	const COLUMN_VALUE_NOT_EQUAL = 3;

	public $primaryKeysData;
	public $expectedRow;
	public $actualRow;
	public $columnName;
	public $expectedValue;
	public $actualValue;
	public $type;
}