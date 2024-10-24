<?php
include "../index.php";
$request = json_decode(file_get_contents('php://input'));
include "../functions/siteFunctions.php";
$has_key = authorization();
if ($has_key) {
    $valid_key = authantication($dbobjx);
    if ($valid_key === true) {
        try {
            $id = $request->id;
            $query = "SELECT courier_code.*,courier_details.account_title FROM `courier_code`
    LEFT JOIN courier_details ON courier_details.id = courier_code.account_id
    WHERE courier_code.account_id = '$id' ";
            $dbobjx->query($query);
            $data = $dbobjx->resultset();
            echo response("1", "Success", $data);
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