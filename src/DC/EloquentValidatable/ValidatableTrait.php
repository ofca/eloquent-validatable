<?php

namespace DC\EloquentValidatable;

use Illuminate\Support\Facades\Validator;
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

    /**
     * Return instance of validator class or array of validation rules.
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
     * @return array
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
        $validator = $this->getValidator();

        $data = array_merge($this->attributes, array_get($validator, 'data', []), array_get($additional, 'data', []));
        $messages = array_merge(array_get($validator, 'messages', []), array_get($additional, 'messages', []));
        $rules = array_merge(array_get($validator, 'rules', []), array_get($additional, 'rules', []));
        $attributes = array_merge(array_get($validator, 'attributes', []), array_get($additional, 'attributes', []));
        
        $validator = Validator::make($data, $rules, $messages, $attributes);

        if ($validator->fails()) {
            $this->validationErrors = $validator->errors();

            if ( ! $silent) {
                throw new Exception('Validation errors occured.', 0, null, $this->validationErrors);
            }
            
            return false;
        }

        return true;
    }
    
    protected function createValidator($validator)
    {
        if (is_array($validator)) {
            return Validator::make(
                array_get($validator, 'data', []),
                array_get($validator, 'rules', []),
                array_get($validator, 'messages', [])
            );
        }

        return $validator;
    }

    /**
     * It's the same method as "save" but without firing events.
     * 
     * @return bool
     */
    public function forceSave()
    {
        $query = $this->newQueryWithoutScopes();

        // If the model already exists in the database we can just update our record
        // that is already in this database using the current IDs in this "where"
        // clause to only update this model. Otherwise, we'll just insert them.
        if ($this->exists)
        {
            $saved = $this->performUpdate($query);
        }

        // If the model is brand new, we'll insert it into our database and set the
        // ID attribute on the model to the value of the newly inserted row's ID
        // which is typically an auto-increment value managed by the database.
        else
        {
            $saved = $this->performInsert($query);
        }

        if ($saved) $this->finishSave($options);

        return $saved;
    }

    public function hasChanged($key)
    {
        return $this->getOriginal($key) !== $this->getAttribute($key);
    }
}