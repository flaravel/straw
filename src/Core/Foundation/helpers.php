<?php

use Straw\Core\Container\Container;
use Straw\Core\Container\BindingResolutionException;

if (! function_exists('app')) {
    /**
     * Get the available container instance.
     *
     * @param string|null $abstract
     * @param array $parameters
     *
     * @return mixed
     * @throws ReflectionException|BindingResolutionException
     */
    function app(string $abstract = null, array $parameters = []): mixed
    {
        if (empty($abstract)) {
            return Container::getInstance();
        }

        return Container::getInstance()->make($abstract, $parameters);
    }
}