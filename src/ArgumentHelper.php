<?php

declare(strict_types=1);

namespace Pollen\WordPressArgs;

use Illuminate\Support\Str;
use Pollen\Services\Translater;

/**
 * The ArgumentHelper class is a trait that provides methods to extract arguments from properties using getter methods.
 */
trait ArgumentHelper
{
    /**
     * Raw post type args.
     *
     * @var args
     */
    protected $rawArgs;

    /**
     * Set the raw arguments for the method.
     *
     * @param  array  $rawArgs The raw arguments to be set.
     * @return self Returns the instance of the object for method chaining.
     */
    public function setRawArgs(array $rawArgs): self
    {
        $this->rawArgs = $rawArgs;

        return $this;
    }

    /**
     * Get the raw arguments
     *
     * @return array|null The raw arguments, or null if not set
     */
    public function getRawArgs(): ?array
    {
        return $this->rawArgs;
    }

    /**
     * Build the arguments array
     *
     * @return array The built arguments array
     */
    protected function buildArguments(): array
    {
        $args = $this->extractArgumentFromProperties();
        $args = wp_parse_args($this->getRawArgs() ?? [], $args);
        $args['names'] = $this->getNames();

        return $args;
    }

    /**
     * Collects all getter methods of the current object.
     *
     * @return array An array containing all getter method names.
     */
    private function collectGetters(): array
    {
        $allMethods = get_class_methods($this);

        return array_filter($allMethods, function ($method) {
            return (str_starts_with($method, 'get') || str_starts_with($method, 'is') || str_starts_with($method, 'has')) && $method !== 'getRawArgs';
        });
    }

    /**
     * Generate argument name from given getter method
     *
     * @return string Argument's name in snake_case format
     */
    private function makeArgName(string $getter): string
    {
        $propertyName = $this->removeMethodPrefix($getter);

        return Str::snake($propertyName);
    }

    /**
     * Remove the prefix of getter method name (e.g. get, is, has)
     *
     * @return string The property name without prefix
     */
    private function removeMethodPrefix(string $methodName): string
    {
        $prefixes = ['get', 'is', 'has'];

        foreach ($prefixes as $prefix) {
            if (str_starts_with($methodName, $prefix)) {
                return substr($methodName, strlen($prefix));
            }
        }

        return $methodName;
    }

    /**
     * Extracts arguments from object properties using getter methods.
     *
     * @return array An associative array containing the extracted arguments.
     */
    public function extractArgumentFromProperties()
    {
        $args = [];
        $getters = $this->collectGetters();

        foreach ($getters as $getter) {
            $argValue = $this->{$getter}();
            if ($argValue === null) {
                continue;
            }
            $args[$this->makeArgName($getter)] = $argValue;
        }

        return $args;
    }
}
