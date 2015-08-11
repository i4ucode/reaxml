<?php
namespace REA;

class Range
{
	protected $min;
	protected $max;

	public function __construct($min = null, $max = null)
	{
		$this->setMin($min);
		$this->setMax($max);
	}

	public function getMin()
	{
		return $this->min;
	}

	public function setMin($min)
	{
		$this->min = $min;
	}

	public function getMax()
	{
		return $this->max;
	}

	public function setMax($max)
	{
		$this->max = $max;
	}

	public function __toString()
	{
		return sprintf('%s - %s', $this->getMin(), $this->getMax());
	}

}
