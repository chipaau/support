<?php

namespace Support\Validation;

use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Foundation\Application;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * @author Ahmed Shifau <Support@gmail.com>
 */
abstract class AbstractValidator
{

    /**
     * Validator object.
     *
     * @var object
     */
    protected $validator;

    /**
     * Array of errors.
     *
     * @var array
     */
    protected $errors;

    /**
     * route parmeters
     * @var array
     */
    protected $params;

    /**
     * current request
     * @var Illuminate\Http\Request
     */
    protected $request;

    /**
     * Array of validating input.
     *
     * @var array
     */
    protected $input;

    /**
     * Array of rules.
     *
     * @var array
     */
    public $rules = [];

    /**
     * Array of messages.
     *
     * @var array
     */
    public $messages = [

        'alpha_slashes'         =>      ':attribute should only contain alpha numeric values, forward slash and hyphen only.',
        'alpha_spaces'          =>      ':attribute should only contain alpha numeric values, spaces, underscore and hyphen only.',
        'alpha_spaces_percent'  =>      ':attribute should only contain alpha numeric values, spaces, underscore, hyphen and percentage only.',
        'exists'                =>      'The selected :attribute does not exist.',
        'row_exists'            =>      'The selected :attribute does not exist.',
        'array_min'             =>      'You should select at least :array_min :attribute',
        'row_unique'            =>      ':attribute is already being taken.',
        'required'              =>      'This field is required.',

    ];

    /**
     * mappings
     * @var array
     */
    public $mappings = [];

    /**
     * Create a new validation service instance.
     *
     * @param  array  $input
     * @return void
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
        $this->request = $this->app['request'];
        $this->params = $this->request->route()->parameters();

        if (!is_array($this->rules($this->request, $this->params))) {
            throw new \Exception("Rules should return an array.", 1);
        }

        $this->validator = $app['validator'];
    }

    public function validator()
    {
        return $this->validator;
    }

    abstract public function rules(Request $request, array $params = array());
    abstract public function mappings();

    /**
     * Validates the input.
     *
     * @throws Aasandha\Core\Helper\Validation\ValidationException
     * @return array validated db value data
     */
    public function validate($removeRequired = false)
    {
        if ($removeRequired) {
            $this->removeRequired();
        }
        $content = $this->request->all();
        $messages = (method_exists($this, 'messages') && is_array($this->messages())) ? array_merge($this->messages, $this->messages()) : $this->messages;
        $this->validator = $this->app['validator']->make($content, $this->getRules(), $messages);
        /** get iterable keys and iterate */
        $mappings = $this->mappings();
        foreach ($mappings as $key => $value) {
            if (is_array($value) && class_exists($value['class']) && array_get($content, $key)) {
                $class = new $value['class']($this->app);
                $messages = (method_exists($class, 'messages') && is_array($class->messages())) ? array_merge($this->messages, $class->messages()) : $this->messages;
                $this->validator->iterate($this->request, $key, $class, $messages);
            }
        }

        if (method_exists($this, 'getSometimes')) {
            call_user_func_array([$this, 'getSometimes'], [$this->validator]);
        }

        if ($this->validator->fails()) {
            throw new ValidationException($this->validator->errors());
            
        }

        return $this->getDbValues($this->validator->getData(), $this->mappings());
    }

    /**
     * get the rules from child class
     * @return array
     */
    public function getRules()
    {
        $rules = empty($this->rules) ? $this->rules($this->request, $this->params) : $this->rules;
        return $this->explodeRules($rules);
    }

    /**
     * Explode the rules into an array of rules.
     *
     * @param  string|array  $rules
     * @return array
     */
    protected function explodeRules($rules)
    {
        foreach ($rules as $key => &$rule) {
            $rule = (is_string($rule)) ? explode('|', $rule) : $rule;
        }
        return $rules;
    }

    public function getValues($key = null)
    {
        $dbValues = $this->getDbValues($this->validator->getData(), $this->mappings());
        if (is_null($key)) {
            foreach ($dbValues as $key => $value) {
                if (is_scalar($value)) {
                    $resp[$key] = $value;
                }
            }
        } else {
            $resp = array_get($dbValues, $key);
        }
        return $resp;
    }

    /**
     * remove required rule from rules function
     *
     * @return array
     */
    public function removeRequired()
    {
        foreach ($this->getRules() as $key => $rules) {
            $newRules[$key] = preg_grep("/^required/", $rules, PREG_GREP_INVERT);
        }
        $this->rules = $newRules;
        return $this;
    }

    public function remove($key, $rule)
    {
        $rules = $this->getRules();
        if (array_key_exists($key, $rules)) {
            $match = preg_grep('/' . $rule . '/', $rules[$key]);
            $rules[$key] = array_diff($rules[$key], $match);
        }
        $this->rules = $rules;
        return $this;
    }

    public function addRules($key, $keyrules = [])
    {
        $rules = $this->getRules();
        //if there is already rules, merge rules
        if (array_key_exists($key, $rules)) {
            foreach ($keyrules as $rule) {
                $arule = $rule;
                //if rule is ":" separated get actual rule
                $pos = strpos($rule, ':');
                if ($pos) {
                    $rule = substr($rule, 0, $pos);
                }
                $match = preg_grep('/' . $rule . '/', $rules[$key]);
                //if match replace the rule value
                if ($match) {
                    $rkey = array_keys($match)[0];
                    $value = array_values($match)[0];
                    $rules[$key][$rkey] = $arule;
                } else {
                    //merge new rule
                    $rules[$key] = array_merge($rules[$key], [ $pos ? $arule : $rule]);
                }
            }
        } else {
            $rules[$key] = $rules;
        }
        $this->rules = $rules;
        return $this;
    }

    private function changeDotToArray($rawResponse)
    {
        $array = [];
        foreach ($rawResponse as $key => $value) {
            array_set($array, $key, $value);
        }
        return $array;
    }

    /**
     * conver given array keys to mapper keys
     * @param  array $inputs
     * @return array
     */
    public function getDbValues(array $input = [], $mappings = [])
    {
       $response = [];
       foreach ($mappings as $inputKey => $dbValue) {
            if (is_array($input) && array_get($input, $inputKey) !== null) {
                $value = array_get($input, $inputKey);
                if ($value instanceof UploadedFile) {
                    array_set($response, $dbValue, $value);
                } elseif (is_array($value)) {
                    if (class_exists($mappings[$inputKey]['class'])) {
                        $class = new $mappings[$inputKey]['class']($this->app);
                        foreach ($value as $v) {
                            $response[$mappings[$inputKey]['key']][] = $this->getDbValues($v, $class->mappings());
                        }
                    } else {
                        array_set($response, $dbValue, $value);
                    }
                } else {
                    array_set($response, $dbValue, (is_bool($value) ? $value : trim($value)));
                }
            }
        }
        
        return $response;
    }

    public function errors()
    {
        return $this->errors;
    }

    public function fields()
    {
        return array_keys($this->mappings());
    }

    public function setIgnoreId($id)
    {
        $this->validator->setIgnoreId($id);
    }
}