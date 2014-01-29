<?php namespace Iyoworks\Support;

class JsonStore {
	protected $filepath;
	protected $prettyOutput;

	public function __construct($filepath, $pretty = true){
		$this->filepath = $filepath;
		$this->prettyOutput = $pretty;
	}

	public function loadData($useArray = true)
	{	
		if (file_exists($this->filepath))
		{
			$contents = file_get_contents($this->filepath);
			return json_decode($contents, $useArray);
		}
		return array();
	}

	public function saveData($data = null)
	{
		$data = is_null($data) ? [] : (array) $data;
		if ($this->prettyOutput)
			$contents = json_encode($data, JSON_PRETTY_PRINT);
		else
			$contents = json_encode($data);
		return file_put_contents($this->filepath, $contents, LOCK_EX);
	}

    public function setData($key, $data)
    {
        $oldData = $this->loadData();
        $oldData[$key] = $data;
        return $this->saveData($oldData);
    }

	public function appendData($data = null)
	{
		$oldData = $this->loadData();
		$data = is_null($data) ? [] : (array) $data;
		$data = array_merge($oldData, $data);
		return $this->saveData($data);
	}

    public function removeData($key)
    {
        $data = $this->loadData();
        unset($data[$key]);
        return $this->saveData($data);
    }
}