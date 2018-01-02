<?php
namespace Ions\Std;

use Countable;
use IteratorAggregate;
use Serializable;
use Ions\Std\Spl\SplPriorityQueue;

/**
 * Class PriorityQueue
 * @package Ions\Std
 */
class PriorityQueue implements Countable, IteratorAggregate, Serializable
{
    const EXTR_DATA     = 0x00000001;
    const EXTR_PRIORITY = 0x00000002;
    const EXTR_BOTH     = 0x00000003;

    /**
     * @var string
     */
    protected $queueClass = SplPriorityQueue::class;

    /**
     * @var array
     */
    protected $items      = [];

    /**
     * @var SplPriorityQueue
     */
    protected $queue;

    /**
     * @param $data
     * @param int $priority
     * @return $this
     */
    public function insert($data, $priority = 1)
    {
        $priority = (int) $priority;
        $this->items[] = [
            'data'     => $data,
            'priority' => $priority,
        ];
        $this->getQueue()->insert($data, $priority);
        return $this;
    }

    /**
     * @param $datum
     * @return bool
     */
    public function remove($datum)
    {
        $found = false;
        foreach ($this->items as $key => $item) {
            if ($item['data'] === $datum) {
                $found = true;
                break;
            }
        }
        if ($found) {
            unset($this->items[$key]);
            $this->queue = null;

            if (! $this->isEmpty()) {
                $queue = $this->getQueue();
                foreach ($this->items as $item) {
                    $queue->insert($item['data'], $item['priority']);
                }
            }
            return true;
        }
        return false;
    }

    /**
     * @return bool
     */
    public function isEmpty()
    {
        return (0 === $this->count());
    }

    /**
     * @return int
     */
    public function count()
    {
        return count($this->items);
    }

    /**
     * @return mixed
     */
    public function top()
    {
        return $this->getIterator()->top();
    }

    /**
     * @return mixed
     */
    public function extract()
    {
        return $this->getQueue()->extract();
    }

    /**
     * @return SplPriorityQueue|\SplPriorityQueue
     */
    public function getIterator()
    {
        $queue = $this->getQueue();
        return clone $queue;
    }

    /**
     * @return string
     */
    public function serialize()
    {
        return serialize($this->items);
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

    /**
     * @param int $flag
     * @return array
     */
    public function toArray($flag = self::EXTR_DATA)
    {
        switch ($flag) {
            case self::EXTR_BOTH:
                return $this->items;
            case self::EXTR_PRIORITY:
                return array_map(function ($item) {
                    return $item['priority'];
                }, $this->items);
            case self::EXTR_DATA:
            default:
                return array_map(function ($item) {
                    return $item['data'];
                }, $this->items);
        }
    }

    /**
     * @param $class
     * @return $this
     */
    public function setInternalQueueClass($class)
    {
        $this->queueClass = (string) $class;
        return $this;
    }


    /**
     * @param $datum
     * @return bool
     */
    public function contains($datum)
    {
        foreach ($this->items as $item) {
            if ($item['data'] === $datum) {
                return true;
            }
        }
        return false;
    }


    /**
     * @param $priority
     * @return bool
     */
    public function hasPriority($priority)
    {
        foreach ($this->items as $item) {
            if ($item['priority'] === $priority) {
                return true;
            }
        }
        return false;
    }


    /**
     * @return SplPriorityQueue|\SplPriorityQueue
     * @throws \DomainException
     */
    protected function getQueue()
    {
        if (null === $this->queue) {
            $this->queue = new $this->queueClass();
            if (! $this->queue instanceof \SplPriorityQueue) {
                throw new \DomainException(sprintf(
                    'PriorityQueue expects an internal queue of type SplPriorityQueue; received "%s"',
                    get_class($this->queue)
                ));
            }
        }
        return $this->queue;
    }

    /**
     * @return void
     */
    public function __clone()
    {
        if (null !== $this->queue) {
            $this->queue = clone $this->queue;
        }
    }
}
