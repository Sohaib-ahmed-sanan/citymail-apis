<?php
include "../index.php";
$registerSchema = json_decode(file_get_contents('../schema/customers/update.json'));
$request = json_decode(file_get_contents('php://input'));
$valid = json_decode(requestvalidateobject($request, $registerSchema));
include "../functions/siteFunctions.php";
$has_key = authorization();
if ($has_key == 200) {
    $valid_key = authantication($dbobjx);
    if ($valid_key === true) {
        if ($valid->status) {
            try {
                $id = $request->id;
                $sales_person_id = isset($request->sales_person_id) ? $request->sales_person_id : '';
                $name = isset($request->name) ? $request->name : '';
                $phone = isset($request->phone) ? $request->phone : '';
                $ntn = isset($request->ntn) ? $request->ntn : '';
                $cnic = isset($request->cnic) ? $request->cnic : '';
                $city_id = isset($request->city_id) ? $request->city_id : '';
                $country_id = isset($request->country_id) ? $request->country_id : '';
                $address = isset($request->address) ? $request->address : '';
                $other_phone = isset($request->other_phone) ? $request->other_phone : '';
                $other_name = isset($request->other_name) ? $request->other_name : '';
                $bank = isset($request->bank) ? $request->bank : '';
                $account_title = isset($request->account_title) ? $request->account_title : '';
                $account_number = isset($request->account_number) ? $request->account_number : '';
                $business_name = isset($request->business_name) ? $request->business_name : '';
                $business_address = isset($request->business_address) ? $request->business_address : '';
                $cnic_image = isset($request->cnic_image) ? $request->cnic_image : '';
                $service_assigned = isset($request->service_assigned) ? $request->service_assigned : '';
                $password = isset($request->password) ? $request->password : '';
                $user_name = isset($request->user_name) ? $request->user_name : '';

                $query = "SELECT acno FROM `customers` WHERE `id` = '$id'";
                $dbobjx->query($query);
                $data = $dbobjx->single();
                $acno = $data->acno;
                
                $more = $update = "";
                if ($sales_person_id != '') {
                    $more = ",`sales_person`='$sales_person_id'";
                }
                if ($password != '') {
                    $salt = generatingSalt();
                    $hashpassword = encryptString($salt, $password);
                    $update = ",`salt`='$salt',`password`='$hashpassword'";
                }
                if ($user_name != '') {
                    $check_availiblity = check_user_name($dbobjx, $user_name);
                    if ($check_availiblity == 200) {
                        $query = "UPDATE `users` SET `user_name` = '$user_name' WHERE `acno` = '$acno'";
                        $dbobjx->query($query);
                        $dbobjx->execute();
                        $more .= ",`user_name` = '$user_name'";
                    } else {
                        echo response("0", "Username exist", "Username already exist please use different");
                        die;
                    }
                }
                $query = "UPDATE `customers` SET `name`='$name',`phone`='$phone',`business_name`='$business_name',`business_address`='$business_address',`other_phone`='$other_phone',`other_name`='$other_name',`ntn`='$ntn',`cnic`='$cnic',`address`='$address',`account_title`='$account_title',`account_number`='$account_number',`bank`='$bank',`cnic_image`='$cnic_image',`city_id`='$city_id',`country_id` = '$country_id' $more WHERE `acno` = $acno";
                $dbobjx->query($query);
                if($dbobjx->execute()){
                    $query = "UPDATE `users` SET `first_name`='$name',`phone`='$phone',`address`='$address',`city_id`='$city_id',`country_id` = '$country_id' $update WHERE `acno` = $acno";
                    $dbobjx->query($query);
                    $dbobjx->execute();
                    
                    $query = "UPDATE `customer_services` SET `service_id`='$service_assigned' WHERE `acno` = $acno";
                    $dbobjx->query($query);
                    $dbobjx->execute();
                }
                echo response("1", "Success", "Customer has been updated successfully");
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