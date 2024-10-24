<?php
include("../index.php");
$request = json_decode(file_get_contents('php://input'));
include "../functions/siteFunctions.php";
try {
    $city_id = isset($request->city_id) && $request->city_id != '' ? $request->city_id : '';
    $account_id = $request->customer_courier_id;
    $cn_number = isset($request->cn_number) ? $request->cn_number : '';
    $type = $request->type;
    $courier_id = $request->courier_id;
    if ($type == 'origin') {
        $query = "SELECT st.city_id
        FROM `arrivals_details` AS ad 
        LEFT JOIN arrivals On ad.arrival_id = arrivals.arrival_no
        LEFT JOIN stations AS st On st.id = arrivals.station_id
        WHERE ad.cn_numbers = '$cn_number' AND ad.arrival_type = '1'";
        $dbobjx->query($query);
        $res = $dbobjx->single();
        $city_id = $res->city_id;
    }
    $query = "SELECT cities.city,cm.courier_city_code,cm.courier_country_code As country_code,countries.country_name
        FROM `courier_mappings` AS cm
        LEFT JOIN `cities` On cities.id = cm.city_id 
        LEFT JOIN `countries` On countries.id = cities.country_id 
        WHERE cm.courier_id = '$courier_id'
        AND cm.city_id = '$city_id'
        AND cm.status = 'active'";
    $dbobjx->query($query);
    $data = $dbobjx->single();
    echo json_encode($data);
} catch (Exception $error) {
    echo response("0", "Error!", $error->getMessage());
}
