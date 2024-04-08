<?php
add_action('wp_enqueue_scripts', 'my_child_theme_enqueue_styles');

function my_child_theme_enqueue_styles() {
    wp_enqueue_style('parent-theme-style', get_template_directory_uri() . '/var/www/html/wordpress/wp-content/themes/twentytwentyfour/style.css');
}

/**
 * Establish MySQL database connection.
 */
$host = 'localhost';
$username = 'root';
$password = 'testpassword';
$database = 'Flora';

$db = new mysqli($host, $username, $password, $database);

if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}

/**
 * Search plants by name.
 */
function get_plants_by_name($cleanedInput) {
    global $db;

    $likeParam = "%" . strtolower($cleanedInput) . "%";

    $sql = "SELECT Botanical_name
            FROM water_plant
            WHERE LOWER(Botanical_name) LIKE ?
               OR LOWER(Common_name) LIKE ?
               AND IF_image = 'Yes'
               AND Image_location LIKE '%s.jpg' LIMIT 100";

    $stmt = $db->prepare($sql);
    $stmt->bind_param("ss", $likeParam, $likeParam);
    
    $stmt->execute();
    $result = $stmt->get_result();
    $plants = array();

    while ($row = $result->fetch_assoc()) {
        $plants[] = $row;
    }
    $stmt->close();

    return $plants;
}

function handle_get_plants_by_name(){
    $userInput = isset($_POST['plantName']) ? sanitize_text_field($_POST['plantName']) : '';
    $cleanedInput = preg_replace('/[^a-zA-Z0-9\s]/', '', $userInput);

    if (empty($cleanedInput)) {
        wp_send_json_error('Please enter a valid search term to find plants.');
    }

    $plants = get_plants_by_name($cleanedInput);
    $botanical_names = array_map(function($item) {
        return $item['Botanical_name'];
    }, $plants);
    if ($botanical_names) {
        $plant_info = get_plant_info($botanical_names);
        $response = json_encode($plant_info);
        echo $response;
    } else {
        wp_send_json_error('No plants found matching the search criteria.');
    }

    wp_die();
}
add_action('wp_ajax_handle_get_plants_by_name', 'handle_get_plants_by_name');
add_action('wp_ajax_nopriv_handle_get_plants_by_name', 'handle_get_plants_by_name');





/**
 * Retrieve plant types based on the user-selected location. --------------- version 2.0
 *
 * Steps:
 * 1. Present a dropdown list in the frontend for users to select a location.
 * 2. Based on the selected location, retrieve the corresponding longitude and latitude from the database.
 * 3. Use the retrieved longitude and latitude to call an API for getting climate information specific to the location.
 * 4. Utilize the climate information obtained from the API to fetch suitable plant types for that climate.
 * 5. Loop through the returned plant types to query the database for their botanical names...
 * 6. For each botanical name, call an API to retrieve image URLs of the plants...
 * 7. Display the plant images on the frontend, potentially using a shortcode for easy integration...
 */

function get_lon_lat($postcode) {
    global $db;

    $stmt = $db->prepare("SELECT longitude, latitude FROM postcodes_geo WHERE postcode = ? LIMIT 1");
    $stmt->bind_param('s', $postcode);
    $stmt->execute();

    $stmt->bind_result($longitude, $latitude);
    $stmt->fetch();
    $stmt->close();

    if ($longitude && $latitude) {
        return array('lon' => $longitude, 'lat' => $latitude);
    } else {
        return false;
    }
}

