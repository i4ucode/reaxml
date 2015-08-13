<?php
namespace REA;

class Property
{
	const TYPE_RESIDENTIAL = 'residential';
	const TYPE_RENTAL = 'rental';
	const TYPE_LAND = 'land';
	const TYPE_RURAL = 'rural';
	const TYPE_COMMERCIAL = 'commercial';
	const TYPE_COMMERCIAL_LAND = 'commercialLand';
	const TYPE_BUSINESS = 'business';

	const STATUS_CURRENT = 'current';
	const STATUS_WITHDRAWN = 'withdrawn';
	const STATUS_OFFMARKET = 'offmarket';
	const STATUS_SOLD = 'sold';
	const STATUS_LEASED = 'leased';

	const LISTING_SALE = 'sale';
	const LISTING_LEASE = 'lease';
	const LISTING_BOTH = 'both';

	const AUTHORITY_AUCTION	 = 'auction';	// Sale by auction
	const AUTHORITY_CONJUNCTIONAL = 'conjunctional';	// The property is “For Sale” and listed with multiple agents working together – sales commission is shared amongst agents
	const AUTHORITY_EOI	= 'eoi';			// Sale by Expression of Interest.
	const AUTHORITY_EXCLUSIVE = 'exclusive';	// The property is “For Sale” via a single Agency
	const AUTHORITY_FOR_SALE	= 'forsale'; 	// For Sale
	const AUTHORITY_MULTILIST = 'multilist';	// The property is “For Sale” and listed with multiple agencies
	const AUTHORITY_OFFERS = 'offers';		// Offers to Purchase.
	const AUTHORITY_OPEN = 'open';		// More than one real estate agent may be employed to sell the property. The owner pays a commission only to the agent who finds the buyer
	const AUTHORITY_SET_SALE = 'setsale';			// The property is offered for sale up to the set sale date.
	const AUTHORITY_SALE = 'sale';			// Sale by Negotiation.
	const AUTHORITY_TENDER = 'tender';		// Sale by public tender by a particular date.

	protected $propertyType;
	protected $modTime;
	protected $status;
	protected $agentId;
	protected $uniqueId;
	protected $exclusivity;
	protected $authority;	// Also used for commercialAuthority
	protected $commercialListingType;
	protected $underOffer;
	protected $agents = array();
	protected $price;
	protected $priceView;
	protected $rent;		// Commercial or rental
	protected $isMultiple;
	protected $address;
	protected $municipality;
	protected $commercialCategories = array();
	protected $headline;
	protected $description;

	protected $highlights = array();		// Commercial only
	protected $carSpaces;
	protected $parkingComments;
	protected $landDetails;
	protected $buildingDetails;
	protected $vendorDetails;
	protected $zone;
	protected $externalLink;
	protected $auctionDate;
	protected $currentLeaseEndDate;
	protected $furtherOptions;
	protected $images = array();
	protected $floorPlans = array();

	public function getPropertyType()
	{
		return $this->propertyType;
	}

	public function setPropertyType($type)
	{
		$this->propertyType = $type;
	}

	public function setModTime($modTime)
	{
		$this->modTime = $modTime;
	}

	public function getModTime()
	{
		return $this->modTime;
	}

	public function setStatus($status)
	{
		$this->status = $status;
	}

	public function getStatus()
	{
		return $this->status;
	}

	public function getAgentId()
	{
		return $this->agentId;
	}

	public function setAgentId($agentId)
	{
		$this->agentId = $agentId;
	}

	public function getUniqueId()
	{
		return $this->uniqueId;
	}

	public function setUniqueId($uniqueId)
	{
		$this->uniqueId = $uniqueId;
	}

	public function getExclusivity()
	{
		return $this->exclusivity;
	}

	public function setExclusivity($exclusivity)
	{
		$this->exclusivity = $exclusivity;
	}

	public function getCommercialListingType()
	{
		return $this->commercialListingType;
	}

	public function setCommercialListingType($commercialListingType)
	{
		$this->commercialListingType = $commercialListingType;
	}

	public function getAuthority()
	{
		return $this->authority;
	}

	public function setAuthority($authority)
	{
		$this->authority = $authority;
	}

	public function setRent(Rent $rent)
	{
		$this->rent = $rent;
	}

	public function getRent()
	{
		return $this->rent;
	}

	public function isUnderOffer()
	{
		return $this->underOffer === true;
	}

	public function setUnderOffer($underOffer)
	{
		$this->underOffer = !(empty($underOffer) || $underOffer === 'no');
	}

	public function addAgent(Person $agent)
	{
		$this->agents[] = $agent;
	}

	public function emptyAgents()
	{
		$this->agents = array();
	}

	public function setAgents($agents)
	{
		$this->emptyAgents();
		foreach ($agents as $agent)
		{
			$this->addAgent($agent);
		}
	}

	public function getAgents()
	{
		return new ArrayIterator($this->agents);
	}

	public function getAgentById($id)
	{
		foreach ($this->agents as $person) {
			if ($person->getId() == $id) {
				return $person;
			}
		}

		return null;
	}


	public function getAgentCount()
	{
		return count($this->agents);
	}

	public function setPrice(Price $price)
	{
		$this->price = $price;
	}

	public function getPrice()
	{
		return $this->price;
	}

	public function getPriceView()
	{
		return $this->priceView;
	}

	public function setPriceView($priceView)
	{
		$this->priceView = $priceView;
	}

	public function getOutgoingsPeriod()
	{
		return $this->outgoingsPeriod;
	}

	public function setOutgoingsPeriod($outgoingsPeriod)
	{
		$this->outgoingsPeriod = $outgoingsPeriod;
	}

