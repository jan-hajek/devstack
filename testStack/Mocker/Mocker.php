<?php
namespace Mocker;

require_once __DIR__ . '/MockMethod.php';
require_once __DIR__ . '/VerifyMethod.php';

class Mock
{
	private $className;
	private $testCase;
	
	private $methods = array();
	private $verifyMethods = array();
	
	public function __construct($className, \PHPUnit_Framework_TestCase $testCase)
	{
		$this->testCase = $testCase;
		$this->className = $className;
	}
	
	/**
	 * @param string $name
	 * @return Method
	 */
	public function mockMethod($name)
	{
		if(!isset($this->methods[$name])) {
			$this->checkOriginClassMethod($name);
			$this->methods[$name] = new Method();
		}
		return $this->methods[$name];
	}
	
	private function checkOriginClassMethod($name)
	{
		$method = $this->getOriginClassMethod($name);
		if(!$method) {
			throw new NonExistentMethodCallException($this->className . "::" . $name . ' does not exists.');
		}
		if($method->isStatic()) {
			throw new StaticMethodException($this->className . "::" . $name . ' is static, MockBuilder is useless. :(');
		}
		if($method->isPrivate()) {
			throw new PrivateMethodException($this->className . "::" . $name . ' is private, MockBuilder is useless. :(');
		}
		if($method->isProtected()) {
			throw new ProtectedMethodException($this->className . "::" . $name . ' is protected, MockBuilder is useless. :(');
		}
	}

	
	/**
	 * @param string $name
	 * @return \ReflectionMethod
	 */
	private function getOriginClassMethod($name)
	{
		foreach ($this->getOriginClassMethods() as $method) {
			if($method->getName() == $name) {
				return $method;
			}
		}
		return null;
	}
	
	private $originClassMethodsCache;
	private function getOriginClassMethods()
	{
		if(!$this->originClassMethodsCache) {
			$object = new \ReflectionClass($this->className);
			$this->originClassMethodsCache = $object->getMethods();
		}
		return $this->originClassMethodsCache;
	}
	
	/**
	 * @param string $name
	 * @return VerifyMethod
	 */
	public function verifyMethod($name)
	{
		if(!isset($this->verifyMethods[$name])) {
			// FIXME - jhajek vyjimka
		}
		return $this->verifyMethods[$name];
	}
	
	public function createMock()
	{
		$methods = $this->getMethodsForMock();
		
		$mock = $this->testCase->getMock($this->className, array_keys($methods), array(), '', false, false, false);
		foreach ($methods as $methodName => $method) {
			$returnParam = $this->getReturnParam($method);
			$this->verifyMethods[$methodName] = new VerifyMethod(
				$methodName,
				$returnParam,
				$mock,
				$this->testCase
			);
		}
		
		return $mock;
	}
	
	private function getMethodsForMock()
	{
		$mockMethods = $this->methods;
		
		foreach ($this->getOriginClassMethods() as $originalMethod) {
			$originalMethodName = $originalMethod->getName();
			if (false == array_key_exists($originalMethodName, $mockMethods)) {
				$method = $this->createUndeclaredMethod($originalMethodName);
				$mockMethods[$originalMethodName] = $method;
			}
		}
		return $mockMethods;
	}
	
	private function createUndeclaredMethod($name)
	{
		$method = new Method();
		$method->willThrow(new UndeclaredMethodInvocationException($name));
		return $method;
	}
	
	private function getReturnParam(Method $method)
	{
		$object = new \ReflectionObject($method);
		$property = $object->getProperty('return');
		$property->setAccessible(true);
		return $property->getValue($method);
	}
}

class CallException extends \Exception {};
class StaticMethodException extends \Exception {}
class PrivateMethodException extends \Exception {}
class ProtectedMethodException extends \Exception {}
class NonExistentMethodCallException extends \Exception {}
class NonExistentInvocationException extends \Exception {}
class UndeclaredMethodInvocationException extends \Exception {}