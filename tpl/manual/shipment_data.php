<?php
include "../../index.php";
$request = json_decode(file_get_contents('php://input'));
include "../../functions/siteFunctions.php";
$has_key = authorization();
if ($has_key) {
    $valid_key = authantication($dbobjx);
    if ($valid_key === true) {
        try {
            $company_id = $request->company_id;
            $cn_number = isset($request->cn_number) ? $request->cn_number : '';
          
            $query = "SELECT 
                shipments.*, 
                delivery_status.name AS status_name,
                destination.city AS destination_city,
                origin.city AS origin_city,
                cust.name AS customer_name
            FROM 
                `shipments`
            LEFT JOIN 
                `delivery_status` ON shipments.status = delivery_status.id
            LEFT JOIN 
                `cities` AS destination ON shipments.destination_city_id = destination.id
            LEFT JOIN 
                `cities` AS origin ON shipments.origin_city_id = origin.id
            LEFT JOIN 
                `customers` AS cust ON shipments.customer_acno = cust.acno
            WHERE 
                shipments.thirdparty_consignment_no = '$cn_number'";
            $dbobjx->query($query);
            $result = $dbobjx->single();
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