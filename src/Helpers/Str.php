<?php

namespace Sura\Menu\Helpers;

use JetBrains\PhpStorm\Pure;

class Str
{
    /**
     * @param string $haystack
     * @param string $needle
     * @return bool
     */
    #[Pure] public static function startsWith(string $haystack, string $needle): bool
    {
        return $needle != '' && str_starts_with($haystack, $needle);
    }

    /**
     * @param string $remove
     * @param string $subject
     * @return string
     */
    #[Pure] public static function removeFromStart(string $remove, string $subject): string
    {
        if (! self::startsWith($subject, $remove)) {
            return $subject;
        }

        return self::replaceFirst($remove, '', $subject);
    }

    /**
     * @param string $search
     * @param string $replace
     * @param string $subject
     * @return string
     */
    #[Pure] public static function replaceFirst(string $search, string $replace, string $subject): string
    {
        $position = strpos($subject, $search);

        if ($position !== false) {
            return substr_replace($subject, $replace, $position, strlen($search));
        }

        return $subject;
    }

    /**
     * @param string $pattern
     * @param string $subject
     * @return string
     */
    #[Pure] public static function ensureLeft(string $pattern, string $subject): string
    {
        if (str_starts_with($subject, $pattern)) {
            return $subject;
        }

        return $pattern.$subject;
    }

    /**
     * @param string $pattern
     * @param string $subject
     * @return string
     */
    #[Pure] public static function ensureRight(string $pattern, string $subject): string
    {
        if (strrpos($subject, $pattern) === strlen($subject) - 1) {
            return $subject;
        }

        return $subject.$pattern;
    }
}
