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

    public function __construct(public DocumentationConfig $config)
    {
    }

    public function processRoute(Route $route, ReflectionFunctionAbstract $method, ?ReflectionClass $controller): array
    {
        $tags = [];
        if ($formRequestClass = $this->getFormRequestReflectionClass($method)) {
            $tagsFromFormRequest = $this->parseData(new DocBlock($formRequestClass->getDocComment()));
            $tags[$formRequestClass] = $tagsFromFormRequest;
        }

        $tags[$controller->name] ??= $this->parseData(RouteDocBlocker::getDocBlocksFromRoute($route)['class']);

        $tags[$controller->name.'/'.$method->name] ??= $this->parseData(RouteDocBlocker::getDocBlocksFromRoute($route)['method']);

        return $tags;
    }

    protected function parseData(DocBlock $docblock)
    {
        /** @var \Mpociot\Reflection\DocBlock\Tag[] $tags */
        $tags = $docblock->getTags() ?? [];

        $parsed = [];
        foreach ($tags as $tag) {
            $parsed[] = match(strtolower($tag->getName())) {
                'bodyparam' => $tag->getContent()
            };
        }

        return $parsed;
    }
}
