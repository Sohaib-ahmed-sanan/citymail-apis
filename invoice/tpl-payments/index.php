<?php
include "../../index.php";
$request = json_decode(file_get_contents('php://input'));
include "../../functions/siteFunctions.php";
$has_key = authorization();
if ($has_key == 200) {
    $valid_key = authantication($dbobjx);
    if ($valid_key == 200) {
        try {
            $company_id = $request->company_id;
            $start_date = isset($request->start_date) ? $request->start_date : '';
            $end_date = isset($request->end_date) ? $request->end_date : '';
            $customer_acno = isset($request->customer_acno) ? $request->customer_acno : '';
            $more = "";
            if (isset($start_date) && $start_date != '') {
                $more .= "AND shipments.created_at BETWEEN '$start_date 00:00:00' AND '$end_date 23:59:59'";
            }
            if (isset($customer_acno) && $customer_acno != '') {
                $ids = implode($customer_acno);
                $more .= "AND shipments.customer_acno IN ($ids)";
            }

            $query = "SELECT shipments.*,customers.name As customer_name,pl.name as shipper_name,ds.name As status_name
            FROM `shipments` 
            LEFT JOIN `customers` ON shipments.customer_acno = customers.acno 
            LEFT JOIN `pickup_locations` AS pl ON shipments.pickup_location_id = pl.id 
            LEFT JOIN `delivery_status` As ds ON shipments.status = ds.id 
            WHERE shipments.company_id = '$company_id' AND shipments.is_deleted = 'N' AND shipments.with_cashier = '0' AND shipments.status IN ('14','15')$more";
            // WHERE shipments.company_id = '$company_id' AND shipments.is_deleted = 'N' AND shipments.status IN ('14','15') $more";

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