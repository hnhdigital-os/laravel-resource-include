<?php

namespace HnhDigital\LaravelResoureInclude;

use Illuminate\Contracts\View\View;

class AutoloadAssetComposer
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
