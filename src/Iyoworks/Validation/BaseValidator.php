<?php namespace Iyoworks\Validation;

abstract class BaseValidator
{
	use \Iyoworks\Support\Traits\ErrorHandlerTrait;
	/**
	 * @var \Illuminate\Validation\Factory
	 */
	public static $factory;

	/**
	 * @var \Iyoworks\Repository\Validation\Validator
	 */
	protected $runner;

	/**
	* @var array
	*/
	protected $data = [];

	/**
	* @var array
	*/
	protected $rules = [];

	/**
	* @var array
	*/
	protected $insertRules = [];

	/**
	* @var array
	*/
	protected $updateRules = [];

	/**
	* @var array
	*/
	protected $deleteRules = [];

	/**
	* @var array
	*/
	protected $messages = [];

	/**
	* @var boolean
	*/
	protected $mode = false;

	/**
	* @var string
	*/
	const 	MODE_INSERT = 'insert';

	/**
	* @var string
	*/
	const 	MODE_UPDATE = 'update';

	/**
	* @var string
	*/
	const 	MODE_DELETE = 'delete';

	/**
	 * Set mode to insert
	 * @return \Iyoworks\Repository\Validator
	 */
	public function insert()
	{
		$this->mode = self::MODE_INSERT;
		return $this;
	}

	/**
	 * Set mode to update
	 * @return \Iyoworks\Repository\Validator
	 */
	public function update()
	{
		$this->mode = self::MODE_UPDATE;
		return $this;
	}

	/**
	 * Set mode to delete
	 * @return \Iyoworks\Repository\Validator
	 */
	public function delete()
	{
		$this->mode = self::MODE_DELETE;
		return $this;
	}

	/**
	 * Called before validation
	 * @return void
	 */
	protected function preValidate() {}

	/**
	 * Called before validation when mode is insert
	 * @return void
	 */
	protected function preValidateOnInsert() {}

	/**
	 * Called before validation when mode is update
	 * @return void
	 */
	protected function preValidateOnUpdate() {}

	/**
	 * Called before validation when mode is delete
	 * @return void
	 */
	protected function preValidateOnDelete() {}

	/**
	 * Run the validator
	 * @param  mixed $data
	 * @return bool  
	 */
	public function valid($data = null)
	{
		if(!empty($data)) $this->setData($data);
		
		 //only validate necessary attributes
		$this->rules = array_intersect_key($this->rules, $this->data);
		// construct the runner	
		$this->runner = static::$factory->make([],[]);

		$this->preValidate();

		if($this->mode)
		{
			$this->rules = array_merge($this->rules,  $this->{$this->mode.'Rules'});
			$this->{'preValidateOn'.studly_case($this->mode)}();
		} 
		
		// if($this->pass())
		{
			$this->runner->setRules($this->rules);
			$this->runner->setData($this->data);
			$this->runner->setCustomMessages($this->messages);

			//determine if any errors occured
			if(!$this->runner->passes())
				$this->addErrBag($this->runner->messages());
		}
		return $this->pass();
	}

	/**
	 * Set the ID or value for a unique rule
	 * @param string  $key       data attribute name
	 * @param mixed  $value     
	 * @param boolean $useActual use the actual $value
	 * @param string  $rkey      rule to apply changes to
	 */
	public function setUnique($key, $value = null, $useActual = false, $rkey = 'unique')
	{
		$rule = $this->rules[$key];
		$start = strpos($rule, $rkey);
		$end = strpos($rule, '|', $start) ?: strlen($rule);
		$uniqueRuleOrig = $uniqueRule = substr ($rule , strpos($rule, $rkey), $end-$start);
		if (!$useActual and $value)
			$value = $this->get($key);
		elseif( !$useActual and !$value)
			$value = $this->get('id');
		$uniqueRule = rtrim($uniqueRule, ',').','. $value;
		$rule = str_replace($uniqueRuleOrig, $uniqueRule, $rule);
		$this->rules[$key] = $rule;
		return $this;
	}

	/**
	 * Parse data and convert it to array
	 * @param  mixed $_data
	 * @return array
	 */
	protected function parseData($_data)
	{
		$this->obj = $_data;
		if(is_array($_data))
			return $_data;
		if(is_object($_data))
		{
			if($this->mode == self::MODE_UPDATE and method_exists($_data, 'getDirty'))
				return $_data->getDirty();
			elseif(method_exists($_data, 'getAttributes'))
				return $_data->getAttributes();
			elseif(method_exists($_data, 'toArray'))
				return $_data->toArray();
		}
		return (array) $_data;
	}

	/**
	 * Determine if validation passed
	 * @return bool
	 */
	public function pass()
	{
		return !$this->hasErrors();
	}

	/**
	 * Get the validator instance
	 * @return \Illuminate\Validation\Validator
	 */
	public function getRunner()
	{
		return $this->runner;
	}
	
	/**
	 * Merge data into the existing data set 
	 * @param  mixed $key     
	 * @param  mixed $value
	 * @return \Iyoworks\Repository\Validator
	 */
	public function addData($data, $value = null)
	{
		if($value) 
			$this->data[$data] = $this->parseData($value);
		else 
			$this->data = array_merge_recursive($this->data, $this->parseData($data));
		return $this;
	}

	/**
	 * Overwrite the existing data
	 * @param mixed $data
	 * @return \Iyoworks\Repository\Validator
	 */
	public function setData($data)
	{
		$this->data = $this->parseData($data);
		return $this;
	}

	/**
	 * Get a value from data
	 * @param  string $key     
	 * @param  mixed $default
	 * @return mixed
	 */
	protected function get($key, $default = null)
	{
		return array_get($this->data, $key, $default);
	}

	/**
	 * Set a value 
	 * @param  string $key     
	 * @param  mixed $value
	 * @return void
	 */
	protected function set($key, $value)
	{
		array_set($this->data, $key, $value);
	}

	/**
	 * Get the messages
	 * @return array
	 */
	public function getMessages()
	{
		return $this->messages;
	}

	/**
	 * Get the rules
	 * @return array
	 */
	public function getRules()
	{
		return $this->rules;
	}

	/**
	 * Get a the data container
	 * @return array
	 */	
	public function getData()
	{
		return $this->data;
	}

	/**
	* Clear the data container
	* @return void
	*/
	public function resetData()
	{
		$this->data = [];
	}

	/**
	 * Get the mode
	 * @return string
	 */
	public function mode()
	{
		return $this->mode;
	}
}
