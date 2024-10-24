<?php
include "../index.php";
$registerSchema = json_decode(file_get_contents('../schema/stations/update.json'));
$request = json_decode(file_get_contents('php://input'));
$valid = json_decode(requestvalidateobject($request, $registerSchema));
include "../functions/siteFunctions.php";
$has_key = authorization();
if ($has_key) {
    $valid_key = authantication($dbobjx);
    if ($valid_key === true) {
        if ($valid->status) {
            $id = $request->id;
            $name = $request->name;
            $address = $request->address;
            $city_id = $request->city_id;
            $country_id = $request->country_id;
            try {
                $query = "UPDATE `stations` SET `name`='$name',`address`='$address',`country_id` = $country_id ,`city_id` = $city_id  WHERE `id` = $id";
                $dbobjx->query($query);
                $dbobjx->execute();
                echo response("1", "Success", "Station has been updated successfully!");
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