<?php namespace Iyoworks\Support;

abstract class BaseEnum {

	public static function isValid($type)
	{
		return array_key_exists($type, static::toArray()) || 
			(array_search($type, static::toArray(), true) !== false);
	}

	public static function toArray(){
		static $reflection;
		if (is_null($reflection)) $reflection = new \ReflectionClass(get_called_class());
		return $reflection->getConstants();
	}
}



