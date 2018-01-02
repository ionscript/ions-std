<?php

namespace Ions\Std\Spl;

use Serializable;

/**
 * Class SplPriorityQueue
 * @package Ions\Std\Spl
 */
class SplPriorityQueue extends \SplPriorityQueue implements Serializable
{
    /**
     * @var int
     */
    protected $serial = PHP_INT_MAX;

    /**
     * @param mixed $datum
     * @param mixed $priority
     */
    public function insert($datum, $priority)
    {
        if (!is_array($priority)) {
            $priority = [$priority, $this->serial--];
        }
        parent::insert($datum, $priority);
    }

    /**
     * @return array
     */
    public function toArray()
    {
        $array = [];
        foreach (clone $this as $item) {
            $array[] = $item;
        }
        return $array;
    }

    /**
     * @return string
     */
    public function serialize()
    {
        $clone = clone $this;
        $clone->setExtractFlags(self::EXTR_BOTH);
        $data = [];
        foreach ($clone as $item) {
            $data[] = $item;
        }
        return serialize($data);
    }

    /**
     * @param string $data
     */
    public function unserialize($data)
    {
        foreach (unserialize($data) as $item) {
            $this->insert($item['data'], $item['priority']);
        }
    }
}
