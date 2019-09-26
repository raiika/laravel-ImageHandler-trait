<?php

namespace App\Libraries;

use JsonSerializable;
use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Contracts\Support\Arrayable;

class SingleImageSeeker implements JsonSerializable, Jsonable, Arrayable
{
    protected $model;
    protected $folder = null;
    protected $string = 'default';

    function __construct($model)
    {
        $this->model = $model;
    }

    public function type($folder)
    {
        $this->folder = $folder;
        
        return $this;
    }

    public function or($string)
    {
        $this->string = $string;
        
        return $this;
    }

    public function orDefault()
    {
        $this->string = 'default';
        
        return $this;
    }

    public function exists()
    {
        return $this->or(null)->__toString() !== null;
    }

    public function get()
    {
        return $this->__toString();
    }

    public function __toString()
    {
        return $this->model->getImage($this->folder, $this->string);
    }

    public function jsonSerialize()
    {
        return self::__toString();
    }

    public function toJson($options = 0)
    {
        return self::__toString();
    }

    public function toArray()
    {
        return self::__toString();   
    }
}
