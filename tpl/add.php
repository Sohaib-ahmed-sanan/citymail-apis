<?php
include "../index.php";
$request = json_decode(file_get_contents('php://input'));
include "../functions/siteFunctions.php";

try {
    $cn_number = isset($request->consignment_no) ? $request->consignment_no : '';
    $third_consigment_no = isset($request->third_consigment_no) ? $request->third_consigment_no : '';
    $courier_id = isset($request->courier_id) ? $request->courier_id : '';
    $courier_mapping_id = isset($request->courier_mapping_id) ? $request->courier_mapping_id : 0;
    $customer_courier_id = isset($request->customer_courier_id) ? $request->customer_courier_id : '';
    // Final query
    $query = "UPDATE `shipments` SET `courier_id`='$courier_id',`account_id`='$customer_courier_id',`courier_mapping_id`='$courier_mapping_id',`thirdparty_consignment_no`='$third_consigment_no',`thirdparty_booking_date`= CURRENT_TIMESTAMP(),`updated_at`= CURRENT_TIMESTAMP() WHERE `consignment_no` = '$cn_number'";
    $dbobjx->query($query);
    $result = $dbobjx->execute();
    if ($result) {
        echo response("1", "Success","Third party generated successfully");
    } else {
        echo response("0", "Error","Something went wrong while updating");
    }
} catch (Exception $e) {
    echo response("0", "Api Error !", $e);
}
