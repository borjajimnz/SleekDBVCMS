<?php

namespace SleekDBVCMS\Services;

use Jenssegers\Blade\Blade;
use Illuminate\Container\Container;

/**
 * Thin wrapper around jenssegers/blade that fixes the container mismatch
 * between jenssegers/blade and illuminate/view: the view engine resolves
 * bindings against the global Container, so we align it at construction time.
 */
class BladeRenderer
{
    private Blade $blade;

    public function __construct(string $viewPath, string $cachePath)
    {
        $this->blade = new Blade([$viewPath], $cachePath);

        // Align the global container so illuminate/view internals can resolve
        // "blade.compiler" and friends. Without this, rendering fails with
        // "Class blade.compiler does not exist".
        $container = (function () {
            return $this->container;
        })->call($this->blade);
        Container::setInstance($container);
    }

    public function render(string $view, array $data = []): string
    {
        return $this->blade->render($view, $data);
    }

    public function exists(string $view): bool
    {
        return $this->blade->exists($view);
    }

    public function directive(string $name, callable $handler): void
    {
        $this->blade->directive($name, $handler);
    }
}
