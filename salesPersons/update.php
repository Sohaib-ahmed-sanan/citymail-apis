<?php
include "../index.php";
$registerSchema = json_decode(file_get_contents('../schema/salesMan/update.json'));
$request = json_decode(file_get_contents('php://input'));
$valid = json_decode(requestvalidateobject($request, $registerSchema));
include "../functions/siteFunctions.php";
$has_key = authorization();
if ($has_key == 200) {
    $valid_key = authantication($dbobjx);
    if ($valid_key == 200) {
        if ($valid->status) {
            $id = $request->id;
            $first_name = isset($request->first_name) ? $request->first_name : '';
            $last_name = isset($request->last_name) ? $request->last_name : '';
            $email = isset($request->email) ? $request->email : '';
            $phone = isset($request->phone) ? $request->phone : '';
            $username = isset($request->user_name) ? $request->user_name : '';
            $city_id = isset($request->city_id) ? $request->city_id : '';
            $country_id = isset($request->country_id) ? $request->country_id : '';
            $more = '';
            $check = 1;
            try {
                if ($username != '') {
                    $avalible = check_user_name($dbobjx, $username);
                    if ($avalible == 200) {
                        $query = "UPDATE `users` SET `user_name` = '$username' WHERE `employee_id` = '$id'";
                        $dbobjx->query($query);
                        $dbobjx->execute();
                        $more = ",`user_name` = '$user_name'";
                    } else {
                        echo response("0", "Error", "Username already taken please use different");
                        $check = 0;
                    }
                }
                if ($check == 1) {
                    $query = "UPDATE `employees` SET `first_name`='$first_name',`last_name`='$last_name',`email`='$email',`phone`='$phone',`country_id` = '$country_id',`city_id`= $city_id $more WHERE `id` = $id";
                    $dbobjx->query($query);
                    if ($dbobjx->execute()) {
                        $query = "UPDATE `users` SET `first_name`='$first_name',`last_name`='$last_name',`email`='$email',`phone`='$phone',`country_id` = '$country_id',`city_id`= $city_id  WHERE `employee_id` = $id";
                        $dbobjx->query($query);
                        $dbobjx->execute();
                        $message = "Sales person info has been updated successfully !";
                        if (isset($request->password) && $request->password != '') {
                            $salt = generatingSalt();
                            $hashpassword = encryptString($salt, $password);
                            $query = "UPDATE `users` SET `salt`='$salt',`password`='$hashpassword' WHERE `employee_id` = '$id'";
                            $dbobjx->query($query);
                            $dbobjx->execute();
                            $message = "Sales person info and password has been updated successfully !";
                        }
                        echo response("1", "Success", $message);
                    } else {
                        echo response("0", "Error", "Could not process this request please try again");
                    }
                }

            } catch (Exception $e) {
                echo response("0", "Api Error !", $e);
            }

        } else {
            echo response("0", "Error !", $valid->error);
        }
    } else {
        if ($valid_key == 401) {
            echo response("0", "Invalid Secret Key", "Secret key is incorect");
        } elseif ($valid_key == 404) {
            echo response("0", "Authantication faild", "Client Id is not correct");
        }
    }
} else {
    echo response("0", "Unauthorized", $has_key);
}
