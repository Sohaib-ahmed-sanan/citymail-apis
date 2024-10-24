<?php
include "../index.php";
// $registerSchema = json_decode(file_get_contents('../schema/demanifists/add.json'));
$request = json_decode(file_get_contents('php://input'));
// $valid = json_decode(requestvalidateobject($request, $registerSchema));
include "../functions/siteFunctions.php";
$has_key = authorization();
if ($has_key == 200) {
    $valid_key = authantication($dbobjx);
    if ($valid_key === true) {
        // if ($valid->status) {
        try {
            $company_id = isset($request->company_id) ? $request->company_id : '';
            $invoice_date = $request->invoice_date;
            $updated_by = get_user_id_header();
            $cheque_no = $request->cheque_no??"";
            $cheque_title = $request->cheque_title??"";
            $payment_type = $request->payment_type??"";
            $referance = $request->referance??"";
            $start_date = $request->start_date;
            $end_date = $request->end_date;
            $customer_acno = $request->customer_acno;
            
            $total_cod = $request->total_cod;
            $total_charges = $request->total_charges;
            $total_cash_handling = $request->total_cash_handling;
            $total_gst = $request->total_gst;
            $total_sst = $request->total_sst;
            $total_bac = $request->total_bac;
            $total_rto_charges = $request->total_rto_charges;
            
            $details = json_decode($request->details);
            $cn_count = count($details);
            $query = "SELECT `invoice_no` FROM `cbc_invoice` ORDER BY `id` DESC LIMIT 1";
            $dbobjx->query($query);
            $last_invoice = $dbobjx->single();
            if($last_invoice->invoice_no != "")
            {
                $invoice_no = $last_invoice->invoice_no + 1;
            }else{
                $invoice_no = 10000001;                
            }
            // $invoice_no = rand(0, 9999999999);
            $main = "INSERT INTO 
            `cbc_invoice`
            (`company_id`, `customer_acno`, `invoice_no`, `cheque_no`, `cheque_title`,`payment_type`,`referance`,
            `consignment_count`, `invoice_creation_date`, `invoice_from_date`, `invoice_to_date`,`total_cod`,`total_charges`,
            `total_cash_handling`,`total_gst`,`total_sst`,`total_bac`,`total_rto`)VALUES ($company_id,$customer_acno,$invoice_no,'$cheque_no','$cheque_title','$payment_type','$referance',$cn_count,'$invoice_date','$start_date','$end_date'
                ,'$total_cod','$total_charges','$total_cash_handling','$total_gst','$total_sst','$total_bac','$total_rto_charges')";
            $dbobjx->query($main);
            if ($dbobjx->execute()) {
                $invoice_id = $dbobjx->lastInsertId();
                $log = [
                    "invoice_id" => $invoice_id,
                    "invoice_no" => $invoice_no,
                    "created_by" => $updated_by
                ];
                foreach ($details as $data) {
                    $get_charges = "SELECT `gst_charges`,`total_charges`,`handling_charges`,`sst_charges`,`bac_charges`,`service_charges`,`rto_charges` FROM `shipments`  WHERE `consignment_no` = '$data->consignment'";
                    $dbobjx->query($get_charges);
                    $charges = $dbobjx->single();
                    $old_service_charges = $charges->service_charges;
                    $rto_charges = $charges->rto_charges;
                    $gst_charges = $charges->gst_charges;
                    $handling_charges = $charges->handling_charges;
                    $sst_charges = $charges->sst_charges;
                    $bac_charges = $charges->bac_charges;
                    // first minus the service charges from the total then add the final changed service charges in it 
                    $total_old_charges = $charges->total_charges - $old_service_charges;

                    $final_total = $total_old_charges + $data->service_charges;

                    $query = "INSERT INTO `invoice_details`(`invoice_id`, `company_id`, `customer_acno`, `consignment_no`,`service_charges`,`old_service_charges`,`rto_charges`) VALUES ('$invoice_id','$company_id','$customer_acno','$data->consignment','$data->service_charges','$data->old_service_charges','$rto_charges')";
                    $dbobjx->query($query);
                    if ($dbobjx->execute()) {
                        $update = "UPDATE `shipments` SET `updated_by` = '$updated_by',`updated_at` = CURRENT_TIMESTAMP(),`service_charges`='$data->service_charges',`total_charges`='$final_total' WHERE `consignment_no` = '$data->consignment'";
                        $dbobjx->query($update);
                        $dbobjx->execute();
                        $log[] = [$data];
                    }
                }
                $log_array = json_encode($log);
                $query = "INSERT INTO `invoice_logs`(`company_id`,`invoice_id`,`log`) VALUES ('$company_id','$invoice_id','$log_array')";
                $dbobjx->query($query);
                $dbobjx->execute();
                echo response("1", "Invoice has been added", "$invoice_id");
            } else {
                echo response("0", "Error", "Something went wrong while creating invoice");
            }
        } catch (Exception $e) {
            echo response("0", "Api Error !", $e);
        }
        // } else {
        //     echo response("0", "Error !", $valid->error);
        // }
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