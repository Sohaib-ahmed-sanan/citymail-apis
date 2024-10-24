<?php
include "../index.php";
$registerSchema = json_decode(file_get_contents('../schema/riders/update.json'));
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
                $first_name = isset($request->first_name) != '' ? $request->first_name : '';
                $last_name = isset($request->last_name) != '' ? $request->last_name : '';
                $email = isset($request->email) != '' ? $request->email : '';
                $phone = isset($request->phone) != '' ? $request->phone : '';
                $address = isset($request->address) != '' ? $request->address : '';
                $company_id = isset($request->company_id) != '' ? $request->company_id : '';
                $user_name = isset($request->user_name) != '' ? $request->user_name : '';
                $station_id = isset($request->station_id) != '' ? $request->station_id : '';
                $city_id = isset($request->city_id) != '' ? $request->city_id : '';
                $country_id = isset($request->country_id) != '' ? $request->country_id : '';
                $password = isset($request->password) ? $request->password : '';
                $user_name = isset($request->user_name) ? $request->user_name : '';
                $more = "";
                $emp = "";
                if ($password != '') {
                    $salt = generatingSalt();
                    $hashpassword = encryptString($salt, $password);
                    $more = ", `password` = '$hashpassword', `salt` = '$salt'";
                }
                if ($user_name != '') {
                    $avalible = check_user_name($dbobjx, $user_name);
                    if ($avalible == 200) {
                        $query = "UPDATE `users` SET `user_name` = '$user_name' WHERE `employee_id` = '$id'";
                        $dbobjx->query($query);
                        $dbobjx->execute();
                        $emp = ", `user_name` = '$user_name'";
                    } else {
                        echo response("0", "Error", "Username already taken please use different");
                        $check = 0;
                    }
                }
                $query = "UPDATE `employees` SET `station_id`='$station_id',`first_name`='$first_name',`last_name`='$last_name',`email`='$email',`phone`='$phone',`country_id` = $country_id,`city_id`='$city_id',`address`='$address' $emp WHERE `id` = $id";
                $dbobjx->query($query);
                if($dbobjx->execute())
                {
                    $query = "UPDATE `users` SET `first_name`='$first_name',`last_name`='$last_name',`email`='$email',`phone`='$phone',`country_id` = $country_id,`city_id`= $city_id $more WHERE `employee_id` = $id";
                    $dbobjx->query($query);
                    $dbobjx->execute();
                }
                echo response("1", "Success", "Rider has been updated successfully !");
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