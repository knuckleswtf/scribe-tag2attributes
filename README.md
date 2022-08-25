# Scribe


This package provides a [Rector](https://github.com/rectorphp/rector) rule to automatically convert most Scribe v3 docblock tags to v4 PHP 8 attributes.

This package will smartly transform the following tags to their attribute equivalents:

- `header`
- `urlParam`, `queryParam`, and `bodyParam`
- `responseField`
- `response`
- `responseFile`
- `apiResource`,`apiResourceCollection`, and `apiResourceModel`
- `transformer`, `transformerCollection`, and `transformerModel`
- `subgroup`
- `authenticated` and `unauthenticated`

It won't transform `@group` tags or endpoint titles and descriptions (because they can look ugly as attributes).

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
+ #[ResponseFromApiResource('App\Http\Resources\UserResource', 'App\Models\User', collection: true, factoryStates: ['admin'], with: ['sideProjects', 'friends'], simplePaginate: 12)]
+ #[ResponseFromFile('responses/not_found.json', status: 404, merge: '{"resource": "user"}', description: '404, User not found')]
  public function doSomething()
```

> Note that this rule will only work on methods in classes. Unfortunately, attributes can't be added to inline (closure) routes in a neat way.

## Usage
- Make sure the minimum PHP version in your `composer.json` is 8.0 (ie you should have `"php": ">= 8.0"` or similar in your `"require"` section).
- Install this package
  ```sh
  composer require knuckleswtf/scribe-tags2attributes --dev
  ```

- Create a `rector.php` file in the root of your project
  ```sh
  ./vendor/bin/rector init
  ```

- Put this in the generated `rector.php` (delete whatever's in it):
  ```php
  <?php

  use Rector\Config\RectorConfig;
  
  return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->disableParallel();
    $rectorConfig->paths([
      __DIR__ . '/app/Http/Controllers', // <- replace this with wherever your controllers are
    ]);
    $rectorConfig->importNames();
    $rectorConfig->rule(Knuckles\Scribe\Docblock2Attributes\RectorRule::class);
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

All done! You can delete the `rector.php` file and run `composer remove knuckleswtf/scribe-tags2attributes`.
