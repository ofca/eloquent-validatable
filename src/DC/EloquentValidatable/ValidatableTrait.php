<?php

namespace DC\EloquentValidatable;

use Illuminate\Support\Facades\Facade;
use Illuminate\Support\MessageBag;

/**
 * Add validation support for Eloquent models.
 */
trait ValidatableTrait {

    /**
     * Validation errors.
     * @var \Illuminate\Support\MessageBag
     */
    protected $validationErrors;

    protected $validationIsOn = true;

    /**
     * Return array of validation rules.
     * 
     * @return Array
     */
    public function getValidator()
    {
        return [
            'rules'     => [],
            'messages'  => [],
            'data'      => [],
            'attributes'=> []
        ];
    }

    /**
     * Return validation errors.
     * 
     * @return MessageBag
     */
    public function getValidationErrors()
    {
        return $this->validationErrors ?: new MessageBag;
    }

    /**
     * Validates models data.
     * 
     * @param array $additional Optional validation data, rules, messages.
     * @param boolean $silent If true, no exception will be thrown if validation fails.
     * @return boolean
     * @throws \DC\EloquentValidatable\Exception If not valid and $silent is false.
     */
    public function validate($additional = null, $silent = false)
    {
        $this->validationIsOn = true;
        $validator = $this->getValidator();

        $data = array_merge($this->attributes, array_get($validator, 'data', []), array_get($additional, 'data', []));
        $messages = array_merge(array_get($validator, 'messages', []), array_get($additional, 'messages', []));
        $rules = array_merge(array_get($validator, 'rules', []), array_get($additional, 'rules', []));
        $attributes = array_merge(array_get($validator, 'attributes', []), array_get($additional, 'attributes', []));

        $validator = Facade::getFacadeApplication()
            ->make('validator')
            ->make($data, $rules, $messages, $attributes);

        if ($validator->fails()) {
            $this->validationErrors = $validator->errors();

            if ( ! $silent) {
                throw new Exception('Validation errors occured.', 0, null, $this->validationErrors);
            }
            
            return false;
        }

        return true;
    }

    /**
     * It's the same method as "save" but without firing events.
     * 
     * @return bool
     */
    public function forceSave(array $options = array())
    {
        $this->validationIsOn = false;
        return $this->save();
    }

    public function isValidationOn()
    {
        return $this->validationIsOn;
    }
}