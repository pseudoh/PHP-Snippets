<?php

/**
** A file upload library.  Can restrict uploads by extensions, MIMEs, maximum size etc...
**
** @author PSEUDOH.com
**/

class Uploader {
		
	private $allowed_types; //An array of file mimes and extensions allowed
	private $save_path; //The path where to save the file uploaded
	private $max_size; //The maximum file size allowed in kilobytes - set to 0 if no limit
	private $input_name; //The upload form's input name
	private $last_error; //Contains the last error occurred 
	private $overwrite; //A boolean that specified whether overwriting of uploaded files is allowed
	private $upload_details; //Contains details about the uploaded file

	public function __construct() {
		$this->allowed_types = array();
		$this->save_path = (defined('UPLOAD_PATH') ? UPLOAD_PATH : './'); 
		$this->max_size = (defined('UPLOAD_MAX_SIZE') ? UPLOAD_MAX_SIZE : 2048); //Try reading from config file other wise by default set to 2MB (2048 in kilobytes)
		$this->input_name = (defined('UPLOAD_INPUT_NAME') ? UPLOAD_INPUT_NAME : 'file'); //By default set to file
		$this->overwrite = (defined('UPLOAD_OVERWRITE') ? UPLOAD_OVERWRITE : FALSE); //Disable overwriting by default
		$this->upload_details = array(); //Initialise to empty array
		$this->last_error = array(0, ''); //An array containing the error code and the error message
	}

	/**
	** Upload the file after validating it first
	** @param $file_name - The name of the file after being uploaded. set to null or don't pass the parameter to keep as is.
	** @param $keep_extension - Sets whether to keep the original extension of the uploaded file. True by default
	** @return boolean - whether the file has been uploaded
	**/
	public function upload($file_name = NULL, $keep_extension = TRUE) {

		//reset last_error and upload_details
		$this->last_error = array(0, '');
		$upload_details = array();

		//Check if there is an upload request
		if (!isset($_FILES[$this->input_name])) {
			$this->last_error = array(105, 'No upload data received');
			return FALSE;
		}

		if (!$this->validate_type())
			return FALSE;

		if (!$this->validate_size())
			return FALSE;

		if (!$this->validate_upload())
			return FALSE;

		//If validation passed

		if ($file_name == NULL) { //if custom file name is not specified set to that of the original file's
			$file_name = $_FILES[$this->input_name]['name'];
		} else { //else use custom filename
			$file_name = $file_name;
		}

		if (!$this->overwrite && file_exists($this->save_path.'/'.$file_name)) {
			$this->last_error = array(103, 'File exists');
			return FALSE;
		}

		$extension = $this->extract_extension($_FILES[$this->input_name]["name"]);

		if ($keep_extension) {
			$file_name = $file_name.'.'.$extension;
		}
		
		if (!move_uploaded_file($_FILES[$this->input_name]['tmp_name'], $this->save_path.'/'.$file_name)) {
			$this->last_error = array(104, 'Unable to move uploaded file from temp directory. Check permissions.');
			return FALSE;
		}

		$this->upload_details['original_name'] = $_FILES[$this->input_name]["name"];
		$this->upload_details['uploaded_name'] = $file_name;
		$this->upload_details['extension'] = $extension;
		$this->upload_details['size'] = $_FILES[$this->input_name]["size"];
		$this->upload_details['type'] = $_FILES[$this->input_name]["type"];
		
		return TRUE;


	}

	/**
	** Sets the allowed types and extension of the upload and override those set in the configuration file
	** @param $allowed_types - A string array of the MIMEs and extension of the files to be allowed
	**/
	public function set_allowed_types($allowed_types) {
		$this->allowed_types = $allowed_types;
	}

	/**
	** Returns an array containing the details of a successfull upload
	** @return Array
	**/
	public function get_upload_details() {
		return $this->upload_details;
	}

	/**
	** Returns an array containing details of the last error thas has occurred;
	** @return Array - 0: Error Code, 1: Error Message
	** Error Codes: 
	** 101: File type not allowed
	** 102: File size exceeds the set maximum allowed size
	** 103: File exists on server
	** 104: Unable to move uploaded file from temp directory. Usually due to permission problems
	** 105: No upload data received
	**/
	public function get_last_error() {
		return $this->last_error;
	}


	/**
	** Validates and check whether the upload process was successfull
	** @return boolean - true if successfull otherwise false
	**/
	private function validate_upload() {
		$error = $_FILES[$this->input_name]['error'];
		if ($error > 0) { //if error found
			$this->last_error = array($error, 'Upload error');
			return FALSE;
		} else {
			return TRUE;
		}
	}

	/**
	** Validates the extension and type of the uploaded file. 
	** @return boolean - true if validation succeeded otherwise false
	**/
	private function validate_type() {
		$type = $_FILES[$this->input_name]["type"]; //Get the type of the upload file
		$file_name = $_FILES[$this->input_name]["name"];

		//Extracts the extension of the uploaded file
		$extension = $this->extract_extension($file_name);

		if (empty($this->allowed_types) || in_array($type, $this->allowed_types) || in_array($extension, $this->allowed_types)) { //Check whether there is a type restriction or if type or extension is in allowed types
			return TRUE; 
		} else {
			$this->last_error = array(101, 'File type not allowed');
			return FALSE;
		}
	}

	/**
	** Validates the size of the uploaded file. 
	** @return boolean - true if size matches otherwise false
	**/
	private function validate_size() {
		$size = $_FILES[$this->input_name]["size"]; //Get the size of the uploaded file
		$size = $size / 1024; //Conver to kilobytes
		//Check if the the file's size is less then or equal to the allowed size
		//If max_size is set to 0 (not limit) return true
		if ($this->max_size == 0 || $size <= $this->max_size) {
			return TRUE;
		} else {
			$this->last_error = array(102, 'File size exceeds maximum allowed size');
			return FALSE;
		}
	}

	/**
	** Returns the extension of a filename
	** @param file - The filename to extract extension from
	** @return String - file's extension without the dot
	**/
	private function extract_extension($file) {
		$extension = explode('.', $file);
		return end($extension); 
	}

}

?>