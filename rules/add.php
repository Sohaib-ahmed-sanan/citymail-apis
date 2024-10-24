<?php
include "../index.php";
$registerSchema = json_decode(file_get_contents('../schema/rules/add.json'));
$request = json_decode(file_get_contents('php://input'));
$valid = json_decode(requestvalidateobject($request, $registerSchema));
include "../functions/siteFunctions.php";
$has_key = authorization();
if ($has_key) {
    $valid_key = authantication($dbobjx);
    if ($valid_key === true) {
        if ($valid->status) {
            try {
                $company_id = $request->company_id;

                $rule_title = isset($request->rule_title) ? $request->rule_title : '';
                $customer_acno = isset($request->customer_acno) ? $request->customer_acno : NULL;
                $rules_status = isset($request->rules_status) ? $request->rules_status : '';
                $pickup_id = isset($request->pickup_id) ? $request->pickup_id : '';
                $courier_id = isset($request->courier_id) ? $request->courier_id : '';
                $customer_courier_id = isset($request->customer_courier_id) ? $request->customer_courier_id : '';
                $service_code = isset($request->service_code) ? $request->service_code : '';
                $status_ids = isset($request->status_ids) ? $request->status_ids : '';

                $weight_type = isset($request->weight_type) ? $request->weight_type : "";
                $weight_value = isset($request->weight_value) ? $request->weight_value : "";
                $payment_method_id = isset($request->payment_method_id) ? $request->payment_method_id : "";
                $destination_city_id = isset($request->destination_city_id) ? $request->destination_city_id : "";
                $destination_country = isset($request->destination_country) ? $request->destination_country : "";
                $order_type = ($request->order_type !== "undefined") ? $request->order_type : "";
                $order_value = isset($request->order_value) ? $request->order_value : "";
                $query = "SELECT * FROM rules WHERE company_id = '$company_id' AND 
            courier_id = '$courier_id' AND account_id = '$customer_courier_id' AND title='$rule_title' AND service_code='$service_code' AND pickup_id='$pickup_id'";
                $dbobjx->query($query);
                $dbobjx->single();
                $result = $dbobjx->rowCount();
                if ($result > 0) {
                    echo response("0", "Automation Already Exist", []);
                    exit;
                }

                $query = "INSERT INTO rules (`company_id`,`title`,`courier_id`,`account_id`,`service_code`,`customer_acno`,`status_id`,`pickup_id`,`insurance`,`fragile`)
         VALUES ('$company_id','$rule_title','$courier_id','$customer_courier_id','$service_code','$customer_acno','$status_ids','$pickup_id','0','0')";
                // print_r($query);die;
                $dbobjx->query($query);
                $dbobjx->execute();
                $rule_id = $dbobjx->lastInsertId();
                $condition = '';
                if ($payment_method_id != '') {
                    $condition .= ", payment_method_id='$payment_method_id'";
                }
                if ($weight_type != '') {
                    $condition .= ", weight_type='$weight_type' , weight_value='$weight_value'";
                }
                if ($order_type != '') {
                    $condition .= ", order_type='$order_type' , order_value='$order_value'";
                }
                if ($destination_city_id != '') {
                    $condition .= ", destination_city_id='$destination_city_id'";
                }
                if ($destination_country != '') {
                    $condition .= ", destination_country='$destination_country'";
                }
                $update = "UPDATE rules SET updated_at = NOW() $condition WHERE id = $rule_id";
                $dbobjx->query($update);
                if ($dbobjx->execute()) {
                    echo response("1", "Automation Added Successfully!", []);
                    $dbobjx->close();
                    return false;
                } else {
                    $dbobjx->close();
                    echo response("0", "Error", []);
                    return false;
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