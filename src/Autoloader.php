<?php

namespace Ions\Std;

/**
 * Class Autoloader
 * @package Ions\Std
 */
final class Autoloader
{
    /**
     * @var array
     */
    private $classmap = [];
    /**
     * @var array
     */
    private $namespaces = [];
    /**
     * @var array
     */
    private $mapsLoaded = [];

    /**
     * @return void
     */
    public function register()
    {
        spl_autoload_register([$this, 'autoload'], true, true);
    }

    /**
     * @return void
     */
    public function unregister()
    {
        spl_autoload_register([$this, 'autoload']);
    }

    /**
     * @param $locations
     * @return $this
     * @throws \InvalidArgumentException
     */
    public function registerMaps($locations)
    {
        if (!is_array($locations)) {
            throw new \InvalidArgumentException('Map list must be an array');
        }
        foreach ($locations as $location) {
            $this->registerMap($location);
        }
        return $this;
    }

    /**
     * @param $map
     * @return $this
     * @throws \InvalidArgumentException
     */
    public function registerMap($map)
    {
        if (is_string($map)) {
            $location = $map;
            if ($this === ($map = $this->loadMap($location))) {
                return $this;
            }
        }

        if (!is_array($map)) {
            throw new \InvalidArgumentException(
                'Wrong Map file: ' . (isset($location) ? '[L]' . $location : '[M]' . $map)
            );
        }

        $this->classmap = $map['classmap'] + $this->classmap;
        $this->namespaces = $map['namespaces'] + $this->namespaces;

        if (isset($location)) {
            $this->mapsLoaded[] = $location;
        }

        return $this;
    }

    /**
     * @param $location
     * @return $this|array|bool
     * @throws \InvalidArgumentException
     */
    private function loadMap($location)
    {
        if (!file_exists($location)) {
            throw new \InvalidArgumentException(sprintf(
                'Map file provided does not exist. Map file: "%s"',
                (is_string($location) ? $location : 'unexpected type: ' . gettype($location))
            ));
        }

        $path = realpath($location);

        if (in_array($path, $this->mapsLoaded, true)) {
            return $this;
        }

        return parse_ini_file($path, true);
    }

    /**
     * @param $namespace
     * @param $directory
     * @return $this
     */
    public function registerNamespace($namespace, $directory)
    {
        $this->namespaces[$namespace] = $directory;
        return $this;
    }

    /**
     * @param array $namespaces
     * @return $this
     */
    public function registerNamespaces(array $namespaces)
    {
        foreach ($namespaces as $namespace => $directory) {
            $this->registerNamespace($namespace, $directory);
        }

        return $this;
    }

    /**
     * @param $class
     * @return bool
     */
    public function autoload($class)
    {
        if (array_key_exists($class, $this->classmap)) {
            require_once $this->classmap[$class];

            return $class;
        }

        $matches = [];
        preg_match('/(?P<namespace>.+\\\)?(?P<class>[^\\\]+$)/', $class, $matches);

        $namespace = array_key_exists('namespace', $matches) ? $matches['namespace'] : '';
        $filename = $matches['class'] . '.php';

        if ($resolved = stream_resolve_include_path($filename)) {
            require_once $resolved;

            return $class;
        }

        $filename = array_key_exists($namespace, $this->namespaces) ? $this->namespaces[$namespace] . $filename : '';

        if ($filename && file_exists($filename)) {
            require_once $filename;

            return $class;
        }

        return false;
    }
}
