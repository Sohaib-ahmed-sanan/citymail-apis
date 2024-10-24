<?php
include ("../index.php");
$request = json_decode(file_get_contents('php://input'));
try {
    $acc_type = isset($request->acc_type) ? $request->acc_type : '';
    $company_id = isset($request->company_id) ? $request->company_id : '';
    $customer_id = isset($request->customer_id) ? $request->customer_id : '';
    $sub_account_id = isset($request->sub_account_id) ? $request->sub_account_id : '';
    $start_date = isset($request->start_date) ? $request->start_date : '';
    $end_date = isset($request->end_date) ? $request->end_date : '';

    $query = "SELECT cd.id,cd.courier_id,c.courier_name,cd.account_title 
    FROM courier_details cd
    INNER JOIN couriers c ON c.id = cd.courier_id AND c.`active`='1' 
    WHERE cd.company_id = '$company_id' AND cd.is_deleted = 'N' ORDER BY cd.courier_id";
    $dbobjx->query($query);
    $result = $dbobjx->resultset();
    $data = [];
    // print_r($result);die;
    foreach($result as $value){
        $courier_id = $value->courier_id;
        $customer_courier_id = $value->id;
        $icon = "{$value->courier_name}.svg";
        $width = "60";
        $thirdparty=strtolower(str_replace(' ', '', $value->courier_name));
        $query = "SELECT status.`id` , status.`name` ,
        SUM((CASE WHEN shipments.thirdparty_consignment_no IS NOT NULL THEN 1 ELSE 0 END)) shipments
        FROM delivery_status As status
        LEFT JOIN shipments ON shipments.`status` = status.`id` AND
        DATE(shipments.created_at) BETWEEN '$start_date' AND '$end_date' AND company_id = '$company_id'
        AND courier_id = '$courier_id' AND account_id = '$customer_courier_id'
        WHERE status.id IN (1, 4, 2, 13, 14, 16,24)
        GROUP BY status.`id` , status.`name`
        ORDER BY id ASC";
        $dbobjx->query($query);
        $response = $dbobjx->resultset();
        $detail = [];
        $total_shipments = 0;
        foreach($response as $row){
            $total_shipments += $row->shipments;
            $detail[] = array(
                'status_id' => $row->id,
                'status_name' => $row->name,
                'shipments' => $row->shipments
            );
        }
        $data[] = array(
            'courier_id'     => $courier_id,
            'customer_courier_id'     => $customer_courier_id,
            'courier_name'   => $value->courier_name,
            'account_title'   => $value->account_title,
            'logo' => "assets/img/shipping-icons/{$icon}",
            'width' => $width,
            'thirdparty' => str_replace('&', '', $thirdparty),
            'total_shipments' => $total_shipments,
            'detail' => $detail,
        );
    }
    echo response("1", "success", $data);
} catch (Exception $error) {
    echo response("0", "Error!", $error->getMessage());
}
