<?php
namespace Jelito\DevStack\Mocker;

class VerifyMethodInvocation
{
	/**
	 * @var array|string
	 */
	private $params;
	/**
	 * @var string
	 */
	private $methodName;

	/**
	 * @param array $params
	 * @param string $methodName
	 */
	public function __construct(array $params, $methodName)
	{
		$this->params = $this->treat($params);
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

	/**
	 * @param $param
	 * @return array|string
	 */
	private function treat($param)
	{
		if (is_array($param)) {
			foreach ($param as $name => $value) {
				$param[$name] = $this->treat($value);
			}
			return $param;
		} elseif (is_object($param)) {
			return get_class($param) . '-' . spl_object_hash($param);
		}
		return $param;
	}
}