<?php

namespace Sura\Menu;

interface Activatable
{
    /**
     * @param bool|callable $active
     *
     * @return $this
     */
    public function setActive($active = true): static;

    /**
     * @return $this
     */
    public function setInactive(): static;

    /**
     * @return string|null
     */
    public function url(): ?string;

    /**
     * @return bool
     */
    public function hasUrl(): bool;

    /**
     * @param string $url
     *
     * @return $this
     */
    public function setUrl(string $url): static;

    /**
     * @param string $url
     * @param string $root
     */
    public function determineActiveForUrl(string $url, string $root = '/');
}
