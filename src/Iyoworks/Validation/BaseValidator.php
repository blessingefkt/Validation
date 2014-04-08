<?php namespace Iyoworks\Validation;
use Illuminate\Support\Contracts\ArrayableInterface;
use Illuminate\Support\Contracts\JsonableInterface;

/**
 * Class BaseValidator
 * @package Iyoworks\Validation
 */
abstract class BaseValidator
{
    /**
     * @var string
     */
    const    MODE_INSERT = 'preValidateOnInsert';
    /**
     * @var string
     */
    const    MODE_UPDATE = 'preValidateOnUpdate';
    /**
     * @var string
     */
    const    MODE_DELETE = 'preValidateOnDelete';
    /**
     * @var \Illuminate\Validation\Factory
     */
    public static $factory;
    /**
     * @var \Iyoworks\Validation\Validator
     */
    protected $runner;
    /**
     * @var Object
     */
    protected $object;
    /**
     * @var bool
     */
    protected $isValid;
    /**
     * @var callable
     */
    protected $errorCallback;
    /**
     * @var \Illuminate\Support\MessageBag
     */
    protected $errors;
    /**
     * @var array
     */
    protected $data = [];
    /**
     * @var array
     */
    protected $rules = [];
    /**
     * @var array
     */
    protected $updateRules = [];
    /**
     * @var array
     */
    protected $insertRules = [];
    /**
     * @var array
     */
    protected $deleteRules = [];
    /**
     * @var array
     */
    protected $messages = [];
    /**
     * @var boolean
     */
    protected $mode = false;
    /**
     * if enabled all rules will be used, even if corresponding data attribute is absent
     * @var boolean
     */
    protected $strict = false;
    /**
     * @var array
     */
    protected $parsedRules;

    /**
     * Set mode to insert
     * @param $data
     * @return bool
     */
    public function validForInsert($data)
    {
        return $this->isValid($data, $this->insertRules, static::MODE_INSERT);
    }

    /**
     * Set mode to update
     * @param array $data
     * @return bool
     */
    public function validForUpdate($data)
    {
        return $this->isValid($data, $this->updateRules,  static::MODE_UPDATE);
    }

    /**
     * Set mode to delete
     * @param array $data
     * @return bool
     */
    public function validForDelete($data)
    {
        return $this->isValid($data, $this->deleteRules, static::MODE_DELETE);
    }

    /**
     * Called before validation
     * @return void
     */
    protected function preValidate()
    {
    }

    /**
     * Called when mode is insert and after runner has been created
     * @return void
     */
    protected function preValidateOnInsert()
    {
    }

    /**
     * Called when mode is update and after runner has been created
     * @return void
     */
    protected function preValidateOnUpdate()
    {
    }

    /**
     * Called when mode is delete and after runner has been created
     * @return void
     */
    protected function preValidateOnDelete()
    {
    }

    protected function prePrune()
    {
    }

    /**
     * Run the validator
     * @param mixed $data
     * @param array $rules
     * @param string $mode
     * @return bool
     */
    public function isValid($data, array $rules = [], $mode = null)
    {
        $this->setData($data);

        //check if I only validate necessary attributes
        list($_rules, $_data) = $this->prune();

        $_rules = $this->parseRuleReplacements($_rules);
        $this->runner = static::$factory->make($_data, $_rules, $this->messages);

        // add additional rules to validator
        $this->runner->addRules($this->parseRuleReplacements($rules));

        // make parsed rules available
        $this->parsedRules = $this->runner->getRules();

        $this->preValidate();
        //if a mode has been set, call the corresponding function
        if ($mode) {
            $this->mode = $mode;
            $this->{$mode}();
        }

        //determine if any errors occurred
        if ($this->isValid = $this->runner->passes())
        {
            $this->handleErrors(null);
        }
        else
        {
            $this->handleErrors($this->runner->messages());
        }
        return $this->isValid;
    }

    /**
     * @return array
     */
    protected function prune()
    {
        $this->prePrune();
        $data = array_dot($this->data);
        if ($this->strict)
            $rules = $this->rules;
        else
            $rules = array_intersect_key($this->rules, $data);
        return [ $rules, $data ];
    }

    /**
     * @param \Illuminate\Support\MessageBag $bag
     */
    protected function handleErrors($bag)
    {
        if ($bag) {
            if (!$this->isValid && $this->errorCallback)
                    call_user_func($this->errorCallback, $bag, $this);
            if (!$this->errors)
                $this->errors = $bag;
            else
                $this->errors->merge($bag->getMessages());
        }
    }

