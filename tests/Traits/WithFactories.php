<?php

namespace Tests\Traits;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Collection;

/**
 * Trait for handling factory operations in tests.
 */
trait WithFactories
{
    /**
     * Create multiple instances of a model using its factory.
     *
     * @param  string  $modelClass  The model class name
     * @param  int  $count  Number of instances to create
     * @param  array  $attributes  Attributes to override
     */
    protected function createMany(string $modelClass, int $count, array $attributes = []): Collection
    {
        return $modelClass::factory()->count($count)->create($attributes);
    }

    /**
     * Create a single instance of a model using its factory.
     *
     * @param  string  $modelClass  The model class name
     * @param  array  $attributes  Attributes to override
     * @return mixed
     */
    protected function createOne(string $modelClass, array $attributes = [])
    {
        return $modelClass::factory()->create($attributes);
    }

    /**
     * Make (but don't persist) multiple instances of a model.
     *
     * @param  string  $modelClass  The model class name
     * @param  int  $count  Number of instances to make
     * @param  array  $attributes  Attributes to override
     */
    protected function makeMany(string $modelClass, int $count, array $attributes = []): Collection
    {
        return $modelClass::factory()->count($count)->make($attributes);
    }

    /**
     * Make (but don't persist) a single instance of a model.
     *
     * @param  string  $modelClass  The model class name
     * @param  array  $attributes  Attributes to override
     * @return mixed
     */
    protected function makeOne(string $modelClass, array $attributes = [])
    {
        return $modelClass::factory()->make($attributes);
    }

    /**
     * Create a model with specific state.
     *
     * @param  string  $modelClass  The model class name
     * @param  string  $state  The state name
     * @param  array  $attributes  Additional attributes
     * @return mixed
     */
    protected function createWithState(string $modelClass, string $state, array $attributes = [])
    {
        return $modelClass::factory()->$state()->create($attributes);
    }

    /**
     * Create multiple models with relationships.
     *
     * @param  string  $modelClass  The model class name
     * @param  string  $relationship  The relationship method name
     * @param  int  $relationCount  Number of related models to create
     * @param  array  $attributes  Attributes for the main model
     * @return mixed
     */
    protected function createWithRelations(
        string $modelClass,
        string $relationship,
        int $relationCount,
        array $attributes = []
    ) {
        return $modelClass::factory()
            ->has($modelClass::factory()->count($relationCount), $relationship)
            ->create($attributes);
    }
}
