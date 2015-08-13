<?php
namespace REA;

class ResidentialCategory extends AbstractCategory
{
	const TYPE = 'residential';

	public function __construct($id = null, $name = null)
	{
		$this->setId($id);
		$this->setName($name);
	}
	
	public function getType()
	{
		return self::TYPE;
	}
}
