<?php

namespace Sura\Menu;

use ArrayIterator;
use Countable;
use IteratorAggregate;
use JetBrains\PhpStorm\Pure;
use Sura\Menu\Helpers\Reflection;
use Sura\Menu\Html\Attributes;
use Sura\Menu\Html\Tag;
use Sura\Menu\Traits\Conditions as ConditionsTrait;
use Sura\Menu\Traits\HasHtmlAttributes as HasHtmlAttributesTrait;
use Sura\Menu\Traits\HasParentAttributes as HasParentAttributesTrait;
use Sura\Menu\Traits\HasTextAttributes as HasAttributesTrait;
use Traversable;

class Menu implements Item, Countable, HasHtmlAttributes, HasParentAttributes, IteratorAggregate
{
    use HasHtmlAttributesTrait;
    use HasParentAttributesTrait;
    use ConditionsTrait;
    use HasAttributesTrait;

    /** @var array */
    protected $items = [];

    /** @var array */
    protected $filters = [];

    /** @var string|Item */
    protected $prepend;
    protected $append = '';

    /** @var array */
    protected $wrap = [];

    /** @var string */
    protected $activeClass = 'active';

    /** @var string */
    protected $exactActiveClass = 'exact-active';

    /** @var string */
    protected $wrapperTagName = 'ul';

    /** @var string|null */
    protected $parentTagName = 'li';

    /** @var bool */
    protected $activeClassOnParent = true;

    /** @var bool */
    protected $activeClassOnLink = false;

    /** @var \Sura\Menu\Html\Attributes */
    protected $htmlAttributes;
    protected $parentAttributes;

    /**
     * Menu constructor.
     * @param Item ...$items
     */
    protected function __construct(Item ...$items)
    {
        $this->items = $items;

        $this->htmlAttributes = new Attributes();
        $this->parentAttributes = new Attributes();
    }

    /**
     * Create a new menu, optionally prefilled with items.
     *
     * @param array $items
     *
     * @return static
     */
    public static function new($items = []): static
    {
        return new static(...array_values($items));
    }

    /**
     * Build a new menu from an array. The callback receives a menu instance as
     * the accumulator, the array item as the second parameter, and the item's
     * key as the third.
     *
     * @param array|\Iterator $items
     * @param callable $callback
     * @param \Sura\Menu\Menu|null $initial
     *
     * @return static
     */
    public static function build($items, callable $callback, self $initial = null): static
    {
        return ($initial ?: static::new())->fill($items, $callback);
    }

    /**
     * Fill a menu from an array. The callback receives a menu instance as
     * the accumulator, the array item as the second parameter, and the item's
     * key as the third.
     *
     * @param array|\Iterator $items
     * @param callable $callback
     *
     * @return static
     */
    public function fill($items, callable $callback): Menu|static
    {
        $menu = $this;

        foreach ($items as $key => $item) {
            $menu = $callback($menu, $item, $key) ?: $menu;
        }

        return $menu;
    }

    /**
     * Add an item to the menu. This also applies all registered filters to the
     * item.
     *
     * @param \Sura\Menu\Item $item
     *
     * @return $this
     */
    public function add(Item $item): static
    {
        foreach ($this->filters as $filter) {
            $this->applyFilter($filter, $item);
        }

        $this->items[] = $item;

        return $this;
    }

    /**
     * Add an item to the menu if a (non-strict) condition is met.
     *
     * @param bool $condition
     * @param \Sura\Menu\Item $item
     *
     * @return $this
     */
    public function addIf($condition, Item $item): static
    {
        if ($this->resolveCondition($condition)) {
            $this->add($item);
        }

        return $this;
    }

    /**
     * Shortcut function to add a plain link to the menu.
     *
     * @param string $url
     * @param string $text
     *
     * @return $this
     */
    public function link(string $url, string $text): static
    {
        return $this->add(Link::to($url, $text));
    }

    /**
     * Shortcut function to add an empty item to the menu.
     *
     * @return $this
     */
    public function empty(): static
    {
        return $this->add(Html::empty());
    }

    /**
     * Add a link to the menu if a (non-strict) condition is met.
     *
     * @param bool $condition
     * @param string $url
     * @param string $text
     *
     * @return $this
     */
    public function linkIf($condition, string $url, string $text): static
    {
        if ($this->resolveCondition($condition)) {
            $this->link($url, $text);
        }

        return $this;
    }

