<?php
namespace REA;

class CommercialCategory extends AbstractCategory
{
	const TYPE = 'commercial';

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
