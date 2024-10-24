<?php
include "../index.php";
$registerSchema = json_decode(file_get_contents('../schema/delivery_sheet/add.json'));
$request = json_decode(file_get_contents('php://input'));
$valid = json_decode(requestvalidateobject($request, $registerSchema));
include "../functions/siteFunctions.php";
$has_key = authorization();
if ($has_key) {
    $valid_key = authantication($dbobjx);
    if ($valid_key === true) {
        if ($valid->status) {
            try {
                $headers = getallheaders();
                $updated_by = $headers['Client-Id'];
                $company_id = isset($request->company_id) ? $request->company_id : '';
                $rider_id = isset($request->rider_id) ? $request->rider_id : '';
                $route_id = isset($request->route_id) ? $request->route_id : '';
                // $details_array = $request->details;
                $is_created = false;
                if ($cn_count != '0') {
                    foreach ($request->details as $currency => $details) {
                        $insertValues = [];
                        $updateCases = [];
                        $consignmentNos = [];
                        $specialCases = [];
                        $cn_count = count($details);
                        $query = "INSERT INTO `delivery_sheet`(`company_id`,`rider_id`,`route_id`,`consignment_count`) VALUES
                        ('$company_id','$rider_id','$route_id','$cn_count')";
                        $dbobjx->query($query);
                        if ($dbobjx->execute()) {
                            $sheet_id = $dbobjx->lastInsertId();
                            foreach ($details as $data) {
                                $insertValues[] = "('$sheet_id','$company_id','$rider_id','$route_id','$data->consignment','$data->status','$data->remarks')";
                                $updateCases[] = "WHEN `consignment_no` = '$data->consignment' THEN '$data->status'";
                                $consignmentNos[] = "'$data->consignment'";
                                track_status($dbobjx, $data->consignment, $data->status);
                                if ($data->status == '16') {
                                    $specialCases[] = $data->consignment;
                                }
                            }
                            if (count($insertValues) > 0) {
                                $query_str = implode(',', $insertValues);
                                $query = "INSERT INTO `delivery_sheet_details`(`sheet_id`, `company_id`, `rider_id`, `route_id`, `consignment_no`,`status_id`,`remarks`) VALUES $query_str";
                                $dbobjx->query($query);
                                if ($dbobjx->execute()) {
                                    if (count($updateCases) > 0) {
                                        $updateQuery = "UPDATE `shipments` 
                                                    SET `status` = CASE " . implode(' ', $updateCases) . " END, 
                                                        `updated_at` = CURRENT_TIMESTAMP(), 
                                                        `updated_by` = '$updated_by' 
                                                        WHERE `consignment_no` IN (" . implode(', ', $consignmentNos) . ")";
                                        $dbobjx->query($updateQuery);
                                        $dbobjx->execute();
                                    }
                                }

                                // if (count($specialCases) > 0) {
                                //     $chargesUpdateQuery = "UPDATE `shipments` 
                                // SET `gst_charges` = '0.00', 
                                //     `sst_charges` = '0.00',
                                //     `bac_charges` = '0.00',
                                //     `service_charges` = '0.00',
                                //     `handling_charges` = '0.00'
                                // WHERE `consignment_no` IN (" . implode(', ', $specialCases) . ")";
                                //     $dbobjx->query($chargesUpdateQuery);
                                //     $dbobjx->execute();
                                // }
                                // print_r(count($specialCases));die;
                            }
                            $is_created = true;     
                        }else{
                            $is_created = false;     
                        }
                    }
                    if($is_created == true)
                    {
                        echo response("1", "Delivery sheet has been added", "$sheet_id");
                    }else {
                        echo response("0", "Error", "Something went wrong while inserting delivery sheet");
                    }
                } else {
                    echo response("0", "Error", "No data to insert");
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
