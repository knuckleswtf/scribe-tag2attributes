<?php

namespace Knuckles\Scribe\Docblock2Attributes;

use Illuminate\Support\ServiceProvider as BaseSP;
use Knuckles\Scribe\Docblock2Attributes\Commands\Process;
use Knuckles\Scribe\Matching\RouteMatcher;
use Knuckles\Scribe\Matching\RouteMatcherInterface;

class ServiceProvider extends BaseSP
{
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                Process::class,
            ]);
        }

        $this->app->bind(RouteMatcherInterface::class, config('scribe.routeMatcher', RouteMatcher::class));
    }
}
