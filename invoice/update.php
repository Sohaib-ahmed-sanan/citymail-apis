<?php
include "../index.php";
$request = json_decode(file_get_contents('php://input'));
$headers = getallheaders();
include "../functions/siteFunctions.php";
$has_key = authorization();
if ($has_key == 200) {
    $valid_key = authantication($dbobjx);
    if ($valid_key == 200) {
        try {
            $company_id = $request->company_id;
            $invoice_id = $request->invoice_id;
            $updated_by = $headers['Client-Id'];
            $query = "UPDATE `cbc_invoice` SET `status`='1',`updated_at`= CURRENT_TIMESTAMP(),`updated_by`='$updated_by' WHERE `id` = '$invoice_id' AND `company_id` = '$company_id'";
            $dbobjx->query($query);
            if ($dbobjx->execute()) {
                $query = "SELECT `consignment_no` FROM `invoice_details` WHERE `invoice_id` = '$invoice_id' AND `company_id` = '$company_id'";
                $dbobjx->query($query);
                $data = $dbobjx->resultset();
                $cn_numbers = "";
                foreach($data as $cn_no){
                    $cn_numbers .= "$cn_no->consignment_no,";                    
                }
                $final_cn = rtrim($cn_numbers, ", ");
                $update = "UPDATE `shipments` SET `payment_status`='1',`updated_by` = '$updated_by',`updated_at` = CURRENT_TIMESTAMP() WHERE `consignment_no` IN($final_cn)";
                $dbobjx->query($update);
                $dbobjx->execute();
                echo response("1", "Success", "Invoice has been updated");
            } else {
                echo response("0", "Error", "Something went wrong while updating invoice");
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