	public function isMultiple()
	{
		return $this->isMultiple === true;
	}

	public function setIsMultiple($isMultiple)
	{
		$this->isMultiple = !(empty($isMultiple) || $isMultiple === 'no');
	}
	
	public function getAddress()
	{
		return $this->address;
	}

	public function setAddress(Address $address)
	{
		$this->address = $address;
	}


	public function getMunicipality()
	{
		return $this->municipality;
	}

	public function setMunicipality($municipality)
	{
		$this->municipality = $municipality;
	}

	public function getCommercialCategories()
	{
		return new ArrayIterator($this->commercialCategories);
	}

	public function addCommercialCategory(IdValue $category)
	{
		$this->commercialCategories[] = $category;
	}

	public function emptyCommercialCategorys()
	{
		$this->commercialCategories = array();
	}

	public function setCommercialCategorys($categories)
	{
		$this->emptyCommercialCategorys();
		foreach ($categories as $category) {
			$this->addCommercialCategory($category);
		}
	}

	public function getCommercialCategoryById($id)
	{
		foreach ($this->commercialCategories as $idValue) {
			if ($idValue->getId() == $id) {
				return $idValue;
			}
		}

		return null;
	}

	public function getCommercialCategoryCount()
	{
		return count($this->commercialCategories);
	}

	public function getHeadline()
	{
		return $this->headline;
	}

	public function setHeadline($headline)
	{
		$this->headline = $headline;
	}

	public function getDescription()
	{
		return $this->description;
	}

	public function setDescription($description)
	{
		return $this->description = $description;
	}

	public function getHighlights()
	{
		return new ArrayIterator($this->highlights);
	}

	public function addHighlight(IdValue $highlight)
	{
		$this->highlights[] = $highlight;
	}

	public function emptyHighlights()
	{
		$this->highlights = array();
	}

	public function setHighlights($highlights)
	{
		$this->emptyHighlights();
		foreach ($highlights as $highlight)
		{
			$this->addHighlight($highlight);
		}
	}

	public function getHighlightById($id)
	{
		foreach ($this->highlights as $idValue) {
			if ($idValue->getId() == $id) {
				return $idValue;
			}
		}

		return null;
	}

	public function getImages()
	{
		return new ArrayIterator($this->images);
	}

	public function getImageById($id)
	{
		foreach ($this->images as $file) {
			if ($file->getId() == $id) {
				return $file;
			}
		}

		return null;
	}

	public function getMainImage()
	{
		return $this->getImageById('m');
	}

	public function addImage(File $image)
	{
		$this->images[] = $image;
	}

	public function emptyImages()
	{
		$this->images = array();
	}

	public function setImages($images)
	{
		$this->emptyImages();
		foreach ($images as $image)
		{
			$this->addImage($image);
		}
	}

	public function getImageCount()
	{
		return count($this->images);
	}


	public function getFloorPlans()
	{
		return new ArrayIterator($this->floorPlans);
	}

	public function getFloorPlanById($id)
	{
		foreach ($this->floorPlans as $file) {
			if ($file->getId() == $id) {
				return $file;
			}
		}

		return null;
	}

	public function getMainFloorPlan()
	{
		return $this->getFloorPlanById('1');
	}

	public function addFloorPlan(File $floorPlan)
	{
		$this->floorPlans[] = $floorPlan;
	}

	public function emptyFloorPlans()
	{
		$this->floorPlans = array();
	}

	public function setFloorPlans($floorPlans)
	{
		$this->emptyFloorPlans();
		foreach ($floorPlans as $floorPlan)
		{
			$this->addFloorPlan($floorPlan);
		}
	}

	public function getFloorPlanCount()
	{
		return count($this->floorPlans);
	}

	public function getLandDetails()
	{
		return $this->landDetails;
	}
		
	public function setLandDetails(LandDetails $landDetails)
	{
		$this->landDetails = $landDetails;
	}

	public function getBuildingDetails()
	{
		return $this->buildingDetails;
	}
		
	public function setBuildingDetails(BuildingDetails $buildingDetails)
	{
		$this->buildingDetails = $buildingDetails;
	}

	public function getVendorDetails()
	{
		return $this->vendorDetails;
	}
		
	public function setVendorDetails(Person $vendorDetails)
	{
		$this->vendorDetails = $vendorDetails;
	}

	public function getCarSpaces()
	{
		return $this->carSpaces;
	}
		

	public function setCarSpaces($carSpaces)
	{
		$this->carSpaces = $carSpaces;
	}


	public function getParkingComments()
	{
		return $this->parkingComments;
	}
		

	public function setParkingComments($parkingComments)
	{
		$this->parkingComments = $parkingComments;
	}

	public function getZone()
	{
		return $this->zone;
	}
		
	public function setZone($zone)
	{
		$this->zone = $zone;
	}

	public function getExternalLink()
	{
		return $this->externalLink;
	}
		
	public function setExternalLink($externalLink)
	{
		$this->externalLink = $externalLink;
	}

	public function getAuctionDate()
	{
		return $this->auctionDate;
	}
		
	public function setAuctionDate($auctionDate)
	{
		$this->auctionDate = $auctionDate;
	}

	public function getCurrentLeaseEndDate()
	{
		return $this->currentLeaseEndDate;
	}
		
	public function setCurrentLeaseEndDate($currentLeaseEndDate)
	{
		$this->currentLeaseEndDate = $currentLeaseEndDate;
	}

	public function getFurtherOptions()
	{
		return $this->furtherOptions;
	}
		
	public function setFurtherOptions($furtherOptions)
	{
		$this->furtherOptions = $furtherOptions;
	}
}
