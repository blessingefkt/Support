<?php namespace Iyoworks\Support\Traits;

trait ErrorHandlerTrait
{
	protected $errors;
	
	/**
	 * Set the errors
	 * @param mixed
	 * @return void
	 */
	public function setErrors($errors)
	{
		if (is_array($errors))
			$this->errors = new \Illuminate\Support\MessageBag($errors);
		else
			$this->errors = $errors;
	}

	/**
	 * Add error
	 * @param sting
	 * @param mixed
	 * @return void
	 */
	public function addError($name, $err = null)
	{
		if($err)
			$this->errors()->add($name, $err);
		else
			$this->errors()->add('error', $name);
	}

	/**
	 * Add errors
	 * @param array $name
	 * @param mixed
	 * @return void
	 */
	protected function addErrors(array $errors)
	{
		$this->errors()->merge($errors);
	}

	/**
	 * Add message bag
	 * @param \Illuminate\Support\MessageBag
	 * @param mixed
	 * @return void
	 */
	protected function addErrBag(\Illuminate\Support\MessageBag $bag)
	{
		$this->errors()->merge($bag->getMessages());
	}

	/**
	 * Get the errors
	 * @return  \Illuminate\Support\MessageBag|mixed
	 */
	public function errors()
	{
		if (is_null($this->errors))
			$this->errors = new \Illuminate\Support\MessageBag;
		return $this->errors;
	}

	/**
	 * Check if errors exist
	 * @return  bool
	 */
	public function hasErrors()
	{
		return $this->errors()->any();
	}

	/**
	 * Get the errors
	 * @return mixed
	 */
	public function errorMsg()
	{
		return implode(' ', $this->errors()->all());
	}
}