<?php

namespace HnhDigital\LaravelResoureInclude;

use Illuminate\Support\Arr;

/**
 * Base class.
 */
abstract class PackageAbstract
{
    /**
     * Package name.
     *
     * @var string
     */
    protected $package_name;

    /**
     * Version.
     *
     * @var string
     */
    protected $version;

    /**
     * Disable calling method.
     *
     * @var string
     */
    protected $disable_method = [];

    /**
     * Default constructor.
     *
     * @param bool $version
     *
     * @return void
     *
     * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
     */
    public function __construct($version = false)
    {
        $this->package_name = class_basename(static::class);
        $this->version = $this->lookupVersion($version);
    }

    /**
     * Check if CDN is enabled.
     *
     * @return bool
     */
    public function isCdn()
    {
        return app('ResoureInclude')->cdn();
    }

    /**
     * Load packages.
     *
     * @return void
     */
    public function load($config)
    {
        $this->callMethod('before');

        // If the package provides cdn/local methods.
        if ($this->isCdn() && !in_array('cdn', $this->disable_method)) {
            $this->callMethod('cdn');
        } elseif (!$this->isCdn() && !in_array('local', $this->disable_method)) {
            $this->callMethod('local');
        }

        $this->callMethod('after');

        if (!empty($config)) {
            $this->callMethod('local', Arr::wrap($config));
        }
    }

    /**
     * Call method.
     *
     * @return mixed
     */
    public function callMethod($method, ...$args)
    {
        if (! is_callable([$this, $method])) {
            return;
        }

        return $this->$method(...$args);
    }

    /**
     * Lookup verison.
     *
     * @return string
     */
    public function lookupVersion($version)
    {
        return app('ResoureInclude')->packageVersion($this->name(), $version);
    }

    /**
     * Get package name.
     *
     * @return string
     */
    public function name()
    {
        return $this->package_name;
    }

    /**
     * Get package version.
     *
     * @return string
     */
    public function version()
    {
        return $this->version;
    }

    /**
     * Get package info.
     *
     * @return mixed
     */
    public function info(...$args)
    {
        return Arr::get(app('ResoureInclude')->packageInfo($this->name()), ...$args);
    }

    /**
     * Get package integrity.
     */
    public function integrity() : string
    {
        return app('ResoureInclude')->packageIntegrity($this->name());
    }

    /**
     * Load package.
     */
    public function package($package) : void
    {
        app('ResoureInclude')->package($package);
    }

    /**
     * Add file.
     */
    public function add(...$args) : void
    {
        app('ResoureInclude')->add(...$args);
    }

    /**
     * Add content.
     */
    public function content(...$args) : void
    {
        app('ResoureInclude')->content(...$args);
    }

    /**
     * Add file first.
     */
    public function addFirst(...$args) : void
    {
        app('ResoureInclude')->addFirst(...$args);
    }

    /**
     * Default for local is to call the cdn.
     */
    public function local() : void
    {
        $this->callMethod('cdn');
    }
}
