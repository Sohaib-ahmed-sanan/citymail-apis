<?php
include "../index.php";
$request = json_decode(file_get_contents('php://input'));
include "../functions/siteFunctions.php";
$has_key = authorization();
if ($has_key) {
    $valid_key = authantication($dbobjx);
    if ($valid_key === true) {
        try {
            $created_by = get_user_id_header();
            $company_id = $request->company_id;
            $first_name = $request->name;
            $last_name = '';
            $user_name = $request->user_name;
            $phone = $request->phone;
            $email = $request->email;
            $country_id = $request->country_id;
            $city_id = $request->city_id;
            $department_id = $request->department_id;
            $address = $request->address;
            $password = $request->password;
            $token = $request->token;
            $salt = generatingSalt();
            $hashed_password = encryptString($salt, $password);
            $Query = "SELECT `id` FROM `users` WHERE BINARY `user_name`='$user_name'";
            $dbobjx->query($Query);
            $result = $dbobjx->single();
            $secret = randomString(50);
            if ($dbobjx->rowCount() === 0) {
                switch ($department_id) {
                    case '5':
                        $menues = "[2,9,11,12,13,15,16,17,31,28,24,25,26]";
                        break;
                        case '9':
                            $menues = "[2,9,13,18,19,25,22,23,25,36,37]";
                        break;
                    default:
                        break;
                }
                $query = "INSERT INTO  `employees` (`department_id`,`first_name`, `last_name`,`email`,`user_name`,`company_id`,`created_by`,`phone`,`address`,`country_id`,`city_id`) VALUES 
                ('$department_id','$first_name','$last_name','$email','$user_name','$company_id','$created_by','$phone','$address','$country_id','$city_id')";
                $dbobjx->query($query);
                if ($dbobjx->execute()) {
                    $employee_id = $dbobjx->lastInsertId();
                    $Query = "INSERT INTO `users`(`employee_id`,`parent_id`,`company_id`,`user_name`,`first_name`,`last_name`,`email`,`phone`,`address`,`country_id`,`city_id`,`token`, `secret_key`, `salt`, `password`)
                    VALUES ('$employee_id','$created_by','$company_id','$user_name','$first_name','$last_name','$email','$phone','$address','$country_id','$city_id','$token','$secret','$salt','$hashed_password')";
                    $dbobjx->query($Query);
                    if ($dbobjx->execute()) {
                        $account_id = $dbobjx->lastInsertId();
                        $rights_query = "INSERT INTO `user_menus`(`account_id`,`menue`) VALUES ('$account_id','$menues')";
                        $dbobjx->query($rights_query);
                        $dbobjx->execute();
                        echo response("1", "success", "User account has been created successfully");
                    }
                }
            }else {
                echo response("0", "error", "User with this username already exists.");
            }
        } catch (Exception $e) {
            echo response("0", "Api Error !", $e);
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
