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

        if ($additional) {
            $data = array_merge($data, array_get($additional, 'data', []));
            $messages = array_merge($messages, array_get($additional, 'messages', []));
            $rules = array_merge($rules, array_get($additional, 'rules', []));
        }
        
        $validator->setData($data);
        $validator->setCustomMessages($messages);
        $validator->setRules($this->buildUniqueExclusionRules($rules));

        if ($validator->fails()) {
            $this->errors = $validator->errors();

            if ( ! $silent) {
                $exception = new Exception;
                $exception->set($this->errors);

                throw new $exception;
            }
            
            return false;
        }

        return true;
    }
    
    protected function createValidator($validator)
    {
        if (is_array($validator)) {
            return Validator::make(
                array_get($validator, 'data'),
                array_get($validator, 'rules'),
                array_get($validator, 'messages')
            );
        }

        return $validator;
    }

    /**
     * When given an ID and a Laravel validation rules array, this function
     * appends the ID to the 'unique' rules given. The resulting array can
     * then be fed to a Ardent save so that unchanged values
     * don't flag a validation issue. Rules can be in either strings
     * with pipes or arrays, but the returned rules are in arrays.
     *
     * @param int   $id
     * @param array $rules
     * @return array Rules with exclusions applied
     * @author https://github.com/laravelbook/ardent/
     */
    protected function buildUniqueExclusionRules(array $rules = array()) 
    {
        foreach ($rules as $field => &$ruleset) {
            // If $ruleset is a pipe-separated string, switch it to array
            $ruleset = (is_string($ruleset))? explode('|', $ruleset) : $ruleset;

            foreach ($ruleset as &$rule) {
              if (strpos($rule, 'unique') === 0) {
                // Stop splitting at 4 so final param will hold optional where clause
                $params = explode(',', $rule, 4); 

                $uniqueRules = array();
                
                // Append table name if needed
                $table = explode(':', $params[0]);
                if (count($table) == 1)
                  $uniqueRules[1] = $this->table;
                else
                  $uniqueRules[1] = $table[1];
               
                // Append field name if needed
                if (count($params) == 1)
                  $uniqueRules[2] = $field;
                else
                  $uniqueRules[2] = $params[1];

                if (isset($this->primaryKey)) {
                  $uniqueRules[3] = $this->{$this->primaryKey};
                  
                  // If optional where rules are passed, append them otherwise use primary key
                  $uniqueRules[4] = isset($params[3]) ? $params[3] : $this->primaryKey;
                }
                else {
                  $uniqueRules[3] = $this->id;
                }
       
                $rule = 'unique:' . implode(',', $uniqueRules);  
              } // end if strpos unique
              
            } // end foreach ruleset
        }
        
        return $rules;
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
}