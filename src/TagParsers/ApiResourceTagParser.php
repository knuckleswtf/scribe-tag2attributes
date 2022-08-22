<?php

namespace Knuckles\Scribe\Docblock2Attributes\TagParsers;

use Illuminate\Support\Arr;
use Knuckles\Scribe\Extracting\ParamHelpers;
use Knuckles\Scribe\Tools\AnnotationParser as a;
use Knuckles\Scribe\Tools\Utils;
use Mpociot\Reflection\DocBlock\Tag;

class ApiResourceTagParser
{
    use ParamHelpers;

    public function __construct(protected string $tagContent, protected array $allTags, protected Tag $tag)
    {
    }

    public function parse()
    {
        [$statusCode, $description, $apiResourceClass, $isCollection] = $this->getStatusCodeAndApiResourceClass();
        [$modelClass, $factoryStates, $relations, $pagination] = $this->getClassToBeTransformedAndAttributes();
        $additionalData = $this->getAdditionalData();

        if (empty($pagination)) {
            [$simplePaginate, $paginate] = [null, null];
        } else {
            if (($pagination[1] ?? null) === 'simple') {
                [$simplePaginate, $paginate] = [$pagination[0], null];
            } else {
                [$paginate, $simplePaginate] = [$pagination[0], null];

            }
        }

        return [
            [
                'type' => 'apiresource',
                'data' => [
                    'status' => (int)$statusCode,
                    'description' => $description,
                    'name' => $apiResourceClass,
                    'model' => $modelClass,
                    'collection' => $isCollection,
                    'factoryStates' => $factoryStates,
                    'with' => $relations,
                    'additionalData' => $additionalData,
                    'paginate' => $paginate,
                    'simplePaginate' => $simplePaginate,
                ],
            ],
        ];
    }

    private function getStatusCodeAndApiResourceClass(): array
    {
        preg_match('/^(\d{3})?\s?([\s\S]*)$/', $this->tagContent, $result);

        $status = $result[1] ?: 200;
        $content = $result[2];

        ['attributes' => $attributes, 'content' => $content] = a::parseIntoContentAndAttributes($content, ['status', 'scenario']);

        $status = $attributes['status'] ?: $status;
        $apiResourceClass = $content;
        $description = ($status && $attributes['scenario']) ? "$status, {$attributes['scenario']}" : "";

        $isCollection = strtolower($this->tag->getName()) == 'apiresourcecollection';
        return [(int)$status, $description, $apiResourceClass, $isCollection];
    }

    private function getClassToBeTransformedAndAttributes(): array
    {
        $modelTag = Arr::first(Utils::filterDocBlockTags($this->allTags, 'apiresourcemodel'));

        $modelClass = null;
        $states = [];
        $relations = [];
        $pagination = [];

        if ($modelTag) {
            ['content' => $modelClass, 'attributes' => $attributes] = a::parseIntoContentAndAttributes($modelTag->getContent(), ['states', 'with', 'paginate']);
            $states = $attributes['states'] ? explode(',', $attributes['states']) : [];
            $relations = $attributes['with'] ? explode(',', $attributes['with']) : [];
            $pagination = $attributes['paginate'] ? explode(',', $attributes['paginate']) : [];
        }

        return [$modelClass, $states, $relations, $pagination];
    }

    private function getAdditionalData(): array
    {
        $tag = Arr::first(Utils::filterDocBlockTags($this->allTags, 'apiresourceadditional'));
        return $tag ? a::parseIntoAttributes($tag->getContent()) : [];
    }
}
