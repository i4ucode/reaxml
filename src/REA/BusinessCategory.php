<?php
namespace REA;

class BusinessCategory extends AbstractCategory
{
	const TYPE = 'business';

	protected $subCategory;

	public function __construct($id = null, $name = null, $subCategory = null)
	{
		$this->setId($id);
		$this->setName($name);
		$this->setSubCategory($subCategory);
	}

	public function getType()
	{
		return self::TYPE;
	}
	
	public function setSubCategory($subCategory)
	{
		$this->subCategory = $subCategory;
	}

	public function getSubCategory()
	{
		return $this->subCategory;
	}

	public function getPathArray()
	{
		$path = array();
		if ($this->name) {
			$path[] = trim($this->name);

			if ($this->subCategory) {
				$path[] = trim($this->subCategory);
			}
		}

		return $path;
	}
}
