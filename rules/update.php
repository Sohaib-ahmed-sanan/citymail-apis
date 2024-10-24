<?php
include "../index.php";
// $registerSchema = json_decode(file_get_contents('../schema/customers/single.json'));
$request = json_decode(file_get_contents('php://input'));
// $valid = json_decode(requestvalidateobject($request, $registerSchema));
include "../functions/siteFunctions.php";
$has_key = authorization();
if ($has_key) {
    $valid_key = authantication($dbobjx);
    if ($valid_key === true) {
        // if ($valid->status) {
        try {
            $company_id = $request->company_id;
            $customer_acno = isset($request->customer_acno) ? $request->customer_acno : NULL;
            $rule_id = isset($request->rule_id) ? $request->rule_id : '';
            $rule_title = isset($request->rule_title) ? $request->rule_title : '';
            $status = isset($request->status) ? $request->status : '';
            $pickup_id = isset($request->pickup_id) ? $request->pickup_id : '';
            $courier_id = isset($request->courier_id) ? $request->courier_id : '';
            $customer_courier_id = isset($request->customer_courier_id) ? $request->customer_courier_id : '';
            $service_code = isset($request->service_code) ? $request->service_code : '';

            $update = "UPDATE rules SET updated_at = NOW(),`service_code`='$service_code',`title`='$rule_title',`courier_id`='$courier_id',`account_id`='$customer_courier_id',`customer_acno`='$customer_acno',`pickup_id`='$pickup_id',`active`='$status' WHERE id = '$rule_id'";
            // print_r($update);die;
            $dbobjx->query($update);
            if ($dbobjx->execute()) {
                echo response("1", "Automation Updated Successfully!", []);
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
        // } else {
        //     echo response("0", "Error !", $valid->error);
        // }
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