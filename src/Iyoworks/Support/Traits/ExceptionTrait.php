<?php namespace Iyoworks\Support\Traits;
use Lang;
use Config;
trait ExceptionTrait{
	protected $title;
	protected static $transformLangGroup;

	public static function make($message = null, $langParams = array(), $title = null, $code = 0)
	{
		static::beforeMake();

		if($message and (Lang::has($message) or Lang::has($nmessage = static::$transformLangGroup($message))))
		{
			$locale = Config::get('app.locale');
			if (!is_array($langParams)) $langParams = ['value' => $langParams];
			if (isset($nmessage)) $message = $nmessage;
			$message = Lang::get($message, $langParams, $locale);
		}

		$error  = new static($message, $code);
		if($title) $error->setTitle($title);

		return $error;
	}

	public static function beforeMake()
	{
		static::$transformLangGroup = function($msg){ return 'exceptions.'.$msg; };
		
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
