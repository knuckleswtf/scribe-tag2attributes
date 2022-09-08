<?php

namespace Knuckles\Scribe\Tags2Attributes\TagParsers;

use Knuckles\Scribe\Extracting\ParamHelpers;

class UrlParamTagParser
{
    use ParamHelpers;

    public function __construct(protected string $tagContent)
    {
    }

    public function parse()
    {
        // Format:
        // @urlParam <name> <type (optional)> <"required" (optional)> <description>
        // Examples:
        // @urlParam id string required The id of the post.
        // @urlParam user_id The ID of the user.

        // We match on all the possible types for URL parameters. It's a limited range, so no biggie.
        preg_match('/(\w+?)\s+((int|integer|string|float|double|number)\s+)?(required\s+)?([\s\S]*)/', $tagContent, $content);
        if (empty($content)) {
            // This means only name was supplied
            $name = $tagContent;
            $required = false;
            $description = '';
            $type = null;
        } else {
            [$_, $name, $__, $type, $required, $description] = $content;
            $description = trim(str_replace(['No-example.', 'No-example'], '', $description));
            if ($description === 'required') {
                $required = true;
                $description = '';
            } else {
                $required = trim($required) === 'required';
            }

            if (empty($type) && $this->isSupportedTypeInDocBlocks($description)) {
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
