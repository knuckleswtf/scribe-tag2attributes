<?php

namespace Knuckles\Scribe\Docblock2Attributes\TagParsers;

use Knuckles\Scribe\Extracting\ParamHelpers;

class QueryParamTagParser
{
    use ParamHelpers;

    public function __construct(protected string $tagContent)
    {
    }

    public function parse()
    {
        // Format:
        // @queryParam <name> <type (optional)> <"required" (optional)> <description>
        // Examples:
        // @queryParam text required The text.
        // @queryParam user_id integer The ID of the user.
        preg_match('/(.+?)\s+([a-zA-Z\[\]]+\s+)?(required\s+)?([\s\S]*)/', $this->tagContent, $content);

        if (empty($content)) {
            // This means only name was supplied
            $name = $this->tagContent;
            $required = false;
            $description = '';
            $type = null;
        } else {
            [$_, $name, $type, $required, $description] = $content;

            $description = trim(str_replace(['No-example.', 'No-example'], '', $description));
            if ($description === 'required') {
                // No description was supplied
                $required = true;
                $description = '';
            } else {
                $required = trim($required) === 'required';
            }

            $type = trim($type);
            if ($type) {
                if ($type === 'required') {
                    // Type wasn't supplied
                    $type = null;
                    $required = true;
                } else {
                    $type = static::normalizeTypeName($type);
                    // Type in annotation is optional
                    if (!$this->isSupportedTypeInDocBlocks($type)) {
                        // Then that wasn't a type, but part of the description
                        $description = trim("$type $description");
                        $type = '';
                    }
                }
            } else if ($this->isSupportedTypeInDocBlocks($description)) {
                // Only type was supplied
                $type = $description;
                $description = '';
            }
        }

        $type = empty($type) ? $type : static::normalizeTypeName($type);

        [$description, $example] = $this->parseExampleFromParamDescription($description, $type);

        $noExample = $this->shouldExcludeExample($this->tagContent);
        if ($noExample) {
            $example = 'No-example';
        }

        return [$name, ...compact('type', 'description', 'required', 'example')];
    }
}
