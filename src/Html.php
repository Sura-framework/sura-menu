<?php

namespace Sura\Menu;

use Sura\Menu\Html\Attributes;
use Sura\Menu\Traits\Activatable as ActivatableTrait;
use Sura\Menu\Traits\HasParentAttributes as HasParentAttributesTrait;

class Html implements Item, Activatable, HasParentAttributes
{
    use ActivatableTrait;
    use HasParentAttributesTrait;

    /** @var string */
    protected string $html;

    /** @var string|null */
    protected string $url = '';

    /** @var bool */
    protected bool $active = false;

    /** @var \Sura\Menu\Html\Attributes */
    protected Attributes $parentAttributes;

    /**
     * @param string $html
     */
    protected function __construct(string $html)
    {
        $this->html = $html;
        $this->parentAttributes = new Attributes();
    }

    /**
     * Create an item containing a chunk of raw html.
     *
     * @param string $html
     *
     * @return static
     */
    public static function raw(string $html): static
    {
        return new static($html);
    }

    /**
     * Create an empty item.
     *
     * @return static
     */
    public static function empty(): static
    {
        return new static('');
    }

    /**
     * @return string
     */
    public function html(): string
    {
        return $this->html;
    }

    /**
     * @return string
     */
    public function render(): string
    {
        return $this->html;
    }
}
