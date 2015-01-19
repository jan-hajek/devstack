<?php
namespace Jelito\DevStack\Mocker;

class Method
{
	/**
	 * @var string
	 */
	private $methodName;
	/**
	 * @var Builder
	 */
	private $builder;

	/**
	 * @param string $methodName
	 * @param Builder $builder
	 */
	public function __construct($methodName, Builder $builder)
	{
		$this->methodName = $methodName;
		$this->builder = $builder;
	}

	/**
	 * @param mixed $value
	 * @return $this
	 */
	public function willReturn($value)
	{
		$this->setReturn('value', $value);
		return $this;
	}

	/**
	 * @param \Exception $exception
	 * @return $this
	 */
	public function willThrow(\Exception $exception)
	{
		$this->setReturn('exception', $exception);
		return $this;
	}

	/**
	 * @return $this
	 */
	public function willReturnSelf()
	{
		$this->setReturn('self', null);
		return $this;
	}

	/**
	 * @param int $argumentNo
	 * @return $this
	 */
	public function willReturnArgument($argumentNo)
	{
		$this->setReturn('argument', $argumentNo - 1);
		return $this;
	}

	/**
	 * @param callable $callBack
	 * @return $this
	 */
	public function willCallback(\Closure $callBack)
	{
		$this->setReturn('callback', $callBack);
		return $this;
	}

	/**
	 * @param string $type
	 * @param mixed $value
	 */
	private function setReturn($type, $value)
	{
		$this->builder->setReturn($this->methodName, array('type' => $type, 'value' => $value));
	}
}