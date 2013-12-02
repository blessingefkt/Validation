<?php namespace Iyoworks\Validation;

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
     * @var \Iyoworks\Repository\Validation\Validator
     */
    protected $runner;
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
     * Set mode to insert
     * @return \Iyoworks\Repository\BaseValidator
     */
    public function validForInsert($data)
    {
        $this->mode = static::MODE_INSERT;
        return $this->isValid($data);
    }

    /**
     * Set mode to update
     * @param array $data
     * @return bool
     */
    public function validForUpdate($data)
    {
        $this->mode = static::MODE_UPDATE;
        return $this->isValid($data);
    }

    /**
     * Set mode to delete
     * @param array $data
     * @return bool
     */
    public function validForDelete($data)
    {
        $this->mode = static::MODE_DELETE;
        return $this->isValid($data);
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

    /**
     * Run the validator
     * @param  mixed $data
     * @return bool
     */
    public function isValid($data)
    {
        $this->data = $data;

        $this->preValidate();

        //if a mode has been set, call the corresponding function
        if ($this->mode) $this->{$this->mode}();

        //check if I only validate necessary attributes
        $_rules = !$this->strict ? array_intersect_key($this->rules, $this->data) : $this->rules;

        // construct the runner
        $this->runner = static::$factory->make($this->data, $_rules, $this->messages);

        //determine if any errors occured
        if (!$this->runner->passes()) {
            $this->handleErrors($this->runner->messages());
            return false;
        }
        $this->handleErrors(null);
        return true;
    }

    /**
     * @param \Illuminate\Support\MessageBag $bag
     */
    protected function handleErrors($bag)
    {
        if ($bag) {
            if (!$this->errors)
                $this->errors = $bag;
            else
                $this->errors->merge($bag->getMessages());
        }
    }

    /**
     * Set the ID or value for a unique rule
     *    $this->setUnique('name', 'id') - get the name rule and append $this->data['id'] to it
     *    $this->setUnique('name', 4, true) - get the name rule and append 4 to it
     *    NOTE: rule should have table column already appended to it.
     *
     * @param string $key data attribute name
     * @param mixed $value
     * @param boolean $useActual use the actual $value
     * @param string $rkey rule to apply changes to
     * @return BaseValidator
     */
    public function setUnique($key, $value = null, $useActual = false, $rkey = 'unique')
    {
        $rule = $this->rules[$key];
        $start = strpos($rule, $rkey);
        $end = strpos($rule, '|', $start) ? : strlen($rule);
        $uniqueRuleOrig = $uniqueRule = substr($rule, strpos($rule, $rkey), $end - $start);
        if (!$useActual and $value)
            $value = $this->get($key);
        elseif (!$useActual and !$value)
            $value = $this->get('id');
        $uniqueRule = rtrim($uniqueRule, ',') . ',' . $value;
        $rule = str_replace($uniqueRuleOrig, $uniqueRule, $rule);
        $this->rules[$key] = $rule;
        return $this;
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
    protected function newMsgBag()
    {
        return new \Illuminate\Support\MessageBag;
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
     * @return \Iyoworks\Repository\BaseValidator
     */
    public function resetData()
    {
        $this->data = [];
        return $this;
    }

    /**
     * Set strict mode
     * @return \Iyoworks\Repository\BaseValidator
     */
    public function strict()
    {
        $this->strict = true;
        return $this;
    }

    /**
     * UnSet strict mode
     * @return \Iyoworks\Repository\BaseValidator
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
     * @return \Iyoworks\Repository\BaseValidator
     */
    public function set($key, $value)
    {
        array_set($this->data, $key, $value);
        return $this;
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
     * @return \Iyoworks\Repository\BaseValidator
     */
    protected function addData($data, $value = null)
    {
        if ($value)
            $this->data[$data] = $value;
        else
            $this->data = array_merge_recursive($this->data, (array)$data);
        return $this;
    }
}
