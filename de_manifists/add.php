<?php
include "../index.php";
$registerSchema = json_decode(file_get_contents('../schema/demanifists/add.json'));
$request = json_decode(file_get_contents('php://input'));
$valid = json_decode(requestvalidateobject($request, $registerSchema));
include "../functions/siteFunctions.php";
$has_key = authorization();
if ($has_key) {
    $valid_key = authantication($dbobjx);
    if ($valid_key === true) {
        if ($valid->status) {
            $company_id = isset($request->company_id) ? $request->company_id : '';
            $created_by = isset($request->created_by) ? $request->created_by : '';
            $seal_no = isset($request->seal_no) ? $request->seal_no : '';
            $station_id = isset($request->station_id) ? $request->station_id : '';
            $rider_id = isset($request->rider_id) ? $request->rider_id : '';
            $data = isset($request->data) ? $request->data : '';
            $cn_num_arr = [];
            $error_array = [];
            try {
                foreach ($data as $row) {
                    $Query = "SELECT `status` FROM `shipments` WHERE `consignment_no` = $row->consignment_no";
                    $dbobjx->query($Query);
                    $result = $dbobjx->single();
                    if ($result->status != '23' && $result->status != '2' && $result->status != '14' && $result->status == '22') {
                        $cn_num_arr[] = ['cn_no' => $row->consignment_no,'manifist_id' => $row->manifist_id];
                    } else {
                        $error_array[] = $row->consignment_no;
                    }
                }
                $cn_count = count($cn_num_arr);
                if ($cn_count > 0) {
                $check = "INSERT INTO `de_manifist`(`company_id`, `seal_no`,`station_id`,`consignment_count`,`created_by`) 
                    VALUES ($company_id,'$seal_no','$station_id',$cn_count,$created_by)";
                    $dbobjx->query($check);
                    if ($dbobjx->execute()) {
                        $de_manifist_id = $dbobjx->lastInsertId();
                        foreach ($cn_num_arr as $key => $value) {
                            $cn_no = isset($value['cn_no']) ? $value['cn_no'] : '';
                            $manifist_id = isset($value['manifist_id']) ? $value['manifist_id'] : '';
                            $query = "INSERT INTO `de_manifist_details`(`company_id`,`de_manifist_id`, `manifist_id`, `consignment_no`) VALUES
                            ('$company_id','$de_manifist_id','$manifist_id','$cn_no')";
                            $dbobjx->query($query);
                            if ($dbobjx->execute()) {
                                $update = "UPDATE `shipments` SET `status`= '23' , `updated_at` = CURRENT_TIMESTAMP() , `updated_by` = '$created_by' WHERE `consignment_no` = $cn_no";
                                $dbobjx->query($update);
                                $dbobjx->execute();
                                track_status($dbobjx, $cn_no, 23);
                            }
                        }
                        echo response("1", "DeManifist is added successfully", "$manifist_id");
                    } else {
                        echo response("0", "Error", "Something went wrong while inserting manifist");
                    }
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