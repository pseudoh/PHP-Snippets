<?php

/**
** A form validation library.
** 
** Validation rules and filters
** ----------------------------
** required : Makes sure that the field is not empty and contains data
** max_length[number] : Ensures that the field value's length doesn't exceed a particular length. e.g max_length[10]
** min_length[number] : Ensures that the field value's length is greater than or a equals a particular length. e.g min_length[10]
**
** @author PSEUDOH.com
**/

class FormValidator {

	/** Stores validation entries applied to the current post request **/
	private $validation_entries = array();
	/** Stores all validation errors ocurred during the process of validation **/
	private $validation_errors = array();
	/** Stores all form data for validation and filtered access **/
	private $values = array();

	/**
	** Apply a validation rule to a form element
	** @param field - The name of the field to apply the validation to
	** @param label - A friendly name to be applied to the rule. Will be included in error string
	** @param rules - An array of rules and filters to apply. See above for a list of them
	**/
	public function add_rule($field, $label, $rules) {
		//Add the rule data to the array of entries
		array_push($this->validation_entries, array('field' => $field, 'label' => $label, 'rules' => $rules));
	}


	/**
	** To be called after rules have been added. It validates all fields in post request and is only executed
	** if a POST request exists otheriwse validation is ignored. 
	** @return Boolean - True if validation passes False otherwise
	**/
	public function validate() {
		//Check if we have a POST request
		if($_SERVER['REQUEST_METHOD'] == "POST")  {

			$this->values = $_POST;
			
			//Process all rules applied to the form
			foreach ($this->validation_entries as $entry) {
				$field = $entry['field'];
				$label = $entry['label'];
				$rules = $entry['rules'];
				
				//Process the rule
				$this->process_rules($field, $label, $rules);
			}

			return count($this->validation_errors) == 0; //Return True if there are no validation errors
		} else { //No POST request, ignore validation
			return FALSE;
		}
	}

	/**
	** Get the errors string of a specific field
	** @param field - The name of the field
	** @return String - The error's string
	**/ 
	public function get_error($field) {
		if (isset($this->validation_errors[$field])) {
			return $this->validation_errors[$field];
		} else {
			return '';
		}
	}

	/**
	** Retrieves all errors ocurred during the validation process
	** @return An array of validation errors
	**/
	public function get_errors() {
		return $this->validation_errors;
	}

	/**
	** Retrieves a value of a particular field. The value may not be the original after validation as 
	** filters may have been applied
	** @param field - The name of the field
	** @return String - The field's value
	**/
	public function get_value($field) {
		return $this->values[$field];
	}

	/**
	** Processes a rule applied to the form
	** @param field - The name of the field
	** @param label - The label of the field
	** @param rules - The array of validations and filters applied of the field
	**/
	private function process_rules($field, $label, $rules) {

		foreach ($rules as $rule) {

			$rule_name = '';
			$rule_value = '';

			//Parse the rule to check whether it is passing a value
			//As used in max_length[number] and min_length[number]
			$pos = strpos($rule, '[');
			if ($pos === FALSE) {
				$rule_name = $rule;
			} else {
				$rule_name = substr($rule, 0, $pos);
				$rule_value = substr($rule, $pos + 1, strlen($rule) - $pos - 2);
			} 
			
			$request_value = $this->values[$field]; //Get the field's value

			//Apply a rule based on the name
			switch ($rule_name) {
				case 'required': //Indicate that the field is required and checks whether it is not empty
					if (empty($request_value)) {
						$this->add_error($field, $rule_name, $label.' Required');
						break 2;
					}
				break;
				case 'max_length': //Indicates that the field has a maximum length
					if (strlen($request_value) > intval($rule_value)) {
						$this->add_error($field, $rule_name, $label.' exceed maximum length');
						break 2;
					}
				break;
				case 'min_length': //Indicates that the field has a minimum length
					if (strlen($request_value) < intval($rule_value)) {
						$this->add_error($field, $rule_name, $label.' is less than minimum length');
						break 2;
					}
				break;
			}

			$this->values[$field] = $request_value; //Update the (filtered) value
		}

	}

	/**
	** Adds an error the to the array of errors
	** @param field - The name of the field
	** @param rule - The rule that was applied and where validation failed
	** @param message - The user friendly error message
	**/
	private function add_error($field, $rule, $message) {
		//Add error details to array
		$this->validation_errors[$field] = array('rule' => $rule, 'message' => $message);
	}

}

?>