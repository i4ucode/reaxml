<?php
namespace REA;

class Rent 
{
    /** @var  Range|float */
	protected $value;

    /** @var  string */
	protected $period;

    /** @var  bool */
	protected $plusOutgoings;

    /** @var  bool */
	protected $plusSAV;

    /** @var  float */
	protected $tax;

    /** @var  bool */
	protected $display;

	public function __construct($value = null, $period = null)
	{
			$this->setValue($value);
			$this->setPeriod($period);
	}

	public function getValue()
	{
		return $this->value;
	}

	public function setValue($value)
	{
		$this->value = $value;
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
