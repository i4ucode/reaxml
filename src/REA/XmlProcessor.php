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
    /** @var  LoggerInterface */
    protected $logger;

    public function __construct()
    {
        libxml_use_internal_errors(true);
    }

    public function setLogger(LoggerInterface $logger)
    {
        if (!is_object($logger) || !method_exists($logger, 'notice')) {
            throw new \Exception('Logger must implement notice() method');
        }
        $this->logger = $logger;
    }

    public function processZipFile($filename, array $options = [])
    {
        $options = array_merge([
            'unzip_bin' => '/usr/bin/unzip',
            'tmp_dir' => '/tmp',
        ], $options);

        if (!file_exists($filename)) {
            throw new \Exception('Invalid ZIP file to process: ' . $filename);
        }

        if (!is_executable($options['unzip_bin'])) {
            throw new \Exception('No executable unzip binary present at: ' . $options['unzip_bin']);
        }

        if (($mime = mime_content_type($filename)) !== 'application/zip') {
            throw new \Exception('File has invalid mime type:' . $mime);
        }

        // Unzip the zip file to a processing folder
        $zipFileInfo = new \SplFileInfo($filename);
        $zipBasename = $zipFileInfo->getBasename($zipFileInfo->getExtension());
        $zipOutputDir = $options['tmp_dir'] . DIRECTORY_SEPARATOR . $zipBasename . uniqid();

        $cmd = sprintf('%s -qq %s -d %s', $options['unzip_bin'], $filename, $zipOutputDir);
        echo "Running command: $cmd\n";
        system($cmd);

        if (!file_exists($zipOutputDir)) {
            throw new \Exception('Unable to create ZIP output directory: ' . $zipOutputDir);
        }

        return $this->processDirectory($zipOutputDir, array_merge($options, ['source' => $filename]));
    }

    protected function processDirectory($directory, array $options = [])
    {
        $options = array_merge([
            'move_source' => true,
            'source' => null,           // If the source is something else (eg. ZIP file)
            'failed_dir' => null,
            'completed_dir' => null,
            'processing_dir' => '/tmp',
        ], $options);

        // We hope to end up with an array of REA\Property objects to return
        $properties = array();

        // Check the directory we are processing actually exists
        if (!is_dir($directory)) {
            throw new \Exception('Directory to process does not exist: ' . $directory);
        }

        // The source file or directory that should be moved around if the move_source option is true
        $source = !empty($options['source']) ? $options['source'] : $directory;
        if (!file_exists($source)) {
            throw new \Exception('The source file/directory does not exist: ' . $source);
        }

        echo "Source is: $source\n";

        // Pre-check the failure directory if specified
        if (!empty($options['failed_dir']) && !file_exists($options['failed_dir'])) {
            throw new \Exception('Failed directory does not exist: ' . $options['failed_dir']);
        }

        // Pre-check the completed directory if specified
        if (!empty($options['completed_dir']) && !file_exists($options['completed_dir'])) {
            throw new \Exception('Failed directory does not exist: ' . $options['completed_dir']);
        }

        // Move to the processing directory
        if (!empty($options['move_source']) && !empty($options['processing_dir'])) {
            if (!is_dir($options['processing_dir'])) {
                throw new \Exception('Processing directory does not exist: ' . $options['processing_dir']);
            }
            $destination = $this->moveToDirectory($source, $options['processing_dir']);
            if (!empty($destination)) {
                $source = $destination;
            }
        }

        // Get all the XML files within the directory (there should be only one)
        if (($fh = opendir($directory)) === false) {
            throw new \Exception('Unable to add directory, cannot open directory: ' . $directory);
        }
        $files = array();
        while (($file = readdir($fh)) !== false) {
            if (!preg_match('/\.xml/i', $file)) {
                // Skip non-xml files
                continue;
            }
            $files[] = $file;
        }
        closedir($fh);
        if (count($files) !== 1) {
            throw new \Exception('There must be at least one (and only one) XML file within the directory ' . $directory);
        }


        $xmlFile = $directory . DIRECTORY_SEPARATOR . $files[0];
        try {

            $this->log(LogLevel::INFO, 'Processing file: ' . $xmlFile);
            $properties = $this->parseXmlFile($xmlFile, $directory);

            $this->log(LogLevel::INFO, 'Processed ' . count($properties) . ' properties within ' . $xmlFile);

            if (!empty($options['move_source']) && !empty($options['completed_dir'])) {
                $this->moveToDirectory($source, $options['completed_dir']);
            }
        } catch (\Exception $e) {
            $this->log(LogLevel::ERROR, 'Error parsing file ' . $xmlFile . ': ' . $e->getMessage());

            if (!empty($options['move_source']) && !empty($options['failed_dir'])) {
                $this->moveToDirectory($source, $options['failed_dir']);
            }
        }

        return $properties;
    }


    protected function moveToDirectory($source, $directory)
    {
        if (!file_exists($directory)) {
            throw new \Exception('Destination directory does not exist: ' . $directory);
        }

        $destination = $directory . DIRECTORY_SEPARATOR . basename($source);

        $this->log(LogLevel::INFO, 'Moving ' . $source . ' to ' . $destination);
        if (rename($source, $destination) !== true) {
            $this->log(LogLevel::WARNING, 'Unable to move ' . $source . ' to ' . $destination);
            $destination = null;
        }

        return $destination;
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
            throw new \Exception('Unable to open file for parsing: ' . $path);
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

        if (empty($str)) {
            throw new \Exception('Cannot parse empty string');
        }

        if (($xml = simplexml_load_string($str)) === false) {
            throw new \Exception('Failed to parse invalid XML');
        }

        /** @var \SimpleXmlElement $propertyNode */
        foreach ($xml->children() as $propertyNode) {
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

    public function getZipFiles($directory)
    {
        // Get all the ZIP files within the directory
        if (($fh = opendir($directory)) === false) {
            throw new \Exception('Unable to open directory: ' . $directory);
        }
        $files = array();
        while (($file = readdir($fh)) !== false) {
            if (preg_match('/\.zip/i', $file)) {
                $files[] = $directory . DIRECTORY_SEPARATOR . $file;
            }

        }
        closedir($fh);

        return $files;
    }
}
