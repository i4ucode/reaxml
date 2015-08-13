<?php
namespace REA;

abstract class AbstractCategory
{
	protected $id;
	protected $name;

	abstract public function getType();
	
	public function setId($id)
	{
		$this->id = $id;
	}

	public function getId()
	{
		return $this->id;
	}

	public function setName($name)
	{
		$this->name = $name;
	}

	public function getName()
	{
		return $this->name;
	}

	protected function getPathArray()
	{
		$path = array();
		if ($this->name) {
			$path[] = trim($this->name);
		}
		return $path;
	}

	public function getPath($delim = '/')
	{
		return implode($delim, $this->getPathArray());
	}

	public function __toString()
	{
		return $this->getPath(' > ');
	}
}
