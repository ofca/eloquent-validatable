<?php

namespace DC\EloquentValidatable;

class Exception extends \Exception {

    protected $errors = [];

    public function __construct($message = null, $code = 0, Exception $previous = null, $errors = array())
    {
        $this->errors = $errors;

        parent::__construct($message, $code, $previous);
    }

    public function setErrors($errors)
    {
        $this->errors = $errors;
    }

    public function getErrors()
    {
        return $this->errors;
    }

}