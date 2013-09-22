<?php namespace Iyoworks\Support\Traits;

use Illuminate\Support\Facades\Facade;

trait AccessAppTrait
{
	protected function getApp($service = null)
	{
		if ($service) return Facade::getFacadeApplication()->make($service);
		return Facade::getFacadeApplication();
	}

}
