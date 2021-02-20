<?php

namespace BoxedCode\BinaryUuid\Test\Feature;

use BoxedCode\BinaryUuid\Test\TestCase;
use BoxedCode\BinaryUuid\Test\TestModel;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Ramsey\Uuid\Uuid;

class HasUuidPrimaryKeyTest extends TestCase
{
    use CreatesModel;

    /** @test */
    public function it_resolves_route_binding()
    {
        $uuid = Uuid::uuid1();
        $this->createModel($uuid);

        $resolvedModel = (new TestModel())->resolveRouteBinding($uuid);

        $this->assertEquals((string) $uuid, $resolvedModel->uuid_text);
    }

    /** @test */
    public function laravel_resolves_route_binding_correctly()
    {
        $uuid = Uuid::uuid1();
        $this->createModel($uuid);

        app('router')
            ->middleware(SubstituteBindings::class)
            ->group(function () {
                app('router')->get('uuid-test/{model}', function (TestModel $model) {
                    return $model;
                });
            });

        $this->get("uuid-test/{$uuid->toString()}")
            ->assertJson([
                'uuid' => $uuid,
            ]);
    }
}
