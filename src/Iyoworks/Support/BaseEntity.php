<?php namespace Iyoworks\Support;
use ArrayAccess;
use DateTime;
use InvalidArgumentException;
use Illuminate\Support\Contracts\JsonableInterface;
use Illuminate\Support\Contracts\ArrayableInterface;

abstract class BaseEntity implements ArrayAccess, ArrayableInterface, JsonableInterface {
	const CREATED_AT = 'created_at';
	const UPDATED_AT = 'updated_at';
	public $exists = false;
	protected $strict = false;
	protected $usesTimestamps = true;
	protected $attributes = [];
	protected $original= [];
	protected $guarded = ['id'];
	protected $hidden = [];
	protected $visible = [];
	protected $attributeDefinitions = [];

	/**
	 * Indicates if all mass assignment is enabled.
	* @var bool
	 */
	protected static $unguarded = false;

	/**
	 * The cache of the mutated attributes for each class.
	* @var array
	 */
	protected static $mutatorCache = array();

	/**
	 * The cache of the attribute definitions for each class.
	* @var array
	 */	
	protected static $attributeDefinitionsCache = [];

	/**
	 * The array of booted entities.
	 * @var array
	 */
	protected static $booted = array();

	/**
	 * Create a new instance
	 * @param  array   $attributes
	 * @param  boolean $exists
	 */
	public function __construct(array $attributes = [], $exists = false)
	{
		if ( ! isset(static::$booted[$class = $this->className()]))
		{
			static::cacheAttributeDefinitions($class, $this->getRawAttributeDefinitions());
			static::boot();
			static::$booted[$class] = true;
		}
		$this->fill($attributes + $this->getDefaultAttributeValues());
		$this->exists = $exists;
	}

	/**
	 * Create a new instance
	 * @param  array   $attributes
	 * @param  boolean $exists
	 * @return \Iyoworks\Repositories\Contracts\EntityInterface
	 */
	public static function make(array $attributes = [], $exists = false)
	{
		return with(new static)->newInstance($attributes, $exists);
	}

	/**
	 * Create a new instance
	 * @param  array   $attributes
	 * @param  boolean $exists
	 * @return \Iyoworks\Repositories\Contracts\EntityInterface
	 */
	public function newInstance(array $attributes = array(), $exists = false)
	{
		// This method just provides a convenient way for us to generate fresh entity
		// instances of this current entity. It is particularly useful during the
		// hydration of new objects via the Eloquent query builder instances.
		$entity = new static($attributes);

		$entity->exists = $exists;

		return $entity;
	}

	/**
	 * The "booting" method of the entity.
	 * @return void
	 */
	protected static function boot()
	{
		$class = get_called_class();

		static::$mutatorCache[$class] = array();

		// Here we will extract all of the mutated attributes so that we can quickly
		// spin through them after we export entities to their array form, which we
		// need to be fast. This will let us always know the attributes mutate.
		foreach (get_class_methods($class) as $method)
		{
			if (preg_match('/^get(.+)Attribute$/', $method, $matches))
			{
				static::$mutatorCache[$class][] = lcfirst($matches[1]);
			}
		}
	}

	/**
	 * Get attribute values
	 * @return array
	 */
	public function getDefaultAttributeValues()
	{
		$defs = $this->getAttributeDefinitions();
		$defaults = [];
		foreach ($defs as $key => $def) {
			$defaults[$key] = AttributeType::get($def, null);
		}
		return $defaults;
	}

	/**
	 * Creates a new entity from the query builder result
	 * @param  stdEntity  $result
	 * @param  boolean $exists
	 * @return \Iyoworks\Repositories\Contracts\EntityInterface
	 */
	public function newExistingInstance($result, $exists = true)
	{
		static::unguard();
		$inst = $this->newInstance( (array) $result, $exists);
		$inst->syncOriginal();
		static::reguard();
		return $inst;
	}

	/**
	 * Set the entity's attibutes
	 * @param  array  $attributes
	 * @return void
	 * @throws Exception;
	 */
	public function fill(array $attributes)
	{
		ksort($attributes);

		foreach ($attributes as $key => $value) {
			if (static::$unguarded  or ( $this->isAttribute($key) and !$this->isGuarded($key) ))
			{
				$this->setAttribute($key, $value);
			}
			elseif ($this->totallyGuarded()) {
				throw new MassAssignmentException($key);
			}
		}
		return $this;
	}

	/**
	 * Get an attribute from the $attributes array.
	 * @param  string  $key
	 * @return mixed
	 */
	protected function getAttributeFromArray($key)
	{
		if (array_key_exists($key, $this->attributes))
		{
			return $this->attributes[$key];
		}
	}