    /**
     * Shortcut function to add raw html to the menu.
     *
     * @param string $html
     * @param array $parentAttributes
     *
     * @return $this
     */
    public function html(string $html, array $parentAttributes = []): static
    {
        return $this->add(Html::raw($html)->setParentAttributes($parentAttributes));
    }

    /**
     * Add a chunk of html if a (non-strict) condition is met.
     *
     * @param bool $condition
     * @param string $html
     * @param array $parentAttributes
     *
     * @return $this
     */
    public function htmlIf($condition, string $html, array $parentAttributes = []): static
    {
        if ($this->resolveCondition($condition)) {
            $this->html($html, $parentAttributes);
        }

        return $this;
    }

    /**
     * @param callable|\Sura\Menu\Menu|\Sura\Menu\Item $header
     * @param callable|\Sura\Menu\Menu|null $menu
     *
     * @return $this
     */
    public function submenu($header, $menu = null): static
    {
        [$header, $menu] = $this->parseSubmenuArgs(func_get_args());

        $menu = $this->createSubmenuMenu($menu);

        return $this->add($menu->prependIf($header, $header));
    }

    /**
     * @param bool $condition
     * @param callable|\Sura\Menu\Menu|\Sura\Menu\Item $header
     * @param callable|\Sura\Menu\Menu|null $menu
     *
     * @return $this
     */
    public function submenuIf($condition, $header, $menu = null): static
    {
        if ($condition) {
            $this->submenu($header, $menu);
        }

        return $this;
    }

    protected function parseSubmenuArgs($args): array
    {
        if (count($args) === 1) {
            return ['', $args[0]];
        }

        return [$args[0], $args[1]];
    }

    /**
     * @param \Sura\Menu\Menu|callable $menu
     *
     * @return \Sura\Menu\Menu
     */
    protected function createSubmenuMenu($menu): self
    {
        if (is_callable($menu)) {
            $transformer = $menu;
            $menu = $this->blueprint();
            $transformer($menu);
        }

        return $menu;
    }

    /**
     * @param \Sura\Menu\Item|string $header
     *
     * @return string
     */
    protected function createSubmenuHeader($header): string
    {
        if ($header instanceof Item) {
            $header = $header->render();
        }

        return $header;
    }

    /**
     * Iterate over all the items and apply a callback. If you typehint the
     * item parameter in the callable, it wil only be applied to items of that
     * type.
     *
     * @param callable $callable
     *
     * @return $this
     */
    public function each(callable $callable): static
    {
        $type = Reflection::firstParameterType($callable);

        foreach ($this->items as $item) {
            if (! Reflection::itemMatchesType($item, $type)) {
                continue;
            }

            $callable($item);
        }

        return $this;
    }

    /**
     * Register a filter to the menu. When an item is added, all filters will be
     * applied to the item. If you typehint the item parameter in the callable, it
     * will only be applied to items of that type.
     *
     * @param callable $callable
     *
     * @return $this
     */
    public function registerFilter(callable $callable): static
    {
        $this->filters[] = $callable;

        return $this;
    }

    /**
     * Apply a filter to an item. Returns the result of the filter.
     *
     * @param callable $filter
     * @param \Sura\Menu\Item $item
     */
    protected function applyFilter(callable $filter, Item $item)
    {
        $type = Reflection::firstParameterType($filter);

        if (! Reflection::itemMatchesType($item, $type)) {
            return;
        }

        $filter($item);
    }

    /**
     * Apply a callable to all existing items, and register it as a filter so it
     * will get applied to all new items too. If you typehint the item parameter
     * in the callable, it wil only be applied to items of that type.
     *
     * @param callable $callable
     *
     * @return $this
     */
    public function applyToAll(callable $callable): static
    {
        $this->each($callable);
        $this->registerFilter($callable);

        return $this;
    }

    /**
     * Wrap the entire menu in an html element. This is another level of
     * wrapping above the `wrapperTag`.
     *
     * @param string $element
     * @param array $attributes
     *
     * @return $this
     */
    public function wrap(string $element, $attributes = []): static
    {
        $this->wrap = [$element, $attributes];

        return $this;
    }

    /**
     * Determine whether the menu is active.
     *
     * @return bool
     */
    public function isActive(): bool
    {
        foreach ($this->items as $item) {
            if ($item->isActive()) {
                return true;
            }
        }

        if ($this->prepend && $this->prepend instanceof Item && $this->prepend->isActive()) {
            return true;
        }

        return false;
    }

