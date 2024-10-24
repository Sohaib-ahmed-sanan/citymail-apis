<?php
include ("../index.php");
$request = json_decode(file_get_contents('php://input'));
try {
    $acc_type = isset($request->acc_type) ? $request->acc_type : '';
    $company_id = isset($request->company_id) ? $request->company_id : '';
    $customer_acno = isset($request->customer_acno) ? $request->customer_acno : '';
    $start_date = isset($request->start_date) ? $request->start_date : '';
    $end_date = isset($request->end_date) ? $request->end_date : '';
    switch ($acc_type) {
        case '1':
            $more = "";
            break;
        case '6':
            $query = "SELECT id FROM `customers` WHERE `acno` = '$customer_acno' AND `active` = '1'";
            $dbobjx->query($query);
            $parent = $dbobjx->single();
            $query = "SELECT acno FROM `customers` WHERE `parent_id` = '$parent->id' AND `active` = '1'";
            $dbobjx->query($query);
            $result = $dbobjx->resultset();
            $acnos = array_column($result, 'acno');
            $acnosString = implode(',', $acnos);
            if ($acnosString != '') {
                $customer_acnos = $customer_acno . ',' . $acnosString;
            } else {
                $customer_acnos = $customer_acno;
            }
            $more = "AND shipments.customer_acno IN($customer_acnos)";
            break;
        case '7':
            $more = "AND shipments.customer_acno = '$customer_acno'";
            break;
        default:
            $more = "";
            break;
    }
    $query = "SELECT status.id , status.name ,  
        SUM((CASE WHEN shipments.status IS NOT NULL THEN 1 ELSE 0 END)) status_count
        FROM delivery_status as status
        LEFT JOIN shipments ON shipments.status = status.id AND shipments.active NOT IN ('0') 
        AND shipments.company_id = '$company_id' $more
         AND shipments.created_at >= '$start_date 00:00:00' 
         AND shipments.created_at <= '$end_date 23:59:00'
        Where status.id IN (1,4,6,2,14,16,21,24,9,13)
        GROUP BY status.id , status.name";
    $dbobjx->query($query);
    // print_r($query);die;
    $result = $dbobjx->resultset();
    $status = [];
    foreach ($result as $data) {
        $status[] = array(
            "status_id" => $data->id,
            "status_name" => $data->name,
            "shipment_count" => $data->status_count,
        );
    }
    echo response("1", "success", $status);
} catch (Exception $error) {
    echo response("0", "Error!", $error->getMessage());
}
