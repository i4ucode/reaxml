<?php
namespace REA;

class Person
{
	protected $id;
	protected $name;
	protected $businessPhone;
	protected $afterHoursPhone;
	protected $mobilePhone;
	protected $email;
	protected $twitterUrl;
	protected $facebookUrl;
	protected $linkedinUrl;

	public function setId($id)
	{
		$this->id = $id;
	}

	public function getId()
	{
		return $this->id;
	}


	public function getName()
	{
		return $this->name;
	}

	public function setName($name)
	{
		$this->name = $name;
	}

	public function getBusinessPhone()
	{
		return $this->businessPhone;
	}

	public function setBusinessPhone($businessPhone)
	{
		$this->businessPhone = $businessPhone;
	}

	public function getAfterHoursPhone()
	{
		return $this->afterHoursPhone;
	}

	public function setAfterHoursPhone($afterHoursPhone)
	{
		$this->afterHoursPhone = $afterHoursPhone;
	}


	public function getMobilePhone()
	{
		return $this->mobilePhone;
	}

	public function setMobilePhone($mobilePhone)
	{
		$this->mobilePhone = $mobilePhone;
	}

	public function getEmail()
	{
		return $this->email;
	}

	public function setEmail($email)
	{
		$this->email = $email;
	}

	public function getTwitterUrl()
	{
		return $this->twitterUrl;
	}

	public function setTwitterUrl($twitterUrl)
	{
		$this->twitterUrl = $twitterUrl;
	}

	public function getFacebookUrl()
	{
		return $this->facebookUrl;
	}

	public function setFacebookUrl($facebookUrl)
	{
		$this->facebookUrl = $facebookUrl;
	}

	public function getLinkedinUrl()
	{
		return $this->linkedinUrl;
	}

	public function setLinkedinUrl($linkedinUrl)
	{
		$this->linkedinUrl = $linkedinUrl;
	}

	public function __toString()
	{
		return $this->getName();
	}
}
