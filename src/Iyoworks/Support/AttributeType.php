<?php namespace Iyoworks\Support;

use DateTime;

class AttributeType {

	const Int = 'integer';
	const Integer = 'integer';
	const Bool = 'boolean';
	const Boolean = 'boolean';
	const DateTime = 'timestamp';
	const Double = 'double';
	const Enum = 'enum';
	const Float = 'float';
	const Handle = 'handle';
	const Json = 'json';
	const Timestamp = 'timestamp';
	const Entity = 'entity';
	const Serial = 'serial';
	const Slug = 'slug';
	const String = 'string';
	const Mixed = 'mixed';
	const UID = 'uid';

	static $customTypes = [];

	protected function set($type, $value)
	{
		$def = $this->getFullDefinition($type);
		$type = $def['type'];
		$method = 'set'.studly_case($type);
		if(method_exists(get_called_class(), $method))
			return $this->$method($value, $def);
		return $value;
	}

	protected function get($type, $value)
	{
		$def = $this->getFullDefinition($type);
		$type = $def['type'];
		$method = 'get'.studly_case($type);
		if(method_exists(get_called_class(), $method))
			return $this->$method($value, $def);
		return $value;
	}

	protected function isValidType($type)
	{
		return array_key_exists($type, $this->toArray()) ||
		(array_search($type, $this->toArray(), true) !== false);
	}

	/**
	 * Checks if an attribute is a date type
	 * @param  string
	 * @return boolean
	 */
	protected function isDateType($type)
	{
		return in_array($type, [static::DateTime, static::Timestamp]);
	}

	protected function toArray(){
		static $reflection;
		if(is_null($reflection)) $reflection = new \ReflectionClass($this);
		return $reflection->getConstants();
	}

	protected function setInteger($value, array $def)
	{
		return (int) $value;
	}

	protected function getInteger($value, array $def)
	{
		return (int) $value;
	}

	protected function setBoolean($value, array $def)
	{
		return (bool) $value;
	}

	protected function getBoolean($value, array $def)
	{
		return (bool) $value;
	}

	protected function setDouble($value, array $def)
	{
		return (double) $value;
	}

	protected function getDouble($value, array $def)
	{
		return (double) $value;
	}

	protected function getEntity($value, array $def)
	{
		if(is_null($value))
		{
			$class = $def['class'] ?: 'StdClass';
			return new $class;
		}
		return $value;
	}

	protected function setEntity($value, array $def)
	{
		return $value;
	}

	protected function setFloat($value, array $def)
	{
		return (float) $value;
	}

	protected function getFloat($value, array $def)
	{
		return (float) $value;
	}

	protected function setHandle($value, array $def)
	{
		return handle($value);
	}

	protected function setJson($value, array $def)
	{
		if($value instanceof \Illuminate\Support\Contracts\JsonableInterface)
			$output = $value->toJson();
		elseif(!is_string($value) or $def['force'])
			$output = json_encode($value ?: []);
		else
			$output = $value;
		return $output;
	}

	protected function getJson($value, array $def)
	{
		if (empty($value) or $value == 'null')
			$output = [];
		else
			$output = json_decode($value, 1);

		return $output;
	}

	protected function setSerial($value, array $def)
	{
		return  serialize($value);
	}

	protected function getSerial($value, array $def)
	{
		return unserialize($value);
	}

	protected function setSlug($value, array $def)
	{
		return slugify($value);
	}

	protected function setString($value, array $def)
	{
		return (string) $value;
	}

	protected function getString($value, array $def)
	{
		return (string)  $value;
	}

	protected function setTimestamp($value, array $def)
	{
		if(is_string($value))
		{
			$date = $this->newDateObject($value);
			return $date->format($def['format']);
		}
		return $value;
	}

	protected function getTimestamp($value, array $def)
	{
		if (is_null($value)) return $this->newDateObject();

		if (is_numeric($value))
		{
			return $this->newDateFromTimestamp($value);
		}
		elseif (is_string($value) && preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $value))
		{
			return $this->newDateFromFormat($value, 'Y-m-d');
		}
        elseif ($value instanceof \DateTime)
        {
            return $value;
        }

		return $this->newDateFromFormat($value, $def['format']);
	}

	protected function getUid($value, array $def)
	{
		if($def['auto'] and is_null($value))
			return Str::superRandom($def['length'], $def['prefix'], $def['pool']);
		return $value;
	}

	protected function setUid($value, array $def)
	{
		return $value;
	}

	/**
	 * Convert definition to appropriate array
	 * @param  str|array $definition 
	 * @return array
	 */
	protected function getFullDefinition($definition)
	{	
		if(!is_array($definition)) $definition = ['type' => $definition];

		$defaults = array_get($this->defaultDefinitions(), $definition['type'], ['type' => static::Mixed]);

		return array_merge($defaults, $definition);
	}

	protected function defaultDefinitions()
	{	
		return array(
			static::Entity => array(
				'class' => null, 
				'many' => false
				),
			static::Json => array(
				'force' => false
				),
			static::Timestamp => array(
				'format' => 'Y-m-d H:i:s'
				),
			static::UID => array(
				'prefix' => null, 
				'length' => 36,
				'pool' => Str::ALPHA_NUM,
				'auto' => false
				)
			);
	}

	protected function newDateObject($value = null)
	{
		if($value) return new DateTime($value);
		return new DateTime;
	}

	protected function newDateFromTimestamp($value)
	{
		$date = new DateTime;
		$date->setTimestamp($value);
		return $date;
	}

	protected function newDateFromFormat($value, $format)
	{
		return new DateTime($value);
	}

	public static function __callStatic($method, $args){
		$instance = new static;
		return call_user_func_array([$instance, $method], $args);
	}
}