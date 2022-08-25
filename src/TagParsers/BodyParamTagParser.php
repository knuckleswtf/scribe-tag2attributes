<?php

namespace Knuckles\Scribe\Docblock2Attributes\TagParsers;

use Knuckles\Scribe\Extracting\ParamHelpers;

class BodyParamTagParser
{
    use ParamHelpers;

    public function __construct(protected string $tagContent)
    {
    }

    public function parse()
    {
        // Format:
        // @bodyParam <name> <type> <"required" (optional)> <description>
        // Examples:
        // @bodyParam text string required The text.
        // @bodyParam user_id integer The ID of the user.
        preg_match('/(.+?)\s+(.+?)\s+(required\s+)?([\s\S]*)/', $this->tagContent, $parsedContent);

        if (empty($parsedContent)) {
            // This means only name and type were supplied
            [$name, $type] = preg_split('/\s+/', $this->tagContent);
            $required = false;
            $description = '';
        } else {
            [$_, $name, $type, $required, $description] = $parsedContent;
            $description = trim(str_replace(['No-example.', 'No-example'], '', $description));
            if ($description == 'required') {
                $required = $description;
                $description = '';
            }
            $required = trim($required) === 'required';
        }

        $type = static::normalizeTypeName($type);
        [$description, $example] = $this->parseExampleFromParamDescription($description, $type);

        $noExample = $this->shouldExcludeExample($this->tagContent);
        if ($noExample) {
            $example = 'No-example';
        }

        return [$name, $type, ...compact('description', 'required', 'example')];
    }
}
