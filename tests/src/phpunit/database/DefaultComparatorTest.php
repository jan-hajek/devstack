<?php
namespace Jelito\DevStack\PHPUnit\Database;

class DefaultComparatorTest extends \PHPUnit_Framework_TestCase
{
	/**
	 * @test
	 */
	public function valuesAreEqual()
	{
		$expected = $actual = $this->getExpectedArray();
		$actual[0]["float_value"] = 3;
		$expectedErrors = array();

		$this->assertErrors($expected, $actual, $expectedErrors);
	}

	/**
	 * @test
	 */
	public function missingExpectedRow()
	{
		$expected = $actual = $this->getExpectedArray();
		$actual[] = $newActualRow = array(
			'id' => 2,
		);

		$expectedErrors[] = $error = new ComparatorError();
		$error->type = ComparatorError::EXPECTED_ROW_NOT_FOUND;
		$error->actualRow = $newActualRow;
		$error->primaryKeysData = array('id' => 2);

		$this->assertErrors($expected, $actual, $expectedErrors);
	}

	/**
	 * @test
	 */
	public function missingActualRow()
	{
		$expected = $actual = $this->getExpectedArray();
		$expected[] = $newExpectedRow = array(
			'id' => 2,
		);

		$expectedErrors[] = $error = new ComparatorError();
		$error->type = ComparatorError::ACTUAL_ROW_NOT_FOUND;
		$error->expectedRow = $newExpectedRow;
		$error->primaryKeysData = array('id' => 2);

		$this->assertErrors($expected, $actual, $expectedErrors);
	}

	/**
	 * @return array
	 */
	private function getExpectedArray()
	{
		return array(
			array(
				'id' => 1,
				'int_value' => 1,
				'string_value' => "ěščřžýáíé",
				"float_value" => (3/100) * 100,
			)
		);
	}

	/**
	 * @param array $expected
	 * @param array $actual
	 * @param ComparatorError[] $expectedErrors
	 */
	private function assertErrors(array $expected, array $actual,  array $expectedErrors)
	{
		$primaryKeys = array('id');
		$actualErrors = (new DefaultComparator())->compare($expected, $actual, $primaryKeys);
		$this->assertEquals($expectedErrors, $actualErrors);
	}
}