function get_climate_info($lon_lat_data) {
    $lon_lat_data['lon'] = floatval($lon_lat_data['lon']);
    $lon_lat_data['lat'] = floatval($lon_lat_data['lat']);

    $api_url = 'http://3.107.4.242:8001/climateinfo';

    $headers = array(
        'Content-Type: application/json'
    );

    $curl = curl_init();

    curl_setopt_array($curl, array(
        CURLOPT_URL => $api_url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => "POST",
        CURLOPT_POSTFIELDS => json_encode($lon_lat_data),
        CURLOPT_HTTPHEADER => $headers,
    ));

    $climate_info = curl_exec($curl);

    if (curl_errno($curl)) {
        $error_msg = curl_error($curl);
        curl_close($curl);
        return "Error: " . $error_msg;
    }

    curl_close($curl);

    return $climate_info;
}

function get_plant_type($climate_info) {
    $api_url = 'http://3.107.4.242:8000/predict';

    $headers = array(
        'Content-Type: application/json'
    );

    $curl = curl_init();

    curl_setopt_array($curl, array(
        CURLOPT_URL => $api_url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => "POST",
        CURLOPT_POSTFIELDS => $climate_info,
        CURLOPT_HTTPHEADER => $headers,
    ));

    $plant_type_prediction = curl_exec($curl);

    if (curl_errno($curl)) {
        $error_msg = curl_error($curl);
        curl_close($curl);
        return "Error: " . $error_msg;
    }

    curl_close($curl);

    $plant_type_prediction = json_decode($plant_type_prediction, true);

    if (isset($plant_type_prediction['prediction'])) {
        $plant_types = $plant_type_prediction['prediction'][0];
    } else {
        return array();
    }

    return $plant_types;
}

function get_botanical_names($plant_types) {
    global $db;

    $botanical_names = array();

    foreach ($plant_types as $plant_type) {
        $stmt = $db->prepare("
            SELECT Botanical_name
            FROM water_plant
            WHERE Plant_type = ?
                AND Native = 'Yes'
                AND IF_image = 'Yes'
                AND Image_location LIKE '%.jpg'
            LIMIT 1
        ");

        $stmt->bind_param('s', $plant_type);
        $stmt->execute();
        $stmt->bind_result($botanical_name);
        while ($stmt->fetch()) {
            $botanical_names[] = $botanical_name;
        }
        $stmt->close();
    }

    return $botanical_names;
}

function get_plant_info($botanical_names) {
    global $db;

    $plant_info = array();

    foreach ($botanical_names as $botanical_name) {
        $stmt = $db->prepare("SELECT * FROM water_plant WHERE Botanical_name = ? AND IF_image = 'Yes' AND Image_location LIKE '%.jpg'");
        $stmt->bind_param('s', $botanical_name);
        $stmt->execute();
        $result = $stmt->get_result();
        $plant_data = $result->fetch_assoc();

        $plant_info[] = $plant_data;

        $stmt->close();
    }

    return $plant_info;
}

function handle_get_plant_types() {
    $postcode = isset($_POST['input']) ? sanitize_text_field($_POST['input']) : '';
    $lon_lat_data = get_lon_lat($postcode);
    $climate_info = get_climate_info($lon_lat_data);
    $plant_types = get_plant_type($climate_info);
    $botanical_names = get_botanical_names($plant_types);
    $plant_info = get_plant_info($botanical_names);
    $response = json_encode($plant_info);

    echo $response;
    wp_die();
}

add_action('wp_ajax_handle_get_plant_types', 'handle_get_plant_types');
add_action('wp_ajax_nopriv_handle_get_plant_types', 'handle_get_plant_types');

/**
 * Lifestyle.
 */
function generate_lifestyle_filter($time_availability, $flexibility, $garden_purpose, $plant_types) {
    $maintenance_mark = 0;

    switch (strtolower($time_availability)) {
        case "limited":
            $maintenance_mark += 1;
            break;
        case "moderate":
            $maintenance_mark += 2;
            break;
        case "abundant":
            $maintenance_mark += 3;
            break;
    }

    switch (strtolower($flexibility)) {
        case "low":
            $maintenance_mark += 1;
            break;
        case "moderate":
            $maintenance_mark += 2;
            break;
        case "high":
            $maintenance_mark += 3;
            break;
    }

    foreach ($garden_purpose as $purpose) {
        switch ($purpose) {
            case "Fresh Produce":
                array_unshift($plantTypes, "Vegetable");
                $maintenance_mark += 1;
                break;
            case "Hobby Leisure":
            case "Education Opportunities":
                $maintenance_mark += 1;
                break;
            case "Basic Garden":
                $maintenance_mark -= 2;
                break;
        }
    }

    if ($maintenance_mark < 3) {
        $maintenance = "Low";
    } elseif ($maintenance_mark >= 3 && $maintenance_mark <= 4) {
        $maintenance = "Medium";
    } else {
        $maintenance = "High";
    }

    return array($maintenance, $plant_types);
}

function generate_lifestyle_botanical_names($lifestyle_filter) {
    global $db;

    $maintenance = $lifestyle_filter[0];
    $plant_types = $lifestyle_filter[1];

    $lifestyle_botanical_names = array();

    foreach ($plant_types as $plant_type) {
        $stmt = $db->prepare("SELECT Botanical_name FROM water_plant WHERE Plant_type = ? AND Maintenance = ? AND IF_image = 'Yes' LIMIT 100");
        $stmt->bind_param("ss", $plant_type, $maintenance);
        $stmt->execute();
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $lifestyle_botanical_names[] = $row['Botanical_name'];
        }

        $stmt->close();
    }

    return $lifestyle_botanical_names;
}

