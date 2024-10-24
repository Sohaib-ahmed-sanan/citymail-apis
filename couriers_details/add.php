<?php
include "../index.php";
$registerSchema = json_decode(file_get_contents('../schema/courier-details/add.json'));
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
                $courier_id = $request->courier_id;
                $account_title = $request->account_title;
                $account_no = $request->account_no;
                $user = $request->user;
                $api_Key = $request->api_Key;
                $password = $request->password;

                $query = "INSERT INTO `courier_details`(`courier_id`, `company_id`,`account_title`, `account_no`, `user`,`password`, `api_key`) 
                VALUES ('$courier_id','$company_id','$account_title','$account_no','$user','$password','$api_Key')";
                $dbobjx->query($query);
                if ($dbobjx->execute()) {
                    $account_id = $dbobjx->lastInsertId();
                    echo response("1", "Success", "Courier has been added successfully !");
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