<?php

namespace FocalStrategy\ViewObjects;

use Exception;
use FocalStrategy\Filter\FilterManager;
use FocalStrategy\Filter\HasFilterManager;
use FocalStrategy\ViewObjects\Fillable;
use Illuminate\Support\Collection;

class Transformer
{
    private $from;
    private $nested = [];
    private $with = [];

    public function __construct(FilterManager $filter)
    {
        $this->filter = $filter;
    }

    /**
     * Set the from class, if not set Array is assumed.
     *
     * @param  [type]
     */
    public function from($class)
    {
        $this->from = $class;

        return $this;
    }

    /**
     * Set the to class, if not set Array is assumed.
     *
     * @param  [type]
     */
    public function to($class)
    {
        if (!class_implements($class, Fillable::class)) {
            throw new Exception('View Object does not implement Fillable');
        }

        $this->to = $class;

        return $this;
    }

    public function with($data, $cl = null)
    {
        $name = 'setArray';
        if ($cl) {
            $name = 'set'.last(explode('\\', $cl));
        }
        $this->with[$name] = $data;

        return $this;
    }

    public function withNested($path, callable $nested)
    {
        $this->nested[$path] = $nested;

        return $this;
    }


    public function transform($data)
    {
        $single = false;
        if (!($data instanceof Collection) && !is_array($data)) {
            $data = collect([$data]);
            $single = true;
        }


        $result = collect([]);
        if (count($data) > 0) {
            if ($this->from == null
                || get_class($data->first()) === $this->from
                || get_class($data->get(0)) === $this->from) {
                $target = $this->to;

                if ($target === null) {
                    $result = $data->map(function ($itm) use ($target) {
                        $cl = is_array($itm) ? $itm : $itm->toArray();

                        return $cl;
                    });
                } else {
                    $result = $data->map(function ($itm) use ($target) {
                        $cl = new $target();
                        $name = 'setArray';
                        if ($this->from != null) {
                            $name = 'set'.last(explode('\\', $this->from));
                        }

                        $cl->$name($itm);

                        $cl = $this->handleNested($itm, $cl);
                        $cl = $this->handleWiths($cl);

                        if ($this->hasTrait(HasFilterManager::class)) {
                            $cl->setFilterManager($this->filter);
                        }
                        return $cl;
                    });
                }
            } else {
                throw new Exception('From '.get_class($data->first()).' does not match expected '.($this->from));
            }
        }

        return $single ? $result->first() : $result;
    }

    public function handleNested($obj, $cl)
    {
        if (count($this->nested) > 0) {
            foreach ($this->nested as $path => $callable) {
                if ($obj->{$path} != null) {
                    $result = $callable(new self($this->filter), $obj->{$path});
                    $cl->$path = $result;
                }
            }
        }
        return $cl;
    }

    public function handleWiths($cl)
    {
        foreach ($this->with as $method => $data) {
            $cl->$method($data);
        }
        return $cl;
    }

    private function hasTrait($source, $trait)
    {
        return in_array($trait, class_uses($source));
    }
}
