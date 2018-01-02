<?php

namespace Ions\Std;

/**
 * Class Message
 * @package Ions\Std
 */
class Message implements MessageInterface
{
    /**
     * @var array
     */
    protected $metadata = [];
    /**
     * @var string
     */
    protected $content = '';

    /**
     * @param $spec
     * @param null $value
     * @return $this
     * @throws \InvalidArgumentException
     */
    public function setMetadata($spec, $value = null)
    {
        if (is_scalar($spec)) {
            $this->metadata[$spec] = $value;
            return $this;
        }

        if (!is_array($spec)) {
            throw new \InvalidArgumentException(sprintf(
                'Expected a string or array argument in first position; received "%s"',
                (is_object($spec) ? get_class($spec) : gettype($spec))
            ));
        }

        foreach ($spec as $key => $value) {
            $this->metadata[$key] = $value;
        }

        return $this;
    }

    /**
     * @param null $key
     * @param null $default
     * @return array|mixed|null
     * @throws \InvalidArgumentException
     */
    public function getMetadata($key = null, $default = null)
    {
        if (null === $key) {
            return $this->metadata;
        }

        if (!is_scalar($key)) {
            throw new \InvalidArgumentException('Non-scalar argument provided for key');
        }

        if (array_key_exists($key, $this->metadata)) {
            return $this->metadata[$key];
        }

        return $default;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setContent($value)
    {
        $this->content = $value;
        return $this;
    }

    /**
     * @return string
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * @return string
     */
    public function toString()
    {
        $request = '';

        foreach ($this->getMetadata() as $key => $value) {
            $request .= sprintf("%s: %s\r\n",
                    (string)$key,
                    (string)$value
            );
        }

        $request .= "\r\n" . $this->getContent();

        return $request;
    }
}
