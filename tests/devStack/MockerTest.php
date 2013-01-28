<?php
require_once PHPUNIT_PROJECT_DIR . '/DevStack/Testing/Mocker/Mocker.php';

class MockerTest extends PHPUnit_Framework_TestCase
{
	/**
	 * @test
	 */
	public function willReturn()
	{
		$mocker = $this->createMocker();
		$mocker->mockMethod('sum')->willReturn(1);
		
		$mock = $mocker->createMock();
		
		$this->assertEquals(1, $mock->sum(1,2));
	}
	
	/**
	 * @test
	 */
	public function willThrowException()
	{
		$mocker = $this->createMocker();
		
		$excpectedException = new Exception('test');
		$mocker->mockMethod('sum')->willThrow($excpectedException);
		
		$mock = $mocker->createMock();
		
		try {
			$mock->sum(1,2);
		} catch (Exception $actualException) {
			$this->assertSame($excpectedException, $actualException);
		}
	}
	
	/**
	 * @test
	 */
	public function willReturnSelf()
	{
		$mocker = $this->createMocker();
		
		$mocker->mockMethod('sum')->willReturnSelf();
		
		$mock = $mocker->createMock();
		
		$this->assertSame($mock, $mock->sum(1));
	}
	
	/**
	 * @test
	 */
	public function willReturnArgument()
	{
		$mocker = $this->createMocker();
		
		$mocker->mockMethod('sum')->willReturnArgument(1);
		
		$mock = $mocker->createMock();
		
		$this->assertEquals(1, $mock->sum(1));
		$this->assertEquals(2, $mock->sum(2));
		$this->assertEquals(3, $mock->sum(3));
	}
	
	/**
	 * @test
	 */
	public function willCallback()
	{
		$mocker = $this->createMocker();
		
		$mocker->mockMethod('sum')->willCallback(function($a, $b, $invocationsCount) {
			return $a * $b;
		});
		
		$mock = $mocker->createMock();
		
		$this->assertEquals(2, $mock->sum(1,2));
		$this->assertEquals(12, $mock->sum(3,4));
		$this->assertEquals(30, $mock->sum(5,6));
	}
	
	/**
	 * @test
	 * @expectedException \Mocker\UnknownVerifyMethodException
	 */
	public function unknownVerifyMethod()
	{
		$mocker = $this->createMocker();
	
		$mock = $mocker->createMock();
	
		$mocker->verifyMethod('sum');
	}
	
	/**
	 * @test
	 */
	public function numberOfInvocations()
	{
		$mocker = $this->createMocker();
		$mocker->mockMethod('sum');
		
		$mock = $mocker->createMock();
		
		$mocker->verifyMethod('sum')->calledNever();
		
		$mock->sum(1,2);
		$mocker->verifyMethod('sum')->calledOnce();
		
		$mock->sum(3,4);
		$mocker->verifyMethod('sum')->calledExactly(2);
	}
	
	/**
	 * @test
	 */
	public function invocationsExpectedParams()
	{
		$mocker = $this->createMocker();
		$mocker->mockMethod('sum');
		
		$mock = $mocker->createMock();
		
		$mock->sum(1,2);
		$mock->sum(3,4);
		$mock->sum(5,6);
		
		$mocker->verifyMethod('sum')->invocationNo(1)->expectedParams(1,2);
		$mocker->verifyMethod('sum')->invocationNo(2)->expectedParams(3,4);
		$mocker->verifyMethod('sum')->invocationNo(3)->expectedParams(5,6);
		$mocker->verifyMethod('sum')->invocationNo(-1)->expectedParams(5,6);
		$mocker->verifyMethod('sum')->invocationNo(-2)->expectedParams(3,4);
		$mocker->verifyMethod('sum')->invocationNo(-3)->expectedParams(1,2);
	}
	
	/**
	 * @test
	 * @expectedException \Mocker\NonExistentInvocationException
	 */
	public function nonExistedInvocation()
	{
		$mocker = $this->createMocker();
		$mocker->mockMethod('sum');
		
		$mock = $mocker->createMock();
		
		$mock->sum(1,2);
		
		$mocker->verifyMethod('sum')->invocationNo(2);
	}
	
	/**
	 * @test
	 */
	public function exceptionLikeExpectedParam()
	{
		$mocker = $this->createMocker();
		$mocker->mockMethod('sum')->willReturn(1);
		$mock = $mocker->createMock();
	
		$e = new InvalidArgumentException("asd");
		$mock->sum($e);
	
		$mocker->verifyMethod('sum')->invocationNo(1)->expectedParams($e, null);
	}
	
	/**
	 * @test
	 * @expectedException \Mocker\StaticMethodException
	 */
	public function tryDeclareStaticFunction()
	{
		$mocker = $this->createMocker();
		$mocker->mockMethod('staticMethod');
	}
	 
	/**
	 * @test
	 * @expectedException \Mocker\PrivateMethodException
	 */
	public function tryDeclarePrivateFunction()
	{
		$mocker = $this->createMocker();
		$mocker->mockMethod('privateMethod');
	}
	 
	/**
	 * @test
	 * @expectedException \Mocker\ProtectedMethodException
	 */
	public function tryDeclareProtectedFunction()
	{
		$mocker = $this->createMocker();
		$mocker->mockMethod('protectedMethod');
	}
	 
	/**
	 * @test
	 * @expectedException \Mocker\NonExistentMethodCallException
	 */
	public function tryDeclareNonExistentMethod()
	{
		$mocker = $this->createMocker();
		$mocker->mockMethod('nonExistentMethod');
	}
	 
	/**
	 * @test
	 * @expectedException \Mocker\UndeclaredMethodInvocationException
	 */
	public function callUndeclaredFunction()
	{
		$mocker = $this->createMocker();
		$mock = $mocker->createMock();
		$mock->sum(5, 6);
	}
	 
	/**
	 * @test
	 */
	public function setPublicProperty()
	{
		$mocker = $this->createMocker();
		$mock = $mocker->createMock();
		$mock->publicProperty = 10;
		$this->assertEquals(10, $mock->publicProperty);
	}
	
	private function createMocker()
	{
		return new \Mocker\Mocker('MockBuilderTestClass', $this);
	}
}

class MockBuilderTestClass
{
	public $publicProperty;
	
	public function sum($a, $b = null)
	{
		return $a + b;
	}
	
	public function divide($a, $b)
	{
		return $a / b;
	}
	
	private function privateMethod($a = null, $b = null)
	{
		return $a + b;
	}
	
	protected function protectedMethod($a = null, $b = null)
	{
		return $a + b;
	}
	
	public static function staticMethod($a = null, $b = null)
	{
		return $a + b;
	}
}

interface MockBuilderTestInterface
{
	public function sum($a, $b = null);
}