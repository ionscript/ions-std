<?php

namespace Ions\Std;

use Countable;
use Iterator;

/**
 * Class PriorityList
 * @package Ions\Std
 */
class PriorityList implements Iterator, Countable
{
    const EXTR_DATA = 0x00000001;
    const EXTR_PRIORITY = 0x00000002;
    const EXTR_BOTH = 0x00000003;

    /**
     * @var array
     */
    protected $items = [];

    /**
     * @var int
     */
    protected $serial = 0;

    /**
     * @var int
     */
    protected $isLIFO = 1;

    /**
     * @var int
     */
    protected $count = 0;

    /**
     * @var bool
     */
    protected $sorted = false;

    /**
     * @param $name
     * @param $value
     * @param int $priority
     */
    public function insert($name, $value, $priority = 0)
    {
        if (!isset($this->items[$name])) {
            $this->count++;
        }

        $this->sorted = false;

        $this->items[$name] = [
            'data' => $value,
            'priority' => (int)$priority,
            'serial' => $this->serial++,
        ];
    }

    /**
     * @param $name
     * @param $priority
     * @return $this
     * @throws \InvalidArgumentException
     */
    public function setPriority($name, $priority)
    {
        if (!isset($this->items[$name])) {
            throw new \InvalidArgumentException("item $name not found");
        }

        $this->items[$name]['priority'] = (int)$priority;
        $this->sorted = false;

        return $this;
    }

    /**
     * @param $name
     */
    public function remove($name)
    {
        if (isset($this->items[$name])) {
            $this->count--;
        }

        unset($this->items[$name]);
    }

    /**
     * @return void
     */
    public function clear()
    {
        $this->items = [];
        $this->serial = 0;
        $this->count = 0;
        $this->sorted = false;
    }

    /**
     * @param $name
     * @return null
     */
    public function get($name)
    {
        if (!isset($this->items[$name])) {
            return null;
        }

        return $this->items[$name]['data'];
    }

    /**
     * @return void
     */
    protected function sort()
    {
        if (!$this->sorted) {
            uasort($this->items, [$this, 'compare']);
            $this->sorted = true;
        }
    }

    /**
     * @param array $item1
     * @param array $item2
     * @return int
     */
    protected function compare(array $item1, array $item2)
    {
        return ($item1['priority'] === $item2['priority']) ? ($item1['serial'] > $item2['serial'] ? -1 : 1) * $this->isLIFO : ($item1['priority'] > $item2['priority'] ? -1 : 1);
    }

    /**
     * @param null $flag
     * @return bool
     */
    public function isLIFO($flag = null)
    {
        if ($flag !== null) {
            $isLifo = $flag === true ? 1 : -1;

            if ($isLifo !== $this->isLIFO) {
                $this->isLIFO = $isLifo;
                $this->sorted = false;
            }
        }

        return 1 === $this->isLIFO;
    }

    /**
     * @return void
     */
    public function rewind()
    {
        $this->sort();
        reset($this->items);
    }

    /**
     * @return bool
     */
    public function current()
    {
        $this->sorted || $this->sort();
        $node = current($this->items);

        return $node ? $node['data'] : false;
    }

    /**
     * @return int|null|string
     */
    public function key()
    {
        $this->sorted || $this->sort();
        return key($this->items);
    }

    /**
     * @return bool
     */
    public function next()
    {
        $node = next($this->items);

        return $node ? $node['data'] : false;
    }

    /**
     * @return bool
     */
    public function valid()
    {
        return current($this->items) !== false;
    }

    /**
     * @return PriorityList
     */
    public function getIterator()
    {
        return clone $this;
    }

    /**
     * @return int
     */
    public function count()
    {
        return $this->count;
    }

    /**
     * @param int $flag
     * @return array
     */
    public function toArray($flag = self::EXTR_DATA)
    {
        $this->sort();

        if ($flag == self::EXTR_BOTH) {
            return $this->items;
        }

        return array_map(
            function ($item) use ($flag) {
                return $flag === PriorityList::EXTR_PRIORITY ? $item['priority'] : $item['data'];
            },
            $this->items
        );
    }
}
