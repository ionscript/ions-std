<?php

namespace Ions\Std\Spl;

use Serializable;

/**
 * Class SplQueue
 * @package Ions\Std\Spl
 */
class SplQueue extends \SplQueue implements Serializable
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
            $this->push($item);
        }
    }
}
