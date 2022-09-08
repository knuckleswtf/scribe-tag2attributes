<?php

namespace Knuckles\Scribe\Tags2Attributes\TagParsers;

use Knuckles\Scribe\Extracting\ParamHelpers;
use Knuckles\Scribe\Tools\AnnotationParser as a;

class ResponseTagParser
{
    use ParamHelpers;

    public function __construct(protected string $tagContent)
    {
    }

    public function parse()
    {
        // Status code (optional) followed by response
        preg_match('/^(\d{3})?\s?([\s\S]*)$/', $this->tagContent, $result);

        $status = $result[1] ?: null;
        $content = $result[2] ?: '';

        ['fields' => $fields, 'content' => $content] = a::parseIntoContentAndFields($content, ['status', 'scenario']);

        $status = $fields['status'] ?: $status;
        if (!empty($fields['scenario'])) {
            $description = (!empty($status) ? "$status, {$fields['scenario']}" : $fields['scenario']);
        } else {
            $description = null;
        }

        if ($content == null || $content == '') {
            return ['status' => $status ? (int) $status : null, 'description' => $description];
        }

        if ($status === null) {
            return [
                $content,
                'description' => $description,
            ];
        }

        return [
            $content, (int) $status, $description,
        ];
    }
}
