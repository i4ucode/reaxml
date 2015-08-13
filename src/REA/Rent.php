<?php
namespace REA;

class Rent 
{
	protected $value;
	protected $period;
	protected $plusOutgoings;
	protected $plusSAV;
	protected $tax;
	protected $display;

	public function __construct($value = null, $period = null)
	{
			$this->setValue($value);
			$this->setPeriod($period);
	}

	public function getValue()
	{
		return $this->rent;
	}

	public function setValue($value)
	{
		$this->rent = $value;
	}

	public function getPeriod()
	{
		return $this->period;
	}

	public function setPeriod($period)
	{
		$this->period = $period;
	}

	public function isPlusOutgoings()
	{
		return $this->plusOutgoings === true;
	}

	public function setPlusOutgoings($plusOutgoings)
	{
		$this->plusOutgoings = !(empty($plusOutgoings) || $plusOutgoings === 'no');
	}

	public function isPlusSAV()
	{
		return $this->plusSAV === true;
	}

	public function setPlusSAV($plusSAV)
	{
		$this->plusSAV = !(empty($plusSAV) || $plusSAV === 'no');
	}

	public function getTax()
	{
		return $this->tax;
	}

	public function setTax($tax)
	{
		$this->tax = $tax;
	}

	public function setDisplay($display)
	{
		$this->display = !(empty($display) || $display === 'no');
	}

	public function isDisplay()
	{
		return $this->display === true;
	}

	public function isRange()
	{
		return $this->value instanceOf Range;
	}
}
