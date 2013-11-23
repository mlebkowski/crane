<?php


namespace Crane\Configuration;


use ArrayIterator;
use Crane\Validator\CraneJsonValidator;
use Crane\Validator\ValidatorException;
use Symfony\Component\Process\Process;

class GlobalConfiguration extends \ArrayObject implements \JsonSerializable
{
	/**
	 * @var \Crane\Validator\CraneJsonValidator
	 */
	private $linter;

	public function __construct(CraneJsonValidator $linter)
	{
		$this->linter = $linter;
	}

	/**
	 * @param Project|array $jsonData
	 *
	 * @return Project|void
	 * @throws \Crane\Validator\ValidatorException
	 */
	public function append($jsonData)
	{
		if (false === ($jsonData instanceof Project))
		{
			$this->linter->check($jsonData);
			if (false === $this->linter->isValid())
			{
				throw new ValidatorException($this->linter);
			}
			$project = new Project($jsonData);
		}
		else
		{
			$project = $jsonData;
		}
		$this->offsetSet($project->getName(), $project);
		return $project;
	}

	/**
	 * @param mixed $index
	 *
	 * @return Project|void
	 */
	public function offsetGet($index)
	{
		return parent::offsetGet($index);
	}

	/**
	 * @param string $index
	 * @param Project $newval
	 *
	 * @throws \InvalidArgumentException
	 */
	public function offsetSet($index, $newval)
	{
		if (false === ($newval instanceof Project))
		{
			throw new \InvalidArgumentException('value must be instance of \Crane\Configuration\Project');
		}
		if ($index != $newval->getName())
		{
			throw new \InvalidArgumentException('\Crane\Configuration\Project::getName() must match index name');
		}
		parent::offsetSet($index, $newval);
	}

	/**
	 * @return Project[]
	 */
	public function getArrayCopy()
	{
		return parent::getArrayCopy();
	}

	/**
	 * @return ArrayIterator|Project[]
	 */
	public function getIterator()
	{
		return parent::getIterator();
	}

	public function exchangeArray($input)
	{
		foreach ($this as $key => $val)
		{
			unset($this[$key]);
		}
		foreach ($input as $val)
		{
			$this->append($val);
		}
	}

	/**
	 * (PHP 5 &gt;= 5.4.0)<br/>
	 * Specify data which should be serialized to JSON
	 *
	 * @link http://php.net/manual/en/jsonserializable.jsonserialize.php
	 * @return mixed data which can be serialized by <b>json_encode</b>,
	 *       which is a value of any type other than a resource.
	 */
	public function jsonSerialize()
	{
		return $this->getArrayCopy();
	}
}