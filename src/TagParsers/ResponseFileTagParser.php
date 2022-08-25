<?php

namespace Knuckles\Scribe\Docblock2Attributes\TagParsers;

use Knuckles\Scribe\Extracting\ParamHelpers;
use Knuckles\Scribe\Tools\AnnotationParser as a;

class ResponseFileTagParser
{
    use ParamHelpers;

    public function __construct(protected string $tagContent)
    {
    }

    public function parse()
    {
        preg_match('/^(\d{3})?\s*(.*?)({.*})?$/', $this->tagContent, $result);
        [$_, $status, $mainContent] = $result;
        $merge = $result[3] ?? null;

        ['attributes' => $attributes, 'content' => $path] = a::parseIntoContentAndAttributes($mainContent, ['status', 'scenario']);

        $status = $attributes['status'] ?: $status;
        if ($status === '') $status = null;
        if (!empty($attributes['scenario'])) {
            $description = (!empty($status) ? "$status, {$attributes['scenario']}" : $attributes['scenario']);
        } else {
            $description = null;
        }

        if ($status === null) {
            return [
                $path,
                'merge' => $merge,
                'description' => $description,
            ];
        }

        return [
            $path,
            'status' => (int) $status,
            'merge' => $merge,
            'description' => $description,
        ];

    }
}
