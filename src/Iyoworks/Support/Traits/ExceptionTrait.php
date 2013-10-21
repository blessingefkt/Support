<?php namespace Iyoworks\Support\Traits;
use Lang;
use Config;
trait ExceptionTrait{
	protected $title;

	public static function make($message = null, $langParams = array(), $title = null, $code = 0)
	{
		if($langKey = static::langGroup($message))
		{
			$locale = Config::get('app.locale');
			if (!is_array($langParams)) $langParams = ['value' => $langParams];
			if (isset($nmessage)) $message = $nmessage;
			$message = Lang::get($langKey, $langParams, $locale);
		}

		$error  = new static($message, $code);
		if($title) $error->setTitle($title);

		return $error;
	}

	public static function langGroup($msg)
	{
		if($msg){
			if(Lang::has($nmsg = 'exceptions.'.$msg))
				return $nmsg;
			if(Lang::has($msg))
				return $msg;
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