	/**
	 * Get a plain attribute (not a entities).
	 * @param  string  $key
	 * @return mixed
	 */
	protected function getAttributeValue($key)
	{
		$value = $this->getAttributeFromArray($key);
		
		if($this->isDefinedAttribute($key))
		{
			$value = AttributeType::get($this->getAttributeDefinition($key), $value);
		}

		// If the attribute has a get mutator, we will call that then return what
		// it returns as the value, which is useful for transforming values on
		// retrieval from the entity to a form that is more useful for usage.
		if ($this->hasGetMutator($key))
		{
			return $this->mutateAttribute($key, $value);
		}

		return $value;
	}

	/**
	 * Get an attribute from the entity.
	 * @param  string  $key
	 * @return mixed
	 */
	public function getAttribute($key)
	{
		if($this->isPivotAttribute($key))
			$key = $this->makePivotKey($key);
		
		$inAttributes = array_key_exists($key, $this->attributes);
		// If the key references an attribute, we can just go ahead and return the
		// plain attribute value from the entity. This allows every attribute to
		// be dynamically accessed through the _get method without accessors.
		if ($inAttributes || $this->isEntity($key) || $this->hasGetMutator($key))
		{
			return $this->getAttributeValue($key);
		}

		if ($this->strict && !$this->isAttribute($key))
			throw new InvalidArgumentException($key);
		
		// If the value has not been set, check if it has a valid attribute type
		// if so, get the default value for the type
		return AttributeType::get($this->getAttributeDefinition($key), null);
	}

	/**
	 * Determine if a get mutator exists for an attribute.
	 * @param  string  $key
	 * @return bool
	 */
	public function hasGetMutator($key)
	{
		return method_exists($this, 'get'.studly_case($key).'Attribute');
	}

	/**
	 * Set a given attribute on the entity.
	 * @param  string  $key
	 * @param  mixed   $value
	 * @return void
	 */
	public function setAttribute($key, $value)
	{
		if($this->isDefinedAttribute($key))
			$value = AttributeType::set($this->getAttributeDefinition($key), $value);

		if($this->isPivotAttribute($key))
			$key = $this->makePivotKey($key);

		// First we will check for the presence of a mutator for the set operation
		// which simply lets the developers tweak the attribute as it is set on
		// the entity, such as "json_encoding" an listing of data for storage.
		if ($this->hasSetMutator($key))
		{
			$method = 'set'.studly_case($key).'Attribute';
			return $this->{$method}($value);
		}

		if ($this->strict && !$this->isAttribute($key))
			throw new InvalidArgumentException($key);

		$this->attributes[$key] = $value;
	}

	/**
	 * Determine if a set mutator exists for an attribute.
	 * @param  string  $key
	 * @return bool
	 */
	public function hasSetMutator($key)
	{
		return method_exists($this, 'set'.studly_case($key).'Attribute');
	}

	/**
	 * Get the value of an attribute using its mutator.
	 * @param  string  $key
	 * @param  mixed   $value
	 * @return mixed
	 */
	protected function mutateAttribute($key, $value)
	{
		return $this->{'get'.studly_case($key).'Attribute'}($value);
	}

	/**
	 * Get the value of an attribute using its mutator.
	 * @param  string  $key
	 * @param  mixed   $value
	 * @return mixed
	 */
	protected function mutateSetAttribute($key, $value)
	{
		return $this->{'set'.studly_case($key).'Attribute'}($value);
	}

	/**
	 * Determine if a given attribute is dirty.
	 * @param  string  $attribute
	 * @return bool
	 */
	public function isDirty($attribute = null)
	{
		if($attribute)
			return array_key_exists($attribute, $this->getDirty());
		return count($this->getDirty()) > 0;

	}

	/**
	 * Get the attributes that have been changed since last sync.
	 * @param string|null $attribute
	 * @return array
	 */
	public function getDirty($attribute = null)
	{
		if(empty($this->original)) return $this->attributes;
		$dirty = array();
		foreach ($this->attributes as $key => $value) {
			$addToDirty =  !array_key_exists($key, $this->original) || $value !== $this->original[$key];
			if ($addToDirty) $dirty[$key] = $value;
		}
		return $dirty;
	}

	/**
	 * Clone the entity into a new, non-existing instance.
	 * @return \Iyoworks\Repositories\Contracts\EntityInterface
	 */
	public function replicate()
	{
		return $this->newExistingInstance($this->attributes, false);
	}

