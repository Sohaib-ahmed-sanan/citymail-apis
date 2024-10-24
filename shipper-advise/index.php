<?php
include "../index.php";
$request = json_decode(file_get_contents('php://input'));
try {
    $company_id = $request->company_id;
    $start_date = isset($request->start_date) ? $request->start_date : '';
    $end_date = isset($request->end_date) ? $request->end_date : '';
    $customer_acno = isset($request->customer_acno) ? $request->customer_acno : '';
    $status_id = isset($request->status_id) ? $request->status_id : '';
    // Default condition for date range
    $more = "AND shipments.created_at BETWEEN '$start_date 00:00:00' AND '$end_date 23:59:59'";
 
    if ($customer_acno != null) {
        $more .= " AND shipments.customer_acno IN ($customer_acno)";
    }
    if ($status_id != null) {
        $more .= " AND shipments.status = $status_id";
    }
    // Final query
    $query = "SELECT shipments.*, delivery_status.name AS status_name,customers.business_name As business_name,customers.acno As acno,
    customers.name As account_name,customers.id As account_id,customers.acno As account_no, customers.parent_id As parent_id
        FROM  `shipments` 
        LEFT JOIN `delivery_status` ON shipments.status = `delivery_status`.`id` 
        LEFT JOIN `customers` ON shipments.customer_acno = `customers`.`acno` 
        WHERE shipments.company_id = '$company_id'
        AND shipments.is_deleted = 'N' 
        $more ORDER BY shipments.id DESC";
    // echo $query;
    // die;
    $dbobjx->query($query);
    $result = $dbobjx->resultset();
    echo response("1", "Success", $result);
} catch (Exception $e) {
    echo response("0", "Api Error !", $e);
}