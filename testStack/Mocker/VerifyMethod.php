<?php
namespace Mocker;

class VerifyMethod
{
	private $methodName;
	private $builder;
		
	public function __construct($methodName, Builder $builder)
	{
		$this->methodName = $methodName;
		$this->builder = $builder;
	}
	
	public function calledOnce()
	{
		$this->invocationsTest(1);
		return $this;
	}
	
	public function calledExactly($count)
	{
		$this->invocationsTest($count);
		return $this;
	}
	
	public function calledNever()
	{
		$this->invocationsTest(0);
		return $this;
	}
	
	private function invocationsTest($expectedTimes)
	{
		$actual = count($this->getInvocations());
		\PHPUnit_Framework_Assert::assertEquals(
			$expectedTimes,
			$actual,
			"Method {$this->methodName} was expected to be called $expectedTimes times, actually called $actual times."
		);
	}
	
	/**
	 * @param int $no
	 * @return VerifyMethodInvocation
	 */
	public function invocationNo($no)
	{
		$invocations = $this->getInvocations();
		if($no < 0) {
			$no = count($invocations) + $no + 1;
		}
		if(!isset($invocations[$no - 1])) {
			throw new NonExistentInvocationException("Invocation no. $no doesn't exists");
		}
		return $invocations[$no - 1];
	}
	
	private function getInvocations()
	{
		return $this->builder->getInvocations($this->methodName);
	}
}

class VerifyMethodInvocation
{
	private $params;
	private $methodName;
	
	public function __construct(array $params, $methodName)
	{
		$this->params = $params;
		$this->methodName = $methodName;
	}
	
	public function expectedParams()
	{
		$expectedParams = func_get_args();
		\PHPUnit_Framework_Assert::assertSame(
			$expectedParams,
			$this->params,
			"Expected params for method {$this->methodName}"
		);
	}
}