<?php
include "../index.php";
$registerSchema = json_decode(file_get_contents('../schema/profile/single-subuser.json'));
$request = json_decode(file_get_contents('php://input'));
$valid = json_decode(requestvalidateobject($request, $registerSchema));
include "../functions/siteFunctions.php";
$has_key = authorization();
if ($has_key) {
    $valid_key = authantication($dbobjx);
    if ($valid_key === true) {
        if ($valid->status) {
            try {
                $emp_id = isset($request->employee_id) ? $request->employee_id : '';
                if ($emp_id != '') {
                    $query = "SELECT * FROM `employees` WHERE `id` = '$emp_id'";
                    $dbobjx->query($query);
                    $data = $dbobjx->single();
                    echo response("1", "Success", $data);
                } else {
                    echo response("0", "Error !", "Please provide all parameters");
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