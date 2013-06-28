<?php namespace Profiler;

use Profiler\Logger\Logger;
use Illuminate\Support\ServiceProvider;

class ProfilerServiceProvider extends ServiceProvider {

	const SESSION_HASH = '_profiler';

	public function boot()
	{
		$this->package('loic-sharma/profiler', null, __DIR__.'/../');
	}

	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register()
	{
		$this->registerProfiler();

		$this->registerProfilerLoggerEvent();

		$this->registerProfilerQueryEvent();

		$this->registerProfilerRouting();

		$this->registerProfilerToOutput();

		$this->listenViewComposing();
	}

	/**
	 * Register the profiler.
	 *
	 * @return void
	 */
	public function registerProfiler()
	{
		$this->app['profiler'] = $this->app->share(function($app)
		{
			$startTime = null;

			// Let's use the Laravel start time if it is defined.
			if(defined('LARAVEL_START'))
			{
				$startTime = LARAVEL_START;
			}

			// Let's see if the profiler is enabled. If the config is set to null, or
			// if the config is not found, we will fallback to the application's
			// debug setting.
			$enabled = $app['config']->get('profiler::enabled', null);

			if(is_null($enabled))
			{
				$enabled = $app['config']->get('app.debug');
			}

			return new Profiler(new Logger, $startTime, $enabled);
		});
	}

	/**
	 * Register an event to automatically fetch Laravel's logs.
	 *
	 * @return void
	 */
	public function registerProfilerLoggerEvent()
	{
		$app = $this->app;

		$app['events']->listen('illuminate.log', function($level, $message, $context) use ($app)
		{
			$app['profiler']->log->log($level, $message, $context);
		});
	}

	/**
	 * Register an event to automatically log database queries.
	 *
	 * @return void
	 */
	public function registerProfilerQueryEvent()
	{
		$app = $this->app;

		$app['events']->listen('illuminate.query', function($query, $bindings, $time, $connectionName) use ($app)
		{
			// If the query had some bindings we'll need to add those back
			// into the query.
			if( ! empty($bindings))
			{
				// Let's grab the query's connection. We will use it to prepare and then quote
				// the bindings before they are inserted back into the query.
				$connection = $app['db']->connection($connectionName);
				$pdo = $connection->getPdo();

				$bindings = $connection->prepareBindings($bindings);

				// Let's loop add each binding back into the original query, one binding
				// at a time.
				foreach($bindings as $binding)
				{
					$query = preg_replace('/\?/', $pdo->quote($binding), $query, 1);
				}
			}

			$app['profiler']->log->query($query, $time);
		});
	}

	/**
	 * Register routes to enable or disable the profiler.
	 *
	 * @return void
	 */
	public function registerProfilerRouting()
	{
		$provider = $this;

		$this->app->booting(function($app) use ($provider)
		{
			// Toggle profiler
			$app['router']->get('/_profiler/toggle/{password?}', function($password = null) use ($app, $provider)
			{
				$config = $app['config'];
				$password_required = in_array($app['env'], $config->get('profiler::require_password'));

				if( ! $password_required or ($password_required and $password === $config->get('profiler::password')))
				{
					$state = $app['session']->get($provider::SESSION_HASH) ? false : true;

					$app['session']->put($provider::SESSION_HASH, $state);
				}

				return $app['redirect']->to($app['url']->previous());
			});
		});
	}

	/**
	 * Register an after filter to automatically display the profiler.
	 *
	 * @return void
	 */
	public function registerProfilerToOutput()
	{
		$app = $this->app;
		$sessionHash = static::SESSION_HASH;

		$app['router']->after(function($request, $response) use ($app, $sessionHash)
		{
			$profiler = $app['profiler'];
			$session = $app['session'];

			if($session->has($sessionHash))
			{
				$profiler->enable($session->get($sessionHash));
			}

			// Do not display profiler on ajax requests or non-HTML responses.
			$isHTML = \Str::startsWith($response->headers->get('Content-Type'), 'text/html');

			if( ! $profiler->isEnabled() or $request->ajax() or ! $isHTML)
			{
				return;
			}

			$responseContent = $response->getContent();
			$profiler = $profiler->render();

			// If we can find a closing HTML tag in the response, let's add the
			// profiler content inside it.
			if(($pos = strrpos($responseContent, '</html>')) !== false)
			{
				$responseContent = substr($responseContent, 0, $pos).$profiler.substr($responseContent, $pos);
			}

			// If we cannot find a closing HTML tag, we'll just append the profiler
			// at the very end of the response's content.
			else
			{
				$responseContent .= $profiler;
			}

			$response->setContent($responseContent);
		});
	}

	/**
	 * Listen to view composing events
	 *
	 * @return void
	 */
	protected function listenViewComposing()
	{
		$this->app['events']->listen('composing:*', function ($data)
		{
			$this->app['profiler']->setViewData($data->getData());
		});
	}

}
