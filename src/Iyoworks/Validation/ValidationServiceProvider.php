<?php namespace Iyoworks\Validation;

use Illuminate\Support\ServiceProvider;

class IyoworksServiceProvider extends ServiceProvider {

	public function boot()
	{	
		BaseValidator::$factory = $this->app['validator'];
		
		$this->app['validator']->resolver(function($translator, $data, $rules, $messages)
		{
			return new Validator($translator, $data, $rules, $messages);
		});
	}

	/**
	 * @return void
	 */
	public function register()
	{	
		
	}
}
