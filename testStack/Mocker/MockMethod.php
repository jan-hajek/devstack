<?php
namespace Mocker;

class Method
{
	private $return;
	
	public function willReturn($value)
	{
		$this->return = array('type' => 'value', 'value' => $value);
		return $this;
	}
	
	public function willThrow(\Exception $exception)
	{
		$this->return = array('type' => 'exception', 'value' => $exception);
		return $this;
	}
	
	public function willReturnSelf()
	{
		$this->return = array('type' => 'self', 'value' => '');
		return $this;
	}
	
	public function willReturnArgument($argumentNo)
	{
		$this->return = array('type' => 'argument', 'value' => $argumentNo - 1);
		return $this;
	}
	
	public function willCallback(\Closure $callBack)
	{
		$this->return = array('type' => 'callback', 'value' => $callBack);
		return $this;
	}
	
}