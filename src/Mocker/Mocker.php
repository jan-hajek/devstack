<?php
namespace Jelito\DevStack\Mocker;

require_once __DIR__ . '/Builder.php';
require_once __DIR__ . '/MockMethod.php';
require_once __DIR__ . '/VerifyMethod.php';

class Mocker
{
	/**
	 * @var Builder
	 */
	private $builder;

	/**
	 * @param string $className
	 * @param \PHPUnit_Framework_TestCase $testCase
	 */
	public function __construct($className, \PHPUnit_Framework_TestCase $testCase)
	{
		$this->builder = new Builder($className, $testCase);
	}

	/**
	 * @param string $name
	 * @return Method
	 */
	public function mockMethod($name)
	{
		return $this->builder->getMockMethod($name);
	}

	/**
	 * @param string $name
	 * @return VerifyMethod
	 */
	public function verifyMethod($name)
	{
		return $this->builder->getVerifyMethod($name);
	}

	/**
	 * @return \PHPUnit_Framework_MockObject_MockObject
	 */
	public function createMock()
	{
		return $this->builder->createMock();
	}
}