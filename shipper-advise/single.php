<?php
include "../index.php";
$request = json_decode(file_get_contents('php://input'));
try {
    $company_id = $request->company_id;
    $consignment_no = $request->consignment_no;

    $query = "SELECT shipper_advise.*,users.first_name FROM `shipper_advise` 
    LEFT JOIN `users` ON users.id = shipper_advise.user_id
    WHERE shipper_advise.company_id AND shipper_advise.consignment_no 
    ORDER BY shipper_advise.created_at ASC";

    $dbobjx->query($query);
    $result = $dbobjx->resultset();
    echo response("1", "Success", $result);
} catch (Exception $e) {
    echo response("0", "Api Error !", $e);
}