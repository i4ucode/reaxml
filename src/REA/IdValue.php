<?php
namespace REA;

class IdValue
{
	protected $id;
	protected $value;

	public function __construct($id, $value)
	{
		$this->setId($id);
		$this->setValue($value);
	}
	
	public function setId($id)
	{
		$this->id = $id;
	}

	public function getId()
	{
		return $this->id;
	}

	public function setValue($value)
	{
		$this->value = $value;
	}

	public function getValue()
	{
		return $this->value;
	}

	public function __toString()
	{
		return $this->getValue();
	}
}
