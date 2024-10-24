<?php
include "../index.php";
$registerSchema = json_decode(file_get_contents('../schema/salesMan/add.json'));
$request = json_decode(file_get_contents('php://input'));
$valid = json_decode(requestvalidateobject($request, $registerSchema));
include "../functions/siteFunctions.php";
$has_key = authorization();
if ($has_key) {
    $valid_key = authantication($dbobjx);
    if ($valid_key === true) {
        // if ($valid->status) {
            try {
                $id = $request->id;
                $courier_id = $request->courier_id;
                $account_title = $request->account_title;
                $account_no = $request->account_no;
                $user = $request->user;
                $api_Key = $request->api_Key;
                $password = isset($request->password) ? $request->password : '';
                $code = isset($request->code) ? $request->code : null;
                $more = "";
                if($password != '')
                {
                    $more = "`password`='$password'";
                }
                $query = "UPDATE `courier_details` SET `account_title`='$account_title',`account_no`='$account_no',`user`='$user',`api_key`='$api_Key' $more WHERE `id` = $id";
                $dbobjx->query($query);
                if ($dbobjx->execute()) {
                    $account_id = $dbobjx->lastInsertId();
                    echo response("1", "Success", "Courier has been updated successfully !");
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