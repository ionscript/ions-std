<?php

namespace Ions\Std\Spl;

/**
 * Class SplFileInfo
 * @package Ions\Std\Spl
 */
class SplFileInfo extends \SplFileInfo
{
    /**
     * @var array
     */
    protected $classes = [];
    /**
     * @var array
     */
    protected $namespaces = [];

    /**
     * @return array
     */
    public function getClasses()
    {
        return $this->classes;
    }

    /**
     * @return array
     */
    public function getNamespaces()
    {
        return $this->namespaces;
    }

    /**
     * @param $class
     * @return $this
     */
    public function addClass($class)
    {
        $this->classes[] = $class;

        return $this;
    }

    /**
     * @param $namespace
     * @return $this
     */
    public function addNamespace($namespace)
    {
        if (in_array($namespace, $this->namespaces)) {
            return $this;
        }

        $this->namespaces[] = $namespace;

        return $this;
    }
}
