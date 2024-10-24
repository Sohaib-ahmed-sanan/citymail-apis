<?php
include "../index.php";
$request = json_decode(file_get_contents('php://input'));
$company_id = ( isset($request->company_id)? $request->company_id : '');

try {
    if (isset($customer) && $customer != '') {
        $more = "`customer_id` = $customer";
    } else {
        $more = "";
    }
    $query = "SELECT * FROM `shipments` WHERE `company_id` = $company_id AND `is_deleted` = 'N' $more";
    $dbobjx->query($query);
    $result = $dbobjx->resultset();
    if (isset($start_date) && $start_date != '' || isset($type) && $type == 'drop') {
        echo response("1", "Success", $result);
    } else {
        echo json_encode($result);
    }
} catch (Exception $e) {
    echo response("0", "Api Error !", $e);
}