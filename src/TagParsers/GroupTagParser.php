<?php

namespace Knuckles\Scribe\Docblock2Attributes\TagParsers;

use Illuminate\Support\Arr;
use Knuckles\Scribe\Extracting\ParamHelpers;
use Knuckles\Scribe\Tools\AnnotationParser as a;
use Knuckles\Scribe\Tools\Utils;
use Mpociot\Reflection\DocBlock;
use Mpociot\Reflection\DocBlock\Tag;

class GroupTagParser
{
    use ParamHelpers;

    public function __construct(
        protected string $tagContent, protected array $allTags, protected Tag $tag,
        protected string $scope, protected DocBlock $docBlock
    )
    {
    }

    public function parse()
    {
        $endpointGroupParts = explode("\n", $this->tagContent);
        $endpointGroupName = array_shift($endpointGroupParts);
        $endpointGroupDescription = trim(implode("\n", $endpointGroupParts));

        // If the endpoint has no title (the methodDocBlock's "short description"),
        // we'll assume the endpointGroupDescription is actually the title
        // Something like this:
        // /**
        //   * Fetch cars. <-- This is endpoint title.
        //   * @group Cars <-- This is group name.
        //   * APIs for cars. <-- This is group description (not required).
        //   **/
        // VS
        // /**
        //   * @group Cars <-- This is group name.
        //   * Fetch cars. <-- This is endpoint title, NOT group description.
        //   **/

        if ($this->scope == 'method' && empty($this->docBlock->getShortDescription())) {
            return [
                [
                    'type' => 'group',
                    'data' => [
                        'name' => $endpointGroupName,
                        'description' => '',
                    ],
                ],
                [
                    'type' => 'endpoint',
                    'data' => [
                        'name' => $endpointGroupDescription,
                        'description' => '',
                    ],
                ],
            ];
        }


        return [
            [
                'type' => 'group',
                'data' => [
                    'name' => $endpointGroupName,
                    'description' => $endpointGroupDescription,
                ],
            ],
        ];
    }
}
