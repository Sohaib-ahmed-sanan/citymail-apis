<?php
include "../index.php";
$registerSchema = json_decode(file_get_contents('../schema/salesMan/add.json'));
$request = json_decode(file_get_contents('php://input'));
$valid = json_decode(requestvalidateobject($request, $registerSchema));
include "../functions/siteFunctions.php";
$has_key = authorization();
if ($has_key) {
    $valid_key = authantication($dbobjx);
    if ($valid_key === true) {
        // print_r($valid_key);die;
        if ($valid->status) {
            try {
                $first_name = $request->first_name;
                $last_name = $request->last_name;
                $email = $request->email;
                $phone = $request->phone;
                $company_id = $request->company_id;
                $user_name = $request->username;
                $password = $request->password;
                $salt = generatingSalt();
                $hashed_password = encryptString($salt, $password);
                $city_id = $request->city_id;
                $country_id = $request->country_id;
                $secret = randomString(50);
                $token = $request->token;
                $Query = "SELECT `id` FROM `users` WHERE `email`='$email' AND `company_id` = '$company_id'";
                $dbobjx->query($Query);
                $result = $dbobjx->single();
                if ($dbobjx->rowCount() === 0) {
                    $check_user = "SELECT `id` FROM `users`  WHERE BINARY `user_name`='$user_name'";
                    $dbobjx->query($check_user);
                    $check = $dbobjx->single();
                    if ($dbobjx->rowCount() === 0) {
                        $query = "SELECT `id` FROM `users` WHERE `parent_id` = '0' AND `employee_id` IS NOT NULL  AND `company_id` = $company_id";
                        $dbobjx->query($query);
                        $parent = $dbobjx->single();
                        $parent_id = $parent->id;
                        $Query = "INSERT INTO  `employees` (`department_id`,`first_name`, `last_name`,`email`,`phone`,`user_name`,`company_id`,`city_id`,`country_id`,`created_by`) VALUES 
                        ('3','$first_name','$last_name','$email','$phone','$user_name','$company_id',$city_id,'$country_id','$parent_id')";
                        $dbobjx->query($Query);
                        $dbobjx->execute();
                        $emp_id = $dbobjx->lastInsertId();
                        $Query = "INSERT INTO `users`(`employee_id`,`parent_id`,`company_id`,`user_name`,`first_name`,`last_name`,`email`,`phone`,`city_id`,`country_id`,`token`, `secret_key`, `salt`, `password`)
                        VALUES ('$emp_id','$parent_id','$company_id','$user_name','$first_name','$last_name','$email','$phone',$city_id,'$country_id','$token','$secret','$salt','$hashed_password')";
                        $dbobjx->query($Query);
                        if ($dbobjx->execute()) {
                            $account_id = $dbobjx->lastInsertId();
                            $rights_query = "INSERT INTO `user_menus`(`account_id`,`menue`) VALUES ('$account_id','[1,4]')";
                            $dbobjx->query($rights_query);
                            $dbobjx->execute();
                            echo response("1", "Success", "Sales person has been added successfully !");
                        }
                    } else {
                        echo response("0", "Username Error", "Username already taken please use different");
                    }
                } else {
                    echo response("0", "Email Error", "Email already taken please use different");
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