<?php
include "../index.php";
$request = json_decode(file_get_contents('php://input'));
include "../functions/siteFunctions.php";
$has_key = authorization();
if ($has_key) {
    $valid_key = authantication($dbobjx);
    if ($valid_key === true) {
        try {
            $company_id = (isset($request->company_id) ? $request->company_id : '');
            // print_r($company_id);die;
            if ($company_id != '') {
                $query = "SELECT id,business_name,acno FROM `customers` WHERE `company_id` = '$company_id' AND `is_deleted` = 'N' AND  `active` = '1'";
                $dbobjx->query($query);
                $result = $dbobjx->resultset();
                echo response("1", "Success", $result);
            } else {
                echo response("0", "Data error", "Please provide all parameters");
            }
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