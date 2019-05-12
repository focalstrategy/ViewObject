<?php

namespace FocalStrategy\ViewObjects;

use JsonSerializable;
use FocalStrategy\ViewObjects\Fillable;
use ArrayAccess;

abstract class ViewObject implements Fillable, JsonSerializable, ArrayAccess
{
    protected $required = [];
    protected $data = [];

    /**
     * @param array Data to pass in, should be overriden in a subclass
     */
    public function __construct(array $required = [])
    {
        $this->required = $required;
    }

    /**
     * @param string Property Name
     *
     * @return string|null
     */
    public function __get($name)
    {
        if (isset($this->data[$name])) {
            return $this->data[$name];
        }

        return;
    }

    public function __set($key, $value)
    {
        $this->data[$key] = $value;
    }

    public function __isset($name)
    {
        return isset($this->data[$name]);
    }

    /**
     * From JsonSerializable interface - allows json_encode to work.
     *
     * @return string
     */
    public function jsonSerialize()
    {
        $this->checkRequired();

        return $this->data;
    }

    public function raw()
    {
        $this->checkRequired();

        return $this->data;
    }

    public function offsetSet($offset, $value)
    {
        if (is_null($offset)) {
            $this->data[] = $value;
        } else {
            $this->data[$offset] = $value;
        }
    }

    public function offsetExists($offset)
    {
        return isset($this->data[$offset]);
    }

    public function offsetUnset($offset)
    {
        unset($this->data[$offset]);
    }

    public function offsetGet($offset)
    {
        return isset($this->data[$offset]) ? $this->data[$offset] : null;
    }

    public function __toString()
    {
        return json_encode($this->data);
    }

    public static function getFactory()
    {
        if (property_exists(get_called_class(), 'factory')) {
            $c = get_called_class();
            return $c::$factory;
        }
        return null;
    }

    private function checkRequired()
    {
        foreach ($this->required as $required) {
            if (!isset($this->data[$required]) || $this->data[$required] === null) {
                throw new \Exception('Missing required field: '.$required);
            }
        }
    }
}
