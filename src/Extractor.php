<?php

namespace Knuckles\Scribe\Docblock2Attributes;

use Illuminate\Routing\Route;
use Knuckles\Scribe\Extracting\FindsFormRequestForMethod;
use Knuckles\Scribe\Extracting\RouteDocBlocker;
use Knuckles\Scribe\Tools\DocumentationConfig;
use Mpociot\Reflection\DocBlock;
use ReflectionClass;
use ReflectionFunctionAbstract;

class Extractor
{
    use FindsFormRequestForMethod;

    protected array $tags = [];

    public function __construct(public DocumentationConfig $config)
    {
    }

    public function processRoute(Route $route, ReflectionFunctionAbstract $method, ?ReflectionClass $controller): array
    {
        if ($formRequestClass = $this->getFormRequestReflectionClass($method)) {
            if (!isset($this->tags[$formRequestClass->name])) {
                $tagsFromFormRequest = $this->parseData(new DocBlock($formRequestClass->getDocComment()), 'formrequest');
                $this->tags[$formRequestClass->getFileName()] = $tagsFromFormRequest;
            }
        }

        if ($controller->name === "Closure") {
            $this->tags[$method->getFileName() . '|' . $method->getStartLine()] ??= $this->parseData(RouteDocBlocker::getDocBlocksFromRoute($route)['method'], 'method');
        } else {
            $this->tags[$controller->getFileName()] ??= $this->parseData(RouteDocBlocker::getDocBlocksFromRoute($route)['class'], 'class');

            $this->tags[$controller->getFileName() . '|' . $method->name] ??= $this->parseData(RouteDocBlocker::getDocBlocksFromRoute($route)['method'], 'method');
        }

        return $this->tags;
    }

    protected function parseData(DocBlock $docblock, string $scope)
    {
        /** @var \Mpociot\Reflection\DocBlock\Tag[] $tags */
        $tags = $docblock->getTags() ?? [];

        $parsed = collect($tags)
            ->map(fn($tag) => TagParser::parse($tag, $tags, $scope, $docblock))
            ->flatten(1);

        if ($parsed->count()) ray($parsed->all());
        return $parsed;
    }
}
