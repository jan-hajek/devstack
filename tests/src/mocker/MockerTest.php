<?php
namespace Jelito\DevStack\Mocker;

use Exception;

require_once __DIR__ . '/MockBuilderInterface.php';
require_once __DIR__ . '/MockBuilderTestClass.php';

class MockerTest extends \PHPUnit_Framework_TestCase
{
	/**
	 * @test
	 */
	public function willReturn()
	{
		$mocker = $this->createMocker();
		$mocker->mockMethod('sum')->willReturn(1);

		$mock = $this->createMock($mocker);

		$this->assertEquals(1, $mock->sum(1, 2));
	}

	/**
	 * @test
	 */
	public function willThrowException()
	{
		$mocker = $this->createMocker();

		$expectedException = new Exception('test');
		$mocker->mockMethod('sum')->willThrow($expectedException);

		$mock = $this->createMock($mocker);

		try {
			$mock->sum(1, 2);
		} catch (Exception $actualException) {
			$this->assertSame($expectedException, $actualException);
		}
	}

	/**
	 * @test
	 */
	public function willReturnSelf()
	{
		$mocker = $this->createMocker();

		$mocker->mockMethod('sum')->willReturnSelf();

		$mock = $this->createMock($mocker);

		$this->assertSame($mock, $mock->sum(1));
	}

	/**
	 * @test
	 */
	public function willReturnArgument()
	{
		$mocker = $this->createMocker();

		$mocker->mockMethod('sum')->willReturnArgument(1);

		$mock = $this->createMock($mocker);

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

		$mocker->mockMethod('sum')->willCallback(function ($a, $b) {
			return $a * $b;
		});

		$mock = $this->createMock($mocker);

		$this->assertEquals(2, $mock->sum(1, 2));
		$this->assertEquals(12, $mock->sum(3, 4));
		$this->assertEquals(30, $mock->sum(5, 6));
	}

	/**
	 * @test
	 * @expectedException \Jelito\DevStack\Mocker\Exception\UnknownVerifyMethodException
	 */
	public function unknownVerifyMethod()
	{
		$mocker = $this->createMocker();

		$mocker->verifyMethod('sum');
	}

	/**
	 * @test
	 */
	public function numberOfInvocations()
	{
		$mocker = $this->createMocker();
		$mocker->mockMethod('sum');

		$mock = $this->createMock($mocker);

		$mocker->verifyMethod('sum')->calledNever();

		$mock->sum(1, 2);
		$mocker->verifyMethod('sum')->calledOnce();

		$mock->sum(3, 4);
		$mocker->verifyMethod('sum')->calledExactly(2);
	}

	/**
	 * @test
	 */
	public function invocationsExpectedParams()
	{
		$mocker = $this->createMocker();
		$mocker->mockMethod('sum');

		$mock = $this->createMock($mocker);

		$mock->sum(1, 2);
		$mock->sum(3, 4);
		$mock->sum(5, 6);

		$mocker->verifyMethod('sum')->invocationNo(1)->expectedParams(1, 2);
		$mocker->verifyMethod('sum')->invocationNo(2)->expectedParams(3, 4);
		$mocker->verifyMethod('sum')->invocationNo(3)->expectedParams(5, 6);
		$mocker->verifyMethod('sum')->invocationNo(-1)->expectedParams(5, 6);
		$mocker->verifyMethod('sum')->invocationNo(-2)->expectedParams(3, 4);
		$mocker->verifyMethod('sum')->invocationNo(-3)->expectedParams(1, 2);
	}

	/**
	 * @test
	 * @expectedException \Jelito\DevStack\Mocker\Exception\NonExistentInvocationException
	 */
	public function nonExistedInvocation()
	{
		$mocker = $this->createMocker();
		$mocker->mockMethod('sum');

		$mock = $this->createMock($mocker);

		$mock->sum(1, 2);

		$mocker->verifyMethod('sum')->invocationNo(2);
	}

	/**
	 * @test
	 */
	public function exceptionLikeExpectedParam()
	{
		$mocker = $this->createMocker();
		$mocker->mockMethod('sum')->willReturn(1);
		$mock = $this->createMock($mocker);

		$e = new \InvalidArgumentException("asd");
		$mock->sum($e);

		$mocker->verifyMethod('sum')->invocationNo(1)->expectedParams($e, null);
	}

	/**
	 * @test
	 * @expectedException \Jelito\DevStack\Mocker\Exception\StaticMethodException
	 */
	public function tryDeclareStaticFunction()
	{
		$mocker = $this->createMocker();
		$mocker->mockMethod('staticMethod');
	}

	/**
	 * @test
	 * @expectedException \Jelito\DevStack\Mocker\Exception\PrivateMethodException
	 */
	public function tryDeclarePrivateFunction()
	{
		$mocker = $this->createMocker();
		$mocker->mockMethod('privateMethod');
	}

	/**
	 * @test
	 * @expectedException \Jelito\DevStack\Mocker\Exception\ProtectedMethodException
	 */
	public function tryDeclareProtectedFunction()
	{
		$mocker = $this->createMocker();
		$mocker->mockMethod('protectedMethod');
	}

	/**
	 * @test
	 * @expectedException \Jelito\DevStack\Mocker\Exception\NonExistentMethodException
	 */
	public function tryDeclareNonExistentMethod()
	{
		$mocker = $this->createMocker();
		$mocker->mockMethod('nonExistentMethod');
	}

	/**
	 * @test
	 * @expectedException \Jelito\DevStack\Mocker\Exception\FinalMethodException
	 */
	public function tryDeclareFinalMethod()
	{
		$mocker = $this->createMocker();
		$mocker->mockMethod('finalMethod');
	}

	/**
	 * @test
	 * @expectedException \Jelito\DevStack\Mocker\Exception\UndeclaredMethodInvocationException
	 */
	public function callUndeclaredFunction()
	{
		$mocker = $this->createMocker();
		$mock = $this->createMock($mocker);
		$mock->sum(5, 6);
	}

	/**
	 * @test
	 */
	public function setPublicProperty()
	{
		$mocker = $this->createMocker();
		$mock = $this->createMock($mocker);
		$mock->publicProperty = 10;
		$this->assertEquals(10, $mock->publicProperty);
	}

	/**
	 * @return Mocker
	 */
	private function createMocker()
	{
		return new Mocker('MockBuilderTestClass', $this);
	}

	/**
	 * @param Mocker $mocker
	 * @return \MockBuilderTestClass
	 */
	private function createMock(Mocker $mocker)
	{
		return $mocker->createMock();
	}
}

