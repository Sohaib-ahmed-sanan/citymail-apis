<?php
include ("../index.php");
$registerSchema = json_decode(file_get_contents('../schema/auth/updateProfle.json'));
$request = json_decode(file_get_contents('php://input'));
$valid = json_decode(requestvalidateobject($request, $registerSchema));
include "../functions/siteFunctions.php";
$has_key = authorization();
if ($has_key) {
    $valid_key = authantication($dbobjx);
    if ($valid_key === true) {
        try {
            $id = $request->id;
            $first_name = $request->first_name;
            $last_name = $request->last_name;
            $country = $request->country;
            $city = $request->city;
            $zip = $request->zip;
            $phone = $request->phone;
            $phone2 = $request->phone2;
            $ntn = $request->ntn;
            $cnic = $request->cnic;
            $ntn_image = $request->ntn_image;
            $cnic_image = $request->cnic_image;

            $query = "UPDATE `users` SET `first_name`='$first_name',`last_name`='$last_name',`phone`='$phone',`city_id`='$city',`country_id` = '$country' WHERE `id` = '$id'";
            $dbobjx->query($query);
            if ($dbobjx->execute()) {
               $query = "SELECT `employee_id` FROM `users` WHERE `id` = '$id'";
                $dbobjx->query($query);
                $data = $dbobjx->single();
                $query = "UPDATE `employees` SET `first_name`='$first_name',`last_name`='$last_name',`ntn_number`='$ntn',`cnic_number`='$cnic',`phone`='$phone',`phone2`='$phone2',`country_id` = '$country',`city_id`='$city',`zip`='$zip',`cnic_img`='$cnic_image',`ntn_img`='$ntn_image' WHERE `id` = '$data->employee_id'";
                $dbobjx->query($query);
                if ($dbobjx->execute()) {
                    echo response("1", "Company profile has been updated successfully");
                } 
            }
            else {
                echo response("0", "Something went wrong");
            }

            $dbobjx->close();
        } catch (Exception $error) {
            echo response("0", "Api Error!", $error->getMessage());
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