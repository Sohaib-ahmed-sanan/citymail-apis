<?php
include "../index.php";
$registerSchema = json_decode(file_get_contents('../schema/pickup_locations/add.json'));
$request = json_decode(file_get_contents('php://input'));
$valid = json_decode(requestvalidateobject($request, $registerSchema));
include "../functions/siteFunctions.php";
$has_key = authorization();
if ($has_key) {
    $valid_key = authantication($dbobjx);
    if ($valid_key === true) {
        if ($valid->status) {
            $company_id = $request->company_id;
            $name = $request->name;
            $title = $request->title;
            $email = $request->email;
            $phone = $request->phone;
            $address = $request->address;
            $country_id = $request->country_id;
            $city_id = $request->city_id;
            $customer_acno = $request->customer_acno;
            try {
                $query = "INSERT INTO `pickup_locations`(`company_id`,`customer_acno`,`name`,`title`,`email`, `address`,`country_id`,`city_id`,`phone`) VALUES ('$company_id','$customer_acno','$name','$title','$email','$address','$country_id','$city_id','$phone')";
                $dbobjx->query($query);
                $dbobjx->execute();
                echo response("1", "Success", "Pickup location has been added successfully !");
            } catch (Exception $e) {
                echo response("0", "Insersion Error !", $e);
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