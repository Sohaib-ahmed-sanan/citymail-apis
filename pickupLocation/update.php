<?php
include "../index.php";
$registerSchema = json_decode(file_get_contents('../schema/pickup_locations/update.json'));
$request = json_decode(file_get_contents('php://input'));
$valid = json_decode(requestvalidateobject($request, $registerSchema));
include "../functions/siteFunctions.php";
$has_key = authorization();
if ($has_key) {
    $valid_key = authantication($dbobjx);
    if ($valid_key === true) {
        if ($valid->status) {
            try {
                $id = $request->id;
                $name = $request->name;
                $title = $request->title;
                $email = $request->email;
                $phone = $request->phone;
                $address = $request->address;
                $country_id = $request->country_id;
                $city_id = $request->city_id;
                $query = "UPDATE `pickup_locations` SET `name`='$name',`title`='$title',`email`='$email',`phone`='$phone',`address`='$address',`city_id`='$city_id',`country_id`='$country_id' WHERE `id` = $id";
                // print_r($query);die;
                $dbobjx->query($query);
                $dbobjx->execute();
                echo response("1", "Success", "Pickup location has been updated successfully !");
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