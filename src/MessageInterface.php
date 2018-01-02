<?php

namespace Ions\Std;

/**
 * Interface MessageInterface
 * @package Ions\Std
 */
interface MessageInterface
{
    /**
     * @param $spec
     * @param null $value
     * @return mixed
     */
    public function setMetadata($spec, $value = null);

    /**
     * @param null $key
     * @return mixed
     */
    public function getMetadata($key = null);

    /**
     * @param $content
     * @return mixed
     */
    public function setContent($content);

    /**
     * @return mixed
     */
    public function getContent();
}
