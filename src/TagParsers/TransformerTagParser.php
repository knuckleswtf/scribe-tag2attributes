<?php

namespace Knuckles\Scribe\Tags2Attributes\TagParsers;

use Illuminate\Support\Arr;
use Knuckles\Scribe\Extracting\ParamHelpers;
use Knuckles\Scribe\Tools\AnnotationParser as a;
use Knuckles\Scribe\Tools\Utils;
use Mpociot\Reflection\DocBlock\Tag;

class TransformerTagParser
{
    use ParamHelpers;

    public function __construct(protected string $tagContent, protected array $allTags, protected $isCollection = false, )
    {
    }

    public function parse()
    {
        [$statusCode, $transformerClass] = $this->getStatusCodeAndTransformerClass();
        [$model, $factoryStates, $relations, $resourceKey] = $this->getClassToBeTransformed();
        $pagination = $this->getTransformerPaginatorData();

        if (!empty($attributes['scenario'])) {
            $description = (!empty($status) ? "$status, {$attributes['scenario']}" : $attributes['scenario']);
        } else {
            $description = null;
        }

        $data = [
            $transformerClass,
        ];

        if (!empty($model)) {
            $data[] = $model;
        }

        if (!empty($statusCode)) {
            if ($model) {
                $data[] = (int)$statusCode;
            } else {
                $data['status'] = (int)$statusCode;
            }
        }

        if ($this->isCollection) {
            $data['collection'] = true;
        }

        $data['description'] = $description;
        $data['factoryStates'] = $factoryStates ?: null;
        $data['with'] = $relations ?: null;

        $data['resourceKey'] = $resourceKey;
        $data['paginate'] = $pagination;
        return $data;
    }

    private function getStatusCodeAndTransformerClass(): array
    {
        preg_match('/^(\d{3})?\s?([\s\S]*)$/', $this->tagContent, $result);
        $status = $result[1] ?: null;
        $transformerClass = $result[2];

        return [$status, $transformerClass];
    }

    private function getClassToBeTransformed(): array
    {
        $modelTag = Arr::first(array_values(
            array_filter($this->allTags, fn($tag) => strtolower($tag->name) == '@transformermodel'))
        );

        $type = null;
        $states = [];
        $relations = [];
        $resourceKey = null;
        if ($modelTag) {
            ['content' => $type, 'fields' => $fields] = a::parseIntoContentAndFields($modelTag->value, ['states', 'with', 'resourceKey']);
            $states = $fields['states'] ? explode(',', $fields['states']) : [];
            $relations = $fields['with'] ? explode(',', $fields['with']) : [];
            $resourceKey = $fields['resourceKey'] ?? null;
        }

        return [$type, $states, $relations, $resourceKey];
    }

    private function getTransformerPaginatorData()
    {
        $tag = Arr::first(array_values(
            array_filter($this->allTags, fn($tag) => strtolower($tag->name) == '@transformerpaginator'))
        );

        if (empty($tag)) {
            return null;
        }

        preg_match('/^\s*(.+?)(\s+\d+)?$/', $tag->value, $result);
        $paginatorAdapter = $result[1];
        $perPage = $result[2] ?? null;

        $data = [$paginatorAdapter];
        if (($perPage = trim($perPage))) $data[] = $perPage;
        return $data;
    }
}
