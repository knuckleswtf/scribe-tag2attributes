<?php

namespace Knuckles\Scribe\Docblock2Attributes\TagParsers;

use Illuminate\Support\Arr;
use Knuckles\Scribe\Extracting\ParamHelpers;
use Knuckles\Scribe\Tools\AnnotationParser as a;
use Knuckles\Scribe\Tools\Utils;
use Mpociot\Reflection\DocBlock\Tag;

class AuthTagParser
{
    use ParamHelpers;

    public function __construct(protected string $tagContent, protected array $allTags, protected Tag $tag)
    {
    }

    public function parse()
    {
        return [
            [
                'type' => strtolower($this->tag->getName()) === 'authenticated'
                    ? 'authenticated' : 'unauthenticated',
            ],
        ];
    }
}