    /**
     * @param $_rules
     * @return mixed
     */
    protected function parseRuleReplacements($_rules)
    {
        foreach ($_rules as $key => $rule) {
            $matches = [];
            if (preg_match_all("/\[(\w+)\]/", $rule,  $matches, PREG_SET_ORDER))
            {
                foreach ($matches as $match) {
                    $rule = str_replace($match[0], $this->get($match[1]), $rule);
                }
            }
            $_rules[$key] = $rule;
        }
        return $_rules;
    }

    /**
     * @param $name
     * @param bool $append
     * @return \Illuminate\Support\MessageBag
     */
    public function mapErrors($name, $append = false)
    {
        $toMerge = [];
        foreach ($this->errors()->getMessages() as $key => $errors) {
            $toMerge["{$name}.{$key}"] = array_map(function($msg) use ($name, $key, $append)
            {
                $replace = ($append) ?  $key .' '.$name : $name .' '.$key;
                return str_replace($key, $replace, $msg);
            }, $errors);
        }

        $this->errors = $this->newMsgBag($toMerge);
        return $this->errors;
    }

    /**
     * Get a value from data
     * @param  string $key
     * @param  mixed $default
     * @return mixed
     */
    public function get($key, $default = null)
    {
        return array_get($this->data, $key, $default);
    }

    /**
     * Get the errors
     * @return mixed
     */
    public function errorMsg()
    {
        return implode(' ', $this->errors()->all());
    }

    /**
     * Get the errors
     * @return  \Illuminate\Support\MessageBag|mixed
     */
    public function errors()
    {
        if (!isset($this->errors))
            $this->errors = $this->newMsgBag();
        return $this->errors;
    }

    /**
     * @return \Illuminate\Support\MessageBag
     */
    protected function newMsgBag($msgs = [])
    {
        return new \Illuminate\Support\MessageBag($msgs);
    }

    /**
     * Get a the data container
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * Clear the data container
     * @return $this
     */
    public function resetData()
    {
        $this->data = [];
        return $this;
    }

    /**
     * Set strict mode
     * @return $this
     */
    public function strict()
    {
        $this->strict = true;
        return $this;
    }

    /**
     * UnSet strict mode
     * @return $this
     */
    public function relaxed()
    {
        $this->strict = false;
        return $this;
    }

    /**
     * Set a value
     * @param  string $key
     * @param  mixed $value
     * @return $this
     */
    public function set($key, $value)
    {
        array_set($this->data, $key, $value);
        return $this;
    }

    /**
     * @param $key
     * @return bool
     */
    public function has($key)
    {
        return (bool) array_get($this->data, $key, false);
    }

    /**
     * Get the validator instance
     * @return \Illuminate\Validation\Validator
     */
    public function getRunner()
    {
        return $this->runner;
    }

    /**
     * Get the messages
     * @return array
     */
    public function getMessages()
    {
        return $this->messages;
    }

    /**
     * Get the rules
     * @return array
     */
    public function getRules()
    {
        return $this->rules;
    }

    /**
     * Get the parsed rules
     * @return array
     */
    public function getParsedRules()
    {
        return $this->parsedRules;
    }

    /**
     * Get the mode
     * @return string
     */
    public function getMode()
    {
        return $this->mode;
    }

    /**
     * Merge data into the existing data set
     * @param  mixed $data
     * @param  mixed $value
     * @return $this
     */
    protected function addData($data, $value = null)
    {
        if ($value)
            $this->data[$data] = $value;
        else
            $this->data = array_merge_recursive($this->data, (array)$data);
        return $this;
    }

    /**
     * @param array $rules
     * @return $this
     */
    public function addRules(array $rules)
    {
        $this->rules = array_merge($this->rules, $rules);
        return $this;
    }

    /**
     * @param $data
     * @return $this
     */
    public function setData($data)
    {
        $this->object = $data;
        $this->data = $this->castToArray($data);
        return $this;
    }

    /**
     * @param $data
     * @return array|mixed
     */
    protected function castToArray($data)
    {
        if ($data instanceof ArrayableInterface)
            return $data->toArray();
        if ($data instanceof JsonableInterface)
            return json_decode($data->toJson(), 1);
        return (array) $data;
    }

    /**
     * @param $callable
     * @return $this
     */
    public function errorCallback($callable)
    {
        $this->errorCallback = $callable;
        return $this;
    }

}