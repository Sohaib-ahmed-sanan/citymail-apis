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
            $more = "AND s.created_at BETWEEN '$start_date 00:00:00' AND '$end_date 23:59:59'";
            if($customer_acno != '')
            {   
                $more .= " AND s.customer_acno IN ($customer_acno) ";
            }
            if($city_id != '')
            {
                $more .= " AND s.destination_city_id IN ($city_id) ";
            }
            if($status_id != '')
            {
                $more .= " AND s.status IN ($status_id) ";
            }
            $query = "SELECT s.*, delivery_status.name AS status_name, 
            destination.city As destination_city,origin.city As origin_city,s.created_at As booking_date,
            ad.created_at As arrival_date,invoice.invoice_no
            FROM  `shipments` As s
            LEFT JOIN `delivery_status` ON s.status = `delivery_status`.`id` 
            LEFT JOIN `cities` As destination ON s.destination_city_id = `destination`.`id` 
            LEFT JOIN `pickup_locations` As pl ON s.pickup_location_id = `pl`.`id` 
            LEFT JOIN `cities` As origin ON pl.city_id = `origin`.`id` 
            LEFT JOIN `arrivals_details` As ad ON s.consignment_no = `ad`.`cn_numbers` 
            LEFT JOIN `invoice_details` As invd ON s.consignment_no = `invd`.`consignment_no` 
            LEFT JOIN `cbc_invoice` As invoice ON invd.invoice_id = `invoice`.`id` 
            WHERE s.company_id = '$company_id' AND s.is_deleted = 'N' $more
            ";
            $dbobjx->query($query);
            $result = $dbobjx->resultset();
            if ($dbobjx->rowCount() > 0) {
                echo response("1", "Success", $result);
            } else {
                echo response("0", "Error", "No data found");
            }
            $dbobjx->close();
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