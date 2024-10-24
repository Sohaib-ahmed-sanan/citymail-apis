<?php
include "../index.php";
$registerSchema = json_decode(file_get_contents('../schema/arrivals/add.json'));
$request = json_decode(file_get_contents('php://input'));
$valid = json_decode(requestvalidateobject($request, $registerSchema));
include "../functions/siteFunctions.php";
$has_key = authorization();
if ($has_key) {
    $valid_key = authantication($dbobjx);
    if ($valid_key == 200) {
        if ($valid->status) {
            try {
                $error_arr = array();
                $rider_id = $request->rider_id ?? '';
                $route_id = $request->route_id ?? '';
                $station_id = $request->station_id;
                $company_id = $request->company_id;
                $created_by = $request->created_by;
                $array = $request->data;

                $cn_count = count($array);
                // $check = "SELECT COUNT(id) WHERE `arrival_count` = '$cn_count' AND `station_id` = '$station_id' AND `arrival_type` = '1' AND `company_id` = '$company_id'";
                $query = "INSERT INTO `arrivals`(`arrival_count`,`company_id`,`station_id`,`created_by`,`arrival_type`) VALUES ('$cn_count','$company_id','$station_id','$created_by','1')";
                $dbobjx->query($query);
                if ($dbobjx->execute()) {
                    $sheet_id = $dbobjx->lastInsertId();
                    $query_values = "";
                    foreach ($array as $value) {
                        $cn_number = $value->cn_number;
                        $auto_tpl[] = $cn_number;
                        $customer_acno = $value->customer_acno;
                        $charged_weight = $value->weight;
                        $charged_peices = $value->peices;
                        // $weight_charges = '0.00';
                        // $handling_charges = '0.00';
                        // $gst_charges = '0.00';
                        // $bank_charges = '0.00';
                        // $sst_charges = '0.00';
                        // $total_charges = '0.00';
                        $check = "SELECT COUNT(id) As row_count FROM `arrivals_details` WHERE `cn_numbers` = '$cn_number'";
                        $dbobjx->query($check);
                        $row_check = $dbobjx->single();
                        if ($row_check->row_count == '1') {
                            $query_values .= "($sheet_id,$charged_weight,$charged_peices,$customer_acno,$cn_number,$created_by,'1'),";
                            // $update = "UPDATE `shipments` SET `status`= '4' , `peices_charged` = '$charged_peices', `weight_charged`= '$charged_weight',`peices` = '$charged_peices', `weight`= '$charged_weight', `updated_at` = CURRENT_TIMESTAMP() ,`updated_by`='$created_by' WHERE `consignment_no` = $cn_number";
                            $update = "UPDATE `shipments` SET `status`= '4' , `updated_at` = CURRENT_TIMESTAMP() ,`updated_by`='$created_by' WHERE `consignment_no` = $cn_number";
                            $dbobjx->query($update);
                            $dbobjx->execute();
                            track_status($dbobjx, $cn_number, '4');
                        } else {
                            $error_arr[] = [
                                "consignment_no" => $cn_number,
                                "message" => "Arrival already exist"
                            ];
                        }
                    }
                    // print_r($final_query);die;
                    $final_query = rtrim($query_values, ", ");
                    $query = "INSERT INTO `arrivals_details`(`arrival_id`,`weight`,`peices`,`customer_acno`, `cn_numbers`,`created_by`,`arrival_type`)VALUES $final_query";
                    $dbobjx->query($query);
                    if ($dbobjx->execute()) {
                        foreach ($auto_tpl as $cn_no) {
                            auto_thirdparty(['cn_number' => $cn_no, 'company_id' => $company_id]);
                        }
                        if ($cn_count > 0 && count($error_arr) == 0) {
                            echo response("1", "Arrival has been created", ["error" => $error_arr, "sheet_id" => $sheet_id]);
                        }
                        if ($cn_count > 0 && count($error_arr) > 0) {
                            echo response("1", "Arrival has been created some consignments have error ", ["error" => $error_arr, "sheet_id" => $sheet_id]);
                        }
                    } else {
                        echo response("0", "Error", "Something went wrong");
                    }
                }else {
                    echo response("0", "Error", "Something went wrong");
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
