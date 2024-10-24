<?php
include ("../index.php");
$request = json_decode(file_get_contents('php://input'));
try {
    $company_id = isset($request->company_id) ? $request->company_id : '';
    $customer_acno = isset($request->customer_acno) ? $request->customer_acno : '';
    $start_date = isset($request->start_date) ? $request->start_date : '';
    $end_date = isset($request->end_date) ? $request->end_date : '';
    if($customer_acno == '')
    {
        $acc_type = '1';
    }else{
        $query = "SELECT `parent_id` FROM `customers` WHERE `acno` = '$customer_acno'";
        $dbobjx->query($query);
        $res  = $dbobjx->single();
        if($res->parent_id == null)
        {
            $acc_type = '6';
        }else{
            $acc_type = '7';
        }
    }
    $params = ["acc_type" => $acc_type, "company_id" => $company_id, "customer_acno" => $customer_acno, "start_date" => $start_date, "end_date" => $end_date];
    $statics = getAPIdata(API_URL . 'dashboard/order_statics', $params);
    $statuses = getAPIdata(API_URL . 'dashboard/shipments_status', $params);
    
    $top_customers = getAPIdata(API_URL . 'dashboard/top-customers', $params);
    $customer_accounts = getAPIdata(API_URL . 'dashboard/accounts', $params);
    if($acc_type === '1')
    {
        $tpl_data = getAPIdata(API_URL . 'dashboard/tpl_accounts', $params);
        $tpl = $tpl_data->payload;
    }else{
        $tpl = [];
    }
    $order_statics = $statics->payload;
    $top_customers_data = $top_customers->payload; 
    $delivary_status = $statuses->payload; 
    $accounts_count = $customer_accounts->payload; 
    $tpl_accounts = $tpl; 
    $return = [
        "statics" => $order_statics,
        "delivary_status" => $delivary_status,
        "top_customers" => $top_customers_data,
        "accounts_count" => $accounts_count,
        "tpl_accounts" => $tpl_accounts,
    ];
    echo response("1", "Counts", $return);
} catch (Exception $error) {
    echo response("0", "Error!", $error->getMessage());
}
