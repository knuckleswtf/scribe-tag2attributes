<?php

namespace Knuckles\Scribe\Docblock2Attributes\TagParsers;

use Knuckles\Scribe\Extracting\ParamHelpers;

class ResponseFieldTagParser
{
    use ParamHelpers;

    public function __construct(protected string $tagContent)
    {
    }

    public function parse()
    {
        // Format:
        // @responseField <name> <type> <description>
        // Examples:
        // @responseField text string The text.
        // @responseField user_id integer The ID of the user.
        preg_match('/(.+?)\s+(.+?)\s+([\s\S]*)/', $this->tagContent, $content);
        if (empty($content)) {
            // This means only name and type were supplied
            [$name, $type] = preg_split('/\s+/', $this->tagContent);
            $description = '';
        } else {
            [$_, $name, $type, $description] = $content;
            $description = trim($description);
        }

        $type = static::normalizeTypeName(trim($type));
        $data = compact('type', 'description');

        // Support optional type in annotation
        // The type can also be a union or nullable type (eg ?string or string|null)
        if (!empty($type) && !$this->isSupportedTypeInDocBlocks(explode('|', trim($type, '?'))[0])) {
            // Then that wasn't a type, but part of the description
            $data['description'] = trim("$type $description");
            $data['type'] = null;
            return [$name,'description' => $data['description']];
        }

        if (empty($data['type']) && empty($data['description'])) {
            return [$name];
        }

        return [$name, $data['type'], $data['description']];
    }
}
