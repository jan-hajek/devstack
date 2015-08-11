<?php

class MockBuilderTestClass
{
	public $publicProperty;

	public function sum($a, $b = null)
	{
		return $a + $b;
	}

	public function divide($a, $b)
	{
		return $a / $b;
	}

	private function privateMethod($a = null, $b = null)
	{
		return $a + $b;
	}

	protected function protectedMethod($a = null, $b = null)
	{
		return $a + $b;
	}

	public static function staticMethod($a = null, $b = null)
	{
		return $a + $b;
	}

	final public function finalMethod($a = null, $b = null)
	{
		return $a + $b;
	}
}
