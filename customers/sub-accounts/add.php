<?php
include "../../index.php";
$registerSchema = json_decode(file_get_contents('../../schema/customers/add-subAccount.json'));
$request = json_decode(file_get_contents('php://input'));
$valid = json_decode(requestvalidateobject($request, $registerSchema));
include "../../functions/siteFunctions.php";
$has_key = authorization();
if ($has_key) {
    $valid_key = authantication($dbobjx);
    if ($valid_key === true) {
        if ($valid->status) {
            try {
                $company_id = isset($request->company_id) ? $request->company_id : '';
                $customer_acno = isset($request->customer_acno) ? $request->customer_acno : '';
                $name = isset($request->name) ? $request->name : '';
                $user_name = isset($request->user_name) ? $request->user_name : '';
                $email = isset($request->email) ? $request->email : '';
                $phone = isset($request->phone) ? $request->phone : '';
                $address = isset($request->address) ? $request->address : '';
                $cnic = isset($request->cnic) ? $request->cnic : '';
                $ntn = isset($request->ntn) ? $request->ntn : '';
                $business_name = isset($request->business_name) ? $request->business_name : '';
                $secret = randomString(50);
                $token = isset($request->token) ? $request->token : '';
                $country_id = isset($request->country_id) ? $request->country_id : '';
                $city_id = isset($request->city_id) ? $request->city_id : '';

                $rights = isset($request->rights) ? $request->rights : '';
                $password = $request->password;
                $salt = generatingSalt();
                $hashpassword = encryptString($salt, $password);
                $check = "SELECT `id` FROM `users` WHERE `email` = '$email' AND `company_id` = '$company_id' AND `acno` = '$customer_acno'";
                $dbobjx->query($check);
                $dbobjx->single();
                if ($dbobjx->rowCount() === 0) {
                    $check_user = "SELECT `id` FROM `users` WHERE BINARY `user_name` ='$user_name'";
                    $dbobjx->query($check_user);
                    $dbobjx->single();
                    if ($dbobjx->rowCount() === 0) {
                        $query = "SELECT CONCAT(LPAD(IFNULL(MAX(SUBSTRING(acno, 4)) + 1,00001),5,0)) acno FROM customers;";
                        $dbobjx->query($query);
                        $result = $dbobjx->single();
                        $acno = '10' . $result->acno;

                        $query = "SELECT `id`,`sales_person` FROM `customers` WHERE `acno` = '$customer_acno' AND `parent_id` IS NULL";
                        $dbobjx->query($query);
                        $get_parent = $dbobjx->single();
                        // print_r($get_parent);die;
                        $main_parent_id = $get_parent->id;
                        $sales_person_id = $get_parent->sales_person;

                        $query = "INSERT INTO `customers`(`company_id`,`sales_person`,`parent_id`,`acno`,`name`,phone, `email`,`business_name`,`business_address`, `other_phone`, `other_name`, `fule_surcharges`, `ntn`, `cnic`,`country_id`,`city_id`,`address`, `type`, `account_title`, `account_type`, `account_number`, `bank`,`user_name`,`cn_start`,`cn_end`,`differance`) VALUES 
                        ($company_id,$sales_person_id,'$main_parent_id',$acno,'$name','$phone','$email','$business_name','$business_address','','','nill','$ntn','$cnic','$country_id','$city_id','$address','-','','sub-account','','','$user_name',null,null,null)";
                        $dbobjx->query($query);
                        if ($dbobjx->execute()) {
                            $query = "INSERT INTO `pickup_locations`(`company_id`,`customer_acno`,`name`,`title`,`email`, `address`,`country_id`,`city_id`,`phone`) 
                            VALUES ('$company_id','$acno','$business_name','$business_name','$email','$business_address','$country_id','$city_id','$phone')";
                            $dbobjx->query($query);
                            $dbobjx->execute();

                            $query = "SELECT `id` FROM `users` WHERE `acno` = '$customer_acno' AND `parent_id` = '0'";
                            $dbobjx->query($query);
                            $get_parent = $dbobjx->single();
                            $parent_id = $get_parent->id;

                            $query = "INSERT INTO `users`(`acno`,`parent_id`,`company_id`,`user_name`, `first_name`,`phone`,`email`,`address`,`country_id`,`city_id`,`token`, `secret_key`, `salt`, `password`) 
                            VALUES ('$acno','$parent_id','$company_id','$user_name','$name','$phone','$email','$address','$country_id','$city_id','$token','$secret','$salt','$hashpassword')";
                            $dbobjx->query($query);
                            if ($dbobjx->execute()) {
                                $account_id = $dbobjx->lastInsertId();
                                $rights_query = "INSERT INTO `user_menus`(`account_id`, `menue`) VALUES ('$account_id','$rights')";
                                $dbobjx->query($rights_query);
                                $dbobjx->execute();
                                echo response("1", "Success", "Sub account has been added successfully");
                            } else {
                                echo response("0", "Something went wrong", "Please try again later");
                            }
                        }else {
                            echo response("0", "Something went wrong", "Please try again later");
                        }
                    } else {
                        echo response("0", "Username Error !", "Username already exist please use different");
                    }

                } else {
                    echo response("0", "Email Error !", "Email already exist please use different");
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