<?php
include "../../index.php";
$registerSchema = json_decode(file_get_contents('../../schema/customers/single-subAccount.json'));
$request = json_decode(file_get_contents('php://input'));
$valid = json_decode(requestvalidateobject($request, $registerSchema));
include "../../functions/siteFunctions.php";
$has_key = authorization();
if ($has_key) {
    $valid_key = authantication($dbobjx);
    if ($valid_key === true) {
        if ($valid->status) {
            try {
                $acno = isset($request->acno) ? $request->acno : '';
                if ($acno != '') {
                    $query = "SELECT sub_accounts.*,user_menus.menue
                     FROM `customers` As sub_accounts 
                     LEFT JOIN `users` On users.acno = sub_accounts.acno
                     LEFT JOIN `user_menus` On user_menus.account_id = users.id
                     WHERE sub_accounts.acno = '$acno' AND sub_accounts.is_deleted = 'N'";
                    $dbobjx->query($query);
                    // print_r($query);die;
                    $data = $dbobjx->single();
                    echo response("1", "Success", $data);
                } else {
                    echo response("0", "Error !", "Please provide required data");
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