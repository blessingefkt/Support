<?php namespace Iyoworks\Support\Traits;
use BadMethodCallException;

trait ExtendableTrait
{
	protected static $extensions = array();

	public static function extend($funcName, $callable)
	{
		static::$extensions[$funcName] = $callable;
	}

	protected function callExtension($method, $args)
	{
		if(isset(static::$extensions[$method]))
		{
			$func = static::$extensions[$method];
			array_push($args, $this);
			return callFuncWithArgs($func, $args);
		}
		throw new BadMethodCallException($method);
	}
}
