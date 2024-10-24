<?php
include "../../index.php";
$registerSchema = json_decode(file_get_contents('../../schema/customers/update-subAccounts.json'));
$request = json_decode(file_get_contents('php://input'));
$valid = json_decode(requestvalidateobject($request, $registerSchema));
include "../../functions/siteFunctions.php";
$has_key = authorization();
if ($has_key) {
    $valid_key = authantication($dbobjx);
    if ($valid_key === true) {
        if ($valid->status) {
            try {
                $acno = $request->acno;
                $name = isset($request->name) ? $request->name : '';
                $phone = isset($request->phone) ? $request->phone : '';
                $country_id = isset($request->country_id) ? $request->country_id : '';
                $city_id = isset($request->city_id) ? $request->city_id : '';
                $user_name = isset($request->user_name) ? $request->user_name : '';
                $email = isset($request->email) ? $request->email : '';
                $cnic = isset($request->cnic) ? $request->cnic : '';
                $ntn = isset($request->ntn) ? $request->ntn : '';
                $business_name = isset($request->business_name) ? $request->business_name : '';
                $address = isset($request->address) ? $request->address : '';
                $password = isset($request->password) ? $request->password : '';
                $rights = isset($request->rights) ? $request->rights : '';
                $more = '';
                 
                if ($user_name != '') {
                    $check_availiblity = check_user_name($dbobjx, $user_name, $id);
                    if ($check_availiblity == 200) {
                        $more = ",`user_name` = '$user_name'";
                    } else {
                        echo response("0", "Username exist", "Username already exist please use different");
                        die;
                    }
                }
                if ($password != '') {
                    $salt = generatingSalt();
                    $hashpassword = encryptString($salt, $password);
                    $update = "UPDATE `users` SET `salt`='$salt',`password`='$hashpassword' WHERE `acno` = '$acno'";
                    $dbobjx->query($update);
                    $dbobjx->execute();
                }
                $query = "UPDATE `customers` SET `name`='$name',`business_name`='$business_name',`email`='$email',`phone`='$phone',`address`='$address',`cnic`='$cnic',`ntn`='$ntn',`country_id`='$country_id',`city_id`='$city_id' $more WHERE `acno` = '$acno'";
                // print_r($query);die;
                $dbobjx->query($query);
                if ($dbobjx->execute()) {
                    $query = "UPDATE `users` SET `first_name`='$name',`phone`='$phone',`address`='$address',`country_id`='$country_id',`city_id`='$city_id' $more WHERE `acno` = '$acno'";
                    $dbobjx->query($query);
                    $dbobjx->execute();
                    $query = "SELECT `id` FROM `users` WHERE `acno` = '$acno'";
                    $dbobjx->query($query);
                    $get_id = $dbobjx->single();
                    $update_menu = "UPDATE `user_menus` SET `menue`='$rights' WHERE `account_id` = '$get_id->id'";
                    $dbobjx->query($update_menu);
                    $dbobjx->execute();
                }
                echo response("1", "Success", "Sub account has been updated successfully");
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