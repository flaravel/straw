<?php

namespace Straw\Core\Container;

use Closure;
use ReflectionNamedType;
use JetBrains\PhpStorm\Pure;

/**
 * @internal
 */
class Util
{
    /**
     * Get the class name of the given parameter's type, if possible.
     *
     * From Reflector::getParameterClassName() in Illuminate\Support.
     *
     * @param \ReflectionParameter $parameter
     *
     * @return string|null
     */
    #[Pure] public static function getParameterClassName(\ReflectionParameter $parameter): ?string
    {
        // 获取当前参数类型
        $type = $parameter->getType();

        // 检测是否设置类参数类型，如果设置就返回对应的类型对象，否则返回NULL, 并且检测是否为内置函数，
        if (! $type instanceof ReflectionNamedType || $type->isBuiltin()) {
            return null;
        }

        $name = $type->getName();

        if (! is_null($class = $parameter->getDeclaringClass())) {
            if ($name === 'self') {
                return $class->getName();
            }

            if ($name === 'parent' && $parent = $class->getParentClass()) {
                return $parent->getName();
            }
        }

        return $name;
    }
}