    /**
     * A menu can be active but not exact-active, unless its prepend is.
     *
     * @return bool
     */
    public function isExactActive(): bool
    {
        if (! $this->prepend) {
            return false;
        }

        // Kind of hacky, should be handled differently in the next major version
        if (! method_exists($this->prepend, 'isExactActive')) {
            return false;
        }

        return $this->prepend->isExactActive();
    }

    /**
     * Set multiple items in the menu as active based on a callable that filters
     * through items. If you typehint the item parameter in the callable, it will
     * only be applied to items of that type.
     *
     * @param callable|string $urlOrCallable
     * @param string $root
     *
     * @return $this
     */
    public function setActive($urlOrCallable, string $root = '/'): static
    {
        if (is_string($urlOrCallable)) {
            try {
                return $this->setActiveFromUrl($urlOrCallable, $root);
            }catch (\Exception $e){

            }
        }

        if (is_callable($urlOrCallable)) {
            return $this->setActiveFromCallable($urlOrCallable);
        }

        throw new \InvalidArgumentException('`setActive` requires a pattern or a callable');
    }

    /**
     * Set the class name that will be used on exact-active items for this menu.
     *
     * @param string $class
     *
     * @return $this
     */
    public function setExactActiveClass(string $class): static
    {
        $this->exactActiveClass = $class;

        return $this;
    }

    /**
     * Set all relevant children active based on the current request's URL.
     *
     * /, /about, /contact => request to /about will set the about link active.
     *
     * /en, /en/about, /en/contact => request to /en won't set /en active if the
     *                                request root is set to /en.
     *
     * @param string $url  The current request url.
     * @param string $root If the link's URL is an exact match with the request
     *                     root, the link won't be set active. This behavior is
     *                     to avoid having home links active on every request.
     *
     * @return $this
     */
    public function setActiveFromUrl(string $url, string $root = '/'): static
    {
        $this->applyToAll(function (Menu $menu) use ($url, $root) {
            $menu->setActiveFromUrl($url, $root);
        });

        if ($this->prepend instanceof Activatable) {
            $this->prepend->determineActiveForUrl($url, $root);
        }

        $this->applyToAll(function (Activatable $item) use ($url, $root) {
            $item->determineActiveForUrl($url, $root);
        });

        return $this;
    }

    /**
     * @param callable $callable
     *
     * @return $this
     */
    public function setActiveFromCallable(callable $callable): static
    {
        $this->applyToAll(function (Menu $menu) use ($callable) {
            $menu->setActiveFromCallable($callable);
        });

        $type = Reflection::firstParameterType($callable);

        $this->applyToAll(function (Activatable $item) use ($callable, $type) {

            /** @var \Sura\Menu\Activatable|\Sura\Menu\Item $item */
            if (! Reflection::itemMatchesType($item, $type)) {
                return;
            }

            if ($callable($item)) {
                $item->setActive();
                /** @psalm-suppress UndefinedInterfaceMethod */
                $item->setExactActive();//FIXME method not found
            }
        });

        return $this;
    }

    /**
     * Set the class name that will be used on active items for this menu.
     *
     * @param string $class
     *
     * @return $this
     */
    public function setActiveClass(string $class): static
    {
        $this->activeClass = $class;

        return $this;
    }

    /**
     * Add a class to all items in the menu.
     *
     * @param string $class
     *
     * @return $this
     */
    public function addItemClass(string $class): static
    {
        $this->applyToAll(function (HasHtmlAttributes $link) use ($class) {
            $link->addClass($class);
        });

        return $this;
    }

    /**
     * Set an attribute on all items in the menu.
     *
     * @param string $attribute
     * @param string $value
     *
     * @return $this
     */
    public function setItemAttribute(string $attribute, string $value = ''): static
    {
        $this->applyToAll(function (HasHtmlAttributes $link) use ($attribute, $value) {
            $link->setAttribute($attribute, $value);
        });

        return $this;
    }

    /**
     * Add a parent class to all items in the menu.
     *
     * @param string $class
     *
     * @return $this
     */
    public function addItemParentClass(string $class): static
    {
        $this->applyToAll(function (HasParentAttributes $item) use ($class) {
            $item->addParentClass($class);
        });

        return $this;
    }

    /**
     * Add a parent attribute to all items in the menu.
     *
     * @param string $attribute
     * @param string $value
     *
     * @return $this
     */
    public function setItemParentAttribute(string $attribute, string $value = ''): static
    {
        $this->applyToAll(function (HasParentAttributes $item) use ($attribute, $value) {
            $item->setParentAttribute($attribute, $value);
        });

        return $this;
    }

