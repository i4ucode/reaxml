<?php
namespace REA;

class Address
{
	protected $site;
	protected $unitNumber;
	protected $lotNumber;
	protected $streetNumber;
	protected $street;
	protected $suburb;
	protected $displaySuburb;
	protected $state;
	protected $postcode;
	protected $country;
	protected $display;

	public function getSite()
	{
		return $this->site;
	}

	public function setSite($site)
	{
		$this->site = $site;
	}

	public function setUnitNumber($unitNumber)
	{
		$this->unitNumber = $unitNumber;
	}

	public function getUnitNumber()
	{
		return $this->unitNumber;
	}


	public function setLotNumber($lotNumber)
	{
		$this->lotNumber = $lotNumber;
	}

	public function getLotNumber()
	{
		return $this->lotNumber;
	}


	public function setStreetNumber($streetNumber)
	{
		$this->streetNumber = $streetNumber;
	}

	public function getStreetNumber()
	{
		return $this->streetNumber;
	}

	public function setStreet($street)
	{
		$this->street = $street;
	}

	public function getStreet()
	{
		return $this->street;
	}

	public function setSuburb($suburb)
	{
		$this->suburb = $suburb;
	}

	public function getSuburb()
	{
		return $this->suburb;
	}

	public function setDisplaySuburb($displaySuburb)
	{
		$this->displaySuburb = !(empty($displaySuburb) || $displaySuburb === 'no');
	}

	public function isDisplaySuburb()
	{
		return $this->displaySuburb === true;
	}

	public function setState($state)
	{
		$this->state = $state;
	}

	public function getState()
	{
		return $this->state;
	}

	public function setPostcode($postcode)
	{
		$this->postcode = $postcode;
	}

	public function getPostcode()
	{
		return $this->postcode;
	}

	public function setCountry($country)
	{
		$this->country = $country;
	}

	public function getCountry()
	{
		return $this->country;
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
