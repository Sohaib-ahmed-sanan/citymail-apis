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
            $sum_income = "SUM(CASE WHEN payment_status ='1'THEN service_charges ELSE 0 END ) AS total_ammount,SUM(CASE WHEN payment_status = '0' THEN service_charges ELSE 0 END) AS unpaid_ammount";
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
            $more = "AND `customer_acno` IN($customer_acnos)";
            $sum_income = "SUM(CASE WHEN payment_status ='1'THEN order_amount ELSE 0 END ) AS total_ammount,SUM(CASE WHEN payment_status = '0' THEN order_amount ELSE 0 END) AS unpaid_ammount";
            break;
        case '7':
            $more = "AND `customer_acno` = '$customer_acno'";
            $sum_income = "SUM(CASE WHEN payment_status ='1'THEN order_amount ELSE 0 END ) AS total_ammount,SUM(CASE WHEN payment_status = '0' THEN order_amount ELSE 0 END) AS unpaid_ammount";
            break;
        default:
            $sum_income = "SUM(CASE WHEN payment_status ='1'THEN service_charges ELSE 0 END ) AS total_ammount,SUM(CASE WHEN payment_status = '0' THEN service_charges ELSE 0 END) AS unpaid_ammount";
            $more = "";
            break;
    }

    // for orders count,total sum of prices
    $query = "SELECT DATE(created_at) AS order_date, COUNT(id) AS orders_count , $sum_income 
        FROM `shipments` 
        WHERE `company_id` = '$company_id' $more 
          AND `active` = '1' 
          AND `is_deleted` = 'N' 
          AND created_at >= '$start_date 00:00:00' 
          AND created_at <= '$end_date 23:59:00'
        GROUP BY order_date
        ORDER BY order_date;";
    $dbobjx->query($query);
    $orders = $dbobjx->resultset();
    $order_counts_arr = [];
    $order_counts = 0;
    $total_revenue_arr = [];
    $total_revenue = 0;
    $total_outstanding_arr = [];
    $total_outstanding = 0;
    foreach ($orders as $order) {
        $order_counts_arr[] = $order->orders_count;
        $order_counts += $order->orders_count;
        $total_revenue_arr[] = $order->total_ammount;
        $total_revenue += $order->total_ammount;
        if ($order->unpaid_ammount > 0) {
            $total_outstanding_arr[] = $order->unpaid_ammount;
            $total_outstanding += $order->unpaid_ammount;
        }
    }
    $counts[] = array(
        "order_counts" => $order_counts,
        "order_counts_arr" => $order_counts_arr,
        "total_revenue" => $total_revenue,
        "total_revenue_arr" => $total_revenue_arr,
        "total_outstanding" => $total_outstanding,
        "total_outstanding_arr" => $total_outstanding_arr,
    );
    echo response("1", "success", $counts);
} catch (Exception $error) {
    echo response("0", "Error!", $error->getMessage());
}
