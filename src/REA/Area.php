<?php
namespace REA;

class Area
{
	protected $area;
	protected $unit;

	public function __construct($area = null, $unit = null)
	{
		$this->setArea($area);
		$this->setUnit($unit);
	}

	public function getArea()
	{
		return $this->area;
	}

	public function setArea($area)
	{
		$this->area = $area;
	}

	public function getUnit()
	{
		return $this->unit;
	}

	public function setUnit($unit)
	{
		$this->unit = $unit;
	}

	public function __toString()
	{
		return sprintf('%s %s', $this->area, $this->unit);
	}

}
