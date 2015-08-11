<?php
namespace REA;

class File
{
	protected $id;
	protected $file;
	protected $url;
	protected $format;
	protected $modTime;
	protected $splFileInfo;

	public function setId($id)
	{
		$this->id = $id;
	}

	public function getId()
	{
		return $this->id;
	}

	public function setFile($file)
	{
		$this->file = $file;
		$this->splFileInfo = new \SplFileInfo($file);
	}

	public function getFile()
	{
		return $this->file;
	}

	public function setUrl($url)
	{
		$this->url = $url;
	}

	public function getUrl()
	{
		return $this->url;
	}

	public function setModTime($modTime)
	{
		$this->modTime = $modTime;
	}

	public function getModTime()
	{
		return $this->modTime;
	}


	public function setFormat($format)
	{
		$this->format = $format;
	}

	public function getFormat()
	{
		return $this->format;
	}

	public function getFilename()
	{
		return isset($this->splFileInfo) ? $this->splFileInfo->getFilename() : null;
	}

	public function getRealPath()
	{
		return isset($this->splFileInfo) ? $this->splFileInfo->getRealPath() : null;
	}

	public function isFile()
	{
		return isset($this->splFileInfo) ? $this->splFileInfo->isFile() : false;
	}

	public function getExtension()
	{
		return isset($this->splFileInfo) ? $this->splFileInfo->getExtension() : null;
	}

	public function exists()
	{
		return isset($this->splFileInfo) ? $this->splFileInfo->isFile() : false;
	}

	public function __toString()
	{
		return !empty($this->file) ? $this->file : $this->url;
	}
}
