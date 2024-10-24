<?php
include "../index.php";
$registerSchema = json_decode(file_get_contents('../schema/manifists/update.json'));
$request = json_decode(file_get_contents('php://input'));
$valid = json_decode(requestvalidateobject($request, $registerSchema));
include "../functions/siteFunctions.php";
$has_key = authorization();
if ($has_key) {
    $valid_key = authantication($dbobjx);
    if ($valid_key === true) {
        if ($valid->status) {
            try {
                $id = isset($request->manifist_id) ? $request->manifist_id : '';
                $station_id = isset($request->station_id) ? $request->station_id : '';
                $check = "UPDATE `manifists` SET `station_id`='$station_id' WHERE `id` = $id";
                $dbobjx->query($check);
                if ($dbobjx->execute()) {
                    echo response("1", "Success", "Manifist has been update");
                } else {
                    echo response("0", "Error", "Something went wrong while inserting manifist");
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