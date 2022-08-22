<?php

namespace Knuckles\Scribe\Docblock2Attributes;

use Knuckles\Scribe\Docblock2Attributes\TagParsers\ApiResourceTagParser;
use Knuckles\Scribe\Docblock2Attributes\TagParsers\AuthTagParser;
use Knuckles\Scribe\Docblock2Attributes\TagParsers\GroupTagParser;
use Knuckles\Scribe\Docblock2Attributes\TagParsers\HeaderTagParser;
use Knuckles\Scribe\Docblock2Attributes\TagParsers\BodyParamTagParser;
use Knuckles\Scribe\Docblock2Attributes\TagParsers\QueryParamTagParser;
use Knuckles\Scribe\Docblock2Attributes\TagParsers\ResponseFieldTagParser;
use Knuckles\Scribe\Docblock2Attributes\TagParsers\ResponseFileTagParser;
use Knuckles\Scribe\Docblock2Attributes\TagParsers\ResponseTagParser;
use Knuckles\Scribe\Docblock2Attributes\TagParsers\SubgroupTagParser;
use Knuckles\Scribe\Docblock2Attributes\TagParsers\TransformerTagParser;
use Knuckles\Scribe\Docblock2Attributes\TagParsers\UrlParamTagParser;
use Mpociot\Reflection\DocBlock;
use Mpociot\Reflection\DocBlock\Tag;

class TagParser
{
    protected const TAG_PARSERS = [
        'header' => HeaderTagParser::class,
        'bodyparam' => BodyParamTagParser::class,
        'queryparam' => QueryParamTagParser::class,
        'urlparam' => UrlParamTagParser::class,
        'responsefield' => ResponseFieldTagParser::class,
        'response' => ResponseTagParser::class,
        'responsefile' => ResponseFileTagParser::class,
        'apiresource' => ApiResourceTagParser::class,
        'apiresourcecollection' => ApiResourceTagParser::class,
        'transformer' => TransformerTagParser::class,
        'transformercollection' => TransformerTagParser::class,
        'authenticated' => AuthTagParser::class,
        'unauthenticated' => AuthTagParser::class,
        'group' => GroupTagParser::class,
        'subgroup' => SubgroupTagParser::class,
    ];

    public static function parse(Tag $tag, array $allTags, string $scope, DocBlock $docBlock)
    {
        $name = strtolower($tag->getName());
        $parserClass = self::TAG_PARSERS[$name] ?? null;

        if (!$parserClass) {
            return [];
        }

        $parser = new $parserClass(trim($tag->getContent()), $allTags, $tag, $scope, $docBlock);

        return $parser->parse();
    }
}
