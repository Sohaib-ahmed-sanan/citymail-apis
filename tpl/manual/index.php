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
            $start_date = isset($request->start_date) ? $request->start_date : '';
            $end_date = isset($request->end_date) ? $request->end_date : '';
            $type = isset($request->type) ? $request->type : '';
            $cn_numbers = isset($request->cn_numbers) ? $request->cn_numbers : '';
            $third_party = isset($request->third_party) ? $request->third_party : '';
            $customer_acno = isset($request->customer_acno) ? implode(',',$request->customer_acno) : '';
            $courier_id = isset($request->courier_id) ? implode(',',$request->courier_id) : '';
            $more = " WHERE shipments.company_id = '$company_id'";
            if ($type != '') {
                $more = " WHERE shipments.consignment_no IN ($cn_numbers) ";
            }
            if ($third_party === '' || $third_party === '0') {
                $more .= " AND shipments.thirdparty_consignment_no IS NULL AND shipments.status IN(4)";
            }
            if ($third_party === '1') {
                $more .= " AND shipments.thirdparty_consignment_no IS NOT NULL AND shipments.thirdparty_consignment_no != '0'";
            }
            if ($courier_id != '') {
                $more .= " AND shipments.account_id IN ($courier_id)";
            }
            if ($customer_acno != '') {
                $more .= " AND shipments.customer_acno IN ($customer_acno)";
            }
            // Final query
          $query = "SELECT shipments.*, delivery_status.name AS status_name,cities.city As destination_city 
          FROM  `shipments` 
          LEFT JOIN `delivery_status` ON shipments.status = `delivery_status`.`id` 
          LEFT JOIN `cities` ON shipments.destination_city_id = cities.id
          $more";
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