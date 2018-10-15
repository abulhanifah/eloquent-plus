<?php
namespace Nahl\Exceptions;

use Exception;
use Nahl\Validator;

class ValidationException extends Exception
{
	public $details;
    public function __construct($details=[])
    {
    	parent::__construct();
    	$this->code = 400;
        $this->message = "The request cannot be performed because of malformed or missing parameters.";

        foreach ($details as $key => $value) {
        	$keyname = "";
        	for ($i=0; $i<strlen($key);$i++) {
        		$keyname .= strtolower($key[$i])." ";
        	}
        	$keyname = trim($keyname);
        	foreach ($value as $k => $v) {
        		$details[$key][$k] = str_replace($keyname, $key, $v);
        	}
        }
        $this->details[] = $details;
    }
    
    public function getErrorCode()
    {
    	return $this->code;
    }

    public static function validate($params, $validation)
    {
        $validator = new Validator($params, $validation);
        if($validator->fails()) {
            throw new self($validator->errors()->getMessages());
        }
    }
}
