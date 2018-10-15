<?php
namespace Nahl\Exceptions;

use Exception;

class NotFoundException extends Exception
{
	public $details;
    public function __construct($details=[])
    {
    	parent::__construct();
    	$this->code = 400;
        $this->message = 'Resource not found.';
        foreach ($details as $key => $value) {
        	$details[$key] = ['The '.$key.' with value \''.$value.'\' cannot be found'];
        }
        $this->details[] = $details;
    }
    public function getErrorCode() {
        return $this->code;
    }
}