<?php

namespace Ions\Std\Spl;

use Serializable;

/**
 * Class SplStack
 * @package Ions\Std\Spl
 */
class SplStack extends \SplStack implements Serializable
{
    /**
     * @return array
     */
    public function toArray()
    {
        $array = [];
        foreach ($this as $item) {
            $array[] = $item;
        }
        return $array;
    }

    /**
     * @return string
     */
    public function serialize()
    {
        return serialize($this->toArray());
    }

    /**
     * @param string $data
     */
    public function unserialize($data)
    {
        foreach (unserialize($data) as $item) {
            $this->unshift($item);
        }
    }
}
