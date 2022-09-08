<?php

namespace Knuckles\Scribe\Tags2Attributes\TagParsers;

use Illuminate\Support\Arr;
use Knuckles\Scribe\Extracting\ParamHelpers;
use Knuckles\Scribe\Tools\AnnotationParser as a;
use Knuckles\Scribe\Tools\Utils;
use Mpociot\Reflection\DocBlock\Tag;

class SubgroupTagParser
{
    use ParamHelpers;

    public function __construct(protected string $tagContent, protected array $allTags)
    {
    }

    public function parse()
    {
        $descTag = Arr::first(array_values(
            array_filter($this->allTags, fn($tag) => strtolower($tag->name) == '@subgroupdescription'))
        );

        return [$this->tagContent, $descTag?->value ? trim($descTag->value) : null];
    }
}
