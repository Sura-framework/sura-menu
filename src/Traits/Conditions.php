<?php

namespace Sura\Menu\Traits;

trait Conditions
{
    /**
     * @param mixed $conditional
     * @return bool
     */
    protected function resolveCondition(mixed $conditional): bool
    {
        return is_callable($conditional) ? $conditional() : $conditional;
    }
}
