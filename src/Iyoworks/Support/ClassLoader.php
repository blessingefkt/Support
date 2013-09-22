<?php namespace Iyoworks\Support;

class ClassLoader extends  \Illuminate\Support\ClassLoader{

	protected static $loader;

	/**
	 * @param  \Composer\Autoload\ClassLoader  $loader
	 * @return void
	 */
	public static function setLoader(\Composer\Autoload\ClassLoader $loader)
	{
		static::$loader = $loader;
	}

	/**
	 * @param  \Composer\Autoload\ClassLoader  $loader
	 * @return void
	 */
	public static function getLoader()
	{
		return static::$loader;
	}
}
