<?php
namespace REA;

class Rent
{
	protected $rent;
	protected $period;
	protected $plusOutgoings;
	protected $plusSAV;
	protected $tax;
	protected $display;

	public function getRent()
	{
		return $this->rent;
	}

	public function setRent($rent)
	{
		$this->rent = $rent;
	}

	public function getPeriod()
	{
		return $this->period;
	}

	public function setPeriod($period)
	{
		$this->period = $period;
	}

	public function getPlusOutgoings()
	{
		return $this->plusOutgoings === true;
	}

	public function setPlusOutgoings($plusOutgoings)
	{
		$this->plusOutgoings = !(empty($plusOutgoings) || $plusOutgoings === 'no');
	}

	public function getPlusSAV()
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
}
