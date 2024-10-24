<?php
include "../../index.php";
// $registerSchema = json_decode(file_get_contents('../schema/stations/add.json'));
$request = json_decode(file_get_contents('php://input'));
// $valid = json_decode(requestvalidateobject($request, $registerSchema));
try {
    $tpl_consignment = isset($request->tpl_consignment) ? $request->tpl_consignment : '';

    $query = "UPDATE `shipments` SET `courier_id`= 0,`account_id`= 0,`courier_mapping_id`= 0,`thirdparty_consignment_no`= NULL,`thirdparty_booking_date`= NULL,`updated_at`= CURRENT_TIMESTAMP() WHERE `thirdparty_consignment_no` = '$tpl_consignment'";
    $dbobjx->query($query);
    $result = $dbobjx->execute();
    $dbobjx->close();
    if ($result) {
        $msg = "Third party consigment void successfully";
    } else {
        $msg = "something went wrong while updating";
    }
    echo response("1", "Success", $msg);
} catch (Exception $e) {
    echo response("0", "Api Error !", $e);
}
