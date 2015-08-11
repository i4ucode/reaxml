<?php
namespace REA;

class LandDetails
{
	protected $area;
	protected $frontage;
	protected $depth;
	protected $crossOver;

	public function setArea($area)
	{
		$this->area = $area;
	}

	public function getArea()
	{
		return $this->area;
	}

	public function setFrontage($frontage)
	{
		$this->frontage = $frontage;
	}

	public function getFrontage()
	{
		return $this->frontage;
	}

	public function setDepth($depth)
	{
		$this->depth = $depth;
	}

	public function getDepth()
	{
		return $this->depth;
	}

	public function setCrossOver($crossOver)
	{
		$this->crossOver = $crossOver;
	}

	public function getCrossOver()
	{
		return $this->crossOver;
	}

	public function __toString()
	{
		return sprintf('Area: %s, Frontage: %s, Depth: %s, Cross Over: %s',
			(string)$this->getArea(),
			(string)$this->getFrontage(),
			(string)$this->getDepth(),
			(string)$this->getCrossOver());
	}
}
