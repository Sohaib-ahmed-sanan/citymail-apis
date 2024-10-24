<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
include "../index.php";
$request = json_decode(file_get_contents('php://input'));
include "../functions/siteFunctions.php";
$headers = getallheaders();
// print_r($headers);die;
$has_key = authorization();
if ($has_key) {
    $valid_key = authantication($dbobjx);
    if ($valid_key === true) {
        try {
            $company_id = $request->company_id;
            $query = "SELECT * FROM `companies` WHERE `company_id` = '$company_id' ";
            $dbobjx->query($query);
            $data = $dbobjx->single();
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
