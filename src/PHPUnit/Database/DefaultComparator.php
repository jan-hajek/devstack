<?php
namespace Jelito\DevStack\PHPUnit\Database;

class DefaultComparator implements Comparator
{
	/**
	 * @param array $expected
	 * @param array $actual
	 * @param array $primaryKeys
	 * @return ComparatorError[]
	 */
	public function compare(array $expected, array $actual, array $primaryKeys)
	{
		$expected = $this->convertArrayByPrimaryKeys($expected, $primaryKeys);
		$actual = $this->convertArrayByPrimaryKeys($actual, $primaryKeys);

		$errors = array();
		$errors = $this->findMissingExpected($expected, $actual, $primaryKeys, $errors);
		$errors = $this->findMissingActual($expected, $actual, $primaryKeys, $errors);
		return $this->findNonEqual($expected, $actual, $primaryKeys, $errors);
	}

	/**
	 * vytvori pole kde index bude string z primary keys
	 * @param array $array
	 * @param array $primaryKeys
	 * @return array
	 */
	private function convertArrayByPrimaryKeys(array $array, array $primaryKeys)
	{
		$temp = array();
		foreach ($array as $row) {
			$id = implode(':', $this->getPrimaryKeyData($row, $primaryKeys));
			$temp[$id] = $row;
		}
		return $temp;
	}

	/**
	 * najde v radku primarni klice
	 * @param array $row
	 * @param array $primaryKeys
	 * @throws \Exception
	 * @return array
	 */
	private function getPrimaryKeyData(array $row, array $primaryKeys)
	{
		$data = array();
		foreach ($primaryKeys as $key) {
			if (!array_key_exists($key, $row)) {
				throw new \Exception("primary key '$key' not found, check expected output");
			}
			$data[$key] = $row[$key];
		}
		return $data;
	}

	/**
	 * @param array $expected
	 * @param array $actual
	 * @param array $primaryKeys
	 * @param ComparatorError[] $errors
	 * @return ComparatorError[]
	 */
	private function findMissingExpected($expected, $actual, $primaryKeys, array $errors)
	{
		$missingExpected = array_diff_key($actual, $expected);
		foreach ($missingExpected as $id => $row) {
			$primaryKeysData = $this->getPrimaryKeyData($row, $primaryKeys);
			$errors[] = $error = new ComparatorError();
			$error->primaryKeysData = $primaryKeysData;
			$error->type = ComparatorError::EXPECTED_ROW_NOT_FOUND;
			$error->actualRow = $row;
		}
		return $errors;
	}

	/**
	 * @param array $expected
	 * @param array $actual
	 * @param array $primaryKeys
	 * @param ComparatorError[] $errors
	 * @return ComparatorError[]
	 */
	private function findMissingActual($expected, $actual, $primaryKeys, array $errors)
	{
		$missingActual = array_diff_key($expected, $actual);
		foreach ($missingActual as $id => $row) {
			$primaryKeysData = $this->getPrimaryKeyData($row, $primaryKeys);
			$errors[] = $error = new ComparatorError();
			$error->primaryKeysData = $primaryKeysData;
			$error->type = ComparatorError::ACTUAL_ROW_NOT_FOUND;
			$error->expectedRow = $row;
		}
		return $errors;
	}

	/**
	 * @param array $expected
	 * @param array $actual
	 * @param array $primaryKeys
	 * @param ComparatorError[] $errors
	 * @return ComparatorError[]
	 */
	private function findNonEqual(array $expected, array $actual, array $primaryKeys, array $errors)
	{
		$forCompare = array_intersect_key($actual, $expected);
		foreach ($forCompare as $id => $actualRow) {
			$expectedRow = $expected[$id];
			$primaryKeysData = $this->getPrimaryKeyData($expectedRow, $primaryKeys);
			foreach ($actualRow as $columnName => $actualValue) {
				$expectedValue = $expectedRow[$columnName];
				if ($expectedValue != $actualValue) {
					$errors[] = $error = new ComparatorError();
					$error->primaryKeysData = $primaryKeysData;
					$error->expectedRow = $expectedRow;
					$error->actualRow = $actualRow;
					$error->columnName = $columnName;
					$error->expectedValue = $expectedValue;
					$error->actualValue = $actualValue;
					$error->type = ComparatorError::COLUMN_VALUE_NOT_EQUAL;
				}
			}
		}
		return $errors;
	}
}