<?php
include "../index.php";
$request = json_decode(file_get_contents('php://input'));
try {
    $company_id = $request->company_id;
    $consignment_no = $request->consignment_no;
    $msg = $request->message;
    $sender_id = $request->sender_id;

    $query = "INSERT INTO `shipper_advise`(`company_id`,`consignment_no`,`status`,`status_date`,`chat`,`user_id`)
     VALUES ('$company_id','$consignment_no',null,null,'$msg','$sender_id')";
    $dbobjx->query($query);
    $dbobjx->execute();
    echo response("1", "Success", $result);
} catch (Exception $e) {
    echo response("0", "Api Error !", $e);
}