<?php
require_once(dirname(__FILE__).'/../vendor/autoload.php');

use REA\XmlProcessor;

$processor = new XmlProcessor();
$processor->addDirectory(dirname(__FILE__).'/data/incoming');
$properties = $processor->process();
echo "There are  ".count($properties)." properties total".PHP_EOL;

foreach ($properties as $property)
{
	echo sprintf('[%s] %s: %s%s', $property->getUniqueId(), $property->getPropertyType(), $property->getHeadline(), PHP_EOL);

	//foreach ($property->getImages() as $image) {
	//	echo $image.PHP_EOL;
	//}
	//foreach ($property->getAgents() as $agent) {
	//	echo $agent.PHP_EOL;
	//}
	//foreach ($property->getCommercialCategories() as $commercialCategory) {
	//	echo $commercialCategory.PHP_EOL;
	//}
	//foreach ($property->getHighlights() as $highlight) {
	//	echo $highlight.PHP_EOL;
	//}
	echo "----------------------------------------------------".PHP_EOL;
	print_r($property);
	echo "----------------------------------------------------".PHP_EOL;

}


