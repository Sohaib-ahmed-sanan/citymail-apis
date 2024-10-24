<?php
include "../index.php";
$request = json_decode(file_get_contents('php://input'));
include "../functions/siteFunctions.php";
// $has_key = authorization();
// if ($has_key) {
//     $valid_key = authantication($dbobjx);
//     if ($valid_key === true) {
try {
    $acno = isset($request->acno) ? $request->acno : '';
    $charges = isset($request->charges) ? $request->charges : '';
    $check = null;
    if (isset($charges->tarif_charges) && count($charges->tarif_charges) > 0) {
        $values = "";
        foreach ($charges->tarif_charges as $key => $tarif) {
            $service = isset($tarif->service_id) ? $tarif->service_id : '';
            $rto_charges = isset($tarif->rto_charges) ? $tarif->rto_charges : 0;
            $additional_rto_charges = isset($tarif->additional_rto_charges) ? $tarif->additional_rto_charges : 0;
            $region_type = isset($tarif->region_type) ? $tarif->region_type : '';
            $origin_country = isset($tarif->origin_country) ? $tarif->origin_country : '';
            $destination_country = isset($tarif->destination_country) ? $tarif->destination_country : '';
            $start_weight = isset($tarif->start_weight) ? $tarif->start_weight : '';
            $end_weight = isset($tarif->end_weight) ? $tarif->end_weight : '';
            $charges_amt = isset($tarif->charges) ? $tarif->charges : '';
            $add_weight = isset($tarif->additional_weight) && $tarif->additional_weight != '' ? $tarif->additional_weight : 0;
            $add_charges = isset($tarif->additional_charges) && $tarif->additional_charges != '' ? $tarif->additional_charges : 0;
            $values .= "($acno,'$service','$origin_country','$destination_country','$region_type','$start_weight','$end_weight','$charges_amt','$add_weight','$add_charges','$rto_charges','$additional_rto_charges'),";
        }
        $final_query = rtrim($values, ", ");
        $query = "INSERT INTO `customer_tariffs`(`customer_acno`, `service_id`,`origin_country`,`destination_country`,`region`, `start_weight`, `end_weight`, `charges`,`additional_weight`,`additional_charges`,`rto_charges`,`additional_rto_charges`)
        VALUES $final_query";
        $dbobjx->query($query);
        if ($dbobjx->execute()) {
            $check = 1;
        } else {
            $check = 0;
        }
    }
    //for cash handlig charges
    if (isset($charges->handling_charges) && count($charges->handling_charges) > 0) {
        $values = "";
        foreach ($charges->handling_charges as $key => $handling) {
            $charges_amt = isset($handling->charges) ? $handling->charges : 0;
            $min_amt = isset($handling->min_amt) && $handling->min_amt != '' ? $handling->min_amt : 0;
            $max_amt = isset($handling->max_amt) && $handling->max_amt != '' ? $handling->max_amt : 0;
            $deduction_type = isset($handling->deduction_type) && $handling->deduction_type != '' ? $handling->deduction_type : '1';
            $values .= "($acno,'$deduction_type',$min_amt,$max_amt,$charges_amt),";
        }
        $final_query = rtrim($values, ", ");
        $query = "INSERT INTO `customer_cash_handling_charges`(`customer_acno`,`charges_type`,`min_amt`, `max_amt`, `handling_charges`) 
        VALUES $final_query";
        $dbobjx->query($query);
        if ($dbobjx->execute()) {
            $check = 1;
        } else {
            $check = 0;
        }
    }
    // for cash additional charges
    if (isset($charges->additional_charges) && count($charges->additional_charges) > 0) {
        $values = "";
        foreach ($charges->additional_charges as $key => $additional_charges) {
            $service = isset($additional_charges->service_id) ? $additional_charges->service_id : 0;
            $charges_type = isset($additional_charges->charges_type) ? $additional_charges->charges_type : 0;
            $charges_amt = isset($additional_charges->charges_amt) && $additional_charges->charges_amt != '' ? $additional_charges->charges_amt : 0;
            $deduction = isset($additional_charges->deduction_type) && $additional_charges->deduction_type != '' ? $additional_charges->deduction_type : '1';
            $values .= "($acno,$service,$charges_type,$deduction,$charges_amt),";
        }
        $final_query = rtrim($values, ", ");
        $query = "INSERT INTO `customer_additional_charges` 
        (`customer_acno`,`service_id`,`charges_type`,`deduction_type`,`charges_amt`) VALUES $final_query";
        $dbobjx->query($query);
        if ($dbobjx->execute()) {
            $check = 1;
        } else {
            $check = 0;
        }
    }
    if ($check == 1) {
        echo response("1", "Success", "Charges added successfully");
    } else {
        echo response("0", "Error !", "Something went wrong while insreting");
    }
} catch (Exception $e) {
    echo response("0", "Api Error !", $e);
}
//     } else {
//         if ($valid_key === 401) {
//             echo response("0", "Invalid Secret Key", "Secret key is incorect");
//         } elseif ($valid_key === 404) {
//             echo response("0", "Authantication faild", "Client Id is not correct");
//         }
//     }
// } else {
//     echo response("0", "Unauthorized", $has_key);
// }