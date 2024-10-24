<?php
include "../index.php";
$registerSchema = json_decode(file_get_contents('../schema/arrivals/update.json'));
$request = json_decode(file_get_contents('php://input'));
$valid = json_decode(requestvalidateobject($request, $registerSchema));
include "../functions/siteFunctions.php";
$has_key = authorization();
if ($has_key) {
    $valid_key = authantication($dbobjx);
    if ($valid_key == 200) {
        if ($valid->status) {
            try {
                $company_id = $request->company_id;
                $updated_by = $request->updated_by;
                $tarif_charges = array();
                $error_arr = array();
                $array = $request->data;
                foreach ($array as $key => $data) {
                    $cn_number = $data->cn_number;
                    $weight = $data->weight;
                    $peices = $data->peices;
                    $origin = $data->origin;
                    $destination = $data->destination;
                    $cod_amt = $data->cod_amt;
                    $service_id = $data->service_id;
                    $params = compact('weight', 'peices', 'service_id', 'cod_amt', 'destination', 'origin', 'cn_number');
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
                        $customer_acno = $payload->customer_acno;
                        $tarif_charges[$data->cn_number] = [
                            "customer_acno" => $customer_acno,
                            "charged_peices" => $charged_peices,
                            "charged_weight" => $charged_weight,
                            "weight_charges" => $weight_charges,
                            "handling_charges" => $handling_charges,
                            "gst_charges" => $gst_charges,
                            "bank_charges" => $bank_charges,
                            "sst_charges" => $sst_charges,
                            "charges" => $charges
                        ];
                    } else {
                        $error_arr[] = [
                            "consignment_no" => $cn_number,
                            "message" => $get_charges->message
                        ];
                    }
                }

                foreach ($tarif_charges as $key => $value) {
                    $customer_acno = isset($value['customer_acno']) ? $value['customer_acno'] : '';
                    $charged_weight = isset($value['charged_weight']) ? $value['charged_weight'] : '';
                    $charged_peices = isset($value['charged_peices']) ? $value['charged_peices'] : '';
                    $weight_charges = isset($value['weight_charges']) ? $value['weight_charges'] : '';
                    $handling_charges = isset($value['handling_charges']) ? $value['handling_charges'] : '';
                    $gst_charges = isset($value['gst_charges']) ? $value['gst_charges'] : '';
                    $bank_charges = isset($value['bank_charges']) ? $value['bank_charges'] : '';
                    $sst_charges = isset($value['sst_charges']) ? $value['sst_charges'] : '';
                    $total_charges = isset($value['charges']) ? $value['charges'] : '';
                    $query = "UPDATE `arrivals_details` SET `peices`='$charged_peices',`weight`='$charged_weight',`weight_charged`='$weight_charges',`handling_charges`='$handling_charges',`gst`='$gst_charges',`sst` = '$sst_charges',`bank_charges`='$bank_charges',`total_charges`='$total_charges',`updated_at` = CURRENT_TIMESTAMP(),`updated_by` = '$updated_by' WHERE `cn_numbers` = $key";
                    $dbobjx->query($query);
                    if ($dbobjx->execute()) {
                        $shipment_update = "UPDATE `shipments` SET `peices_charged` = '$charged_peices', `weight_charged`= '$charged_weight',`handling_charges`='$handling_charges',`gst_charges`=$gst_charges,`service_charges`=$weight_charges,`sst_charges`=$sst_charges,`bac_charges`=$bank_charges,`total_charges`='$total_charges', `updated_at` = CURRENT_TIMESTAMP() WHERE `consignment_no` = $key";
                        $dbobjx->query($shipment_update);
                        $dbobjx->execute();
                    }
                }
                echo response("1", "Success", "Arrival sheet has been updated successfully !");
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