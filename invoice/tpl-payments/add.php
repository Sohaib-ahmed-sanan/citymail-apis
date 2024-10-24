<?php
include "../../index.php";
$request = json_decode(file_get_contents('php://input'));
include "../../functions/siteFunctions.php";
$has_key = authorization();
if ($has_key == 200) {
    $valid_key = authantication($dbobjx);
    if ($valid_key == 200) {
        try {
            $headers = getallheaders();
            $updated_by = $headers['Client-Id'];
            $consignments = isset($request->consignments) ? $request->consignments : '';
            
            $imploded_cn = implode(',',$consignments);
            $query = "UPDATE `shipments` SET `updated_at`= CURRENT_TIMESTAMP(),`updated_by`= '$updated_by',`with_cashier`='1' WHERE `consignment_no` IN ($imploded_cn)";
            $dbobjx->query($query);
            if($dbobjx->execute())
            {
                echo response("1", "Success", 'Updated successfully');
            }else{
                echo response("0", "Error", 'Something went wrong');
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