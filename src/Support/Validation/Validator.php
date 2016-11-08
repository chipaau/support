<?php

namespace Support\Validation;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Fluent;
use Illuminate\Validation\Validator as IlluminateValidation;
use Support\Validation\AbstractValidator;

/**
 * main validator class
 * @author Ahmed Shifau <Support@gmail.com>
 */
class Validator extends IlluminateValidation
{

    /**
     * @param $attribute
     * @param array $ruleSet
     * @param array $messages
     * @throws \InvalidArgumentException
     */    
    protected $ignoreId = null;

    public function iterate(Request $request, $attribute, AbstractValidator $validator, $messages = [])
    {
        $this->files = property_exists($this, 'files') ? $this->files : [];
        $payload = array_merge($this->data, $this->files);
        $input = array_get($payload, $attribute);
        if ((!is_null($input) && !is_array($input)) || empty($input)) {
            throw new \InvalidArgumentException('Attribute for iterate() must be an array.');
        }
        foreach ($input as $key => $value) {
            $this->addIteratedValidationRules($attribute.'.'.$key.'.', $validator->rules($request), $messages);
        }
    }


    /**
     * @param string $attribute
     * @param array $ruleSet
     * @param array $messages
     *
     * @return void
     */
    protected function addIteratedValidationRules($attribute, $ruleSet = [], $messages = [])
    {
        foreach ($ruleSet as $field => $rules) {
            $rules = str_replace('{parent}', rtrim($attribute, '.'), $rules);
            $rules = str_replace('{index}.', $attribute, $rules);
            $rules = is_string($rules) ? explode('|', $rules) : $rules;

            //If it contains nested iterated items, recursively add validation rules for them too
            if (isset($rules['iterate'])) {
                $this->iterateNestedRuleSet($attribute.$field, $rules);
                unset($rules['iterate']);
            }

            $this->mergeRules($attribute.$field, $rules);
        }
        $this->addIteratedValidationMessages($attribute, $messages);
    }

    /**
     * Add any custom messages for this ruleSet to the validator
     *
     * @param $attribute
     * @param array $messages
     *
     * @return void
     */
    protected function addIteratedValidationMessages($attribute, $messages = [])
    {
        foreach ($messages as $field => $message) {
            $field_name = $attribute.$field;
            $messages[$field_name] = $message;
        }
        $this->setCustomMessages($messages);
    }

    /**
     * @param $attribute
     * @param $rules
     *
     * @return void
     */
    protected function iterateNestedRuleSet($attribute, $rules)
    {
        $nestedRuleSet = isset($rules['iterate']['rules']) ? $rules['iterate']['rules'] : [];
        $nestedMessages = isset($rules['iterate']['messages']) ? $rules['iterate']['messages'] : [];
        $this->iterate($attribute, $nestedRuleSet, $nestedMessages);
    }

    public function validateRowExists($attributes, $value, $parameters)
    {
        $this->requireParameterCount(1, $parameters, 'exists');

        $table = $parameters[0];

        // The second parameter position holds the name of the column that should be
        // verified as existing. If this parameter is not specified we will guess
        // that the columns being "verified" shares the given attribute's name.
        $column = isset($parameters[1]) ? $parameters[1] : $attribute;

        $expected = (is_array($value)) ? count($value) : 1;

        return $this->getRowExistCount($table, $column, $value, $parameters) >= $expected;
    }

    /**
     * Get the number of records that exist in storage.
     *
     * @param  string  $table
     * @param  string  $column
     * @param  mixed   $value
     * @param  array   $parameters
     * @return int
     */
    protected function getRowExistCount($table, $column, $value, $parameters)
    {
        $verifier = $this->getPresenceVerifier();
        $extra = $this->getExtraExistConditions($parameters);
        if (is_array($value)) {
            return $verifier->getMultiRowCount($table, $column, $value, $extra, $this->getData());
        } else {
            return $verifier->getRowCount($table, $column, $value, null, null, $extra, $this->getData());
        }
    }

    public function validateAlphaSpaces($attributes, $value, $parameters)
    {
        return preg_match('/^([a-z0-9_\-\s\,\.])+$/i', $value);
    }

    public function validateAlphaSpacesPercent($attributes, $value, $parameters)
    {
        return preg_match('/^([a-z0-9_\-\%\s\,\.])+$/i', $value);
    }

    public function validateAlphaSlashes($attributes, $value, $parameters)
    {
        return preg_match('/^([a-z0-9\-\s\/])+$/i', $value);
    }

    public function validateIs($attributes, $value, $parameters)
    {
        return $value == $parameters[0];
    }

    public function validateDateBetween($attributes, $value, $parameters)
    {
        $date = \DateTime::createFromFormat('Y-m-d', $value);
        $date1 = \DateTime::createFromFormat('Y-m-d', $parameters[0]);
        $date2 = \DateTime::createFromFormat('Y-m-d', $parameters[1]);

        if ($date && $date1 && $date2) {
            return ($date1 < $date && $date < $date2);
        }
    }

