<?php

namespace App\Libraries;

use \JsonSerializable;

class SingleImageSeeker implements JsonSerializable
{
    protected $model;
    protected $folder = null;
    protected $string = 'default';

    function __construct($model)
    {
        $this->model = $model;
    }

    public function type($folder){
        $this->folder = $folder;
        
        return $this;
    }

    public function or($string){
        $this->string = $string;
        
        return $this;
    }

    public function orDefault(){
        $this->string = 'default';
        
        return $this;
    }

    public function __toString(){
    	return $this->model->getImage($this->folder, $this->string);
	}

    public function jsonSerialize() {
        return self::__toString();
    }
}
