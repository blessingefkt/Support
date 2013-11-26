<?php namespace Iyoworks\Support\Traits;
use Cache;

trait CachableTrait {

	abstract public function cacheBaseName($str = null);

	abstract public function getCacher();

	public function cacheName($str = null, $sep = '.'){
		if(!empty($str)) $str = $sep.$str;
		return $this->cacheBaseName().$str;
	}
	protected function cacheForever($value, $key = null)
	{
		return $this->getCacher()->forever($this->cacheName($key), $value);
	}

	protected function cacheRemember($value, $key = null, $minutes=5)
	{
		return $this->getCacher()->remember($this->cacheName($key), $minutes, $value);
	}

	protected function cacheRemForever($value, $key = null)
	{
		return $this->getCacher()->rememberForever($this->cacheName($key), $value);
	}

	protected function cacheGet($key = null, $default = null)
	{
		return $this->getCacher()->get($this->cacheName($key),  $default);
	}

	protected function cacheHas($key = null)
	{
		$key = $this->cacheName($key);
		return $this->getCacher()->has($key);
	}

	protected function cacheForget($key = null)
	{
		if(is_array($key))
			foreach ($key as $k) $this->cacheForget($k);
		$key = $this->cacheName($key);
		if ($this->getCacher()->has($key)) 
			$this->getCacher()->forget($key);
	}
}
