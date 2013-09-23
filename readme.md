Validation
==========
Provides a base class to implement validation as a service. Also adds a little sugar to Laravel's validator. ***Uses PHP 5.4 Traits***.

Dynamics
----------
Sometimes you may not want to add a global rule to the validator or you may want your validator to handle something differently. The validator has been extended to allow dynamic rules.

```
$validator = Validator::make($rules, $data);
if(isset($data['fieldtype']))
{
    $validator->addDynamic('fieldtype', 'exists', function($value){
        return app()->make('fieldtypes')->exists($value);
    });
}
```

BaseValidator
----------
Extend the BaseValidator to create your own validation classes.
```
use Iyoworks\Validation\BaseValidator;

class FieldValidator extends BaseValidator{

    //only selected if the attribute name is present in the data array
	protected $rules = [
	'name' => 'required',
	'handle' => 'required|unique:fields,handle',
	];
	
	//applied when the mode is insert
	protected $insertRules = [
	'handle' => 'required|unique:fields,handle',
	'fieldtype' => 'required',
	];

	//applied when the mode is update
	protected $updateRules = [
	'id' => 'required|exists:fields,id'
	];

	//applied when the mode is delete
	protected $deleteRules = [];
		
	//executed before validation
	protected function preValidate() {
	if($fieldtype = $this->get('fieldtype'))
		$this->runner->addDynamic('fieldtype', 'exists', function($value){
			return say()->fieldtypes->exists($value);
		});
	}

	//executed before validation on insert mode	
	protected function preValidateOnInsert() {}

	//executed before validation on update mode
	protected function preValidateOnUpdate() {
		if($this->get('handle'))
			$this->setUnique('handle', $this->get('id'), true);
	}

	//executed before validation on delete mode
	protected function preValidateOnDelete() {}
}
```
Next instantiate your validator
```
$validator = new FieldValidator;

//$entity is anything with a getAttributes()/toArray() method.
//if the mode is update, first the validator looks for a getDirty() method
//if none of these things exist, it's passed as is.

//set the mode and pass your data object
if($validator->insert()->valid($entity)){
    //your logic
} else {
	$this->errors = $this->validator->errors();
    //more logic
}
```
