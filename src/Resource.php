<?php

namespace HnhDigital\LaravelResourceInclude;

use Illuminate\Support\Arr;

class Resource
{
    private $path;
    private $content;
    private $location;
    private $type;
    private $hash;
    private $attributes = [];

    /**
     * Priority of resource.
     *
     * @var int
     */
    private $priority = 100;

    /**
     * Create resource by path.
     */
    public static function createByPath(string $path, string $location, array $attributes = []) : Resource
    {
        $resource = (new self())
            ->setPath($path);

        list($type, $location) = app('ResourceInclude')->parseExtension($resource->getPath(), $location);

        $resource->setType($type)
            ->setLocation($location)
            ->setAttributes($attributes);

        return $resource;
    }

    /**
     * Create resource by content.
     */
    public static function createByContent(string $type, string $content, string $location) : Resource
    {
        $resource = (new self())
            ->setType($type)
            ->setContent($content)
            ->setLocation($location);

        return $resource;
    }

    /**
     * Set path.
     *
     * @return self
     */
    public function setPath(string $path) : Resource
    {
        if (stripos($path, base_path()) === false) {
            $path = app('ResourceInclude')->url($path);
        }

        $this->path = $path;

        $this->hash = hash('sha256', $this->path);

        return $this;
    }

    /**
     * Get path.
     */
    public function getPath() : string
    {
        return $this->path;
    }

    /**
     * Set the type.
     */
    public function setType(string $type) : Resource
    {
        $this->type = $type;

        if (is_null($this->location)) {
            $this->setLocation(Arr::get(app('ResourceInclude')->extension_default_locations, $this->type, 'footer'));
        }

        return $this;
    }

    /**
     * Get the hash for this resource.
     */
    public function getHash() : string
    {
        return $this->hash;
    }

    /**
     * Get type of this resource.
     */
    public function getType() : string
    {
        return $this->type;
    }

    /**
     * Set the location.
     */
    public function setLocation(string $location) : Resource
    {
        $this->location = $location;

        return $this;
    }

    /**
     * Get the location.
     */
    public function getLocation() : string
    {
        return $this->location;
    }

    /**
     * Set the content.
     */
    public function setContent(string $content) : Resource
    {
        $this->content = $content;

        $this->hash = hash('sha256', $this->content);

        return $this;
    }

    /**
     * Set the priority.
     */
    public function setPriority(integer $priority) : Resource
    {
        $this->priority = $priority;

        return $this;
    }

    /**
     * Set the attributes.
     */
    public function setAttributes(array $attributes) : Resource
    {
        $this->attributes = $attributes;

        return $this;
    }

    /**
     * Get the URL for this resource
     */
    public function getUrl() : string
    {
        if ($this->isExternal()) {
            return $this->path;
        }

        if (app('ResourceInclude')->getDomain() === '/' && app()->environment() !== 'local') {
            return resource($this->path, app('ResourceInclude')->isSecure());
        }

        return rtrim(app('ResourceInclude')->getDomain(), '/').'/'.ltrim($this->path, '/');
    }

    /**
     * Is this resource external?
     */
    public function isExternal() : bool
    {
        return ! empty($this->path) && preg_match('/(https?:)?\/\//i', $this->path);
    }

    /**
     * Store resource.
     *
     * @return self
     */
    public function store() : Resource
    {
        app('ResourceInclude')->storeAsset($this);

        return $this;
    }

    /**
     * Render this resource.
     */
    public function render() : string
    {
        $result = '';

        if ($this->isInline()) {
            return $this->renderInline($this->location);
        }

        switch ($this->type) {
            case 'styles':
                break;
            case 'js':
                // type, defer, async
                $result = '<script src="'.$this->getUrl().'"'.$this->renderAttributes().'></script>';
                break;
            case 'css':
                $result = '<link rel="stylesheet" type="text/css" href="'.$this->getUrl().'"'.$this->renderAttributes().'></script>';
                break;
        }

        return $result;
    }

    /**
     * Render attributes for this resource.
     */
    private function renderAttributes() : string
    {
        $result = '';

        if (!is_array($this->attributes)) {
            return '';
        }

        foreach ($this->attributes as $key => $value) {
            if (is_int($key)) {
                $result .= " {$value}";
                continue;
            }

            $result .= sprintf(' %s="%s"', $key, $value);
        }

        return $result;
    }

    /**
     * Render this resource inline.
     */
    private function renderInline(string $location) : string
    {
        $result = '';

        if (!file_exists($this->path)) {
            return '';
        }

        $content = file_get_contents($this->path);

        if ($this->type === 'js' && $location === 'ready') {
            $content = sprintf('$(function(){ %s });', $content);
        }

        switch ($this->type) {
            case 'css':
                $result = sprintf('<style type="text/css">%s</style>', $content);
                break;
            case 'js':
                $result = sprintf('<script type="text/javascript">%s</script>', $content);
                break;

        }

        return $result;
    }

    /**
     * Render content within a document ready.
     */
    private function renderReady() : string
    {
        return '';
    }

    /**
     * Check if this resource has been marked inline.
     */
    private function isInline() : bool
    {
        return stripos($this->location, 'inline') !== false || $this->location === 'ready';
    }

    /**
     * Output HTTP2 header for this resource.
     */
    public function http2() : void
    {
        if ($this->isInline()) {
            return;
        }

        // Can't prefetch when resource has an integrity check.
        if (Arr::has($this->attributes, 'integrity')) {
            return;
        }

        switch ($this->type) {
            case 'js':
                $link_as = 'script';
                break;
            case 'css':
                $link_as = 'style';
                break;
            default:
                return;
        }

        header('Link: <'.$this->getUrl().'>; rel=preload; as='.$link_as.';', false);
    }

    /**
     * Magic get.
     *
     * @param string $name
     *
     * @return string
     */
    public function __get($name) : string
    {
        if (!isset($this->$name)) {
            return;
        }

        return $this->$name;
    }
}
