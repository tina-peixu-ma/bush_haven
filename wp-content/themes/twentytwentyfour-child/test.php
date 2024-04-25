<?php
// Load WordPress
require_once('/var/www/html/wordpress/wp-load.php');

// Include necessary function definitions
require_once('/var/www/html/wordpress/wp-content/themes/twentytwentyfour-child/functions.php');



//$plantTypes = getPlantGroup($plantType);
//print_r($plantTypes);
//$attr_botanical_names = get_plant_recommendation_attr($plantTypes, $heightRange, $spreadRange, $foliageColour, $flowerColour);
//print_r($attr_botanical_names);
//$attr_plant_info = get_plant_info($attr_botanical_names);
//$response = json_encode($attr_plant_info);
// echo $response;
$userInputName="bean";
$postcode="3161";
$lon_lat_data=get_lon_lat($postcode);
print_r($lon_lat_data);
//$plants=get_water_needs($userInputName);
//print_r($plants);
//$climate_water_text = get_climate_water($lon_lat_data);
//var_dump($climate_water_text['seasonal_rainfall_water']);
//$plants_with_water_needs = get_water_surplus($userInputName, get_lon_lat($postcode));
//print_r($plants_with_water_needs);


//$x=get_plants_by_name($userInputName);
//print_r($x);
//var_dump($x);
//$botanical_names = array_map(function($item) {
 //   return $item['Botanical_name'];
//}, $x);
//print_r($botanical_names);
//var_dump($botanical_names);
//$y=get_plant_info($botanical_names);

//print_r($y);
$plants = get_plants_by_name($userInputName);
var_dump($plants);
$botanical_names = array_map(function($item) {
   return $item['Botanical_name'];
}, $plants);
if ($botanical_names) {
   $plant_info = get_plant_info($botanical_names);
   $response = json_encode($plant_info);
   echo $response;
}
else {
   echo 'error';
}
?>
