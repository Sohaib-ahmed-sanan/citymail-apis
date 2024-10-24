<?php
include "../index.php";
$registerSchema = json_decode(file_get_contents('../schema/arrivals/add.json'));
$request = json_decode(file_get_contents('php://input'));
$valid = json_decode(requestvalidateobject($request, $registerSchema));
try {
    $peices = isset($request->peices) ? $request->peices : '';
    $weight = isset($request->weight) ? $request->weight : '';
    $cn_no = isset($request->cn_number) ? $request->cn_number : '';
    $weight_charges = null;
    $service_id = isset($request->service_id) ? $request->service_id : '';
    $customer_acno = isset($request->customer_acno) ? $request->customer_acno : '';
    $origin_city = isset($request->origin_city) ? $request->origin_city : '';
    $destination_city = isset($request->destination_city) ? $request->destination_city : '';
    $origin_country = isset($request->origin_country) ? $request->origin_country : '';
    $destination_country = isset($request->destination_country) ? $request->destination_country : '';
    $cod_amt = isset($request->cod_amt) ? $request->cod_amt : '';
    $gst_type = $gst_charges = $sst_type = $sst_charges = $bank_charge_type = $bank_charges = 0;
    // print_r($customer_acno);die;
    // to get the customer ano in cases of sub account 
    if ($customer_acno == '') {
        $query = "SELECT shipments.customer_acno,customers.parent_id 
        FROM `shipments` 
        LEFT JOIN `customers` ON shipments.customer_acno = customers.acno 
        WHERE `consignment_no` = '$cn_no'";
        $dbobjx->query($query);
        $info = $dbobjx->single();
        // print_r($info);die;
        if ($info->parent_id != '' && $info->parent_id != null) {
            $query = "SELECT acno
            FROM `customers` 
            WHERE `id` = '$info->parent_id'";
            $dbobjx->query($query);
            $res = $dbobjx->single();
            $customer_acno = $res->acno;
        } else {
            $customer_acno = $info->customer_acno;
        }
    }

    $region = "DEF";
    if (in_array($destination_country, ['395'])) {
        $query = "SELECT `zone` FROM `cities` WHERE `id` = '$destination_city'";
        $dbobjx->query($query);
        $res = $dbobjx->single();
        $region = $res->zone;
    }

    $tarif_query = "SELECT * FROM `customer_tariffs` WHERE `customer_acno` = '$customer_acno' AND `service_id` = '$service_id' AND `origin_country` = '$origin_country' AND `destination_country` = '$destination_country' AND `region` = '$region'";
    $dbobjx->query($tarif_query);
    $tariffs = $dbobjx->resultset();
    $erors = [];
    $check = null;
    if (count($tariffs) > 0) {
        foreach ($tariffs as $key => $row) {
            if (($weight >= $row->start_weight) && ($weight <= $row->end_weight)) {
                $weight_charges = round($row->charges, 0);
                $rto_charges = $row->rto_charges;
                $tarif_id = $row->id;
                break;
            } elseif ($row->region == $region && $row->additional_weight > 0) {
          
            if($row->additional_weight == 0.5){
                $advance_weight = ((round($weight*2)/2)-$row->end_weight)*2;
                $weight_charges = round($row->charges+($advance_weight*$row->additional_charges),0);
                $rto_charges = round($row->rto_charges + ($advance_weight*$row->additional_rto_charges), 0);
            }else{
                $weight_charges = round($row->charges+(ceil(($weight-$row->end_weight))*$row->additional_charges),0);
            }
                $tarif_id = $row->id;
                break;
            }
        }

        // for cash handling charges
        $handling_charges_query = "SELECT * FROM `customer_cash_handling_charges` WHERE `customer_acno` = '$customer_acno'";
        $dbobjx->query($handling_charges_query);
        $cash_handling = $dbobjx->resultset();
        $handling_charges = 0;
        $handling_charges_type = null;
        if (count($cash_handling) > 0) {
            foreach ($cash_handling as $key => $handling) {
                if ((round($cod_amt) >= $handling->min_amt) && (round($cod_amt) <= $handling->max_amt)) {
                    if ($handling->charges_type == '1') {
                        $handling_charges = round($handling->handling_charges);
                        $handling_charges_type = 'Flat';
                    } else if ($handling->charges_type == '2') {
                        $handling_charges = round(($cod_amt * $handling->handling_charges) / 100);
                        $handling_charges_type = 'Percent';
                    }
                    break;
                }
            }
        } else {
            $handling_charges = 0;
        }
        // print_r($handling_charges);die;
        // for additional charges
        $additional_charges_query = "SELECT * FROM `customer_additional_charges` WHERE `customer_acno` = '$customer_acno' AND `service_id` = '$service_id'";
        $dbobjx->query($additional_charges_query);
        $additional = $dbobjx->resultset();
        if (count($additional) > 0) {
            foreach ($additional as $key => $addition) {
                switch ($addition->charges_type) {
                    case '1': //gst
                        if ($addition->deduction_type == '1') {
                            $gst_charges = round($addition->charges_amt);
                            $gst_type = "Flat";
                        } else if ($addition->deduction_type == '2') {
                            $gst_charges = round(($weight_charges * $addition->charges_amt) / 100);
                            $gst_type = "Percent";
                        }
                        break;
                    case '2': //sst
                        if ($addition->deduction_type == '1') {
                            $sst_charges = round($addition->charges_amt);
                            $sst_type = "Flat";
                        } else if ($addition->deduction_type == '2') {
                            $sst_charges = round(($cod_amt * $addition->charges_amt) / 100);
                            $sst_type = "Percent";
                        }
                        break;
                    case '3': //bank
                        if ($addition->deduction_type == '1') {
                            $bank_charges = round($addition->charges_amt);
                            $bank_charge_type = "Flat";
                        } else if ($addition->deduction_type == '2') {
                            $bank_charges = round(($cod_amt * $addition->charges_amt) / 100);
                            $bank_charge_type = "Percent";
                        }
                        break;

                    default:
                        break;
                }
            }
        }

        $total = $weight_charges + $handling_charges + $gst_charges + $sst_charges + $bank_charges;
        $data = [
            'customer_acno' => $customer_acno,
            'weight' => round($weight, 2),
            'peices' => round($peices, 2),
            'region' => $region,
            'weight_charges' => $weight_charges,
            'handling_charges_type' => $handling_charges_type,
            'handling_charges' => $handling_charges,
            'gst_type' => $gst_type,
            'gst_charges' => $gst_charges,
            'sst_type' => $sst_type,
            'sst_charges' => $sst_charges,
            'bank_charge_type' => $bank_charge_type,
            'bank_charges' => $bank_charges,
            'total_charges' => $total,
            'rto_charges' => $rto_charges,
            'tarif_id' => $tarif_id,
        ];
        // print_r($data    );die;
        echo response("1", "Success", $data);
    } else {
        echo response("0", "Tarif not found", 'No tarifs are defined.');
    }

} catch (Exception $e) {
    echo response("0", "Api Error !", $e);
}