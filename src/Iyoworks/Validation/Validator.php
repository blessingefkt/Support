<?php namespace Iyoworks\Validation;
use Closure, DB;
use Illuminate\Validation\Validator as LValidator;
use Illuminate\Support\MessageBag;

class Validator extends LValidator
{

	protected $actions = array();

	public function addAction($attribute, $rule, closure $action, $message  = null)
	{
		$this->rules[$attribute][$rule] = "action:$rule";
		$this->actions[$attribute][$rule] = $action;
		if($message) $this->setCustomMessages([$attribute.'.'.$rule => $message]);
	}

	/**
	 * Validate a given attribute against a rule.
	 *
	 * @param  string  $attribute
	 * @param  string  $rule
	 * @return void
	 */
	protected function validate($attribute, $rule)
	{
		if(!starts_with($rule, 'action'))
			return parent::validate($attribute, $rule);
		
		list($rule, $parameters) = $this->parseRule($rule);

		$rule = array_shift($parameters);
		$value = $this->getValue($attribute);

		$action = $this->actions[$attribute][$rule];

		if(!$validatable = $action($value, $parameters))
			$this->addFailure($attribute, $rule, $parameters);
	}

	/**
	 * validate a composite unique in a table
	 * Usage: composite_unique:table_name,col_1,col_2
	 * Usage: composite_unique:table_name,col_1,col_2[,col2_value,ignore_id]
	 *
	 * @param type $attribute
	 * @param type $value
	 * @param type $parameters
	 * @return type
	 */
	protected function validateCompositeUnique( $attribute, $value, $parameters )
	{

		$table = $parameters[0];

		$col1 = $parameters[1];

		$col2 = $parameters[2];

		if(isset($parameters[3]))
			$query = DB::table($table)->where($col1,$value)->where($col2,$parameters[3]);
		//if a specific value is not passed for the second column (usually on an initial insert) then we'll essentially run a uniqueness check
		else
			$query = DB::table($table)->where($col1,$value)->whereNull($col2);

		if(isset($parameters[4]))
		{
			$query = $query->where('id', '<>', $parameters[4]);
		}

		return $query->count() == 0;

	}

	/**
	 *
	 * @param type $attribute
	 * @param type $value
	 * @param type $parameters
	 */
	protected function replaceCompositeUnique( $message, $attribute, $rule, $parameters )
	{
		$message = str_replace(':composite_attribute', $parameters[2], $message);
		return str_replace(':attribute', $parameters[1], $message);
	}

	public function setRules(array $rules)
	{
		$this->rules = $this->explodeRules(array_merge_recursive($this->rules, $rules));
	}

}
