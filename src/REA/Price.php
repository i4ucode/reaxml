<?php
namespace REA;

class Price
{
	protected $price;
	protected $display;

	public function getPrice()
	{
		return $this->price;
	}

	/**
	 * Could be price (decimal) or price range (Range) 
	 */
	public function setPrice($price)
	{
		$this->price = $price;
	}

	public function getDisplay()
	{
		return $this->display === true;
	}

	public function setDisplay($bool)
	{
		$this->display = !empty($bool);
	}

}
