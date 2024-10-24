<?php
include ("../index.php");
$request = json_decode(file_get_contents('php://input'));
try {
    $acc_type = isset($request->acc_type) ? $request->acc_type : '';
    $company_id = isset($request->company_id) ? $request->company_id : '';
    $customer_acno = isset($request->customer_acno) ? $request->customer_acno : '';
    $start_date = isset($request->start_date) ? $request->start_date : '';
    $end_date = isset($request->end_date) ? $request->end_date : '';
    $tabel = 'customers';
    switch ($acc_type) {
        case '1':
            $more = "AND `parent_id` IS NULL";
            break;
        case '6':
            $query = "SELECT id FROM `customers` WHERE `acno` = '$customer_acno' AND `active` = '1'";
            $dbobjx->query($query);
            $parent = $dbobjx->single();
            $more = "AND `parent_id` = '$parent->id'";
            break;
        default:
            $more = "AND `parent_id`  IS NULL";
        break;
    }
    if ($tabel != '' && $acc_type != '7') {
        $query = "SELECT DATE(created_at) AS created_date, COUNT(id) AS accounts_count 
        FROM $tabel 
        WHERE `company_id` = '$company_id' $more
          AND `active` = '1' 
          AND `is_deleted` = 'N' 
          AND created_at >= '$start_date 00:00:00' 
          AND created_at <= '$end_date 23:59:00'
        GROUP BY created_date
        ORDER BY created_date;";
        $dbobjx->query($query);
        $dbobjx->execute();
        $accounts_count = $dbobjx->resultset();
    } else {
        $accounts_count = [];
    }
    $counts[] = array(
        "accounts" => $accounts_count,
    );
    echo response("1", "success", $counts);
} catch (Exception $error) {
    echo response("0", "Error!", $error->getMessage());
}
