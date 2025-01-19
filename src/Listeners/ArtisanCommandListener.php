<?php

namespace LaraSwagger\Listeners;

use Illuminate\Console\Events\CommandStarting;
use Illuminate\Console\Events\CommandFinished;
use Illuminate\Support\Facades\Artisan;
use Symfony\Component\Process\Process;

class ArtisanCommandListener
{
  protected $watchProcess;
  public function handleCommandStarting(CommandStarting $event)
  {
    if ($event->command === 'serve') {
      $this->startWatchingApiRoutes();
    }
  }

  public function handleCommandFinished(CommandFinished $event)
  {
    if ($event->command !== 'swagger:generate') {
      Artisan::call('swagger:generate');
    }


    if ($event->command === 'serve') {
      $this->stopWatchingApiRoutes();
    }
  }

  protected function startWatchingApiRoutes()
  {
    $this->watchProcess = new Process(['php', '-r', '
            while (true) {
                exec("php artisan swagger:generate");
                sleep(2);
            }
        ']);

    $this->watchProcess->start();
  }

  protected function stopWatchingApiRoutes()
  {
    if ($this->watchProcess) {
      $this->watchProcess->stop();
    }
  }
}
