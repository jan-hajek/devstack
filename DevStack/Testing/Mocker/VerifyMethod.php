<?php
namespace Mocker;

class VerifyMethod
{
	private $className;
	private $methodName;
	private $builder;
		
	public function __construct($methodName, $className, Builder $builder)
	{
		$this->className = $className;
		$this->methodName = $methodName;
		$this->builder = $builder;
	}
	
	/**
	 * @return \Mocker\VerifyMethod
	 */
	public function calledOnce()
	{
		$this->invocationsTest(1);
		return $this;
	}
	
	/**
	 * @param int $count
	 * @return \Mocker\VerifyMethod
	 */
	public function calledExactly($count)
	{
		$this->invocationsTest($count);
		return $this;
	}
	
	/**
	 * @return \Mocker\VerifyMethod
	 */
	public function calledNever()
	{
		$this->invocationsTest(0);
		return $this;
	}
	
	private function invocationsTest($expectedTimes)
	{
		$actual = count($this->getInvocations());
		if($expectedTimes !== $actual) {
			throw new \PHPUnit_Framework_AssertionFailedError("Method {$this->className}::{$this->methodName} was expected to be called $expectedTimes times, actually called $actual times.");
		}
	}
	
	/**
	 * @param int $no
	 * @return \Mocker\VerifyMethodInvocation
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
		$this->params =  $this->treat($params);
		$this->methodName = $methodName;
	}
	
	/**
	 * @param mixed $param1
	 * @param mixed $param2
	 * @param mixed $paramN
	 * @return void
	 */
	public function expectedParams($param1 = null, $param2 = null, $paramN = null)
	{
		$expectedParams = $this->treat(func_get_args());
		
		\PHPUnit_Framework_Assert::assertSame(
			$expectedParams,
			$this->params,
			"Expected params for method {$this->methodName}"
		);
	}
	
	private function treat($param)
	{
		if(is_array($param)) {
			foreach ($param as $name => $value) {
				$param[$name] = $this->treat($value);
			}
			return $param;
		} elseif(is_object($param)) {
			return get_class($param) . '-' . spl_object_hash($param);
		}
		return $param;
	}
}