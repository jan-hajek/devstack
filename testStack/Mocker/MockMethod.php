<?php
namespace Mocker;

class Method
{
	private $methodName;
	private $builder;
	
	public function __construct($methodName, Builder $builder)
	{
		$this->methodName = $methodName;
		$this->builder = $builder;
	}
	
	public function willReturn($value)
	{
		$this->setReturn('value', $value);
		return $this;
	}
	
	public function willThrow(\Exception $exception)
	{
		$this->setReturn('exception', $exception);
		return $this;
	}
	
	public function willReturnSelf()
	{
		$this->setReturn('self', null);
		return $this;
	}
	
	public function willReturnArgument($argumentNo)
	{
		$this->setReturn('argument', $argumentNo - 1);
		return $this;
	}
	
	public function willCallback(\Closure $callBack)
	{
		$this->setReturn('callback', $callBack);
		return $this;
	}
	
	private function setReturn($type, $value)
	{
		$this->builder->setReturn($this->methodName, array('type' => $type, 'value' => $value));
	}
}