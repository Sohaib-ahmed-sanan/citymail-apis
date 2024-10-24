<?php
include "../index.php";
$registerSchema = json_decode(file_get_contents('../schema/delivery_sheet/update.json'));
$request = json_decode(file_get_contents('php://input'));
$valid = json_decode(requestvalidateobject($request, $registerSchema));
include "../functions/siteFunctions.php";
$has_key = authorization();
if ($has_key) {
    $valid_key = authantication($dbobjx);
    if ($valid_key === true) {
        if ($valid->status) {
            try {
                $company_id = isset($request->company_id) ? $request->company_id : '';
                $sheet_no = isset($request->sheet_no) ? $request->sheet_no : '';
                $updated_by = isset($request->updated_by) ? $request->updated_by : '';
                $details = isset($request->details) ? $request->details : [];
                if ($dbobjx->rowCount() > 0) {
                    $sheet_id = $sheet_no;
                    foreach ($details as $key => $data) {
                        $consignmet = $data->consignmet;
                        $status_id = $data->status;
                        $remark = $data->remarks;
                        $query = "UPDATE `delivery_sheet_details` SET `remarks`='$remark',`status_id`='$status_id' WHERE `consignment_no` = '$consignmet' AND `sheet_id` = '$sheet_id'";
                        $dbobjx->query($query);
                        if ($dbobjx->execute()) {
                            $update = "UPDATE `shipments` SET `status`= '$status_id' , `updated_at` = CURRENT_TIMESTAMP(),`updated_by` = '$updated_by' WHERE `consignment_no` = '$consignmet'";
                            $dbobjx->query($update);
                            $dbobjx->execute();
                            track_status($dbobjx, $consignmet, $status_id);
                        }
                    }
                    echo response("1", "Success", "Sheet has been updated");
                } else {
                    echo response("0", "Invalid Sheet No", "No sheet is present with this sheet no");
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