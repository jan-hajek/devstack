<?php
namespace Jelito\DevStack\Mocker;

class VerifyMethod
{
	/**
	 * @var string
	 */
	private $className;
	/**
	 * @var string
	 */
	private $methodName;
	/**
	 * @var Builder
	 */
	private $builder;

	/**
	 * @param string $methodName
	 * @param string $className
	 * @param Builder $builder
	 */
	public function __construct($methodName, $className, Builder $builder)
	{
		$this->className = $className;
		$this->methodName = $methodName;
		$this->builder = $builder;
	}

	/**
	 * @return VerifyMethod
	 */
	public function calledOnce()
	{
		$this->invocationsTest(1);
		return $this;
	}

	/**
	 * @param int $count
	 * @return VerifyMethod
	 */
	public function calledExactly($count)
	{
		$this->invocationsTest($count);
		return $this;
	}

	/**
	 * @return VerifyMethod
	 */
	public function calledNever()
	{
		$this->invocationsTest(0);
		return $this;
	}

	/**
	 * @param int $expectedTimes
	 * @throws \PHPUnit_Framework_AssertionFailedError
	 */
	private function invocationsTest($expectedTimes)
	{
		$actual = count($this->getInvocations());
		if ($expectedTimes !== $actual) {
			throw new \PHPUnit_Framework_AssertionFailedError(
				"Method {$this->className}::{$this->methodName} was expected to be called $expectedTimes times, actually called $actual times."
			);
		}
	}

	/**
	 * @param $no
	 * @return VerifyMethodInvocation
	 * @throws NonExistentInvocationException
	 */
	public function invocationNo($no)
	{
		$invocations = $this->getInvocations();
		if ($no < 0) {
			$no = count($invocations) + $no + 1;
		}
		if (!isset($invocations[$no - 1])) {
			throw new NonExistentInvocationException("Invocation no. $no doesn't exists");
		}
		return $invocations[$no - 1];
	}

	/**
	 * @return VerifyMethodInvocation[]
	 */
	private function getInvocations()
	{
		return $this->builder->getInvocations($this->methodName);
	}
}