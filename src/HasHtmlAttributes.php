<?php

namespace Sura\Menu;

interface HasHtmlAttributes
{
    /**
     * @param string $attribute
     * @param string $value
     *
     * @return $this
     */
    public function setAttribute(string $attribute, string $value = ''): static;

    /**
     * @param array $attributes
     *
     * @return $this
     */
    public function setAttributes(array $attributes): static;

    /**
     * @param string $class
     *
     * @return $this
     */
    public function addClass(string $class): static;
}
