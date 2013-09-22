<?php namespace Iyoworks\Support\Traits;
use Cache;

trait CachableTrait {

	abstract public function cacheBaseName($str = null);

	public function cacheName($str = null, $sep = '.'){
		if(!empty($str)) $str = $sep.$str;
		return $this->cacheBaseName().$str;
	}
	protected function cacheForever($value, $key = null)
	{
		return Cache::forever($this->cacheName($key), $value);
	}

	protected function cacheRemember($value, $key = null, $minutes=5)
	{
		return Cache::remember($this->cacheName($key), $minutes, $value);
	}

	protected function cacheRemForever($value, $key = null)
	{
		return Cache::rememberForever($this->cacheName($key), $value);
	}

	protected function cacheGet($key = null, $default = null)
	{
		return Cache::get($this->cacheName($key),  $default);
	}

	protected function cacheHas($key = null)
	{
		$key = $this->cacheName($key);
		return Cache::has($key);
	}

	protected function cacheForget($key = null)
	{
		if(is_array($key))
			foreach ($key as $k) $this->cacheForget($k);
		$key = $this->cacheName($key);
		if (Cache::has($key)) Cache::forget($key);
	}
}
