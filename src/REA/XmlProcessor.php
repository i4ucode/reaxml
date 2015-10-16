<?php
/**
 * Provides an interface for processing multiple XML files in REA (realestate.com.au) format.
 * Parsed properties are returned as \REA\Property objects.
 *
 * See: http://reaxml.realestate.com.au/docs/
 *
 * Basic Usage:
 *  $rea = new \REA\XmlProcessor();
 *
 * Add a directory to process:
 *  $rea->addDirectory('data/incoming');
 *
 * Add a directory, and move files after processing:
 *  $rea->addDirectory('data/incoming', 'data/processed', 'data/failed');
 *
 * Add an individual file to process:
 *  $rea->addFile('somefile.xml');
 *
 * Get a list of added files
 *  $files = $rea->getIncomingFiles();
 *  print_r($files);
 *
 * Process properties in all added files:
 *  $properties = $rea->process();
 *  foreach ($properties as $property) {
 *         ...
 *  }
 *
 * @author Jodie Dunlop <jodiedunlop@gmail.com>
 * Copyright (C) 2015 i4U - Creative Internet Consultants Pty Ltd.
 */
namespace REA;

use Psr\Log\LoggerInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LogLevel;

class XmlProcessor implements LoggerAwareInterface
{
	protected $files;
	protected $numProcessFailed;

    /** @var  LoggerInterface */
	protected $logger;

	public function __construct()
	{
		$this->files = array();
		$this->numProcessed = 0;
		libxml_use_internal_errors(true);
	}

	public function setLogger(LoggerInterface $logger)
	{
		if (!is_object($logger) || !method_exists($logger, 'notice')) {
			throw new \Exception('Logger must implement notice() method');
		}
		$this->logger = $logger;
	}

	public function addDirectory($incomingDir, $processedDir = null, $failedDir = null)
	{
		if (!file_exists($incomingDir)) {
			throw new \Exception('Unable to add directory, does not exist: '.$incomingDir);
		}
		if (!empty($processedDir) && !file_exists($processedDir)) {
			throw new \Exception('Processed directory does not exist: '.$processedDir);
		}
		if (!empty($failedDir) && !file_exists($failedDir)) {
			throw new \Exception('Failed directory does not exist: '.$failedDir);
		}
		if (($fh = opendir($incomingDir)) === false) {
			throw new \Exception('Unable to add directory, cannot open directory: '.$incomingDir);
		}

		$files = array();
		while (($file = readdir($fh)) !== false) {
			if (!preg_match('/\.xml/i', $file)) {
				// Skip non-xml files
				continue;
			}
			$files[] = $file;
		}

		// Sort files which are named according to timestamp
		sort($files, SORT_STRING|SORT_FLAG_CASE);

		$added = 0;
		foreach ($files as $file) {
			// Add each XML file
			$this->addFile($incomingDir.DIRECTORY_SEPARATOR.$file,
				!empty($processedDir) ? $processedDir.DIRECTORY_SEPARATOR.$file : null,
				!empty($failedDir) ? $failedDir.DIRECTORY_SEPARATOR.$file : null);
			$added++;
		}
		closedir($fh);

		return $added;
	}

	public function addFile($incomingFile, $processedFile = null, $failedFile = null)
	{
		if (!file_exists($incomingFile)) {
			throw new \Exception('Unable to add file for processing, does not exist: '.$incomingFile);
		}

		$this->files[] = array($incomingFile, $processedFile, $failedFile);
	}


	public function reset()
	{
		$this->numProcessFailed = 0;
		$this->files = array();
	}

	public function process()
	{
		$propertiesAll = array();
		$this->numProcessFailed = 0;

		if ($this->getFileCount() < 1) {
			throw new \Exception('No files to process');
		}

		$this->log(LogLevel::INFO, 'Processing '.$this->getFileCount().' files');
		foreach ($this->files as $filePaths) {
			list($incomingFile, $processedFile, $failedFile) = $filePaths;

			try {
				$this->log(LogLevel::INFO, 'Processing file: '.$incomingFile);

				$properties = $this->parseXmlFile($incomingFile);

				$this->log(LogLevel::INFO, 'Processed '.count($properties).' properties within file');
				
				// TODO: This might be slow - append to allProperties array
				$propertiesAll = array_merge($propertiesAll, $properties);

				if (!empty($processedFile)) {
					$this->moveFile($incomingFile, $processedFile);
				}
			} catch (\Exception $e) {
				$this->log(LogLevel::ERROR, 'Error parsing file '.$incomingFile.': '.$e->getMessage());
				$this->numProcessFailed++;

				if (!empty($failedFile)) {
					$this->moveFile($incomingFile, $failedFile);
				}
				// Move on to next file
			}
		}

		return $propertiesAll;
	}


