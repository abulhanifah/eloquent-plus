<?php
namespace Zahirlib\Eloquent\Exceptions;

use Exception;
use Illuminate\Support\Collection;

class PermissionException extends Exception
{
	public $details;
    public function __construct($details=[])
    {
    	parent::__construct();
    	$this->code = 403;
        $this->message = 'The user does not have enough permission to access the resource.';
        $this->details = $details;
    }
    public function getErrorCode()
    {
    	return $this->code;
    }

    public static function checkPermission($roles, $user_roles=[], $filters=[])
    {
        $col = new Collection(array_diff_key($roles,$user_roles));
        if($col->isNotEmpty()) {
            foreach ($filters as $key => $value) {
                $col = $col->contains($key,$value);    
            }
            if($col->isNotEmpty()) {
                throw new self([$col->all()]);
            }
        }
    }
}
