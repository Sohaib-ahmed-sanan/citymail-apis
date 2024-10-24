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
                $tarif_charges = array();
                $error_arr = array();
                $auto_tpl = array();
                // $rider_id = $request->rider_id;
                // $route_id = $request->route_id;
                $station_id = $request->station_id;
                $company_id = $request->company_id;
                $created_by = $request->created_by;
                $arrival_type = $request->arrival_type;
                $array = $request->data;
                foreach ($array as $key => $data) {
                    $cn_number = $data->cn_number;
                    $weight = $data->weight;
                    $peices = $data->peices;
                    $origin_city = $data->origin_city;
                    $origin_country = $data->origin_country;
                    $destination_city = $data->destination_city;
                    $destination_country = $data->destination_country;
                    $cod_amt = $data->cod_amt;
                    $service_id = $data->service_id;
                    $params = compact('weight', 'peices', 'service_id', 'cod_amt', 'destination_city', 'origin_city','origin_country','destination_country','cn_number');
                    $get_charges = calculate_tariff($params);
                    // print_r($get_charges);die;
                    if ($get_charges->status == 1) {
                        $payload = $get_charges->payload;
                        $charged_peices = $payload->peices;
                        $charged_weight = $payload->weight;
                        $weight_charges = $payload->weight_charges;
                        $handling_charges = $payload->handling_charges;
                        $gst_charges = $payload->gst_charges;
                        $bank_charges = $payload->bank_charges;
                        $sst_charges = $payload->sst_charges;
                        $charges = $payload->total_charges;
                        $tarif_id = $payload->tarif_id;
                        $customer_acno = $payload->customer_acno;
                        $rto_charges = $payload->rto_charges;

                        $tarif_charges[$data->cn_number] = [
                            "customer_acno" => $customer_acno,
                            "charged_peices" => $charged_peices,
                            "charged_weight" => $charged_weight,
                            "weight_charges" => $weight_charges,
                            "handling_charges" => $handling_charges,
                            "gst_charges" => $gst_charges,
                            "bank_charges" => $bank_charges,
                            "sst_charges" => $sst_charges,
                            "charges" => $charges,
                            "rto_charges" => $rto_charges,
                            "tarif_id" => $tarif_id
                        ];


                    } else {
                        $error_arr[] = [
                            "consignment_no" => $cn_number,
                            "message" => $get_charges->message
                        ];
                    }
                }
                $cn_count = count((array) $tarif_charges);
                if ($cn_count > 0) {
                    $query = "INSERT INTO `arrivals`(`arrival_count`,`company_id`,`station_id`,`created_by`,`arrival_type`) VALUES ('$cn_count','$company_id','$station_id','$created_by','0')";
                    $dbobjx->query($query);
                    if ($dbobjx->execute()) {
                        $sheet_id = $dbobjx->lastInsertId();
                        $query_values = "";
                        foreach ($tarif_charges as $key => $value) {
                            $auto_tpl[] = $key;
                            $customer_acno = isset($value['customer_acno']) ? $value['customer_acno'] : '';
                            $charged_weight = isset($value['charged_weight']) ? $value['charged_weight'] : '';
                            // print_r($charged_weight);die;
                            $charged_peices = isset($value['charged_peices']) ? $value['charged_peices'] : '';
                            $weight_charges = isset($value['weight_charges']) ? $value['weight_charges'] : '';
                            $weight = isset($value['weight']) ? $value['weight'] : '';
                            $peices = isset($value['peices']) ? $value['peices'] : '';
                            $handling_charges = isset($value['handling_charges']) ? $value['handling_charges'] : '';
                            $gst_charges = isset($value['gst_charges']) ? $value['gst_charges'] : '';
                            $bank_charges = isset($value['bank_charges']) ? $value['bank_charges'] : '';
                            $sst_charges = isset($value['sst_charges']) ? $value['sst_charges'] : '';
                            $total_charges = isset($value['charges']) ? $value['charges'] : '';
                            $rto_charges = isset($value['rto_charges']) ? $value['rto_charges'] : '';
                            $check = "SELECT COUNT(id) AS row_count FROM `arrivals_details` WHERE `cn_numbers` = '$key'";
                            $dbobjx->query($check);
                            $row = $dbobjx->single();
                            if ($row->row_count == 0) {
                                $query_values .= "($sheet_id,$charged_weight,$charged_peices,$weight_charges,$handling_charges,$gst_charges,$sst_charges,$bank_charges,$total_charges,$customer_acno,$key,$created_by,'0'),";
                                $update = "UPDATE `shipments` SET `status`= '19' , `peices_charged` = '$charged_peices', `weight_charged`= '$charged_weight',`peices` = '$charged_peices', `weight`= '$charged_weight',`handling_charges`='$handling_charges',`gst_charges`=$gst_charges,`service_charges`= $weight_charges,`sst_charges`=$sst_charges,`bac_charges`=$bank_charges,`total_charges`='$total_charges',`tarif_id` = '$tarif_id',`rto_charges`='$rto_charges',`updated_at` = CURRENT_TIMESTAMP() ,`updated_by`='$created_by' WHERE `consignment_no` = $key";
                                $dbobjx->query($update);
                                $dbobjx->execute();
                                track_status($dbobjx, $key, '19');
                            } else {
                                $error_arr[] = [
                                    "consignment_no" => $key,
                                    "message" => "Arrival already exist"
                                ];
                            }
                        }
                        $final_query = rtrim($query_values, ", ");
                        if($final_query!=""){
                            $query = "INSERT INTO `arrivals_details`(`arrival_id`,`weight`,`peices`,`weight_charged`,`handling_charges`,`gst`,`sst`,`bank_charges`,`total_charges`,`customer_acno`, `cn_numbers`,`created_by`,`arrival_type`)VALUES $final_query";
                            $dbobjx->query($query);
                            if ($dbobjx->execute()) {
                                if ($cn_count > 0 && count($error_arr) == 0) {
                                    echo response("1", "Pickup has been created", ["error" => $error_arr, "sheet_id" => $sheet_id]);
                                }
                                if ($cn_count > 0 && count($error_arr) > 0) {
                                    echo response("1","Pickup has been created some consignments have error ", ["error" => $error_arr, "sheet_id" => $sheet_id]);
                                }
                            } else {
                                echo response("0", "Error", "Something went wrong");
                            }
                        }
                    }
                } else {
                    echo response("0", "Error", "No Tariffs defined");
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