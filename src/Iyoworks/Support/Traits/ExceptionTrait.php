<?php namespace Iyoworks\Support\Traits;
use Lang;
use Config;
trait ExceptionTrait{
	protected $title;

	public function paraseMessage($key, $params)
	{
		if (!is_array($params)) $params = [$params];
		foreach ($params as &$param) {
			if(is_array($param)) $param = implode(', ', $param);
		}

		return $this->getMessageTemplate($key, $params);
	}

	abstract protected function getMessageTemplate($key, $params);

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