	protected function moveFile($srcFile, $destFile)
	{
		if (file_exists($destFile)) {
			throw new \Exception('Destination file already exists');
		}
		
		if (copy($srcFile, $destFile) === true) {
			unlink($srcFile);
		} else {
			$this->log(LogLevel::WARNING, 'Unable to move '.$srcFile.' to '.$destFile);
		}
	}

    /**
     * File wrapper for parseXmlString()
     * Returns an array of Property objects
     * @param string $path
     * @param string|null|bool $assetDirectory Optionally pass to source assets (images/floor plans)
     * @return array An array of REA\Property objects
     * @throws \Exception
     */
	public function parseXmlFile($path, $assetDirectory = null)
	{
		if (!file_exists($path)) {
			throw new \Exception('Unable to open file for parsing: '.$path);
		}

        if ($assetDirectory === null) {
            // If assetDirectory is null, assume that assets are in the same path as the file
            // Pass false to avoid this behavior
            $assetDirectory = dirname($path);
        }
		
		$str = file_get_contents($path);
		return $this->parseXmlString($str, $assetDirectory);
	}


    /**
     * Returns an array of Property objects
     * @param string $str An XML string
     * @param string $assetDirectory Optionally pass to source assets (images/floor plans)
     * @return array An array of REA\Property objects
     * @throws \Exception
     */
	public function parseXmlString($str, $assetDirectory = null)
	{
		$properties = array();

		if (empty($str))
		{
			throw new \Exception('Cannot parse empty string');
		}

		if (($xml = simplexml_load_string($str)) === false)
		{
			throw new \Exception('Failed to parse invalid XML');
		}

        /** @var \SimpleXmlElement $propertyNode */
        foreach ($xml->children() as $propertyNode)
		{
			$propertyType = $propertyNode->getName();

			$property = new Property();
			$property->setPropertyType($propertyType);
			$property->setModTime((string)$propertyNode['modTime']);
			$property->setStatus((string)$propertyNode['status']);
			$property->setAgentId((string)$propertyNode->agentID);
			$property->setUniqueId((string)$propertyNode->uniqueID);

			if (isset($propertyNode->underOffer)) {
				$property->setUnderOffer((string)$propertyNode->underOffer['value']);
			}

			foreach ($propertyNode->listingAgent as $listingAgentNode) {
				if (isset($listingAgentNode->name) && !empty($listingAgentNode->name)) {
					$person = new Person();
					$person->setId((string)$listingAgentNode['id']);
					$person->setName((string)$listingAgentNode->name);

					if (isset($listingAgentNode->email)) {
						$person->setEmail((string)$listingAgentNode->email);
					}
					if (isset($listingAgentNode->twitterURL)) {
						$person->setTwitterUrl((string)$listingAgentNode->twitterURL);
					}
					if (isset($listingAgentNode->facebookURL)) {
						$person->setFacebookUrl((string)$listingAgentNode->facebookURL);
					}
					if (isset($listingAgentNode->linkedinURL)) {
						$person->setLinkedinUrl((string)$listingAgentNode->linkedinURL);
					}

					if (isset($listingAgentNode->telephone)) {
						// According to DTD agent can have multiple telphone records?
						foreach ($listingAgentNode->telephone as $telephoneNode) {
							switch ((string)$telephoneNode['type']) {
								case 'BH':
									$person->setBusinessPhone((string)$telephoneNode);
									break;
								case 'AH':
									$person->setAfterHoursPhone((string)$telephoneNode);
									break;
								case 'mobile':
									$person->setMobilePhone((string)$telephoneNode);
									break;
								default:
									// Unsupported telephone type attribute
									break;
							}
						}
					}

					$property->addAgent($person);
				}
			}

			if (isset($propertyNode->commercialListingType)) {
				$property->setCommercialListingType((string)$propertyNode->commercialListingType['value']);
				
			}
			if (isset($propertyNode->price)) {
				$priceNode = $propertyNode->price;

				// Rent can be a single value or range
				$priceValue = isset($priceNode->range) ?
					new Range((string)$priceNode->range->min, (string)$priceNode->range->max) :
					(string)$priceNode;

				$price = new Price();
				$price->setValue($priceValue);
				if (isset($priceNode['display'])) {
					$price->setDisplay((string)$priceNode['display']);
				}
				if (isset($priceNode['display'])) {
					$price->setPlusSAV((string)$priceNode['plusSAV']);
				}
				if (isset($priceNode['tax'])) {
					$price->setTax((string)$priceNode['tax']);
				}
				
				$property->setPrice($price);
				unset($priceNode);
			}

			if (isset($propertyNode->priceView)) {
				$property->setPriceView((string)$propertyNode->priceView);
			}

			if (isset($propertyNode->exclusivity)) {
				$property->setExclusivity((string)$propertyNode->exclusivity['value']);
			}

			if (isset($propertyNode->commercialRent)) {
				$commercialRentNode = $propertyNode->commercialRent;

				// Rent can be a single value or range
				$rentValue = isset($commercialRentNode->rentPerSquareMetre) ?
					new Range(
						(string)$commercialRentNode->rentPerSquareMetre->range->min,
						(string)$commercialRentNode->rentPerSquareMetre->range->max
					) :
					(string)$commercialRentNode;

				$rent = new Rent();
				$rent->setValue($rentValue);
				if (isset($commercialRentNode['period'])) {
					$rent->setPeriod((string)$commercialRentNode['period']);
				}
				if (isset($commercialRentNode['plusOutgoings'])) {
					$rent->setPlusOutgoings((string)$commercialRentNode['plusOutgoings']);
				}
				if (isset($commercialRentNode['plusSAV'])) {
					$rent->setPlusSAV((string)$commercialRentNode['plusSAV']);
				}
				if (isset($commercialRentNode['tax'])) {
					$rent->setTax((string)$commercialRentNode['tax']);
				}
				
				$property->setRent($rent);
				unset($commercialRentNode);
			} elseif (isset($propertyNode->rent)) {

				// TODO: I don't think typical rent is under rentPerSquareMeter? Check DTD
				$rentNode = $propertyNode->rent;
				// Rent can be a single value or range
				$rentValue = isset($rentNode->rentPerSquareMetre) ?
					new Range(
						(string)$rentNode->rentPerSquareMetre->range->min,
						(string)$rentNode->rentPerSquareMetre->range->max
					) :
					(string)$rentNode;

				$rent = new Rent();
				$rent->setValue($rentValue);
				if (isset($rentNode['period'])) {
					$rent->setPeriod((string)$rentNode['period']);
				}
				if (isset($rentNode['display'])) {
					$rent->setDisplay((string)$rentNode['display']);
				}
				
				$property->setRent($rent);
				unset($rent);
			}

			if (isset($propertyNode->address)) {
				$addressNode = $propertyNode->address;
				$address = new Address();
				if (isset($addressNode['display'])) {
					$address->setDisplay((string)$addressNode['display']);
				}
				if (isset($addressNode->site)) {
					$address->setSite((string)$addressNode->site);
				}
				if (isset($addressNode->subNumber)) {
					$address->setSubNumber((string)$addressNode->subNumber);
				}
				if (isset($addressNode->unitNumber)) {
					$address->setUnitNumber((string)$addressNode->unitNumber);
				}
				if (isset($addressNode->lotNumber)) {
					$address->setLotNumber((string)$addressNode->lotNumber);
				}
				if (isset($addressNode->streetNumber)) {
					$address->setStreetNumber((string)$addressNode->streetNumber);
				}
				$address->setStreet((string)$addressNode->street);
				$address->setSuburb((string)$addressNode->suburb);
				$address->setDisplaySuburb((string)$addressNode->suburb['display']);
				if (isset($addressNode->region)) {
					$address->setRegion((string)$addressNode->region);
				}
				if (isset($addressNode->state)) {
					$address->setState((string)$addressNode->state);
				}
				$address->setPostcode((string)$addressNode->postcode);
				$address->setCountry((string)$addressNode->country);
				$property->setAddress($address);
				unset($addressNode);
			}
			
			if (isset($propertyNode->municipality)) {
				$property->setMunicipality((string)$propertyNode->municipality);
			}

			if (isset($propertyNode->category)) {
				foreach ($propertyNode->category as $categoryNode) {
					if (isset($categoryNode['name'])) {
						$property->addCategory(new ResidentialCategory(null, (string)$categoryNode['name']));
					}
				}
			}

			if (isset($propertyNode->commercialCategory)) {
				foreach ($propertyNode->commercialCategory as $commercialCategoryNode) {
					if (isset($commercialCategoryNode['name'])) {
						$property->addCategory(new CommercialCategory((string)$commercialCategoryNode['id'], (string)$commercialCategoryNode['name']));
					}
				}
			}

			if (isset($propertyNode->businessCategory)) {
				foreach ($propertyNode->businessCategory as $businessCategoryNode) {
					if (isset($businessCategoryNode->name)) {
						$subCategory = null;
						if (isset($businessCategoryNode->businessSubCategory)) {
							$subCategory = (string)$businessCategoryNode->businessSubCategory;
						}
						$property->addCategory(new BusinessCategory((string)$businessCategoryNode['id'], (string)$businessCategoryNode->name, $subCategory));
					}
				}
			}

			$property->setHeadline((string)$propertyNode->headline);
			$property->setDescription((string)$propertyNode->description);

			if (isset($propertyNode->parkingComments)) {
				$property->setParkingComments((string)$propertyNode->parkingComments);
			}

			if (isset($propertyNode->isMultiple)) {
				$property->setIsMultiple((string)$propertyNode->isMultiple['value']);
			}

			if (isset($propertyNode->carSpaces)) {
				$property->setCarSpaces((string)$propertyNode->carSpaces);
			}

			if (isset($propertyNode->vendorDetails)) {
				$vendorDetailsNode = $propertyNode->vendorDetails;
				$person = new Person();
				$person->setName((string)$vendorDetailsNode->name);

				if (isset($vendorDetailsNode->telephone)) {
					// According to DTD agent can have multiple telephone records?
					foreach ($vendorDetailsNode->telephone as $telephoneNode) {
						switch ((string)$telephoneNode['type']) {
							case 'BH':
								$person->setBusinessPhone((string)$telephoneNode);
								break;
							case 'AH':
								$person->setAfterHoursPhone((string)$telephoneNode);
								break;
							case 'mobile':
								$person->setMobilePhone((string)$telephoneNode);
								break;
							default:
								// Unsupported telephone type attribute
								break;
						}
					}
				}
				if (isset($vendorDetailsNode->email)) {
					$person->setEmail((string)$vendorDetailsNode->email);
				}
				$property->setVendorDetails($person);
				unset($vendorDetailsNode);
			}
	
			if (isset($propertyNode->zone)) {
				$property->setZone((string)$propertyNode->zone);
			}

			if (isset($propertyNode->currentLeaseEndDate)) {
				$property->setCurrentLeaseEndDate((string)$propertyNode->currentLeaseEndDate);
			}

			if (isset($propertyNode->furtherOptions)) {
				$property->setFurtherOptions((string)$propertyNode->furtherOptions);
			}

			if (isset($propertyNode->auction) && isset($propertyNode->auction['date'])) {
				$property->setAuctionDate((string)$propertyNode->auction['date']);
			}

			if (isset($propertyNode->externalLink)) {
				$property->setExternalLink((string)$propertyNode->externalLink);
			}

			if (isset($propertyNode->images)) {
				foreach ($propertyNode->images->img as $imgNode) {
					if (isset($imgNode['file']) || isset($imgNode['url'])) {
						$file = new File();
						$file->setId((string)$imgNode['id']);
						if (isset($imgNode['file'])) {
                            $imagePath = !empty($assetDirectory) ?
                                sprintf('%s/%s', $assetDirectory, $imgNode['file']) :
                                (string)$imgNode['file'];
							$file->setFile($imagePath);
                        } elseif (isset($imgNode['url'])) {
							$file->setUrl((string)$imgNode['url']);
						}
						$file->setFormat((string)$imgNode['format']);
						$file->setModTime((string)$imgNode['modTime']);
						$property->addImage($file);
					}
				}
			}

			if (isset($propertyNode->objects)) {
				foreach ($propertyNode->objects->floorPlan as $floorPlanNode) {
					if (isset($floorPlanNode['file']) || isset($floorPlanNode['url'])) {
						$file = new File();
						$file->setId((string)$floorPlanNode['id']);
						if (isset($floorPlanNode['file'])) {
                            $floorPlanPath = !empty($assetDirectory) ?
                                sprintf('%s/%s', $assetDirectory, $floorPlanNode['file']) :
                                (string)$floorPlanNode['file'];
							$file->setFile($floorPlanPath);
						} elseif (isset($floorPlanNode['url'])) {
							$file->setUrl((string)$floorPlanNode['url']);
						}
						$file->setFormat((string)$floorPlanNode['format']);
						$file->setModTime((string)$floorPlanNode['modTime']);
						$property->addFloorPlan($file);
					}
				}
			}

			if (isset($propertyNode->highlight)) {
				foreach ($propertyNode->highlight as $highlightNode) {
					if (!empty($highlightNode)) {
						$property->addHighlight(new IdValue((string)$highlightNode['id'], (string)$highlightNode));
					}
				}
			}

			if (isset($propertyNode->landDetails)) {
				$landDetailsNode = $propertyNode->landDetails;
				$landDetails = new LandDetails();

				// TODO: Handle frontage, depth (multi), crossover
				if (isset($landDetailsNode->area)) {
					$areaNode = $landDetailsNode->area;
					$areaValue = isset($areaNode->range) ?
						new Range((string)$areaNode->range->min, (string)$areaNode->range->max) :
						(string)$areaNode;
					$area = new Area($areaValue, (string)$areaNode['unit']);
					$landDetails->setArea($area);
				}
				$property->setLandDetails($landDetails);
			}

			if (isset($propertyNode->landCategory) && isset($propertyNode->landCategory['name'])) {
				$property->setLandCategory((string)$propertyNode->landCategory['name']);
			}

			if (isset($propertyNode->buildingDetails)) {
				$buildingDetailsNode = $propertyNode->buildingDetails;
				$buildingDetails = new BuildingDetails();

				if (isset($buildingDetailsNode->area)) {
					$areaNode = $buildingDetailsNode->area;
					// The area may be a range or a single value
					$areaValue = isset($areaNode->range) ? new Range((string)$areaNode->range->min, (string)$areaNode->range->max) : (string)$areaNode;
					$area = new Area($areaValue, (string)$areaNode['unit']);
					$buildingDetails->setArea($area);
				}
				$property->setBuildingDetails($buildingDetails);
			}

			if (isset($propertyNode->commercialAuthority)) {
				$property->setAuthority((string)$propertyNode->commercialAuthority);
			} elseif (isset($propertyNode->authority)) {
				$property->setAuthority((string)$propertyNode->authority['value']);
			}

			$properties[] = $property;
		}


		return $properties;
	}

	public function getFileCount()
	{
		return count($this->files);
	}

	public function getIncomingFiles()
	{
		$incoming = array();
		foreach ($this->files as $filePaths) {
			$incoming[] = $filePaths[0];
		}

		return $incoming;
	}

	public function log($logLevel, $message, $context = array())
	{
		if (!isset($this->logger)) {
			return;
		}

		switch ($logLevel) {
			case LogLevel::EMERGENCY:
				$this->logger->emergency($message, $context);
				break;
			case LogLevel::ALERT:
				$this->logger->alert($message, $context);
				break;
			case LogLevel::CRITICAL:
				$this->logger->critical($message, $context);
				break;
			case LogLevel::ERROR:
				$this->logger->error($message, $context);
				break;
			case LogLevel::WARNING:
				$this->logger->warning($message, $context);
				break;
			case LogLevel::NOTICE:
				$this->logger->notice($message, $context);
				break;
			case LogLevel::INFO:
				$this->logger->info($message, $context);
				break;
			case LogLevel::DEBUG:
				$this->logger->debug($message, $context);
				break;
			default:
				// Unknown log level
				throw new \InvalidArgumentException('Invalid log level');
				break;
		}
	}
}
