<?php

namespace LaraSwagger;

use Illuminate\Console\Events\CommandFinished;
use Illuminate\Console\Events\CommandStarting;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use LaraSwagger\Commands\GenerateSwaggerCommand;
use LaraSwagger\Listeners\ArtisanCommandListener;

class SwaggerGeneratorServiceProvider extends ServiceProvider
{
  public function register()
  {
    $this->mergeConfigFrom(__DIR__.'/../config/laraswagger.php', 'laraswagger');

    $this->commands([
      GenerateSwaggerCommand::class,
    ]);
  }

  public function boot()
  {
    $this->publishes([
      __DIR__.'/../config/laraswagger.php' => config_path('laraswagger.php'),
    ], 'laraswagger-config');

    $this->loadViewsFrom(__DIR__.'/views', 'laraswagger');
    $this->publishes([
      __DIR__.'/views' => resource_path('views/vendor/laraswagger'),
    ], 'laraswagger-views');

    $this->loadRoutesFrom(__DIR__.'/routes/web.php');

    if (config('laraswagger.auto_generate', true)) {
      Event::listen(CommandStarting::class, [ArtisanCommandListener::class, 'handleCommandStarting']);
      Event::listen(CommandFinished::class, [ArtisanCommandListener::class, 'handleCommandFinished']);
    }
  }
}
