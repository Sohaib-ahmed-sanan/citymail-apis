<?php
include "../index.php";
$request = json_decode(file_get_contents('php://input'));
include "../functions/siteFunctions.php";
$has_key = authorization();
if ($has_key) {
    $valid_key = authantication($dbobjx);
    if ($valid_key === true) {
        try {
            $company_id = isset($request->company_id) ? $request->company_id : '';
            if ($company_id != '') {
                $query = "SELECT employees.cnic_number,employees.cnic_img,employees.phone,employees.city_id,companies.name,companies.headoffice_address
                FROM 
                `companies`
                LEFT JOIN employees ON employees.company_id = companies.company_id
                WHERE companies.company_id = '$company_id'";
                $dbobjx->query($query);
                // print_r($query);die;
                $result = $dbobjx->single();
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