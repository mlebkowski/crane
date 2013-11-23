<?php

namespace Crane\Validator;

use JsonSchema\Validator;

class CraneJsonValidator extends Validator
{

	private $schema;

	public function __construct($schemaUri)
	{
		$this->schema = $this->retrieveUri("file://" . $schemaUri);
	}

	public function check($value, $schema = null, $path = null, $i = null)
	{
		parent::check($value, $schema ?: $this->schema, $path, $i);
	}

}