function handle_get_plant_recommendation_lifestyle() {
    if (isset($_POST['time_availablity']) && isset($_POST['flexibility']) && isset($_POST['garden_purpose']) && isset($_POST['plant_types'])) {
        $time_availablity = sanitize_text_field($_POST['time_availablity']);
        $flexibility = sanitize_text_field($_POST['flexibility']);
        $garden_purpose = sanitize_text_field($_POST['garden_purpose']);
        $plant_types = $_POST['plant_types'];

        $lifestyle_filter = generate_lifestyle_filter($time_availablity, $flexibility, $garden_purpose, $plant_types);
        $lifestyle_botanical_names = generate_lifestyle_botanical_names($lifestyle_filter);
        $lifestyle_plant_info = get_plant_info($lifestyle_botanical_names);
	$response = json_encode($lifestyle_plant_info);
        echo $response;

    } else {
        wp_send_json_error('Missing parameters');
    }

    wp_die();
}

add_action('wp_ajax_get_plant_recommendation_lifestyle', 'handle_get_plant_recommendation_lifestyle');
add_action('wp_ajax_nopriv_get_plant_recommendation_lifestyle', 'handle_get_plant_recommendation_lifestyle');

/**
 * All plant type.
 */
function get_all_plant_types() {
    global $db;

    $plant_types = array();

    $stmt = $db->prepare("SELECT DISTINCT Plant_type FROM water_plant WHERE Plant_type IS NOT NULL AND Plant_type <> ''");
    $stmt->execute();
    $stmt->bind_result($plant_type);

    while ($stmt->fetch()) {
        $plant_types[] = $plant_type;
    }

    $stmt->close();

    return $plant_types;
}

function handle_get_all_plant_types() {
    $plant_types = get_all_plant_types();
    $botanical_names = get_botanical_names($plant_types);
    $plant_info = get_plant_info($botanical_names);
    $response = json_encode($plant_info);

    echo $response;
    wp_die();
}

add_action('wp_ajax_handle_get_all_plant_types', 'handle_get_all_plant_types');
add_action('wp_ajax_nopriv_handle_get_all_plant_types', 'handle_get_all_plant_types');

/**
 * Plant attr.
 */
