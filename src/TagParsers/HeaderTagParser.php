<?php

namespace Knuckles\Scribe\Docblock2Attributes\TagParsers;

class HeaderTagParser
{
    public function __construct(protected string $tagContent)
    {
    }

    public function parse()
    {
        // Format:
        // @header <name> <example>
        // Examples:
        // @header X-Custom An API header
        preg_match('/([\S]+)(.*)?/', $this->tagContent, $content);

        [$_, $name, $example] = $content;
        $example = trim($example);

        return [
            [
                'type' => 'header',
                'data' => [
                    'name' => $name,
                    'example' => $example,
                ],
            ],
        ];
    }
}
