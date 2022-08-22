<?php

namespace Knuckles\Scribe\Docblock2Attributes\TagParsers;

use Illuminate\Support\Arr;
use Knuckles\Scribe\Extracting\ParamHelpers;
use Knuckles\Scribe\Tools\AnnotationParser as a;
use Knuckles\Scribe\Tools\Utils;
use Mpociot\Reflection\DocBlock\Tag;

class SubgroupTagParser
{
    use ParamHelpers;

    public function __construct(protected string $tagContent, protected array $allTags, protected Tag $tag)
    {
    }

    public function parse()
    {
        $descTag = Arr::first(Utils::filterDocBlockTags($this->allTags, 'subgroupdescription'));

        return [
            [
                'type' => 'subgroup',
                'data' => [
                    'name' => $this->tagContent,
                    'description' => trim($descTag?->getContent()),
                ]
            ],
        ];
    }
}
