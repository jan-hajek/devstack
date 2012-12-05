<?php
namespace Mocker;

class VerifyMethod
{
	private $testCase;
	private $methodName;
	
	private $invocations = array();
	
	public function __construct(
		$methodName,
		$returnParam,
		\PHPUnit_Framework_MockObject_MockObject $mock,
		\PHPUnit_Framework_TestCase $testCase
	)
	{
		$this->testCase = $testCase;
		$this->methodName = $methodName;
		
		$invocations = &$this->invocations;
	
		$callBackFunction = function() use (&$invocations, $returnParam, $methodName, $testCase, $mock){
			$inputParams = func_get_args();
				
			$invocations[] = new VerifyMethodInvocation($inputParams, $methodName, $testCase);
				
			$returnValue = $returnParam['value'];
			$returnType = $returnParam['type'];
				
			switch ($returnType) {
				case 'exception':
					throw $returnValue;
					break;
				case 'value':
					return $returnValue;
					break;
				case 'self':
					return $mock;
					break;
				case 'argument':
					return isset($inputParams[$returnValue]) ? $inputParams[$returnValue] : null;
					break;
				case 'callback':
					$inputParams[] = count($invocations);
					return call_user_func_array($returnValue, $inputParams);
					break;
			}
		};
		
		$method = $mock->expects($testCase->any());
		$method->method($methodName);
		$method->will($this->testCase->returnCallback($callBackFunction));
	}
	
	public function calledOnce()
	{
		$this->timesCalledTest(1);
		return $this;
	}
	
	public function calledExactly($count)
	{
		$this->timesCalledTest($count);
		return $this;
	}
	
	public function calledNever()
	{
		$this->timesCalledTest(0);
		return $this;
	}
	
	private function timesCalledTest($expectedTimes)
	{
		$actual = count($this->invocations);
		$this->testCase->assertEquals(
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
		if($no < 0) {
			$no = count($this->invocations) + $no + 1;
		}
		if(!isset($this->invocations[$no - 1])) {
			throw new NonExistentInvocationException("Invocation no. $no doesn't exists");
		}
		return $this->invocations[$no - 1];
	}
}

class VerifyMethodInvocation
{
	private $params;
	private $methodName;
	private $testCase;
	
	public function __construct(array $params, $methodName, \PHPUnit_Framework_TestCase $testCase)
	{
		$this->params = $params;
		$this->methodName = $methodName;
		$this->testCase = $testCase;
	}
	
	public function expectedParams()
	{
		$expectedParams = func_get_args();
		$this->testCase->assertSame(
			$expectedParams,
			$this->params,
			"Expected params for method {$this->methodName}"
		);
	}
}