function getPlantGroup($plantType) {
    $plant_groups = array(
        'Trees' => array('Medium Tree', 'Small Tree', 'Large Tree', 'Tree'),
        'Shrubs and Bushes' => array('Shrub', 'Groundcover'),
        'Flowers and Plants' => array('Annual', 'Perennial', 'Bulb', 'Climber', 'Fern', 'Grass', 'Herb'),
        'Fruits and Vegetables' => array('Vegetable', 'Fruit'),
        'Other Categories' => array('Palms', 'Cycads', 'Bromeliad', 'Aquatic', 'Succulent', 'Orchid', 'Bamboo')
    );

    return $plant_groups[$plantType] ?? get_all_plant_types();
}

function get_plant_recommendation_attr($plantTypes, $heightRange, $spreadRange, $foliageColour, $flowerColour) {
    global $db;

    $placeholders = implode(',', array_fill(0, count($plantTypes), '?'));

    $sql = "SELECT Botanical_name
            FROM water_plant
            WHERE Plant_type IN ($placeholders)";
    
    $conditions = array();
    $params = $plantTypes;
    
    if (!empty($heightRange)) {
        $conditions[] = "Height_ranges = ?";
        $params[] = $heightRange;
    }
    if (!empty($spreadRange)) {
        $conditions[] = "Spread_ranges = ?";
        $params[] = $spreadRange;
    }
    if (!empty($foliageColour)) {
        $conditions[] = "LOWER(Foliage_colour) LIKE ?";
        $params[] = '%' . strtolower($foliageColour) . '%';
    }
    if (!empty($flowerColour)) {
        $conditions[] = "LOWER(Flower_colour) LIKE ?";
        $params[] = '%' . strtolower($flowerColour) . '%';
    }
    
    if (!empty($conditions)) {
        $sql .= " AND " . implode(" AND ", $conditions);
    }
    
    $sql .= " AND IF_image = 'Yes' LIMIT 100";

    $stmt = $db->prepare($sql);

    $types = str_repeat('s', count($params));

    $stmt->bind_param($types, ...$params);
    
    $stmt->execute();
    $stmt->bind_result($botanicalName);

    $botanicalNames = array();
    while ($stmt->fetch()) {
        $botanicalNames[] = $botanicalName;
    }

    $stmt->close();

    return $botanicalNames;
}

function handle_get_plant_recommendation_attr() {
    try {
        $plantType = isset($_POST['plant-type']) ? $_POST['plant-type'] : '';
        $heightRange = isset($_POST['height-range']) ? $_POST['height-range'] : '';
        $spreadRange = isset($_POST['spread-range']) ? $_POST['spread-range'] : '';
        $foliageColour = isset($_POST['foliage-colour']) ? $_POST['foliage-colour'] : '';
        $flowerColour = isset($_POST['flower-colour']) ? $_POST['flower-colour'] : '';

        $plantTypes = getPlantGroup($plantType);
        $attr_botanical_names = get_plant_recommendation_attr($plantTypes, $heightRange, $spreadRange, $foliageColour, $flowerColour);
        $attr_plant_info = get_plant_info($attr_botanical_names);
        $response = json_encode($attr_plant_info);
        echo $response;

        wp_die();
    } catch (Exception $e) {
        echo json_encode(array('error' => $e->getMessage()));
        wp_die();
    }
}

add_action('wp_ajax_handle_get_plant_recommendation_attr', 'handle_get_plant_recommendation_attr');
add_action('wp_ajax_nopriv_handle_get_plant_recommendation_attr', 'handle_get_plant_recommendation_attr');


/**
 * Get water need for the plants.
 */

function get_climate_water($lon_lat_data) {
    $lon_lat_data['lon'] = floatval($lon_lat_data['lon']);
    $lon_lat_data['lat'] = floatval($lon_lat_data['lat']);

    $api_url = 'http://3.107.4.242:8001/climatewater';

    $headers = array(
        'Content-Type: application/json'
    );

    $curl = curl_init();

    curl_setopt_array($curl, array(
        CURLOPT_URL => $api_url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => "POST",
        CURLOPT_POSTFIELDS => json_encode($lon_lat_data),
        CURLOPT_HTTPHEADER => $headers,
    ));

    $climate_water = curl_exec($curl);

    if (curl_errno($curl)) {
        $error_msg = curl_error($curl);
        curl_close($curl);
        return "Error: " . $error_msg;
    }

    curl_close($curl);
    $climate_water_text = json_decode($climate_water, true);
    if ($climate_water_text === null) {
        return "Error decoding JSON data";
    }
    return $climate_water_text;
}

