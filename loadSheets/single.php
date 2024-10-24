<?php
include "../index.php";
$request = json_decode(file_get_contents('php://input'));
$registerSchema = json_decode(file_get_contents('../schema/loadsheet/single.json'));
$request = json_decode(file_get_contents('php://input'));
$valid = json_decode(requestvalidateobject($request, $registerSchema));
include "../functions/siteFunctions.php";
$has_key = authorization();
if ($has_key == 200) {
    $valid_key = authantication($dbobjx);
    if ($valid_key == 200) {
        if ($valid->status) {
            try {
                $sheet_no = (isset($request->sheet_no) ? $request->sheet_no : '');
                $query = "SELECT *,pl.city_id As origin_city
                FROM `loadsheets` 
                LEFT JOIN `loadsheet_details` ON loadsheet_details.loadsheet_id = loadsheets.sheet_no 
                LEFT JOIN `shipments` ON shipments.consignment_no = loadsheet_details.cn_numbers 
                LEFT JOIN `customers` ON customers.acno = loadsheets.customer_acno 
                LEFT JOIN `pickup_locations` AS pl ON loadsheets.pickup_location_id = pl.id 
                WHERE loadsheets.sheet_no = '$sheet_no'";
                $dbobjx->query($query);
                $result = $dbobjx->resultset();
                echo response("1", "Success", $result);
            } catch (Exception $e) {
                echo response("0", "Api Error !", $e);
            }
        } else {
            echo response("0", "Error !", $valid->error);
        }
    } else {
        if ($valid_key == 401) {
            echo response("0", "Invalid Secret Key", "Secret key is incorect");
        } elseif ($valid_key == 404) {
            echo response("0", "Authantication faild", "Client Id is not correct");
        }
    }
} else {
    echo response("0", "Unauthorized", $has_key);
}