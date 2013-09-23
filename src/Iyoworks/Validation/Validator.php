<?php namespace Iyoworks\Validation;
use Closure, DB;
use Illuminate\Validation\Validator as LValidator;
use Illuminate\Support\MessageBag;

class Validator extends LValidator
{

	protected $dynamics = array();

	/**
	 * Add a dynamic (function) rule to the validator
	 * @param string  $attribute
	 * @param string  $rule     
	 * @param closure $dynamic  
	 * @param string  $message  
	 */
	public function addDynamic($attribute, $rule, closure $dynamic, $message  = null)
	{
		$this->rules[$attribute][$rule] = "dynamic:$rule";
		$this->dynamics[$attribute][$rule] = $dynamic;
		if($message) $this->setCustomMessages([$attribute.'.'.$rule => $message]);
	}

	/**
	 * Validate a given attribute against a rule.
	 *
	 * @param  string  $attribute
	 * @param  string  $rule
	 * @return void
	 */
	protected function validate($attribute, $rule)
	{
		if(starts_with($rule, 'dynamic:')){
			list($rule, $parameters) = $this->parseRule($rule);
			$rule = substr($rule, 7);
			$value = $this->getValue($attribute);
			$dynamic = $this->dynamics[$attribute][$rule];
			if(!$dynamic($attribute, $value, $parameters, $this))
				return $this->addFailure($attribute, $rule, $parameters);
		}
		
		return parent::validate($attribute, $rule);
	}

	/**
	 * set the rules on the validator
	 * @param array $rules 
	 */
	public function setRules(array $rules)
	{
		$this->rules = $this->explodeRules($rules);
	}

	/**
	 * add rules to the validator
	 * @param array $rules 
	 */
	public function addRules(array $rules)
	{
		$rules = $this->explodeRules($rules);
		$this->rules = array_merge_recursive($this->rules, $rules);
	}

}
