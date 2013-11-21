<?php namespace Iyoworks\Support;

class ClassLoader extends  \Illuminate\Support\ClassLoader
{

	protected static $composer;

	/**
	 * @param  \Composer\Autoload\ClassLoader  $composer
	 * @return void
	 */
	public static function setComposer(\Composer\Autoload\ClassLoader $composer)
	{
		static::$composer = $composer;
	}

	/**
	 * @param  \Composer\Autoload\ClassLoader  $composer
	 * @return void
	 */
	public static function getComposer()
	{
		return static::$composer;
	}
}
