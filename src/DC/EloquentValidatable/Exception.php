<?php

namespace DC\EloquentValidable;

class Exception extends \Exception {

    protected $errors = [];

    public function setErrors($errors)
    {
        $this->errors = $errors;
    }

    public function getErrors()
    {
        return $this->errors;
    }

}