<?php

namespace HnhDigital\LaravelResourceInclude;

/*
 * Resource Include.
 *
 * @author Rocco Howard <rocco@hnh.digital>
 */

use Illuminate\Support\Arr;
use Html;

class ResourceInclude
{
    /**
     * The domain for resources.
     *
     * @var string
     */
    private $domain = '/';

    /**
     * Secure?
     *
     * @var string
     */
    private $secure = false;

    /**
     * Resources.
     *
     * @var array
     */
    private $resources = [];

    /**
     * Packages.
     *
     * @var array
     */
    private $packages = [];

    /**
     * Meta entries.
     *
     * @var array
     */
    private $head_tags = [];

    /**
     * Meta entries.
     *
     * @var array
     */
    private $meta = [];

    /**
     * Extension mapping.
     *
     * @var array
     */
    private $extension_mapping = [
        'css'  => ['css'],
        'js'   => ['js'],
    ];

    /**
     * Extension mapping.
     *
     * @var array
     */
    public $extension_default_locations = [
        'css'  => 'header',
        'js'   => 'footer',
    ];

    public function __construct()
    {
        $this->resources = collect();
    }

    /**
     * Is CDN activated?
     */
    public function cdn() : bool
    {
        return config('hnhdigital.resources.cdn', true);
    }

    /**
     * Set the domain.
     */
    public function setDomain(string $domain) : ResourceInclude
    {
        $this->domain = rtrim($domain, '/');

        return $this;
    }

    /**
     * Get the domain.
     */
    public function getDomain() : string
    {
        return $this->domain;
    }

    /**
     * Is secure?
     *
     * @return string
     */
    public function setSecure($secure)
    {
        $this->secure = $secure;

        return $this;
    }

    /**
     * Is secure?
     *
     * @return string
     */
    public function isSecure()
    {
        return $this->secure;
    }

    /**
     * Identify where to add an asset.
     */
    public function parseExtension(string $path, ?string $location = null) : array
    {
        $key = null;

        foreach ($this->extension_mapping as $store => $extensions) {
            foreach ($extensions as $ext) {
                if (preg_match("/(\.".$ext."|\/".$ext."\?)$/i", $path)) {
                    $key = $store;
                    break;
                }
            }
        }

        if (is_null($location) && ! is_null($key)) {
            $location = Arr::get($this->extension_default_locations, $key, 'footer');
        }

        return [$key, $location];
    }

    /**
     * Add a resource.
     */
    public function add(string $path, ?string $location = null, array $attributes = [], ?integer $priority = null) : Resource
    {
        $resource = Resource::createByPath($path, $location, $attributes);

        if (!is_null($priority)) {
            $resource->setPriority($priority);
        }

        $this->storeResource($resource);

        return $resource;
    }

    /**
     * Store resource.
     */
    public function storeResource(Resource $resource) : ResourceInclude
    {
        $this->resources->put($resource->getHash(), $resource);

        return $this;
    }

    /**
     * Add Resource.
     */
    public function addFirst(string $path, ?string $location = null, array $attributes = []) : Resource
    {
        return $this->add($path, $location, $attributes, 1);
    }

    /**
     * Get resources by type.
     */
    private function getResourceByType(string $type, string $location)
    {
        return $this->resources->filter(function ($resource, $hash) use ($type, $location) {
            return $resource->location === $location && $resource->type === $type;
        })->sortBy(function ($resource, $hash) {
            return $resource->priority;
        });
    }

    /**
     * Add content.
     */
    public function content(string $type, string $content, string $location) : void
    {
        $resource = Resource::createByContent($type, $content, $location);

        $this->resources[$resource->getHash()] = $resource;
    }

    /**
     * Render resource type for location.
     */
    public function render(string $type,  $location) : string
    {
        $result = '';

        if (config('app.env') === 'local') {
            $result = "<!-- {$type}/{$location} -->\n";
        }

        $resources = $this->getResourceByType($type, $location);

        foreach ($resources as $resource) {
            $render = $resource->render();
            $render = is_null($render) ? '' : $render."\n";
            $result .= $render;
        }

        return $result;
    }

