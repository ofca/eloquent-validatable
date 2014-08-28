<?php

namespace DC\EloquentValidatable;

use Illuminate\Support\Facades\Validator;

/**
 * Add validation support for Eloquent models.
 */
trait ValidatableTrait {

    /**
     * Validation errors.
     * @var \Illuminate\Support\MessageBag
     */
    protected $validationErrors = [];

    /**
     * Return instance of validator class or array of validation rules.
     * 
     * @return \Validator|Array
     */
    public function getValidator()
    {
        return [
            'rules'     => [],
            'messages'  => [],
            'data'      => []
        ];
    }

    /**
     * Return validation errors.
     * 
     * @return array
     */
    public function getValidationErrors()
    {
        return $this->validationErrors;
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
        $validator = $this->createValidator($this->getValidator());

        $data = array_merge($this->attributes, $validator->getData() + $validator->getFiles());
        $messages = $validator->getCustomMessages();
        $rules = $validator->getRules();

        if (is_array($additional)) {
            $data = array_merge($data, array_get($additional, 'data', []));
            $messages = array_merge($messages, array_get($additional, 'messages', []));
            $rules = array_merge($rules, array_get($additional, 'rules', []));
        }
        
        $validator->setData($data);
        $validator->setCustomMessages($messages);
        $validator->setRules($rules);

        if ($validator->fails()) {
            $this->errors = $validator->errors();

            if ( ! $silent) {
                throw new Exception('Validation errors occured.', 0, null, $this->errors);
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
        return $this->getOriginal('name') !== $this->getAttribute('name');
    }
}