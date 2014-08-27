<?php namespace Dc\EloquentValidatable;

use Illuminate\Support\ServiceProvider;

class EloquentValidatableServiceProvider extends ServiceProvider {

	/**
	 * Indicates if loading of the provider is deferred.
	 *
	 * @var bool
	 */
	protected $defer = false;

	/**
	 * Bootstrap the application events.
	 *
	 * @return void
	 */
	public function boot()
	{
		$this->package('dc/eloquent-validatable');
	}

	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register()
	{
		$this->app['events']->listen('eloquent.saving*', function($model) {
            if (in_array('DC\EloquentValidatable\ValidatableTrait', class_uses($model))) {
                return $model->validate();
            }
        });
	}

	/**
	 * Get the services provided by the provider.
	 *
	 * @return array
	 */
	public function provides()
	{
		return array();
	}

}