    /**
     * Get the package integrity.
     *
     * @return arr
     */
    public function packageInfo(stirng $name) : array
    {
        if (config()->has('hnhdigital.resources.packages.'.$name)) {
            return config('hnhdigital.resources.packages.'.$name);
        }

        return [];
    }

    /**
     * Get the package integrity.
     */
    public function packageVersion(string $name, bool $version = false)
    {
        if (!empty($version)) {
            return $version;
        }

        if (config()->has('hnhdigital.resources.packages.'.$name.'.version')) {
            return config('hnhdigital.resources.packages.'.$name.'.version');

        // Backwards compatibility.
        } elseif (config()->has('hnhdigital.resources.packages.'.$name.'.1')) {
            return config('hnhdigital.resources.packages.'.$name.'.1');
        }

        return false;
    }

    /**
     * Get the package integrity.
     */
    public function packageIntegrity(string $name, string $asset = '') : string 
    {
        $integrity = config('hnhdigital.resources.packages.'.$name.'.integrity', []);

        if (is_array($integrity)) {
            if (isset($integrity[$asset])) {
                return $integrity[$asset];
            } else {
                return '';
            }
        }

        return $integrity;
    }

    /**
     * Add a head tag.
     */
    public function addHeadTag(array $tag) : ResourceInclude
    {
        $this->head_tags[] = $tag;

        return $this;
    }

    /**
     * Add a head tags.
     */
    public function addHeadTags(array $tags) : ResourceInclude
    {
        foreach ($tags as $tag) {
            $this->head_tags[] = $tag;
        }

        return $this;
    }

    /**
     * Output meta.
     *
     * @return array
     */
    public function headTags($echo = true) : string
    {
        $output = '';

        foreach ($this->head_tags as $attributes) {
            $output .= Html::element($attributes['tag'])
                ->attributes(Arr::except($attributes, ['tag', 'config']));
            $output .= "\n";
        }

        if ($echo) {
            echo $output;
        }

        return $output;
    }

    /**
     * Add a meta attribute.
     */
    public function addMeta(string $meta, array $data = []) : ResourceInclude
    {
        if (is_string($meta)) {
            $meta = [$meta => $data];
        }

        foreach ($meta as $key => $data) {
            $this->meta[$key] = $data;
        }

        return $this;
    }

    /**
     * Output meta.
     *
     * @return array
     */
    public function meta($echo = true) : string
    {
        $output = '';

        foreach ($this->meta as $name => $attributes) {
            $output .= Html::element('meta')
                ->attribute('name', Arr::has($attributes, 'config.noname') ? false : $name)
                ->attributes(Arr::except($attributes, ['config']));
            $output .= "\n";
        }

        if ($echo) {
            echo $output;
        }

        return $output;
    }

    /**
     * Load muiltiple packages.
     */
    public function packages(...$arguments) : void
    {
        if (!isset($arguments[0])) {
            return;
        }

        $container_list = $arguments[0];

        foreach ($container_list as $container_settings) {
            $this->package($container_settings);
        }
    }

    /**
     * Add a package.
     */
    public function package($settings, $config = []) : void
    {
        if (is_array($settings)) {
            $resource_name = array_shift($settings);
        } else {
            $resource_name = $settings;
            $settings = [];
        }

        $class_name = false;

        if ($resource_details = config('hnhdigital.resources.packages.'.$resource_name, false)) {
            $class_name = Arr::get($resource_details, 'class', Arr::get($resource_details, 0, false));
        }

        if ($class_name !== false && !isset($this->packages[$class_name]) && class_exists($class_name)) {
            $this->packages[$class_name] = new $class_name(...$settings);
            $this->callPackage($class_name, 'load', $config);
        }
    }

    /**
     * Call package method.
     */
    public function callPackage(string $class_name, string $method, ...$args)
    {
        if (! isset($this->packages[$class_name])) {
            return null;
        }

        if (! is_callable([$this->packages[$class_name], $method])) {
            return null;
        }

        return $this->packages[$class_name]->$method(...$args);
    }

