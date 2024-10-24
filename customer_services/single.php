<?php
include "../index.php";
$request = json_decode(file_get_contents('php://input'));
include "../functions/siteFunctions.php";
$has_key = authorization();
if ($has_key == 200) {
    $valid_key = authantication($dbobjx);
    if ($valid_key == 200) {
        try {
            $cn_no = $request->cn_no;
            $query = "SELECT shipments.*, delivery_status.name AS status_name, cities.city As destination_city, orign.city As origin_city,
            pickup_locations.name As shipper_name , pickup_locations.phone As shipper_phone,pickup_locations.address As shipper_address
            FROM  `shipments`
            LEFT JOIN `delivery_status` ON shipments.status = `delivery_status`.`id` 
            LEFT JOIN `pickup_locations` ON shipments.pickup_location_id = `pickup_locations`.`id` 
            LEFT JOIN `cities` ON shipments.destination_city_id = `cities`.`id` 
            LEFT JOIN `cities` As orign ON pickup_locations.city_id = orign.`id` 
            WHERE shipments.consignment_no IN($cn_no) AND shipments.is_deleted = 'N'";
            // die;
            $dbobjx->query($query);
            $result = $dbobjx->resultset();
            echo response("1", "Success", $result);
        } catch (Exception $e) {
            echo response("0", "Api Error !", $e);
        }
    } else {
        if ($valid_key === 401) {
            echo response("0", "Invalid Secret Key", "Secret key is incorect");
        } elseif ($valid_key === 404) {
            echo response("0", "Authantication faild", "Client Id is not correct");
        }
    }
} else {
    echo response("0", "Unauthorized", $has_key);
}