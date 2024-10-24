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
            $start_date = isset($request->start_date) ? $request->start_date : '';
            $end_date = isset($request->end_date) ? $request->end_date : '';
            $customer_acno = isset($request->customer_acno) ? $request->customer_acno : '';

            $query = "SELECT shipments.*, delivery_status.name AS status_name,cities.city AS destination_city
             FROM  `shipments` 
             LEFT JOIN `delivery_status` ON shipments.status = `delivery_status`.`id` 
             LEFT JOIN `cities` ON shipments.destination_city_id  = `cities`.`id` 
             WHERE shipments.company_id = '$company_id' AND shipments.is_deleted = 'N' 
                AND (
                    (shipments.status = '14' AND shipments.with_cashier = '1')
                    OR shipments.status = '16'
                )
             AND shipments.customer_acno = '$customer_acno'
             AND shipments.payment_status = '0'
             AND shipments.created_at BETWEEN '$start_date 00:00:00' AND '$end_date 23:59:59'";
            $dbobjx->query($query);
        
            $result = $dbobjx->resultset();
            
            if($dbobjx->rowCount() > 0)
            {
                $cn_numbers_arr = array_column($result,'consignment_no'); 
                $cn_numbers_str = implode(',',$cn_numbers_arr);
                $query = "SELECT `consignment_no` FROM `invoice_details` WHERE `consignment_no` IN($cn_numbers_str)";
                $dbobjx->query($query);
                $details = $dbobjx->resultset();
                $invalid_cn_numbers = array_column($details,'consignment_no'); 
                
                $filtered_shipments = array_filter($result, function ($shipment) use ($invalid_cn_numbers) {
                    return !in_array($shipment->consignment_no, $invalid_cn_numbers);
                });

                if(count($filtered_shipments) > 0)
                {
                    echo response("1", "Success", $filtered_shipments);
                }else{
                    echo response("0", "Error", "No data found");
                }
                $dbobjx->close();
                exit();
            }else{
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