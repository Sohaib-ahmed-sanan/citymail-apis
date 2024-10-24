<?php
include ("../index.php");
$registerSchema = json_decode(file_get_contents('../schema/auth/login.json'));
$request = json_decode(file_get_contents('php://input'));
$valid = json_decode(requestvalidateobject($request, $registerSchema));
try{
    $user_name = $request->user_name;
    $password = $request->password;
    $query = "SELECT `id`, `acno`, `employee_id`, `company_id`, `parent_id`, `user_name`, `first_name`, `last_name`, `phone`, `email`, `address`, `city_id`,`token`, `secret_key`, `salt`, `password` FROM `users` WHERE BINARY `user_name`='$user_name'";
    $dbobjx->query($query);
    $result = $dbobjx->single();
    if ($dbobjx->rowCount() > 0) {
        $type = null;
        $get_info = null;
        if ($result->employee_id != '' && $result->employee_id != '0') {
            // print_r($result);die;
            $info = "SELECT `id`, `station_id`, `department_id`, `company_id`, `ntn_number`, `cnic_number`, `phone`, `phone2`, `city_id`, `zip`, `active`, `is_deleted`, `created_by`
            FROM `employees`
            WHERE `id` = '$result->employee_id'";
            $dbobjx->query($info);
            $get_info = $dbobjx->single();
            $type = $get_info->department_id;
        } 
        if ($result->acno != '' && $result->acno != '0') {
            // check account type
            if($result->parent_id != "" && $result->parent_id != '0')
            {   
                $query = "SELECT `id`,`parent_id`, `acno` FROM `users` WHERE `id` = '$result->parent_id'";
                $dbobjx->query($query);
                $check = $dbobjx->single();
                if($check->acno == $result->acno)
                {
                    $type = '8';
                }
                if(($check->acno != $result->acno) && ($check->id == $result->parent_id))
                {
                    $type = '7';
                }
            }else{
                $type = '6';
            }
            $info = "SELECT `id`, `company_id`,`parent_id`, `acno`, name As first_name, `email`,`type`, `active`, `is_otp`, `is_deleted` 
            FROM `customers` WHERE `acno` = '$result->acno'";
            $dbobjx->query($info);
            $get_info = $dbobjx->single();
        }

        if ($get_info->is_deleted == 'Y') {
            echo response("0", "Your Account has been Deactivated", []);
            exit;
        }
        if (encryptString($result->salt, $password) == $result->password) {
            if ($get_info->active == '1') {
                $first_name = $result->first_name;
                $logged_id = $result->id;
                $primary_id = $get_info->id;
                $employee_id = $result->employee_id;
                $email = $result->email;
                $user_name = $result->user_name;
                $acno = $result->acno;
                $company_id = $result->company_id;
                $secret_key = $result->secret_key;
                $city_id = $result->city_id;

                $fetch_roles = "SELECT * FROM `user_menus` WHERE `account_id` = '$result->id'";
                // print_r($fetch_roles);die;
                $dbobjx->query($fetch_roles);
                $return = $dbobjx->single();
                if ($dbobjx->rowCount() > 0 && $return->menue != '') {
                    $menue_arr = $return->menue;
                    $menue_ids = implode(',', json_decode($menue_arr));
                    $fetch_menues = "SELECT * FROM `menu` WHERE `id` IN ($menue_ids) AND `active` = 1 ORDER BY `sorting` ASC";
                    $dbobjx->query($fetch_menues);
                    $menues = $dbobjx->resultset();
                    $data[] = array(
                        "company_id" => $company_id,
                        "primary_id" => $primary_id,
                        "logged_id" => $logged_id,
                        "city_id" => $city_id,
                        "acno" => $acno,
                        "first_name" => $first_name,
                        "email" => $email,
                        "user_name" => $user_name,
                        "secret_key" => $secret_key,
                        "account_type" => $type,
                        "menus" => $menues
                    );
                    echo response("1", "You have loggedin successfully", $data);
                    exit;
                } else {
                    echo response("0", "Something unexpected happend", "Your account does not have rights to access");
                }
            } else {
                echo response("0", "Activation Error", "Your account status is inactive");
            }
        } else {
            echo response("0", "Wrong password", "Incorect password");
        }
        $dbobjx->close();
    } else {
        echo response("0", "Error", "No user found against this username");
    }
}catch (Exception $e) {
    echo response("0", "Api Error !", $e);
}