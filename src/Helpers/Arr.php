<?php

namespace Sura\Menu\Helpers;

class Arr
{

    /**
     * @param array $array
     * @param $callback
     * @return array
     */
    public static function map(array $array, $callback): array
    {
        $keys = array_keys($array);

        $items = array_map($callback, $array, $keys);

        return array_combine($keys, $items);
    }

    /**
     * @param array $array
     * @param $item
     * @return array
     */
    public static function push(array $array, $item): array
    {
        $array[] = $item;

        return $array;
    }
}
