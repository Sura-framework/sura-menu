<?php

namespace Sura\Menu\Html;

use JetBrains\PhpStorm\Pure;

class Tag
{
    /** @var string */
    protected string $tagName;

    /** @var \Sura\Menu\Html\Attributes */
    protected Attributes $attributes;

    public function __construct(string $tagName, Attributes $attributes = null)
    {
        $this->tagName = $tagName;
        $this->attributes = $attributes ?: new Attributes();
    }

    public static function make(string $tagName, Attributes $attributes = null): Tag
    {
        return new self($tagName, $attributes);
    }

    #[Pure] public function withContents($contents): string
    {
        if (is_array($contents)) {
            $contents = implode('', $contents);
        }

        return $this->open().$contents.$this->close();
    }

    #[Pure] public function open(): string
    {
        if ($this->attributes->isEmpty()) {
            return "<{$this->tagName}>";
        }

        return "<{$this->tagName} {$this->attributes}>";
    }

    public function close(): string
    {
        return "</{$this->tagName}>";
    }
}
