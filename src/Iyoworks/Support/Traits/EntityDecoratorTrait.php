<?php namespace Iyoworks\Support\Traits;
trait EntityDecoratorTrait {
	protected $entity;

	public function getEntity()
	{
		return $this->entity;
	}

	public function toJson($option = 1){
		return json_encode($this->toArray(), $option);
	}

	public function toArray(){
		return $this->entity ? $this->entity->toArray() : [];
	}

	public function __get($key)
	{
		$method = 'get'.studly_case($key);
		if(method_exists($this, $method))
			return $this->{$method}();
		if($this->entity and $this->entity->isDefinedAttribute($key))
			return $this->entity->$key;
	}

	public function __set($key, $value)
	{
		$method = 'set'.studly_case($key);
		if(method_exists($this, $method))
			return $this->{$method}($key, $value);
		return parent::__set($key, $value);
	}
}
