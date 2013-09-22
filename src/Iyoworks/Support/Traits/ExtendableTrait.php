<?php namespace Iyoworks\Support\Traits;
use Iyoworks\BadMethodCallException;
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
		throw BadMethodCallException::make('exceptions.method_dne', compact('method'));
	}
}
