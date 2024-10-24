<?php
include ("../index.php");
$request = json_decode(file_get_contents('php://input'));
include "../functions/siteFunctions.php";
$has_key = authorization();
if ($has_key) {
    $valid_key = authantication($dbobjx);
    if ($valid_key === true) {
        try {
            $query = "SELECT * FROM `couriers` WHERE `active` = '1'";
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