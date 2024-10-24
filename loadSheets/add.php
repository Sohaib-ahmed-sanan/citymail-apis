<?php
include "../index.php";
$request = json_decode(file_get_contents('php://input'));
$registerSchema = json_decode(file_get_contents('../schema/loadsheet/add.json'));
$valid = json_decode(requestvalidateobject($request, $registerSchema));
include "../functions/siteFunctions.php";
$has_key = authorization();
if ($has_key == 200) {
    $valid_key = authantication($dbobjx);
    if ($valid_key == 200) {
        if ($valid->status) {
            try {
                $company_id = isset($request->company_id) ? $request->company_id : '';
                $created_by = isset($request->created_by) ? $request->created_by : '';
                $cn_array = isset($request->consignments_array) ? $request->consignments_array : '';
                $grouped_data = [];

                foreach ($cn_array as $cn) {
                    $query = "SELECT `customer_acno`,`pickup_location_id` FROM `shipments` WHERE `consignment_no` = '$cn' ";
                    $dbobjx->query($query);
                    $result = $dbobjx->single();
                    if ($dbobjx->rowCount() > 0) {
                        $pl_id = $result->pickup_location_id;
                        $customer_acno = $result->customer_acno;
                        if (!isset($grouped_data[$customer_acno])) {
                            $grouped_data[$customer_acno] = [];
                        }
                        if (!isset($grouped_data[$customer_acno][$pl_id])) {
                            $grouped_data[$customer_acno][$pl_id] = [];
                        }
                        $grouped_data[$customer_acno][$pl_id][] = $cn;
                    }
                }

                $detail_query = "";
                foreach ($grouped_data as $customer_acno => $pickup) {
                    foreach ($pickup as $key => $cn) {
                        $cn_count = count($cn);
                        $query = "INSERT INTO `loadsheets`(`cn_count`,`company_id`,`pickup_location_id`,`customer_acno`,`created_by`) VALUES ($cn_count,$company_id,$key,$customer_acno,$created_by)";
                        $dbobjx->query($query);
                        if ($dbobjx->execute()) {
                            $sheet_id = $dbobjx->lastInsertId();
                            foreach ($cn as $no) {
                                $detail_query .= "($sheet_id,$no,$customer_acno),";
                                $update = "UPDATE `shipments` SET `status`= '21' , `updated_at` = CURRENT_TIMESTAMP() WHERE `consignment_no` = $no";
                                $dbobjx->query($update);
                                if ($dbobjx->execute()) {
                                    track_status($dbobjx, $no, 21);
                                }
                            }
                        }
                    }
                }
                $final_query = rtrim($detail_query, ",");
                $query = "INSERT INTO `loadsheet_details` (`loadsheet_id`,`cn_numbers`,`customer_acno`) VALUES $final_query";
                $dbobjx->query($query);
                if ($dbobjx->execute()) {
                    echo response("1", "Success", "Load sheet has been created");
                } else {
                    echo response("0", "Error", "Something went wrong while inserting details");
                }

            } catch (Exception $e) {
                echo response("0", "Api Error !", $e);
            }
        } else {
            echo response("0", "Api Error !", $valid->error);
        }
    } else {
        if ($valid_key == 401) {
            echo response("0", "Invalid Secret Key", "Secret key is incorect");
        } elseif ($valid_key == 404) {
            echo response("0", "Authantication faild", "Client Id is not correct");
        }
    }
} else {
    echo response("0", "Unauthorized", $has_key);
}
