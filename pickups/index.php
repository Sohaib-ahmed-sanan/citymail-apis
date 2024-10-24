<?php
include "../index.php";
$request = json_decode(file_get_contents('php://input'));
include "../functions/siteFunctions.php";
$has_key = authorization();
if ($has_key) {
    $valid_key = authantication($dbobjx);
    if ($valid_key === true) {
        try {
            $customer_acno = isset($request->customer_acno) ? $request->customer_acno : '';
            $compnay_id = isset($request->compnay_id) ? $request->compnay_id : '';
            $city_id = isset($request->city_id) ? $request->city_id : '';
            $status = isset($request->status) ? $request->status : '';
            $where = '';
            if ($customer_acno != null) {
                $where .= "AND loadsheets.customer_acno = $customer_acno";
            }
            if ($status != null) {
                $where .= "AND loadsheets.status = '$status'";
            }
            if ($status == null && $customer_acno == null) {
                $where = "AND loadsheets.status IS NULL";
            }
            if ($city_id != null) {
                $where = "AND pl.city_id = $city_id";
            }

            $query = "SELECT loadsheets.*,customers.business_name,customers.acno,customers.parent_id As parent_id,
                pickups.rider_id As asigned_rider,cities.city As pickup_city,pl.title As pickup_location 
                FROM `loadsheets` 
                LEFT JOIN `customers` ON loadsheets.customer_acno = `customers`.`acno`
                LEFT JOIN `pickups` ON loadsheets.sheet_no = `pickups`.`loadsheet_id`
                LEFT JOIN `pickup_locations` As pl ON loadsheets.pickup_location_id = pl.id
                LEFT JOIN `cities` ON pl.city_id = cities.id
                WHERE loadsheets.company_id = '$compnay_id'  $where ";
            // print_r($query);die;
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
