<?php namespace Nikapps\BazaarPush;

use Illuminate\Support\ServiceProvider;

class BazaarPushServiceProvider extends ServiceProvider {

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
		$this->package('nikapps/bazaar-push');
	}

	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register()
	{
        $this->commands(['sale']);
        $this->registerCommands();

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

    public function registerCommands(){
        $this->app['sale'] = $this->app->share(function($app)
        {
            return new BazaarPushCommand;
        });
    }

}
