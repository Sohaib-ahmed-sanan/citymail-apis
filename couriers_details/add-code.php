<?php
include "../index.php";
$registerSchema = json_decode(file_get_contents('../schema/courier-details/add-code.json'));
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
                $account_id = $request->account_id;
                $code = isset($request->code) ? $request->code : null;
                $query = "INSERT INTO `courier_code`(`company_id`, `account_id`,`code`) VALUES ('$company_id','$account_id','$code')";
                $dbobjx->query($query);
                if ($dbobjx->execute()) {
                    echo response("1", "Success", "Courier code has been added successfully !");
                } else {
                    echo response("0", "Error", "something went wrong");
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