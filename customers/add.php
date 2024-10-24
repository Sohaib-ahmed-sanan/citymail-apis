<?php
include "../index.php";
$registerSchema = json_decode(file_get_contents('../schema/customers/add.json'));
$request = json_decode(file_get_contents('php://input'));
$valid = json_decode(requestvalidateobject($request, $registerSchema));
include "../functions/siteFunctions.php";
$has_key = authorization();
if ($has_key) {
    $valid_key = authantication($dbobjx);
    if ($valid_key === true) {
        if ($valid->status) {
            try {
                $company_id = isset($request->company_id) ? $request->company_id : '';
                $sales_person_id = isset($request->sales_person_id) ? $request->sales_person_id : '';
                $name = isset($request->name) ? $request->name : '';
                $user_name = isset($request->user_name) ? $request->user_name : '';
                $email = isset($request->email) ? $request->email : '';
                $phone = isset($request->phone) ? $request->phone : '';
                $ntn = isset($request->ntn) ? $request->ntn : '';
                $service_assigned = isset($request->service_assigned) ? $request->service_assigned : '';
                $city_id = isset($request->city_id) ? $request->city_id : '';
                $country_id = isset($request->country_id) ? $request->country_id : '';
                $cnic = isset($request->cnic) ? $request->cnic : '';
                $address = isset($request->address) ? $request->address : '';
                $business_name = isset($request->business_name) ? $request->business_name : '';
                $business_address = isset($request->business_address) ? $request->business_address : '';
                $other_phone = isset($request->other_phone) ? $request->other_phone : '';
                $other_name = isset($request->other_name) ? $request->other_name : '';
                $bank = isset($request->bank) ? $request->bank : '';
                $account_title = isset($request->account_title) ? $request->account_title : '';
                $account_number = isset($request->account_number) ? $request->account_number : '';
                $secret = randomString(50);
                $token = isset($request->token) ? $request->token : '';
                $charges = isset($request->charges) ? $request->charges : '';

                $password = $request->password;
                $salt = generatingSalt();
                $hashpassword = encryptString($salt, $password);

                $check = "SELECT * FROM `customers` WHERE `email` = '$email' AND `company_id` = $company_id";
                $dbobjx->query($check);
                $dbobjx->single();
                if ($dbobjx->rowCount() === 0) {
                    $check_user = "SELECT `id` FROM `users` WHERE BINARY `user_name`='$user_name'";
                    $dbobjx->query($check_user);
                    $dbobjx->single();
                    if ($dbobjx->rowCount() === 0) {
                        $query = "SELECT CONCAT(LPAD(IFNULL(MAX(SUBSTRING(acno, 4)) + 1,00001),5,0)) acno FROM customers;";
                        $dbobjx->query($query);
                        $result = $dbobjx->single();
                        $acno = '10'.$result->acno;
                        // $query = "SELECT MAX(cn_end) AS cn_end FROM customers";
                        // $dbobjx->query($query);
                        // $result = $dbobjx->single(); // Assuming this fetches a single row
                        // $consignment_end = $result->cn_end;

                        // // Increment the consignment_end value by 200000
                        // $new_consignment = $consignment_end + 200000;
                        $query = "INSERT INTO `customers`(`company_id`,`sales_person`,`acno`,`name`,phone, `email`,`business_name`,`business_address`, `other_phone`, `other_name`, `fule_surcharges`, `ntn`, `cnic`,`country_id`,`city_id`,`address`, `type`, `account_title`, `account_type`, `account_number`, `bank`,`user_name`,`cn_start`,`cn_end`,`differance`) VALUES 
                        ($company_id,$sales_person_id,$acno,'$name','$phone','$email','$business_name','$business_address','$other_phone','$other_name','nill','$ntn','$cnic','$country_id','$city_id','$address','type','$account_title','main','$account_number','$bank','$user_name',null,null,null)";
                        $dbobjx->query($query);
                        if ($dbobjx->execute()) {
                            $query = "INSERT INTO `users`(`acno`,`company_id`,`user_name`, `first_name`,`phone`,`email`,`address`,`country_id`,`city_id`,`token`, `secret_key`, `salt`, `password`) 
                            VALUES ('$acno','$company_id','$user_name','$name','$phone','$email','$address','$country_id','$city_id','$token','$secret','$salt','$hashpassword')";
                            $dbobjx->query($query);
                            $dbobjx->execute();
                            $customer_id = $dbobjx->lastInsertId();
                            $rights_query = "INSERT INTO `user_menus`(`account_id`,`menue`) VALUES ('$customer_id','[1,2,7,8,9,10,13,20,22,23,29,35,39]')";
                            $dbobjx->query($rights_query);
                            $dbobjx->execute();
                            $query = "INSERT INTO `customer_services`(`acno`, `service_id`) VALUES ($acno,'$service_assigned')";
                            $dbobjx->query($query);
                            $dbobjx->execute();
                            $query = "INSERT INTO `pickup_locations`(`company_id`,`customer_acno`,`name`,`title`,`email`, `address`,`country_id`,`city_id`,`phone`) VALUES ('$company_id','$acno','$business_name','$business_name','$email','$business_address','$country_id','$city_id','$phone')";
                            $dbobjx->query($query);
                            $dbobjx->execute();
                            // for tarrif
                            $add_charges = getAPIdata(API_URL . 'customers/addCharges', ['charges' => $charges, 'acno' => $acno]);
                            // print_r($add_charges);die;
                            echo response("1", "Success", "Customer has been added successfully");
                        } else {
                            echo response("0", "Something went wrong", "Please try again later");
                        }
                    } else {
                        echo response("0", "Username Error !", "Username already exist please use different");
                    }

                } else {
                    echo response("0", "Email Error !", "Customer email already exist please use different");
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