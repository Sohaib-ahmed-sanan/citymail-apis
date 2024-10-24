<?php
include "../index.php";
$registerSchema = json_decode(file_get_contents('../schema/loadsheet/index.json'));
$request = json_decode(file_get_contents('php://input'));
$valid = json_decode(requestvalidateobject($request, $registerSchema));
include "../functions/siteFunctions.php";
$has_key = authorization();
if ($has_key == 200) {
    $valid_key = authantication($dbobjx);
    if ($valid_key == 200) {
        if ($valid->status) {
            try {
                $customer_acno = (isset($request->customer_acno) ? $request->customer_acno : '');
                $start_date = (isset($request->start_date) ? $request->start_date : '');
                $end_date = (isset($request->end_date) ? $request->end_date : '');
                $more = null;
                if (isset($start_date) && $start_date != '') {
                    $more = "AND loadsheets.created_at BETWEEN '$start_date 00:00:00' AND '$end_date 23:59:59'";
                }
                $query = "SELECT loadsheets.*,customers.parent_id,cities.city As pickup_city,pl.title As pickup_location 
                FROM `loadsheets` 
                LEFT JOIN `customers` ON loadsheets.customer_acno = customers.acno
                LEFT JOIN `pickup_locations` As pl ON loadsheets.pickup_location_id = pl.id
                LEFT JOIN `cities` ON pl.city_id = cities.id
                WHERE loadsheets.customer_acno IN ($customer_acno) $more";
                $dbobjx->query($query);
                $result = $dbobjx->resultset();
                if (isset($start_date) && $start_date != '') {
                    echo response("1", "Success", $result);
                } else {
                    echo json_encode($result);
                }
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