	/**
	 * Get all of the current attributes on the entity.
	 * @return array
	 */
	public function getAttributes()
	{
		return $this->attributes;
	}

	/**
	 * Set the array of entity attributes. No checking is done.
	 * @param  array  $attributes
	 * @param  bool   $sync
	 * @return void
	 */
	public function setRawAttributes(array $attributes, $sync = false)
	{
		$this->attributes = $attributes;
	}

	/**
	 * Get the entity's original attribute values.
	 * @param  string  $key get the original attribute value with key
	 * @param  mixed   $default
	 * @return array
	 */
	public function getOriginal($key = null, $default = null)
	{
		if($key) return array_get($this->original, $key, $default);
		return $this->original;
	}

	/**
	 * Sync the original attributes with the current.
	 * @return \Iyoworks\Repositories\Contracts\EntityInterface
	 */
	public function syncOriginal()
	{
		$this->original = $this->attributes;
		return $this;
	}

	/**
	 * Get the mutated attributes for a given instance.
	 * @return array
	 */
	public function getMutatedAttributes()
	{
		$class = $this->className();

		if (isset(static::$mutatorCache[$class]))
			return static::$mutatorCache[$class];

		return [];
	}

	/**
	 * Check if entity exists
	 * @return bool
	 */
	public function exists()
	{
		$value = $this->getKey();
		return ($this->exists and isset($value));
	}

	/**
	 * Get the entity key name
	 * @return string
	 */
	public function getKeyName()
	{
		return 'id';
	}

	/**
	 * Get the entity key
	 * @return int|mixed
	 */
	public function getKey()
	{
		return $this->{$this->getKeyName()};
	}


	/**
	 * Checks if an attribute has a definition
	 * @param  string
	 * @return boolean
	 */
	public function isDefinedAttribute($key)
	{
		return array_key_exists($key, $this->getAttributeDefinitions());
	}

	/**
	 * Checks if an attribute exists as a pivot attribute
	 * @param  string
	 * @return boolean
	 */
	public function isPivotAttribute($key)
	{
		return $this->isDefinedAttribute($this->makePivotKey($key));
	}

	/**
	 * Checks if an attribute is a date type
	 * @param  string
	 * @return boolean
	 */
	public function isDateType($key)
	{
		return AttributeType::isDateType($this->attributeType($key));
	}

	/**
	 * Determine if an attribute exists
	 * @param  string  $key 
	 * @return boolean      
	 */
	public function isAttribute($key)
	{
		if(!$this->strict)
			return true;
		return $this->isDefinedAttribute($key) or $this->isPivotAttribute($key);
	}

	/**
	 * Determine if an attribute exists
	 * @param  string  $key 
	 * @return boolean      
	 */
	public function isEntity($key)
	{
		return $this->attributeTypeMatches($key, AttributeType::Entity);
	}

	/**
	 * Determine if an attribute's type matches the given type
	 * @param  string $key 
	 * @param  string $type
	 * @return bool
	 */
	public function attributeTypeMatches($key, $type)
	{
		$atype = $this->attributeType($key);
		return in_array($atype, (array) $type);
	}

	/**
	 * Get raw attribute definitions
	 * @return array
	 */
	public function getRawAttributeDefinitions()
	{
		if($this->usesTimestamps)
		{
			$this->attributeDefinitions[static::CREATED_AT] = AttributeType::Timestamp;
			$this->attributeDefinitions[static::UPDATED_AT] = AttributeType::Timestamp;
		}
		return $this->attributeDefinitions;
	}

	/**
	 * Get attribute definitions
	 * @return array
	 */
	public function getAttributeDefinitions()
	{
		return array_get(static::$attributeDefinitionsCache, $this->className(), []);
	}

	/**
	 * Get an attribute's definition
	 * @return array
	 */
	public function getAttributeDefinition($key)
	{
		return array_get($this->getAttributeDefinitions(), $key, ['type' => AttributeType::Mixed]);	
	}

	/**
	 * Get the attribute type
	 * @param $key
	 * @return string
	 */
	public function attributeType($key)
	{
		$definition = $this->getAttributeDefinition($key);
		return array_get($definition, 'type', AttributeType::Mixed);
	}

	/**
	 * Convert attribute name to pivot attribute 
	 * @param  string $key 
	 * @return string      
	 */
	protected function makePivotKey($key)
	{
		return 'pivot'.studly_case($key);
	}

	/**
	 * Get name of current object
	 * @return string      
	 */
	protected function className()
	{
		return get_class($this);
	}

