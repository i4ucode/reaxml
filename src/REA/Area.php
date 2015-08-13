<?php
namespace REA;

class Area
{
	protected $value;
	protected $unit;

	public function __construct($value = null, $unit = null)
	{
		$this->setValue($value);
		$this->setUnit($unit);
	}

	public function getValue()
	{
		return $this->value;
	}

	public function setValue($value)
	{
		$this->value = $value;
	}

	public function getUnit()
	{
		return $this->unit;
	}

	public function setUnit($unit)
	{
		$this->unit = $unit;
	}

	public function isRange()
	{
		return $this->value instanceOf Range;
	}

	public function __toString()
	{
		return sprintf('%s %s', $this->value, $this->unit);
	}

}
