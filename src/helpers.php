<?php

use Straw\Application;
use Straw\Core\Container\Container;

if (! function_exists('app')) {
    /**
     * Get the available container instance.
     *
     * @param string|null $abstract
     * @param  array  $parameters
     *
     * @return mixed|Application
     */
    function app(string $abstract = null, array $parameters = [])
    {
        if (empty($abstract)) {
            return Container::getInstance();
        }

        return Container::getInstance()->make($abstract, $parameters);
    }
}