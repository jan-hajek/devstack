<?php
namespace Jelito\DevStack;

use InvalidArgumentException;
use ReflectionClass;
use ReflectionMethod;
use ReflectionObject;
use Reflector;

class ReflectionHelper
{
	/**
	 * @param string|object $class
	 * @return ReflectionClass|ReflectionObject
	 * @throws \InvalidArgumentException
	 */
	public static function getObject ($class)
	{
		if (is_string($class)) {
			$refObj = new ReflectionClass($class);
		} elseif (is_object($class)) {
			if ($class instanceof Reflector) {
				return $class;
			}
			$refObj = new ReflectionObject($class);
		} else {
			throw new InvalidArgumentException('parameter class must be string or object');
		}
		return $refObj;
	}

	/**
	 *
	 * @param $class object
	 * @param $name string
	 * @return ReflectionMethod
	 */
	public static function getMethod ($class, $name)
	{
		$class = self::getObject($class);
		$method = $class->getMethod($name);
		$method->setAccessible(true);
		return $method;
	}

	/**
	 * @param string|object $class
	 * @param string $name
	 * @return bool
	 */
	public static function isMethodStatic ($class, $name)
	{
		$method = self::getMethod($class, $name);
		return $method->isStatic();
	}

	/**
	 * @param string|object $class
	 * @param string $name
	 * @param null $arg1
	 * @param null $arg2
	 * @return mixed
	 */
	public static function callMethod ($class, $name, $arg1 = null, $arg2 = null)
	{
		$args = func_get_args();
		array_shift($args);
		array_shift($args);
		
		$method = self::getMethod($class, $name);
		return $method->invokeArgs($class, $args);
	}

	/**
	 * @param string|object $class
	 * @param string $propertyName
	 * @return \ReflectionProperty
	 */
	public static function getProperty ($class, $propertyName)
	{
		$refObj = self::getObject($class);
		$refProp = $refObj->getProperty($propertyName);
		$refProp->setAccessible(true);
		return $refProp;
	}

	/**
	 * @param string|object $class
	 * @param string $propertyName
	 * @return mixed
	 */
	public static function getPropertyValue ($class, $propertyName)
	{
		$refProp = self::getProperty($class, $propertyName);
		return $refProp->getValue($class);
	}

	/**
	 * @param string|object $class
	 * @param string $propertyName
	 * @param mixed $value
	 */
	public static function setPropertyValue ($class, $propertyName, $value)
	{
		$refObj = self::getObject($class);
		$refProp = $refObj->getProperty($propertyName);
		$refProp->setAccessible(true);
		$refProp->setValue($class, $value);
	}

	/**
	 * @param string|object $class
	 * @param string $propertyName
	 * @param mixed $value
	 */
	public static function setStaticPropertyValue ($class, $propertyName, $value)
	{
		$refProp = self::getProperty($class, $propertyName);
		$refProp->setValue($value);
	}
}