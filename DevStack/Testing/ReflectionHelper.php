<?php
class ReflectionHelper
{
	/**
	 *
	 * @param $class string|class
	 * @throws InvalidArgumentException
	 * @return Reflector
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
			throw new InvalidArgumentException('parametr class musi byt string nebo objekt');
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

	public static function isMethodStatic ($class, $name)
	{
		$method = self::getMethod($class, $name);
		return $method->isStatic();
	}

	/**
	 *
	 * @param $class object
	 * @param $name string
	 * @param $args array
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
	 *
	 * @param $class string|object
	 * @param $propertyName string
	 * @return ReflectionProperty
	 */
	public static function getProperty ($class, $propertyName)
	{
		$refObj = self::getObject($class);
		$refProp = $refObj->getProperty($propertyName);
		$refProp->setAccessible(true);
		return $refProp;
	}

	public static function getPropertyValue ($class, $propertyName)
	{
		$refProp = self::getProperty($class, $propertyName);
		return $refProp->getValue($class);
	}

	public static function setPropertyValue ($class, $propertyName, $value)
	{
		$refObj = self::getObject($class);
		$refProp = $refObj->getProperty($propertyName);
		$refProp->setAccessible(true);
		$refProp->setValue($class, $value);
	}

	public static function setStaticPropertyValue ($class, $propertyName, $value)
	{
		$refProp = self::getProperty($class, $propertyName);
		$refProp->setValue($value);
	}
}