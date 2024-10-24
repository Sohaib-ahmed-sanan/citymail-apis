<?php
include "../index.php";
$request = json_decode(file_get_contents('php://input'));
include "../functions/siteFunctions.php";
$has_key = authorization();
if ($has_key == 200) {
    $valid_key = authantication($dbobjx);
    if ($valid_key == 200) {
        try {
            $company_id = $request->company_id;
            $start_date = isset($request->start_date) ? $request->start_date : '';
            $end_date = isset($request->end_date) ? $request->end_date : '';
            $customer_acno = isset($request->customer_acno) ? $request->customer_acno : '';
            $more = "";
            if (isset($start_date) && $start_date != '') {
                $more .= "AND cbc_invoice.created_at BETWEEN '$start_date 00:00:00' AND '$end_date 23:59:59'";
            }
            if (isset($customer_acno) && $customer_acno != '') {
                $ids = implode($customer_acno);
                $more .= "AND cbc_invoice.customer_acno IN ($ids)";
            }

            $query = "SELECT cbc_invoice.*,customers.name As customer_name 
            FROM `cbc_invoice` 
            LEFT JOIN `customers` ON cbc_invoice.customer_acno = customers.acno 
            WHERE cbc_invoice.company_id = '$company_id' AND cbc_invoice.is_deleted = 'N' $more";

            // die;
            $dbobjx->query($query);
            $result = $dbobjx->resultset();
            echo response("1", "Success", $result);
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