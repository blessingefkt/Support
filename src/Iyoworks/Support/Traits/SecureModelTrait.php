<?php namespace Iyoworks\Support\Traits;

trait SecureModelTrait {
	protected $errors;
	protected $validator;

	protected function preModelCreate() { 
		$this->validator = $this->makeValidator();
		$pass = $this->validator->validForInsert($this->attributes);
		$this->errors = $this->validator->errors();
		return $pass;
	}
	
	protected function preModelUpdate() { 
		$data = $this->getDirty();
		$data['id'] = $this->getKey();
		$this->validator = $this->makeValidator();
		$pass = $this->validator->validForUpdate($data);
		$this->errors = $this->validator->errors();
		return  $pass;
	}

	protected function preModelDestroy() {
		$data['id'] = $this->getKey();
		$this->validator = $this->makeValidator();
		$pass = $this->validator->validForDelete($data);
		$this->errors = $this->validator->errors();
		return $pass;
	}

	abstract protected function makeValidator();

	public function errors()
	{
		return $this->errors;
	}
}