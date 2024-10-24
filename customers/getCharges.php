<?php
include "../index.php";
$registerSchema = json_decode(file_get_contents('../schema/customers/getCharges.json'));
$request = json_decode(file_get_contents('php://input'));
$valid = json_decode(requestvalidateobject($request, $registerSchema));
include "../functions/siteFunctions.php";
$has_key = authorization();
if ($has_key) {
    $valid_key = authantication($dbobjx);
    if ($valid_key === true) {
        if ($valid->status) {
            try {
                $acno = isset($request->acno) ? $request->acno : '';
                $query = "SELECT * FROM `customer_tariffs` WHERE `customer_acno` = '$acno' AND `active` = '1' AND `is_deleted` = 'N'";
                $dbobjx->query($query);
                $tariffs = $dbobjx->resultset();
                $query = "SELECT * FROM `customer_cash_handling_charges` WHERE `customer_acno` = '$acno' AND `active` = '1' AND `is_deleted` = 'N'";
                $dbobjx->query($query);
                $cash_handling = $dbobjx->resultset();
                $query = "SELECT * FROM `customer_additional_charges` WHERE `customer_acno` = '$acno' AND `active` = '1' AND `is_deleted` = 'N'";
                $dbobjx->query($query);
                $additional_charges = $dbobjx->resultset();
                $data = [
                    "tarifs" => $tariffs,
                    "cash_handling" => $cash_handling,
                    "additional_charges" => $additional_charges,
                ];
                echo json_encode($data);
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