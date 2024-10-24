<?php
include "../index.php";
$registerSchema = json_decode(file_get_contents('../schema/manifists/add.json'));
$request = json_decode(file_get_contents('php://input'));
$valid = json_decode(requestvalidateobject($request, $registerSchema));
include "../functions/siteFunctions.php";
$has_key = authorization();
if ($has_key) {
    $valid_key = authantication($dbobjx);
    if ($valid_key === true) {
        if ($valid->status) {
            $company_id = isset($request->company_id) ? $request->company_id : '';
            $station_id = isset($request->station_id) ? $request->station_id : '';
            $rider_id = isset($request->rider_id) ? $request->rider_id : null;
            $seal_no = isset($request->seal_no) ? $request->seal_no : '';
            $batch_name = isset($request->batch_name) ? $request->batch_name : '';
            $cn_numbers = isset($request->cn_numbers) ? $request->cn_numbers : '';
            $cn_num_arr = [];
            $error_array = [];
            try {
                foreach ($cn_numbers as $cn_number) {
                    $Query = "SELECT COUNT(id) as count FROM `manifist_details` WHERE `consignment_no` = '$cn_number'";
                    $dbobjx->query($Query);
                    $result = $dbobjx->single();
                    if ($result->count == 0) {
                        $cn_num_arr[] = $cn_number;
                    } else {
                        $error_array[] = $cn_number;
                    }
                }
                $cn_count = count($cn_num_arr);

                if ($cn_count > 0) {
                    $query = "INSERT INTO `manifists`(`company_id`, `seal_no`,`batch_name`,`type`,`station_id`,`consignment_count`)
                     VALUES ('$company_id','$seal_no','$batch_name','1','$station_id','$cn_count')";
                    $dbobjx->query($query);
                    if ($dbobjx->execute()) {
                        $manifist_id = $dbobjx->lastInsertId();
                        foreach ($cn_num_arr as $key => $cn) {
                            $customer_id = isset($request->customer_id[$key]) ? $request->customer_id[$key] : '';
                            $query = "INSERT INTO `manifist_details`(`manifist_id`, `consignment_no`) VALUES
                            ($manifist_id,$cn)";
                            $dbobjx->query($query);
                            if ($dbobjx->execute()) {
                                $update = "UPDATE `shipments` SET `status`= '22' , `updated_at` = CURRENT_TIMESTAMP() WHERE `consignment_no` = $cn";
                                $dbobjx->query($update);
                                $dbobjx->execute();
                                track_status($dbobjx, $cn, 22);
                            }
                        }
                        echo response("1", "Manifist is added successfully", "$manifist_id");
                    } else {
                        echo response("0", "Error", "Something went wrong while inserting manifist");
                    }
                } else {
                    echo response("0", "Error", "Manifist already exist with these consignments");
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