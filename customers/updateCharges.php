<?php
include "../index.php";
$request = json_decode(file_get_contents('php://input'));
include "../functions/siteFunctions.php";
$has_key = authorization();
if ($has_key) {
    $valid_key = authantication($dbobjx);
    if ($valid_key === true) {
        try {
            $charges = $request->charges;
            $check = null;
            // for tarif
            if (isset($charges->tarif_charges) && count($charges->tarif_charges) > 0) {
                foreach ($charges->tarif_charges as $key => $tarif) {
                    $id = isset($tarif->id) ? $tarif->id : '';
                    $service = isset($tarif->service_id) ? $tarif->service_id : '';
                    $region_type = isset($tarif->region_type) ? $tarif->region_type : '';
                    $origin_country = isset($tarif->origin_country) ? $tarif->origin_country : '';
                    $destination_country = isset($tarif->destination_country) ? $tarif->destination_country : '';
                    $start_weight = isset($tarif->start_weight) ? $tarif->start_weight : '';
                    $end_weight = isset($tarif->end_weight) ? $tarif->end_weight : '';
                    $charges = isset($tarif->charges) ? $tarif->charges : '';
                    $add_weight = isset($tarif->additional_weight) && $tarif->additional_weight != '' ? $tarif->additional_weight : 0;
                    $add_charges = isset($tarif->additional_charges) && $tarif->additional_charges != '' ? $tarif->additional_charges : 0;
                    $rto_charges = isset($tarif->rto_charges) ? $tarif->rto_charges : 0;
                    $additional_rto_charges = isset($tarif->additional_rto_charges) ? $tarif->additional_rto_charges : 0;
                    $query = "UPDATE `customer_tariffs` SET 
                        `service_id`= '$service',`origin_country` = '$origin_country',`destination_country`='$destination_country',`region`='$region_type',`start_weight`='$start_weight',`end_weight`='$end_weight',`charges`='$charges',`additional_weight`='$add_weight',`additional_charges`='$add_charges',`rto_charges`='$rto_charges',`additional_rto_charges`='$additional_rto_charges',`updated_at`= CURRENT_TIMESTAMP() WHERE `id` = $id";
                    $dbobjx->query($query);
                    // print_r($query);die;
                    if ($dbobjx->execute()) {
                        $check = 1;
                    } else {
                        $check = 0;
                    }
                }
            }

            // for cash handling
            if (isset($charges->handling_charges) && count($charges->handling_charges) > 0) {
                foreach ($charges->handling_charges as $key => $handling_charges) {
                    $id = isset($handling_charges->id) ? $handling_charges->id : '';
                    $min_amt = isset($handling_charges->min_amt) ? $handling_charges->min_amt : 0;
                    $max_amt = isset($handling_charges->max_amt) ? $handling_charges->max_amt : 0;
                    $handling_charges = isset($handling_charges->charges) ? $handling_charges->charges : 0;
                   
                    $query = "UPDATE `customer_cash_handling_charges` SET `min_amt`=$min_amt,`max_amt`=$max_amt,`handling_charges`=$handling_charges,`updated_at`= CURRENT_TIMESTAMP() WHERE `id` = $id";
                    $dbobjx->query($query);
                    if ($dbobjx->execute()) {
                        $check = 1;
                    } else {
                        $check = 0;
                    }
                }
            }
            // for additional
            if (isset($charges->additional_charges) && count($charges->additional_charges) > 0) {
                foreach ($charges->additional_charges as $key => $additional_charges) {
                    $id = isset($additional_charges->id) ? $additional_charges->id : 0;
                    $service_id = isset($additional_charges->service_id) ? $additional_charges->service_id : 0;
                    $charges_type = isset($additional_charges->charges_type) ? $additional_charges->charges_type : 0;
                    $deduction_type = isset($additional_charges->deduction_type) ? $additional_charges->deduction_type : 0;
                    $charges_amt = isset($additional_charges->charges_amt) ? $additional_charges->charges_amt : 0;

                    $query = "UPDATE `customer_additional_charges` SET
                     `service_id`=$service_id,`charges_type`=$charges_type,`deduction_type`=$deduction_type,`charges_amt`=$charges_amt,`updated_at`= CURRENT_TIMESTAMP() WHERE `id` = $id";
                    $dbobjx->query($query);
                    // print_r($query);die;
                    if ($dbobjx->execute()) {
                        $check = 1;
                    } else {
                        $check = 0;
                    }
                }
            }
            if ($check == 1) {
                echo response("1", "Success", "Charges Updated Successfully");
            } else {
                echo response("0", "Error !", "Something went wrong while updating");
            }
        } catch (Exception $e) {
            echo response("0", "Api Error !", $e);
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