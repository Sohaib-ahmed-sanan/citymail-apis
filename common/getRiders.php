<?php
include ("../index.php");
$request = json_decode(file_get_contents('php://input'));
include "../functions/siteFunctions.php";
$has_key = authorization();
if ($has_key) {
    $valid_key = authantication($dbobjx);
    if ($valid_key === true) {
        try {
            $company_id = $request->company_id;
            $account_type = $request->account_type;
            $user_id = $request->user_id;

            $query = "SELECT `city_id` FROM `users` WHERE `id` = '$user_id'";
            $dbobjx->query($query);
            $info = $dbobjx->single();
            $city_id = $info->city_id;

            $query = "SELECT `id`,`first_name`,`last_name` FROM `employees` WHERE `active` = '1' AND `is_deleted` = 'N' AND department_id = '2' AND `company_id` = '$company_id' AND `city_id` = '$city_id'";
            $dbobjx->query($query);
            $dbobjx->execute();
            echo json_encode($dbobjx->resultset());
        } catch (Exception $error) {
            echo response("0", "Error!", $error->getMessage());
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