    /**
     * Set tag for items wrapper.
     *
     * @param string|null $wrapperTagName
     * @return $this
     */
    public function setWrapperTag($wrapperTagName = null): static
    {
        $this->wrapperTagName = $wrapperTagName;

        return $this;
    }

    /**
     * Set tag for items wrapper.
     *
     * @param string|null $wrapperTagName
     * @return $this
     */
    public function withoutWrapperTag(): static
    {
        $this->wrapperTagName = null;

        return $this;
    }

    /**
     * Set the parent tag name.
     *
     * @param string|null $parentTagName
     * @return $this
     */
    public function setParentTag($parentTagName = null): static
    {
        $this->parentTagName = $parentTagName;

        return $this;
    }

    /**
     * Render items without a parent tag.
     *
     * @return $this
     */
    public function withoutParentTag(): static
    {
        $this->parentTagName = null;

        return $this;
    }

    /**
     * Set whether active class should (also) be on link.
     *
     * @param $activeClassOnLink
     * @return $this
     */
    public function setActiveClassOnLink(bool $activeClassOnLink = true): static
    {
        $this->activeClassOnLink = $activeClassOnLink;

        return $this;
    }

    /**
     * Set whether active class should (also) be on parent.
     *
     * @param $activeClassOnParent
     * @return $this
     */
    public function setActiveClassOnParent(bool $activeClassOnParent = true): static
    {
        $this->activeClassOnParent = $activeClassOnParent;

        return $this;
    }

    /**
     * @param bool $condition
     * @param callable $callable
     *
     * @return $this
     */
    public function if(bool $condition, callable $callable): Menu|static
    {
        return $condition ? $callable($this) : $this;
    }

    /**
     * Create a empty blueprint of the menu (copies `filters` and `activeClass`).
     *
     * @return static
     */
    public function blueprint(): static
    {
        $clone = new static();

        $clone->filters = $this->filters;
        $clone->activeClass = $this->activeClass;

        return $clone;
    }

    /**
     * Render the menu.
     *
     * @return string
     */
    public function render(): string
    {
        $tag = $this->wrapperTagName
            ? new Tag($this->wrapperTagName, $this->htmlAttributes)
            : null;

        $contents = array_map([$this, 'renderItem'], $this->items);

        $wrappedContents = $tag ? $tag->withContents($contents) : implode('', $contents);

        if ($this->prepend instanceof Item && $this->prepend->isActive()) {
            $this->prepend = $this->renderActiveClassOnLink($this->prepend);
        }

        $menu = $this->prepend.$wrappedContents.$this->append;

        if (! empty($this->wrap)) {
            return Tag::make($this->wrap[0], new Attributes($this->wrap[1]))->withContents($menu);
        }

        return $menu;
    }

    protected function renderItem(Item $item): string
    {
        $attributes = new Attributes();

        if (method_exists($item, 'beforeRender')) {
            $item->beforeRender();
        }

        if (method_exists($item, 'willRender') && $item->willRender() === false) {
            return '';
        }

        if ($item->isActive()) {
            if ($this->activeClassOnParent) {
                $attributes->addClass($this->activeClass);

                /** @psalm-suppress UndefinedInterfaceMethod */
                if ($item->isExactActive()) {
                    $attributes->addClass($this->exactActiveClass);
                }
            }

            $item = $this->renderActiveClassOnLink($item);
        }

        if ($item instanceof HasParentAttributes) {
            $attributes->setAttributes($item->parentAttributes());
        }

        if (! $this->parentTagName) {
            return $item->render();
        }

        return Tag::make($this->parentTagName, $attributes)->withContents($item->render());
    }

    /**
     * @param Item $item
     * @return Item
     */
    protected function renderActiveClassOnLink(Item $item): Item
    {
        if ($this->activeClassOnLink && $item instanceof HasHtmlAttributes && ! $item instanceof Menu) {
            $item->addClass($this->activeClass);

            /** @psalm-suppress UndefinedInterfaceMethod */
            if ($item->isExactActive()) {
                $item->addClass($this->exactActiveClass);
            }
        }

        return $item;
    }

    /**
     * The amount of items in the menu.
     *
     * @return int
     */
    #[Pure] public function count(): int
    {
        return count($this->items);
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->render();
    }

    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->items);
    }
}
