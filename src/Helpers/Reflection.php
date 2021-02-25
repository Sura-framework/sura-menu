<?php

namespace Sura\Menu\Helpers;

use ReflectionClass;
use ReflectionFunction;
use ReflectionObject;
use ReflectionParameter;
use Sura\Menu\Item;

class Reflection
{
    public static function firstParameterType(callable $callable): string
    {
        try {
            $reflection = is_object($callable)
                ? (new ReflectionObject((object)$callable))->getMethod('__invoke')
                : new ReflectionFunction($callable);
        } catch (\ReflectionException $e) {
        }

        $parameters = $reflection->getParameters();

        $parameterTypes = array_map(function (ReflectionParameter $parameter) {
            try {
                $class = $parameter->getType() && !$parameter->getType()->isBuiltin()//FIXME
                    ? new ReflectionClass($parameter->getType()->getName())
                    : null;
            } catch (\Exception $e) {
            }

            return $class ? $class->name : null;
        }, $parameters);

        return $parameterTypes[0] ?? '';
    }

    public static function itemMatchesType(Item $item, string $type): bool
    {
        if ($type === '') {
            return true;
        }

        return $item instanceof $type;
    }
}
