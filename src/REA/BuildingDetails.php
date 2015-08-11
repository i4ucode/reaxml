<?php
namespace REA;

class BuildingDetails
{
	protected $area;
	protected $energyRating;

	public function setArea($area)
	{
		$this->area = $area;
	}

	public function getArea()
	{
		return $this->area;
	}

	public function setEnergyRating($energyRating)
	{
		$this->energyRating = $energyRating;
	}

	public function getEnergyRating()
	{
		return $this->energyRating;
	}

	public function __toString()
	{
		return (string)$this->getArea();
	}
}
