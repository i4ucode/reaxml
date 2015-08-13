<?php
namespace REA;

class Price
{
	protected $value;
	protected $plusSAV;
	protected $tax;
	protected $display;

	public function getValue()
	{
		return $this->value;
	}

	/**
	 * Could be value (decimal) or value range (Range) 
	 */
	public function setValue($value)
	{
		$this->value = $value;
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


	public function isDisplay()
	{
		return $this->display === true;
	}

	public function setDisplay($bool)
	{
		$this->display = !(empty($bool) || $bool === 'no');
	}

	public function isRange()
	{
		return $this->value instanceOf Range;
	}
}