    /**
     * Author: aimme
     * an example rule and how it works
     * 'payable.data.id'=> 
     *     [
     *         'multiple_exists_if:payable.data.type,prescription-consumable,consumables,id,prescription-medicine,medicines,id'
     *     ]
     *
     *      this function validates that 
     *      - if the value in payable.data.type is prescription-consumable then the value of payable.data.id should exist in consumables table id column or
     *      - if the value in payable.data.type is prescription-medicine then the value of payable.data.id should exist in medicines table id column
     *      - if payable.data.type is other than prescription-consumable or prescription-medicine then the validation is ignored
     *      - only tablename,columnname,columnvalue is accepted for each check
     *     
     */
    public function validateMultipleExistsIf($attribute, $value, $parameters)
    {
        return $this->getExistIfParameters($attribute, $value, $parameters, 2);
    }

    /**
     * Author: aimme
     * an example rules and how it works
     *  'payable.data.id' => [
                'exists_if:payable.data.type,prescription-consumable,consumables,id',
                'exists_if:payable.data.type,prescription-medicine,prescription_medicines,id'
            ],
     *
     *      this function validates that 
     *      - same as basic exists validation except that it is checked only if the value of specific key has a specific value
     *      - if payable.data.type is requested-service than it checks whether id exists in consumables id column
     *      - one at a time, parameters are same as basic exists:
     *     
     */

    public function validateExistsIf($attribute, $value, $parameters)
    {
        return $this->getExistIfParameters($attribute, $value, $parameters);
    }

    protected function getExistIfParameters($attribute, $value, $parameters, $length = NULL)
    {
        $checkAttribute = array_pull($parameters,0);
        $checkValue = $this->getValue($checkAttribute);
        if($found = array_search($checkValue, $parameters)) {
            $parameters = array_slice($parameters,$found, $length);
            return $this->validateExists($attribute, $value, $parameters);
        } else {
            return true;
        }
    }


    public function validateArrayMin($attribute, $value, $parameters)
    {
        if (! is_array($value)) {
            return;
        }

        return count($value) >= $parameters[0];
    }

    public function validateFileExtension($attribute, $value, $parameters)
    {
        $value = \Input::file($attribute);
        $this->data[$attribute] = $value;
        return ($value->getClientOriginalExtension() == $parameters[0]);
    }

    public function validateArrayExists($attribute, $array, $parameters)
    {
        if (! is_array($array)) {
            return;
        }
        foreach ($array as $key => $v) {
            if (is_array($v)) {
                if (!array_key_exists($parameters[0], $v)) {
                    return;
                }
                if ($v[$parameters[0]] == $parameters[1]) {
                    return true;
                }
            } else {
                if ($key == $parameters[0] && $v == $parameters[1]) {
                    return true;
                }
            }
        }

        return;
    }

    public function validateRowUnique($attribute, $value, $parameters)
    {
        $this->requireParameterCount(1, $parameters, 'unique');

        $table = $parameters[0];

        // The second parameter position holds the name of the column that needs to
        // be verified as unique. If this parameter isn't specified we will just
        // assume that this column to be verified shares the attribute's name.
        $column = isset($parameters[1]) ? $parameters[1] : $attribute;

        list($idColumn, $id) = array(null, null);

        if (isset($parameters[2])) {
            list($idColumn, $id) = $this->getUniqueIds($parameters);

            if (strtolower($id) == 'null') {
                $id = null;
            }
        }

        $id = $this->ignoreId ?: $id;
        
        // The presence verifier is responsible for counting rows within this store
        // mechanism which might be a relational database or any other permanent
        // data store like Redis, etc. We will use it to determine uniqueness.
        $verifier = $this->getPresenceVerifier();

        $extra = $this->getUniqueExtra($parameters);

        return $verifier->getCount(

            $table, $column, $value, $id, $idColumn, $extra

        ) == 0;
    }

    protected function replaceExistsIf($message, $attribute, $rule, $parameters)
    {
        $message = "$attribute id does not exist.";

        return $message;
    }

    protected function replaceIs($message, $attribute, $rule, $parameters)
    {
        $message = "This field value should be equal to " . $parameters[0] .".";
        return $message;
    }

    protected function replaceArrayExists($message, $attribute, $rule, $parameters)
    {
        $message = "$attribute should have $parameters[0] key with value $parameters[1].";

        return $message;
    }

    public function replaceDateBetween($message, $attribute, $rule, $parameters)
    {
        return "This date should be between " . $parameters[0] . " and " . $parameters[1] . ".";
    }
    public function replaceHiisApiExists($message, $attribute, $rule, $parameters)
    {
        $input = $this->getData();
        return "There is no record of '" . $input[$attribute] . "' in $attribute.";
    }


    protected function replaceArrayMin($message, $attribute, $rule, $parameters)
    {
        return str_replace(':array_min', $parameters[0], $message);
    }

    public function setIgnoreId($id)
    {
        $this->ignoreId = (int) $id;
    }
}