	/**
	 * Get an attribute array of all arrayable attributes.
	 * @return array
	 */
	protected function getArrayableAttributes()
	{
		if (count($this->visible) > 0)
		{
			return array_intersect_key($this->attributes, array_flip($this->visible));
		}

		return array_diff_key($this->attributes, array_flip($this->hidden));
	}

	/**
	 * Get the hidden attributes for the entity.
	 * @return array
	 */
	public function getHidden()
	{
		return $this->hidden;
	}

	/**
	 * Set the hidden attributes for the entity.
	 * @param  array  $hidden
	 * @return void
	 */
	public function setHidden(array $hidden)
	{
		$this->hidden = $hidden;
	}

	/**
	 * Determine if the given key is guarded.
	 * @param  string  $key
	 * @return bool
	 */
	public function isGuarded($key)
	{
		return $this->totallyGuarded() or in_array($key, $this->guarded);
	}

	/**
	 * Determine if the entity is totally guarded.
	 * @return bool
	 */
	public function totallyGuarded()
	{
		return $this->guarded == array('*');
	}

	/**
	 * Convert the entity instance to an array.
	 * @return array
	 */
	public function toArray()
	{
		$attributes = $this->getArrayableAttributes();

		// We want to spin through all the mutated attributes for this entity and call
		// the mutator for the attribute. We cache off every mutated attributes so
		// we don't have to constantly check on attributes that actually change.
		
		foreach ($this->getMutatedAttributes() as $key)
		{
			if (! array_key_exists($key, $attributes)) continue;

			$attributes[$key] = $this->mutateAttribute($key, $attributes[$key]);
		}

		foreach ($attributes as $key => &$value)
		{
			if($this->isDateType($key) and $value instanceof \DateTime)
			{
				$format = $this->getAttributeDefinition($key)['format'];
				$value = $value->format($format);
			}
		}

		return $attributes;
	}

	/**
	 * Convert the entity instance to JSON.
	 * @param  int  $options
	 * @return string
	 */
	public function toJson($options = 0)
	{
		return json_encode($this->toArray(), $options);
	}

	/**
	 * Dynamically retrieve attributes on the entity.
	 * @param  string  $key
	 * @return mixed
	 */
	public function __get($key)
	{
		return $this->getAttribute($key);
	}

	/**
	 * Dynamically set attributes on the entity.
	 * @param  string  $key
	 * @param  mixed   $value
	 * @return void
	 */
	public function __set($key, $value)
	{
		$this->setAttribute($key, $value);
	}

	/**
	 * Determine if the given attribute exists.
	 * @param  mixed  $offset
	 * @return bool
	 */
	public function offsetExists($offset)
	{
		return isset($this->$offset);
	}

	/**
	 * Get the value for a given offset.
	 * @param  mixed  $offset
	 * @return mixed
	 */
	public function offsetGet($offset)
	{
		return $this->$offset;
	}

	/**
	 * Set the value for a given offset.
	 * @param  mixed  $offset
	 * @param  mixed  $value
	 * @return void
	 */
	public function offsetSet($offset, $value)
	{
		$this->$offset = $value;
	}

	/**
	 * Unset the value for a given offset.
	 * @param  mixed  $offset
	 * @return void
	 */
	public function offsetUnset($offset)
	{
		unset($this->$offset);
	}

	/**
	 * Determine if an attribute exists on the entity.
	 * @param  string  $key
	 * @return void
	 */
	public function __isset($key)
	{
		return isset($this->attributes[$key]);
	}

	/**
	 * Unset an attribute on the entity.
	 * @param  string  $key
	 * @return void
	 */
	public function __unset($key)
	{
		unset($this->attributes[$key]);
	}

	/**
	 * Convert the entity to its string representation.
	 * @return string
	 */
	public function __toString()
	{
		return $this->toJson();
	}

	/******************************************
	*** Static Methods 
	*****************************************/

	/**
	 * Disable all mass assignable restrictions.
	 * @return void
	 */
	public static function unguard()
	{
		static::$unguarded = true;
	}

	/**
	 * Enable the mass assignment restrictions.
	 * @return void
	 */
	public static function reguard()
	{
		static::$unguarded = false;
	}

	protected static function cacheAttributeDefinitions($class, array $defs)
	{
		foreach ($defs as $attr => $def) {
			$defs[$attr] = AttributeType::getFullDefinition($def);
		}
		if(isset(static::$attributeDefinitionsCache[$class]))
			$defs = array_replace_recursive(static::$attributeDefinitionsCache[$class], $defs);
		static::$attributeDefinitionsCache[$class] = $defs;
	}

	public static function getCachedAttributeDefinitions()
	{
		return static::$attributeDefinitionsCache;
	}
}