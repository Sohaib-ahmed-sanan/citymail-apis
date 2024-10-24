<?php
include "../index.php";
$registerSchema = json_decode(file_get_contents('../schema/riders/add.json'));
$request = json_decode(file_get_contents('php://input'));
$valid = json_decode(requestvalidateobject($request, $registerSchema));
include "../functions/siteFunctions.php";
$has_key = authorization();
if ($has_key) {
    $valid_key = authantication($dbobjx);
    if ($valid_key === true) {
        if ($valid->status) {
            try {
                $first_name = $request->first_name;
                $last_name = $request->last_name;
                $email = $request->email;
                $phone = $request->phone;
                $address = $request->address;
                $company_id = $request->company_id;
                $user_name = $request->user_name;
                $station_id = $request->station_id;
                $city_id = $request->city_id;
                $country_id = $request->country_id;
                $password = $request->password;
                $salt = generatingSalt();
                $hashpassword = encryptString($salt, $password);
                $secret = randomString(50);
                $token = $request->token;
                // check if exist
                $Query = "SELECT `id` FROM employees WHERE `email`='$email' AND `company_id` = '$company_id'";
                $dbobjx->query($Query);
                $result = $dbobjx->single();
                $token = bin2hex(random_bytes(15));
                if ($dbobjx->rowCount() === 0) {
                    $check_user = "SELECT `id` FROM `users` WHERE BINARY `user_name`='$user_name'";
                    $dbobjx->query($check_user);
                    $check = $dbobjx->single();
                    if ($dbobjx->rowCount() === 0) {
                        $query = "SELECT `id` FROM `users` WHERE `parent_id` = '0' AND `employee_id` IS NOT NULL  AND `company_id` = $company_id";
                        $dbobjx->query($query);
                        $parent = $dbobjx->single();
                        $parent_id = $parent->id;
                        $query = "INSERT INTO `employees`(`company_id`,`station_id`,`department_id`,`user_name`,`first_name`,`last_name`, `email`, `phone`, `address`,`country_id`,`city_id`,`created_by`) VALUES
                        ($company_id,$station_id,'2','$user_name','$first_name','$last_name','$email','$phone','$address','$country_id',$city_id,$parent_id)";
                        $dbobjx->query($query);
                        if ($dbobjx->execute()) {
                            $account_id = $dbobjx->lastInsertId();
                            $Query = "INSERT INTO `users`(`employee_id`,`parent_id`,`company_id`,`user_name`,`first_name`,`last_name`,`email`,`phone`,`country_id`,`city_id`, `token`, `secret_key`, `salt`, `password`)
                            VALUES ('$account_id',$parent_id,'$company_id','$user_name','$first_name','$last_name','$email','$phone','$country_id',$city_id,'$token','$secret','$salt','$hashpassword')";
                            $dbobjx->query($Query);
                            $dbobjx->execute();
                            echo response("1", "Success", "Rider has been added successfully");
                        }
                    } else {
                        echo response("0", "Username Error", "Username already taken please use different");
                    }
                } else {
                    echo response("0", "Error !", "Rider email already exist.");
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