function get_water_needs($userInputName) {
    global $db;

    $cleanedInputName = preg_replace('/[^a-zA-Z0-9\s]/', '', $userInputName);

    if (empty($cleanedInputName)) {
        return array('error' => 'Please enter some letters to search.');
    }

    $likeParam = "%" . strtolower($cleanedInputName) . "%";

    $sql = "SELECT *
            FROM water_plant
            WHERE (LOWER(Botanical_name) LIKE ? OR LOWER(Common_name) LIKE ?) AND IF_image = 'Yes' AND Image_location LIKE '%.jpg'
            LIMIT 100";

    $stmt = $db->prepare($sql);

    $stmt->bind_param("ss", $likeParam, $likeParam);
    $stmt->execute();

    $result = $stmt->get_result();
    $plants = array();

    while ($row = $result->fetch_assoc()) {
        $plants[] = $row;
    }
    $stmt->close();

    return $plants;
}

function transfer_rainfall($txt, $type) {
    $database_water_needed = array(
        'aquatic environment' => 2500,
        '100 to 300mm' => 200,
        '300 to 600mm' => 450,
        '600 to 900mm' => 750,
        '900 to 1400mm' => 1150,
        '1400 to 2000mm' => 1700,
        '2000 to 2500mm' => 2250,
        '2500mm or more' => 2500
    );

    $climate_rainfall = array(
        "more than 1200mm" => 1200,
        "650-1200mm" => 900,
        "350-650mm" => 500,
        "0-350mm" => 200,
        "more than 800mm" => 1000,
        "500-800mm" => 650,
        "250-500mm" => 350
    );

    if ($type == 1) {
        return $database_water_needed[$txt];
    } elseif ($type == 2) {
        return $climate_rainfall[$txt];
    } else {
        return "Invalid type";
    }
}

function get_water_surplus($userInputName, $lon_lat_data) {
    $plants_get_water_needs = get_water_needs($userInputName);

    $climate_water_text = get_climate_water($lon_lat_data);
    $climate_water_text = $climate_water_text['seasonal_rainfall_water'];

    $result = array();
    if (!empty($plants_get_water_needs)) {
        $climate_water_value = transfer_rainfall($climate_water_text, 2);

        foreach ($plants_get_water_needs as $plant_get_water_needs) {
            $water_needs_text = $plant_get_water_needs['Water_needs'];
            $water_needs_value = transfer_rainfall($water_needs_text, 1);
            $water_surplus = max($water_needs_value - $climate_water_value, 0);
            $plant_get_water_needs['Water_surplus'] = $water_surplus;
            $result[] = $plant_get_water_needs;
        }
    } else {
        return "Climate water data not available or plant not found";
    }
    return $result;
}

function handle_water_needs() {
    try {
        $postcode = isset($_POST['postcode']) ? $_POST['postcode'] : '';
        $userInputName = isset($_POST['userInputName']) ? $_POST['userInputName'] : '';

        $plants_with_water_needs = get_water_surplus($userInputName, get_lon_lat($postcode));
        $response = json_encode($plants_with_water_needs);
        echo $response;

        wp_die();
    } catch (Exception $e) {
        echo json_encode(array('error' => $e->getMessage()));
        wp_die();
    }
}

add_action('wp_ajax_handle_water_needs', 'handle_water_needs');
add_action('wp_ajax_nopriv_handle_water_needs', 'handle_water_needs');
