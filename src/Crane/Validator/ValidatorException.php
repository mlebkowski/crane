<?php


namespace Crane\Validator;


use Exception;

class ValidatorException extends \RuntimeException
{
	private $errors = [];

	public function __construct(CraneJsonValidator $validator, Exception $previous = null)
	{
		$this->errors = $validator->getErrors();
		parent::__construct("JSON is not valid\n" . print_r($validator->getErrors(),1) , 1, $previous);
	}

	public function __toString()
	{
		return parent::__toString() . implode("\n", $this->errors);
	}

}