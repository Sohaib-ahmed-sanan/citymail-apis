<?php
include "../index.php";
$registerSchema = json_decode(file_get_contents('../schema/pickups/add.json'));
$request = json_decode(file_get_contents('php://input'));
$valid = json_decode(requestvalidateobject($request, $registerSchema));
include "../functions/siteFunctions.php";
$has_key = authorization();
if ($has_key) {
    $valid_key = authantication($dbobjx);
    if ($valid_key === true) {
        if ($valid->status) {
            try {
                $check = null;
                $company_id = isset($request->company_id) ? $request->company_id : '';
                $created_by = isset($request->created_by) ? $request->created_by : '';
                $data = isset($request->data) ? $request->data : '';
                $loadsheet_sheet_no = [];
                foreach ($data as $row) {
                    $loadsheet_no = $row->sheet_no != '' ? $row->sheet_no : null;
                    $rider_id = $row->rider_id != '' ? $row->rider_id : null;
                    if ($loadsheet_no != null && $rider_id != null) {
                        $query = "INSERT INTO `pickups`(`company_id`,`created_by`,`loadsheet_id`, `rider_id`) VALUES ($company_id,$created_by,$loadsheet_no,$rider_id)";
                        $dbobjx->query($query);
                        if ($dbobjx->execute()) {
                            $loadsheet_sheet_no [] = $loadsheet_no;
                        }
                    } else {
                        echo response("0", "Value error", "Some values are missing");
                    }
                }
                $imploded = implode(',',$loadsheet_sheet_no);
                $update = "UPDATE `loadsheets` SET `status`='1' WHERE `sheet_no` IN($imploded)";
                $dbobjx->query($update);
                if ($dbobjx->execute()) {
                    echo response("1", "Success", "Pickup sheet has been created");
                } else {
                    echo response("0", "Error", "Something went wrong while inserting details");
                }
            } catch (Exception $e) {
                echo response("0", "Error !", $e);
            }
        } else {
            echo response("0", "Api Error !", $valid->error);
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
