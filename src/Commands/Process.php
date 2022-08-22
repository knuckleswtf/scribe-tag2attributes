<?php

namespace Knuckles\Scribe\Docblock2Attributes\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Knuckles\Scribe\Docblock2Attributes\Extractor;
use Knuckles\Scribe\GroupedEndpoints\GroupedEndpointsFactory;
use Knuckles\Scribe\Matching\RouteMatcherInterface;
use Knuckles\Scribe\Tools\ConsoleOutputUtils as c;
use Knuckles\Scribe\Tools\DocumentationConfig;
use Knuckles\Scribe\Tools\ErrorHandlingUtils as e;
use Knuckles\Scribe\Tools\Globals;
use Knuckles\Scribe\Tools\Utils as u;
use Mpociot\Reflection\DocBlock;
use Mpociot\Reflection\DocBlock\Tag;
use ReflectionClass;

class Process extends Command
{
    protected $signature = "scribe:docblock2attributes
                            {--config=scribe : choose which config file to use}
    ";

    protected $description = 'Convert docblock tags to PHP attributes.';

    protected DocumentationConfig $docConfig;

    public function handle(RouteMatcherInterface $routeMatcher, GroupedEndpointsFactory $groupedEndpointsFactory): void
    {
        $this->bootstrap();

        if (!empty($this->docConfig->get("default_group"))) {
            $this->warn("It looks like you just upgraded to Scribe v4.");
            $this->warn("Please run the upgrade command first: `php artisan scribe:upgrade`.");
            exit(1);
        }

        $routes = $routeMatcher->getRoutes($this->docConfig->get('routes', []), $this->docConfig->get('router'));
        $extracted = $this->extract($routes);
        // $this->transform($routes);
    }

    protected function extract(array $matches): array
    {
        $extractor = new Extractor($this->docConfig);
        $parsedEndpoints = [];

        foreach ($matches as $routeItem) {
            $route = $routeItem->getRoute();

            $routeControllerAndMethod = u::getRouteClassAndMethodNames($route);
            if (!$this->isValidRoute($routeControllerAndMethod)) {
                c::warn('Skipping invalid route: ' . c::getRouteRepresentation($route));
                continue;
            }

            if (!$this->doesControllerMethodExist($routeControllerAndMethod)) {
                c::warn('Skipping route: ' . c::getRouteRepresentation($route) . ' - Controller method does not exist.');
                continue;
            }

            if ($this->isRouteHiddenFromDocumentation($routeControllerAndMethod)) {
                c::warn('Skipping route: ' . c::getRouteRepresentation($route) . ': @hideFromAPIDocumentation was specified.');
                continue;
            }

            try {
                c::info('Processing route: ' . c::getRouteRepresentation($route));
                [$controllerName, $methodName] = u::getRouteClassAndMethodNames($route);
                $controller = new ReflectionClass($controllerName);
                $method = u::getReflectedRouteMethod([$controllerName, $methodName]);
                $endpointData = $extractor->processRoute($route, $method, $controller);
                ray($endpointData);
                c::success('Processed route: ' . c::getRouteRepresentation($route));
            } catch (\Exception $exception) {
                $this->encounteredErrors = true;
                c::error('Failed processing route: ' . c::getRouteRepresentation($route) . ' - Exception encountered.');
                e::dumpExceptionIfVerbose($exception);
            }
        }

        return $parsedEndpoints;
    }

    protected function transform(array $matches): array
    {
        $extractor = new Transformer($this->docConfig);
        $parsedEndpoints = [];

        foreach ($matches as $routeItem) {
            $route = $routeItem->getRoute();

            $routeControllerAndMethod = u::getRouteClassAndMethodNames($route);
            if (!$this->isValidRoute($routeControllerAndMethod)) {
                c::warn('Skipping invalid route: ' . c::getRouteRepresentation($route));
                continue;
            }

            if (!$this->doesControllerMethodExist($routeControllerAndMethod)) {
                c::warn('Skipping route: ' . c::getRouteRepresentation($route) . ' - Controller method does not exist.');
                continue;
            }

            if ($this->isRouteHiddenFromDocumentation($routeControllerAndMethod)) {
                c::warn('Skipping route: ' . c::getRouteRepresentation($route) . ': @hideFromAPIDocumentation was specified.');
                continue;
            }

            try {
                c::info('Processing route: ' . c::getRouteRepresentation($route));
                $endpointData = $extractor->processRoute($route, $routeItem->getRules());
                c::success('Processed route: ' . c::getRouteRepresentation($route));
            } catch (\Exception $exception) {
                $this->encounteredErrors = true;
                c::error('Failed processing route: ' . c::getRouteRepresentation($route) . ' - Exception encountered.');
                e::dumpExceptionIfVerbose($exception);
            }
        }

        return $parsedEndpoints;
    }


    private function isValidRoute(array $routeControllerAndMethod = null): bool
    {
        if (is_array($routeControllerAndMethod)) {
            [$classOrObject, $method] = $routeControllerAndMethod;
            if (u::isInvokableObject($classOrObject)) {
                return true;
            }
            $routeControllerAndMethod = $classOrObject . '@' . $method;
        }

        return !is_callable($routeControllerAndMethod) && !is_null($routeControllerAndMethod);
    }

    private function doesControllerMethodExist(array $routeControllerAndMethod): bool
    {
        [$class, $method] = $routeControllerAndMethod;
        $reflection = new ReflectionClass($class);

        if ($reflection->hasMethod($method)) {
            return true;
        }

        return false;
    }

    private function isRouteHiddenFromDocumentation(array $routeControllerAndMethod): bool
    {
        if (!($class = $routeControllerAndMethod[0]) instanceof \Closure) {
            $classDocBlock = new DocBlock((new ReflectionClass($class))->getDocComment() ?: '');
            $shouldIgnoreClass = collect($classDocBlock->getTags())
                ->filter(function (Tag $tag) {
                    return Str::lower($tag->getName()) === 'hidefromapidocumentation';
                })->isNotEmpty();

            if ($shouldIgnoreClass) {
                return true;
            }
        }

        $methodDocBlock = new DocBlock(u::getReflectedRouteMethod($routeControllerAndMethod)->getDocComment() ?: '');
        $shouldIgnoreMethod = collect($methodDocBlock->getTags())
            ->filter(function (Tag $tag) {
                return Str::lower($tag->getName()) === 'hidefromapidocumentation';
            })->isNotEmpty();

        return $shouldIgnoreMethod;
    }


    public function bootstrap(): void
    {
        // The --verbose option is included with all Artisan commands.
        Globals::$shouldBeVerbose = $this->option('verbose');

        c::bootstrapOutput($this->output);

        $this->configName = $this->option('config');
        if (!config($this->configName)) {
            throw new \InvalidArgumentException("The specified config (config/{$this->configName}.php) doesn't exist.");
        }

        $this->docConfig = new DocumentationConfig(config($this->configName));
    }

}
