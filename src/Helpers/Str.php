<?php

namespace Sura\Menu\Helpers;

use JetBrains\PhpStorm\Pure;

class Str
{
    #[Pure] public static function startsWith(string $haystack, string $needle): bool
    {
        return $needle != '' && str_starts_with($haystack, $needle);
    }

    #[Pure] public static function removeFromStart(string $remove, string $subject): string
    {
        if (! self::startsWith($subject, $remove)) {
            return $subject;
        }

        return self::replaceFirst($remove, '', $subject);
    }

    #[Pure] public static function replaceFirst(string $search, string $replace, string $subject): string
    {
        $position = strpos($subject, $search);

        if ($position !== false) {
            return substr_replace($subject, $replace, $position, strlen($search));
        }

        return $subject;
    }

    #[Pure] public static function ensureLeft(string $pattern, string $subject): string
    {
        if (str_starts_with($subject, $pattern)) {
            return $subject;
        }

        return $pattern.$subject;
    }

    #[Pure] public static function ensureRight(string $pattern, string $subject): string
    {
        if (strrpos($subject, $pattern) === strlen($subject) - 1) {
            return $subject;
        }

        return $subject.$pattern;
    }
}
