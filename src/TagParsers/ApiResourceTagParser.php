<?php

namespace Knuckles\Scribe\Tags2Attributes\TagParsers;

use Illuminate\Support\Arr;
use Knuckles\Scribe\Extracting\ParamHelpers;
use Knuckles\Scribe\Tools\AnnotationParser as a;
use Knuckles\Scribe\Tools\Utils;
use Mpociot\Reflection\DocBlock\Tag;

class ApiResourceTagParser
{
    use ParamHelpers;

    public function __construct(protected string $tagContent, protected array $allTags, protected $isCollection = false, )
    {
    }

    public function parse()
    {
        [$statusCode, $description, $apiResourceClass] = $this->getStatusCodeAndApiResourceClass();
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

        $data = [
            $apiResourceClass,
            $modelClass,
        ];

        if (!empty($statusCode)) {
            $data[] = (int)$statusCode;
        }

        if ($this->isCollection) {
            $data['collection'] = true;
        }

        $data['description'] = $description;
        $data['factoryStates'] = $factoryStates ?: null;
        $data['with'] = $relations ?: null;
        $data['additional'] = $additionalData ?: null;
        $data['paginate'] = $paginate;
        $data['simplePaginate'] = $simplePaginate;
        return $data;
    }

    private function getStatusCodeAndApiResourceClass(): array
    {
        preg_match('/^(\d{3})?\s?([\s\S]*)$/', $this->tagContent, $result);

        $status = $result[1] ?: null;
        $content = $result[2];

        ['fields' => $fields, 'content' => $content] = a::parseIntoContentAndFields($content, ['status', 'scenario']);

        $status = $fields['status'] ?: $status;
        $apiResourceClass = $content;
        if (!empty($fields['scenario'])) {
            $description = (!empty($status) ? "$status, {$fields['scenario']}" : $fields['scenario']);
        } else {
            $description = null;
        }

        return [$status, $description, $apiResourceClass];
    }

    private function getClassToBeTransformedAndAttributes(): array
    {
        $modelTag = Arr::first(array_values(
            array_filter($this->allTags, fn($tag) => strtolower($tag->name) == '@apiresourcemodel'))
        );

        $modelClass = null;
        $states = [];
        $relations = [];
        $pagination = [];

        if ($modelTag) {
            ['content' => $modelClass, 'fields' => $fields] = a::parseIntoContentAndFields($modelTag->value, ['states', 'with', 'paginate']);
            $states = $fields['states'] ? explode(',', $fields['states']) : [];
            $relations = $fields['with'] ? explode(',', $fields['with']) : [];
            $pagination = $fields['paginate'] ? explode(',', $fields['paginate']) : [];
        }

        return [$modelClass, $states, $relations, $pagination];
    }

    private function getAdditionalData()
    {
        $tag = Arr::first(array_values(
            array_filter($this->allTags, fn($tag) => strtolower($tag->name) == '@apiresourceadditional'))
        );
        return $tag ? a::parseIntoAttributes($tag->value) : null;
    }
}
