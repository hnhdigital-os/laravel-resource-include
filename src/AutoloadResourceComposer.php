<?php

namespace HnhDigital\LaravelResourceInclude;

use Illuminate\Contracts\View\View;

class AutoloadResourceComposer
{
    /**
     * Bind data to the view.
     *
     * @param View $view
     *
     * @return void
     */
    public function compose(View $view)
    {
        app('ResourceInclude')->autoInclude(['js', 'css'], $view->name());
    }
}
