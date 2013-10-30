<?php namespace Iyoworks\Support\Traits;
use Lang;
use Config;
trait ExceptionTrait{
	protected $title;

	public function langGroup($msg, array $params)
	{
		if($msg){
			if(Lang::has($nmsg = 'exceptions.'.$msg) 
				or Lang::has($nmsg = $msg))
				return Lang::get($nmsg, $params);
		}
		return false;
	}

	public function setTitle($title)
	{
		$this->title = $title;
	}

	public function getTitle()
	{
		return $this->title?: $this->getDefaultTitle();
	}

	public function getDefaultTitle()
	{
		return "Looks like we have a problem";
	}

	public function getTraceArray()
	{
		$trace = preg_split ( '%\#[0-9]* %' , $this->getTraceAsString() , -1 );

		array_shift($trace);

		return $trace;
	}

	public function __toString()
	{
		return $this->title.': '.$this->getMessage();
	}
}