    /**
     * Autoload resources for a given path.
     */
    public function autoInclude(array $extensions, string $path) : void
    {
        // Force array.
        $extensions = Arr::wrap($extensions);

        // Replace dots with slashes.
        $path = str_replace('.', '/', $path);

        if (substr($path, -1) === '*') {
            $this->autoIncludeByWildcard($extensions, substr($path, 0, -1));

            return;
        }

        // Go through each file extension folder.
        foreach ($extensions as $extension) {
            $file_name = $path.'.'.$extension;

            $local_file_path = dirname(resource_path().'/views/'.$file_name);
            $local_file_path .= '/'.$extension.'/'.basename($file_name);

            $full_path = '';

            // Adjust for local environment.
            if (app()->environment() === 'local') {
                if (file_exists($local_file_path)) {
                    $full_path = $local_file_path;
                } else {
                    $full_path = public_path().'/assets/'.$file_name;
                }
            }

            $this->loadResource($file_name, $extension, $full_path);
        }
    }

    /**
     * Autoload resources using a wildcard search of their path.
     */
    public function autoIncludeByWildcard(mixed $extensions, string $path) : void
    {
        // Force array.
        $extensions = Arr::wrap($extensions);

        // Replace dots with slashes.
        $path = str_replace('.', '/', $file);

        $root_path = dirname($path);
        $filename = basename($path);

        foreach ($extensions as $extension) {
            $extension_dir = $root_path.'/'.$extension.'/'.$filename;
            $scanned_paths = scandir(resource_path().'/views/'.$extension_dir);

            foreach ($scanned_paths as $scanned_file) {
                if ($scanned_file == '.' || $scanned_file == '..') {
                    continue;
                }

                $full_path = resource_path().'/views/'.$extension_dir.'/'.$scanned_file;
                $file_name = $extension_dir.'/'.$scanned_file;

                $this->loadResource($file_name, $extension, $full_path);
            }
        }
    }

    /**
     * Load an resource.
     */
    public function loadResource(string $file_name, string $extension, string $full_path = '') : void
    {
        // Load resources as script/link.
        // File needs to be in the manifest.
        if (!config('hnhdigital.resources.inline', false)) {

            // File is not in the manifest.
            if (!Arr::has(config(config('hnhdigital.resources.manifest-revisions'), []), $file_name)) {
                return;
            }

            $this->add($file_name);

            return;
        }

        // Path is empty or the path does not exist.
        if (!empty($full_path) && !file_exists($full_path)) {
            return;
        }

        // Add the file inline.
        $this->add($full_path, 'footer-inline');
    }

    /**
     * Get the URL for given path.
     */
    public function url(string $path) : string
    {
        // Detect path points to external url.
        if (stripos($path, '://') !== false) {
            return $path;
        }

        if (Arr::has(config(config('hnhdigital.resources.manifest-revisions'), []), $path)) {
            if (config('hnhdigital.resources.source', 'build') === 'build') {
                return '/build/'.Arr::get(config(config('hnhdigital.resources.manifest-revisions'), []), $path);
            }

            return '/'.config('hnhdigital.resources.source').'/'.$path;
        }

        if (file_exists(public_path().'/'.$path)) {
            return $path;
        }

        if (file_exists(public_path().'/assets/'.$path)) {
            return '/assets/'.$path;
        }

        return '';
    }

    /**
     * Enforce HTTP2.
     */
    public function http2() : void
    {
        if (!config('hnhdigital.resources.http2', false)) {
            return;
        }

        foreach ($this->resources as $resource) {
            $resource->http2();
        }
    }

    /**
     * Output header html.
     */
    public function header(bool $echo = true) : string
    {
        $output = '';
        $output .= $this->headTags(false);
        $output .= $this->meta(false);
        $output .= $this->render('css', 'header');
        $output .= $this->render('css', 'inline');
        $output .= $this->render('js', 'header');
        $output .= $this->render('js', 'header-inline');

        if ($echo) {
            echo $output;
        }

        return $output;
    }

    /**
     */
    public function footer(bool $echo = true) : string
    {
        $output = '';
        $output .= $this->render('css', 'footer');
        $output .= $this->render('css', 'footer-inline');
        $output .= $this->render('js', 'footer');
        $output .= $this->render('js', 'footer-inline');
        $output .= $this->render('js', 'ready');

        if ($echo) {
            echo $output;
        }

        return $output;
    }
}
