<?php
include "../index.php";
$registerSchema = json_decode(file_get_contents('../schema/route/update.json'));
$request = json_decode(file_get_contents('php://input'));
$valid = json_decode(requestvalidateobject($request, $registerSchema));
include "../functions/siteFunctions.php";
$has_key = authorization();
if ($has_key) {
    $valid_key = authantication($dbobjx);
    if ($valid_key === true) {
        if ($valid->status) {
            $id = $request->id;
            $address = $request->address;
            $country_id = $request->country_id;
            $city_id = $request->city_id;
            try {
                $rider_id = isset($request->rider_id) != '' ? $request->rider_id : '';
                $query = "UPDATE `routes` SET `address`='$address',`country_id` = '$country_id',`city_id` = '$city_id',`updated_at` = CURRENT_TIMESTAMP() WHERE `id` = '$id'";
                $dbobjx->query($query);
                $dbobjx->execute();
                echo response("1", "Success", "Route has been updated successfully !");
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