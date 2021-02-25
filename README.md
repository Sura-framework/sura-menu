# sura-menu
## Menu Generator

The `sura/menu` The package provides a fluent interface to build menus of any size in your php application.

## Human Readable, Fluent Interface

All classes provide a human readable, fluent interface (no array configuration). Additionally, you can opt for a more verbose and flexible syntax, or for convenience methods that cover most use cases.

```php
Menu::new()
    ->add(Link::to('/', 'Home'))
    ->add(Link::to('/about', 'About'))
    ->add(Link::to('/contact', 'Contact'))
    ->add(Html::empty())
    ->render();

// Or just...
Menu::new()
    ->link('/', 'Home')
    ->link('/about', 'About')
    ->link('/contact', 'Contact')
    ->empty()
```

```html
<ul>
    <li><a href="/">Home</a></li>
    <li><a href="/about">About</a></li>
    <li><a href="/contact">Contact</a></li>
    <li></li>
</ul>
```

## Or a More Programmatic Approach

Menus can also be created through a reduce-like callable.

```php
$pages = [
    '/' => 'Home',
    '/about' => 'About',
    '/contact' => 'Contact',
];

Menu::build($pages, function ($menu, $label, $url) {
    $menu->add($url, $label);
})->render();
```

```html
<ul>
    <li><a href="/">Home</a></li>
    <li><a href="/about">About</a></li>
    <li><a href="/contact">Contact</a></li>
</ul>
```

## Strong Control Over the Html Output

You can programatically add html classes and attributes to any item in the menu, or to the menu itself.

```php
Menu::new()
    ->addClass('navigation')
    ->add(Link::to('/', 'Home')->addClass('home-link'))
    ->add(Link::to('/about', 'About'))
    ->add(Link::to('/contact', 'Contact')->addParentClass('float-right'))
    ->wrap('div.wrapper')
```

```html
<div class="wrapper">
    <ul class="navigation">
        <li><a href="/" class="home-link">Home</a></li>
        <li><a href="/about">About</a></li>
        <li class="float-right"><a href="/contact">Contact</a></li>
    </ul>
</div
```

## Not Afraid of Depths

The menu supports submenus, which in turn can be nested infinitely.

```php
Menu::new()
    ->link('/', 'Home')
    ->submenu('More', Menu::new()
        ->addClass('submenu')
        ->link('/about', 'About')
        ->link('/contact', 'Contact')
    );
```

```html
<ul>
    <li><a href="/">Home</a></li>
    <li>
        More
        <ul class="submenu">
            <li><a href="/about">About</a></li>
            <li><a href="/contact">Contact</a></li>
        </ul>
    </li>
</ul>
```

## Install

You can install the package via composer:

``` bash
composer require sura/menu
```
