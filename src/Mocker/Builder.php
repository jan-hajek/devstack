<?php
namespace Jelito\DevStack\Mocker;

/**
 * @internal
 */
class Builder
{
	private $className;
	private $mockMethods = array();
	private $returns = array();
	/**
	 * @var \PHPUnit_Framework_MockObject_MockObject
	 */
	private $mock;
	/**
	 * @var \PHPUnit_Framework_TestCase
	 */
	private $testCase;

	/**
	 * @var \ReflectionMethod[]
	 */
	private $originClassMethods = array();

	/**
	 * @var VerifyMethodInvocation[]
	 */
	private $invocations = array();
	/**
	 * @var VerifyMethod[]
	 */
	private $verifyMethods = array();

	public function __construct($className, \PHPUnit_Framework_TestCase $testCase)
	{
		$this->className = $className;
		$this->testCase = $testCase;

		$object = new \ReflectionClass($this->className);
		foreach ($object->getMethods() as $method) {
			$this->originClassMethods[$method->getName()] = $method;
		}
	}

	public function setReturn($methodName, $return)
	{
		$this->returns[$methodName] = $return;
	}

	/**
	 * @param $methodName
	 * @return VerifyMethodInvocation[]
	 */
	public function getInvocations($methodName)
	{
		return $this->invocations[$methodName];
	}

	/**
	 * @param string $name
	 * @return Method
	 */
	public function getMockMethod($name)
	{
		if (!isset($this->mockMethods[$name])) {
			$this->checkOriginClassMethod($name);
			$this->mockMethods[$name] = $method = new Method($name, $this);
			$this->invocations[$name] = array();
			$method->willReturn(null);
		}
		return $this->mockMethods[$name];
	}

	/**
	 * @param $name
	 * @return mixed
	 * @throws UnknownVerifyMethodException
	 */
	public function getVerifyMethod($name)
	{
		if (!isset($this->mockMethods[$name])) {
			throw new UnknownVerifyMethodException("verify method '$name' doesn't exists");
		}
		if (!isset($this->verifyMethods[$name])) {
			$this->verifyMethods[$name] = new VerifyMethod(
				$name,
				$this->className,
				$this
			);
		}
		return $this->verifyMethods[$name];
	}

	/**
	 * @param string $name
	 * @throws PrivateMethodException
	 * @throws NonExistentMethodCallException
	 * @throws StaticMethodException
	 * @throws ProtectedMethodException
	 */
	private function checkOriginClassMethod($name)
	{
		$originMethods = $this->originClassMethods;
		if (!array_key_exists($name, $originMethods)) {
			throw new NonExistentMethodCallException($this->className . "::" . $name . ' does not exists.');
		}
		$method = $originMethods[$name];
		if ($method->isStatic()) {
			throw new StaticMethodException($this->className . "::" . $name . ' is static, MockBuilder is useless. :(');
		}
		if ($method->isPrivate()) {
			throw new PrivateMethodException($this->className . "::" . $name . ' is private, MockBuilder is useless. :(');
		}
		if ($method->isProtected()) {
			throw new ProtectedMethodException($this->className . "::" . $name . ' is protected, MockBuilder is useless. :(');
		}
	}

	/**
	 * @return \PHPUnit_Framework_MockObject_MockObject
	 */
	public function createMock()
	{
		$methods = $this->mockMethods;

		$allMethodsNames = array_unique(array_merge(array_keys($methods), array_keys($this->originClassMethods)));

		$this->mock = $this->testCase->getMock($this->className, $allMethodsNames, array(), '', false, false, false);
		foreach ($methods as $methodName => $method) {
			$returnParam = isset($this->returns[$methodName]) ? $this->returns[$methodName] : null;
			$this->buildMethod($methodName, $returnParam);
		}
		$this->createUndeclaredMethods();
		return $this->mock;
	}

	/**
	 * @param string $methodName
	 * @param mixed $returnParam
	 */
	private function buildMethod($methodName, $returnParam)
	{
		$self = $this;

		$callBackFunction = function () use ($self, $methodName) {
			$inputParams = func_get_args();

			$return = $self->getReturn($methodName);
			$returnValue = $return['value'];
			$returnType = $return['type'];

			$self->addInvocation($methodName, $inputParams);

			switch ($returnType) {
				case 'exception':
					throw $returnValue;
					break;
				case 'value':
					return $returnValue;
					break;
				case 'self':
					return $self->getMock();
					break;
				case 'argument':
					return isset($inputParams[$returnValue]) ? $inputParams[$returnValue] : null;
					break;
				case 'callback':
					$inputParams[] = count($self->getInvocations($methodName));
					return call_user_func_array($returnValue, $inputParams);
					break;
			}
		};

		$method = $this->mock->expects($this->testCase->any());
		$method->method($methodName);
		$method->will($this->testCase->returnCallback($callBackFunction));
	}

	private function createUndeclaredMethods()
	{
		foreach (array_keys($this->originClassMethods) as $originalMethodName) {
			if (false == array_key_exists($originalMethodName, $this->mockMethods)) {
				$method = $this->mock->expects($this->testCase->any());
				$method->method($originalMethodName);
				$method->will(
					$this->testCase->throwException(new UndeclaredMethodInvocationException($originalMethodName))
				);
			}
		}
	}

	/**
	 * @return \PHPUnit_Framework_MockObject_MockObject
	 */
	public function getMock()
	{
		return $this->mock;
	}

	/**
	 * @param string $methodName
	 * @return mixed
	 */
	public function getReturn($methodName)
	{
		return $this->returns[$methodName];
	}

	/**
	 * @param string $methodName
	 * @param array $inputParams
	 */
	public function addInvocation($methodName, $inputParams)
	{
		$this->invocations[$methodName][] = new VerifyMethodInvocation($inputParams, $this->className . '::' . $methodName, $this);
	}
}