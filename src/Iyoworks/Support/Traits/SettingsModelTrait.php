<?php namespace Iyoworks\Support\Traits;

trait SettingsModelTrait {
	
	public function setSettingsAttribute($data)
	{
		if(is_array($data)) $data = json_encode($data);
		$this->attributes['settings'] = $data;
	}

	public function getSettingsAttribute($data)
	{
		if(is_string($data)) $data = json_decode($data, true);
		return $data;
	}

}