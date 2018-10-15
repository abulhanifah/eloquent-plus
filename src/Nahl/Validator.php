<?php

namespace Nahl;
use Illuminate\Support\Facades\Validator as BaseValidator;
use Nahl\Convert;

class Validator extends BaseValidator
{
	private $validator;

	public function __construct($params,$map) {     
		$this->validator = $this->createValidation($params,$map);
	}

	private function createValidation($params, $map) {

		Validator::extendImplicit('required_header', function ($attribute, $value, $parameters, $validator) {
			return isset($value);
		}, 'The :attribute header is required.');

		Validator::extend('uuid', function ($attribute, $value, $parameters, $validator) {
			return Convert::isValidUUID($value);
		}, 'The :attribute must be valid uuid.');

		$custom_error_message = [
			'in' => 'The :attribute must be one of the following types: :values.',
		];

		return Validator::make($params, $map, $custom_error_message);
	}

	public function fails() {
		return $this->validator->fails();
	}

	public function errors(){
		return $this->validator->errors();
	}
}
