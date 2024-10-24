<?php
include "../index.php";
$request = json_decode(file_get_contents('php://input'));
$registerSchema = json_decode(file_get_contents('../schema/pickup_locations/get.json'));
$valid = json_decode(requestvalidateobject($request, $registerSchema));
include "../functions/siteFunctions.php";
$has_key = authorization();
if ($has_key == 200) {
    $valid_key = authantication($dbobjx);
    // print_r($valid_key);die;
    if ($valid_key === true) {
        if ($valid->status) {
            $company_id = (isset($request->company_id) ? $request->company_id : '');
            $start_date = (isset($request->start_date) ? $request->start_date : '');
            $end_date = (isset($request->end_date) ? $request->end_date : '');
            $customer_acno = (isset($request->customer_acno) ? $request->customer_acno : '');
            $city_id = (isset($request->city_id) ? $request->city_id : '');
            // die;
            $headers = getallheaders();
            list($acc_type, $secret_key) = explode("%", $headers['Api-Key']);
            list($key, $timestamp) = explode(":", $secret_key);
            list($num, $company_id) = explode("!", $key);
            $acc_type = base64_decode($acc_type);
            if (in_array($acc_type, ['6', '7', '8'])) {
                if (!isset($request->customer_acno)) {
                    echo response("0", "Error", "Please provide customer account number");
                    exit();
                }
                $company_id = $company_id;
                $select = "";
            }else{
                $select = ",customers.parent_id As parent_id";
            }
            $type = (isset($request->type) ? $request->type : '');
            try {
                if (isset($start_date) && $start_date != '') {
                    $more = "AND p.created_at BETWEEN '$start_date 00:00:00' AND '$end_date 23:59:59'";
                } else {
                    $more = "AND p.active = '1'";
                }
                if ($customer_acno != '') {
                    $more .= "AND p.customer_acno IN ($customer_acno)";
                }
                if ($city_id != '') {
                    $more .= "AND p.city_id IN ($city_id)";
                }
                $query = "SELECT p.*,cities.city,customers.parent_id,customers.business_name As customer_business_name,customers.acno As customer_acno $select
                FROM `pickup_locations` p 
                LEFT JOIN `customers` ON p.customer_acno = customers.acno
                LEFT JOIN `cities` ON p.city_id = `cities`.`id` 
                WHERE p.company_id = '$company_id' AND p.is_deleted = 'N' $more";
                $dbobjx->query($query);
                $result = $dbobjx->resultset();
                if ($dbobjx->rowCount() > 0) {
                    echo response("1", "Success", $result);
                }else{
                    echo response("0", "Error", "Incorect account number no data found");
                }
            } catch (Exception $e) {
                echo response("0", "Api Error !", $e);
            }
        } else {
            echo response("0", "Error !", $valid->error);
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
