<?php

namespace Ions\Std;

use Traversable;

/**
 * Class AbstractOptions
 * @package Ions\Std
 */
abstract class AbstractOptions implements ParameterInterface
{
    /**
     * AbstractOptions constructor.
     * @param null $options
     */
    public function __construct($options = null)
    {
        if (null !== $options) {
            $this->setFromArray($options);
        }
    }

    /**
     * @param $options
     * @return $this
     * @throws \InvalidArgumentException
     */
    public function setFromArray($options)
    {
        if ($options instanceof self) {
            $options = $options->toArray();
        }

        if (!is_array($options) && !$options instanceof Traversable) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Parameter provided to %s must be an %s, %s or %s',
                    __METHOD__,
                    'array',
                    'Traversable',
                    static::class
                )
            );
        }

        foreach ($options as $key => $value) {
            $this->__set($key, $value);
        }

        return $this;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        $array = [];

        foreach ($this as $key => $value) {
            $array[$key] = $value;
        }

        return $array;
    }

    /**
     * @param string $key
     * @param mixed $value
     * @throws \BadFunctionCallException
     */
    public function __set($key, $value)
    {
        $setter = 'set' . str_replace('_', '', $key);

        if (is_callable([$this, $setter])) {
            $this->{$setter}($value);

            return;
        }

        throw new \BadMethodCallException(sprintf(
            'The option "%s" does not have a callable "%s" ("%s") setter method which must be defined',
            $key,
            'set' . str_replace(' ', '', ucwords(str_replace('_', ' ', $key))),
            $setter
        ));
    }

    /**
     * @param string $key
     * @return mixed
     * @throws \BadFunctionCallException
     */
    public function __get($key)
    {
        $getter = 'get' . str_replace('_', '', $key);

        if (is_callable([$this, $getter])) {
            return $this->{$getter}();
        }

        throw new \BadMethodCallException(sprintf(
            'The option "%s" does not have a callable "%s" getter method which must be defined',
            $key,
            'get' . str_replace(' ', '', ucwords(str_replace('_', ' ', $key)))
        ));
    }

    /**
     * @param string $key
     * @return bool
     */
    public function __isset($key)
    {
        $getter = 'get' . str_replace('_', '', $key);

        return method_exists($this, $getter) && null !== $this->__get($key);
    }

    /**
     * @param string $key
     * @throws \BadFunctionCallException|\InvalidArgumentException
     */
    public function __unset($key)
    {
        try {
            $this->__set($key, null);
        } catch (\BadMethodCallException $e) {
            throw new \InvalidArgumentException(
                'The class property $' . $key . ' cannot be unset as'
                . ' NULL is an invalid value for it',
                0,
                $e
            );
        }
    }
}
