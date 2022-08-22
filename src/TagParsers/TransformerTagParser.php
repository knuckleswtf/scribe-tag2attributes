<?php

namespace Knuckles\Scribe\Docblock2Attributes\TagParsers;

use Illuminate\Support\Arr;
use Knuckles\Scribe\Extracting\ParamHelpers;
use Knuckles\Scribe\Tools\AnnotationParser as a;
use Knuckles\Scribe\Tools\Utils;
use Mpociot\Reflection\DocBlock\Tag;

class TransformerTagParser
{
    use ParamHelpers;

    public function __construct(protected string $tagContent, protected array $allTags, protected Tag $tag)
    {
    }

    public function parse()
    {
        [$statusCode, $transformerClass, $isCollection] = $this->getStatusCodeAndTransformerClass();
        [$model, $factoryStates, $relations, $resourceKey] = $this->getClassToBeTransformed();
        $pagination = $this->getTransformerPaginatorData();

        return [
            [
                'type' => 'transformer',
                'data' => [
                    'status' => (int)$statusCode,
                    'description' => '',
                    'name' => $transformerClass,
                    'model' => $model,
                    'collection' => $isCollection,
                    'factoryStates' => $factoryStates,
                    'with' => $relations,
                    'resourceKey' => $resourceKey,
                    'pagination' => $pagination,
                ],
            ],
        ];
    }

    private function getStatusCodeAndTransformerClass(): array
    {
        preg_match('/^(\d{3})?\s?([\s\S]*)$/', $this->tagContent, $result);
        $status = (int)($result[1] ?: 200);
        $transformerClass = $result[2];
        $isCollection = strtolower($this->tag->getName()) == 'transformercollection';

        return [$status, $transformerClass, $isCollection];
    }

    private function getClassToBeTransformed(): array
    {
        $modelTag = Arr::first(Utils::filterDocBlockTags($this->allTags, 'transformermodel'));

        $type = null;
        $states = [];
        $relations = [];
        $resourceKey = null;
        if ($modelTag) {
            ['content' => $type, 'attributes' => $attributes] = a::parseIntoContentAndAttributes($modelTag->getContent(), ['states', 'with', 'resourceKey']);
            $states = $attributes['states'] ? explode(',', $attributes['states']) : [];
            $relations = $attributes['with'] ? explode(',', $attributes['with']) : [];
            $resourceKey = $attributes['resourceKey'] ?? null;
        } else {
            $parameter = Arr::first($transformerMethod->getParameters());
            if ($parameter->hasType() && !$parameter->getType()->isBuiltin() && class_exists($parameter->getType()->getName())) {
                $type = $parameter->getType()->getName();
            }
        }

        return [$type, $states, $relations, $resourceKey];
    }

    private function getTransformerPaginatorData(): array
    {
        $tag = Arr::first(Utils::filterDocBlockTags($this->allTags, 'transformerpaginator'));
        if (empty($tag)) {
            return ['adapter' => null, 'perPage' => null];
        }

        preg_match('/^\s*(.+?)\s+(\d+)?$/', $tag->getContent(), $result);
        $paginatorAdapter = $result[1];
        $perPage = $result[2] ?? null;

        return ['adapter' => $paginatorAdapter, 'perPage' => $perPage];
    }
}
