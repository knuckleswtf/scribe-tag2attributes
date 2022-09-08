# Scribe


This package provides a [Rector](https://github.com/rectorphp/rector) rule to automatically convert most Scribe v3 docblock tags to v4 PHP 8 attributes.

This package **will** smartly transform the following tags on controller methods to their attribute equivalents:

- `header`, `urlParam`, `queryParam`, and `bodyParam`
- `responseField`, `response` and `responseFile`
- `apiResource`,`apiResourceCollection`, and `apiResourceModel`
- `transformer`, `transformerCollection`, and `transformerModel`
- `subgroup`
- `authenticated` and `unauthenticated`

It **won't** transform `@group` tags or endpoint titles and descriptions (because they can look ugly as attributes).

It will only work on methods in classes. Unfortunately, attributes can't be added to inline (closure) routes in a neat way.

Example:

```diff
  /*
   * Do a thing.
   *
   * Because you want to.
   *
   * @group Endpoints for doing things
-  * @subgroup Getting started
-  * @subgroupDescription Get started doing stuff
-  * @header Test Value
-  * @response 204 scenario="Nothing to see here"
-  * @apiResourceCollection App\Http\Resources\UserResource
-  * @apiResourceModel App\Models\User with=sideProjects,friends states=admin paginate=12,simple
-  * @responseFile 404 scenario="User not found" responses/not_found.json {"resource": "user"}
   */
+ #[Subgroup('Getting started', 'Get started doing stuff')]
+ #[Header('Test', 'Value')]
+ #[Response(status: 204, description: '204, Nothing to see here')]
+ #[ResponseFromApiResource(UserResource::class, User::class, collection: true, factoryStates: ['admin'], with: ['sideProjects', 'friends'], simplePaginate: 12)]
+ #[ResponseFromFile('responses/not_found.json', status: 404, merge: '{"resource": "user"}', description: '404, User not found')]
  public function doSomething()
```

## Usage
- Make sure the minimum PHP version in your `composer.json` is 8 (ie you should have `"php": ">= 8.0"` or similar in your `"require"` section).
- Install this package
  ```sh
  composer require knuckleswtf/scribe-tags2attributes --dev
  ```

- Run the Rector `init` command to create a `rector.php` file in the root of your project
  ```sh
  ./vendor/bin/rector init
  ```

- Put this in the generated `rector.php` (delete whatever's in the file):
  ```php
  <?php

  use Rector\Config\RectorConfig;
  
  return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->disableParallel();
    $rectorConfig->paths([
      __DIR__ . '/app/Http/Controllers', // <- replace this with wherever your controllers are
    ]);
    $rectorConfig->importNames();
    $rectorConfig->rule(\Knuckles\Scribe\Tags2Attributes\RectorRule::class);
  };
  ```

- Do a dry run. This will tell Rector to print out the changes that will be made, without actually making them. That way you can inspect and verify that it looks okay. We also recommend doing a `git commit`.
  ```sh
  ./vendor/bin/rector process --dry-run --clear-cache
  ```

- When you're ready, run the command.
  ```sh
  ./vendor/bin/rector process --clear-cache
  ```
- Make sure to add the attribute strategies to your `config/scribe.php`:
  ```diff
    'strategies' => [
        'metadata' => [
            Strategies\Metadata\GetFromDocBlocks::class,
  +         Strategies\Metadata\GetFromMetadataAttributes::class,
        ],
        'urlParameters' => [
            Strategies\UrlParameters\GetFromLaravelAPI::class,
            Strategies\UrlParameters\GetFromLumenAPI::class,
            Strategies\UrlParameters\GetFromUrlParamTag::class,
  +         Strategies\UrlParameters\GetFromUrlParamAttribute::class,
        ],
        'queryParameters' => [
            Strategies\QueryParameters\GetFromFormRequest::class,
            Strategies\QueryParameters\GetFromInlineValidator::class,
            Strategies\QueryParameters\GetFromQueryParamTag::class,
  +         Strategies\QueryParameters\GetFromQueryParamAttribute::class,
        ],
        'headers' => [
            Strategies\Headers\GetFromRouteRules::class,
            Strategies\Headers\GetFromHeaderTag::class,
  +         Strategies\Headers\GetFromHeaderAttribute::class,
        ],
        'bodyParameters' => [
            Strategies\BodyParameters\GetFromFormRequest::class,
            Strategies\BodyParameters\GetFromInlineValidator::class,
            Strategies\BodyParameters\GetFromBodyParamTag::class,
  +         Strategies\BodyParameters\GetFromBodyParamAttribute::class,
        ],
        'responses' => [
            Strategies\Responses\UseTransformerTags::class,
            Strategies\Responses\UseResponseTag::class,
            Strategies\Responses\UseResponseFileTag::class,
            Strategies\Responses\UseApiResourceTags::class,
  +         Strategies\Responses\UseResponseAttributes::class,
            Strategies\Responses\ResponseCalls::class,
        ],
        'responseFields' => [
            Strategies\ResponseFields\GetFromResponseFieldTag::class,
  +         Strategies\ResponseFields\GetFromResponseFieldAttribute::class,
        ],
    ],
  ```

All done! You can delete the `rector.php` file and run `composer remove knuckleswtf/scribe-tags2attributes`.
