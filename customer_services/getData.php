<?php
include "../index.php";
$request = json_decode(file_get_contents('php://input'));
include "../functions/siteFunctions.php";
$has_key = authorization();
if ($has_key == 200) {
    $valid_key = authantication($dbobjx);
    if ($valid_key == 200) {
        try {
            $company_id = $request->company_id;
            $customer_acno = isset($request->customer_acno) ? implode(',', $request->customer_acno) : '';
            $city_id = isset($request->city_id) ? implode(',', $request->city_id) : '';
            $status_id = isset($request->status_id) ? implode(',', $request->status_id) : '';
            $start_date = isset($request->start_date) ? $request->start_date : '';
            $end_date = isset($request->end_date) ? $request->end_date : '';
            $more = "AND shipments.created_at BETWEEN '$start_date 00:00:00' AND '$end_date 23:59:59'";
            
            if($customer_acno != '')
            {   
                $more .= " AND shipments.customer_acno IN ($customer_acno) ";
            }
            if($city_id != '')
            {
                $more .= " AND shipments.destination_city_id IN ($city_id) ";
            }
            if($status_id != '')
            {
                $more .= " AND shipments.status IN ($status_id) ";
            }
            $query = "SELECT shipments.*, delivery_status.name AS status_name, cities.city As destination_city
            FROM  `shipments`
            LEFT JOIN `delivery_status` ON shipments.status = `delivery_status`.`id` 
            LEFT JOIN `cities` ON shipments.destination_city_id = `cities`.`id` 
            WHERE shipments.company_id = '$company_id' AND shipments.is_deleted = 'N' $more
            ";
            $dbobjx->query($query);
            $result = $dbobjx->resultset();
            if ($dbobjx->rowCount() > 0) {
                echo response("1", "Success", $result);
            } else {
                echo response("0", "Error", "No data found");
            }
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