<?php
namespace Jelito\DevStack\PHPUnit\Database;

interface Comparator
{
	/**
	 * @param array $expected
	 * @param array $actual
	 * @param array $primaryKeys
	 */
	public function compare(array $expected, array $actual, array $primaryKeys);
}