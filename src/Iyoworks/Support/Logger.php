<?php namespace Iyoworks\Support;
use Illuminate\Filesystem\Filesystem;

class Logger
{
	public static $delimiter = "\t";
	protected $filesystem;
	protected $baseFilename;
	protected $path;
	protected $filepath;
	protected $contents = array();
	protected $lineCount;
	protected $descOrder = true;
	protected $lastModified;
	protected $onlyOnce = array();
	protected $dataKeys = array();

	public function __construct($path, $baseFilename = null, $overwriteFile = true)
	{
		if(!file_exists($path))
			throw IyoworksException::make('path_dne', $path);
		if(!is_dir($path))
			throw IyoworksException::make('invalid_directory', $path);

		$this->filesystem = new Filesystem;

		$this->path = $path;
		$this->baseFilename = $baseFilename ?  $baseFilename.'.txt' : 'logger.txt';
		$this->filepath = $path.'/'.ltrim($this->baseFilename, '/');

		if($overwriteFile or !$this->filesystem->exists($this->filepath))
			$this->filesystem->put($this->filepath, '');

		$this->lastModified = $this->filesystem->lastModified($this->filepath);
	}

	public function write($message, $key = 'info')
	{
		$out[] = date('Y-m-d H:i:s');
		$out[] = $key;
		$out[] = $message;
		foreach (array_slice( func_get_args(), 2) as $value)
			array_push($out, $value);
		$data = implode(self::$delimiter, $out)."\n";
		$this->filesystem->append($this->filepath, $data);
		return $out;
	}

	public function writeOnce($message, $key = 'info')
	{
		if(in_array($key, $this->onlyOnce)) return;
		$out = call_user_func_array([$this, 'write'], func_get_args());
		$this->onlyOnce[$key] = $key;
	}

	public function getLog()
	{
		if(empty($this->contents) or $this->hasChanged())
		{
			$contents = file($this->filepath, FILE_IGNORE_NEW_LINES|FILE_SKIP_EMPTY_LINES);
			$this->processContents($contents);
		}
		return $this->contents;
	}

	public function getKey($key)
	{
		$log = $this->getLog();
		$data = [];
		if($this->hasKey($key))
		{
			foreach ($this->dataKeys[$key] as $num) {
				if($this->descOrder)
					array_unshift($data, $log[$num]);
				else
					array_push($data, $log[$num]);
			}
		}
		return $data;
	}

	//helpers
	public function hasChanged()
	{
		return $this->filesystem->lastModified($this->filepath) > $this->lastModified;
	}

	public function hasKey($key)
	{
		return isset($this->dataKeys[$key]);
	}

	public function modified()
	{
		return $this->filesystem->lastModified($this->filepath);
	}
	//chainable
	public function clear()
	{
		$this->filesystem->put($this->filepath, '');
		return $this;
	}

	public function ascOrder()
	{
		$this->descOrder = false;
		return $this;
	}

	public function descOrder()
	{
		$this->descOrder = true;
		return $this;
	}
	//data
	public function keys()
	{
		return array_keys($this->dataKeys);
	}

	public function filepath()
	{
		return $this->filepath;
	}
	protected function processContents($arrayIn)
	{
		$this->lineCount = count($arrayIn);
		$array = array_unique($arrayIn);
		$this->contents = array();
		foreach ($array as $num => $value) {
			$value = str_getcsv($value, self::$delimiter);
			$line = new \stdClass();
			$line->time = $value[0];
			$line->key = $value[1];
			$line->message = $value[2];
			$line->extra = array_slice( $value, 3);
			$this->contents[$num] = $line;
			$this->dataKeys[$line->key][] = $num;
		}
		if(count($arrayIn) > count($array))
			$this->filesystem->put($this->filepath, implode("\n", $array)."\n");
	}
}
