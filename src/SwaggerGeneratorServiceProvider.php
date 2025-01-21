<?php

namespace LaraSwagger;

use Illuminate\Support\ServiceProvider;
use LaraSwagger\Commands\GenerateSwaggerCommand;
use Illuminate\Support\Facades\Event;
use Illuminate\Console\Events\CommandFinished;
use Illuminate\Console\Events\CommandStarting;
use LaraSwagger\Listeners\ArtisanCommandListener;

class SwaggerGeneratorServiceProvider extends ServiceProvider
{
  public function register()
  {

    $this->commands([
      GenerateSwaggerCommand::class,
    ]);
  }
  public function boot()
  {
    $this->ensureEnvKeyExists('AUTO_GENERATE_DOCS', 'true');
    $this->ensureEnvKeyExists('APP_DESCRIPTION', '');
    if (env('AUTO_GENERATE_DOCS', false)) {
      Event::listen(CommandStarting::class, [ArtisanCommandListener::class, 'handleCommandStarting']);
      Event::listen(CommandFinished::class, [ArtisanCommandListener::class, 'handleCommandFinished']);
    }
  }
  private function ensureEnvKeyExists(string $key, string $value)
  {
    $envPath = base_path('.env');

    if (file_exists($envPath)) {
      $envContent = file_get_contents($envPath);

      if (!str_contains($envContent, "{$key}=")) {
        file_put_contents($envPath, PHP_EOL . "{$key}={$value}" . PHP_EOL, FILE_APPEND);
      }
    } else {
      if (file_exists(base_path('.env.example'))) {
        copy(base_path('.env.example'), $envPath);
        file_put_contents($envPath, PHP_EOL . "{$key}={$value}" . PHP_EOL, FILE_APPEND);
      }
    }
  }
}
