<?php
include ("../index.php");
$request = json_decode(file_get_contents('php://input'));
try {
    $customer_acno = isset($request->customer_acno) ? $request->customer_acno : 0;
    $company_id = isset($request->company_id) ? $request->company_id : 0;
    $type = isset($request->type) ? $request->type : '';
    if ($type == 'all') {
        $query = "SELECT * FROM `services` WHERE `active` = '1'";
    }
    if ($type == '6' || $type == 'add_shipment') {
        $get_parent = "SELECT parent_id,id 
            FROM customers
            WHERE acno = $customer_acno";
        $dbobjx->query($get_parent);
        $get =  $dbobjx->single();
        if($get->parent_id == '')
        {
            $acno = $customer_acno;
        }else{
            $get_acno = "SELECT acno 
            FROM customers
            WHERE id = $get->parent_id";
            $dbobjx->query($get_acno);
            $get =  $dbobjx->single();
            $acno = $get->acno;
        }
        $acno = $acno != '' ? $acno : '0';
        $query = "SELECT * FROM `customer_services` WHERE `acno` = $acno";
    }
    $dbobjx->query($query);
    $dbobjx->execute();
    if ($type === 'add_shipment') {
        $res = $dbobjx->single();
        $ids = $res->service_id;
        $arr = [];
        $query = "SELECT * FROM `services` WHERE `id` IN ($ids) AND `active` = '1'";
        $dbobjx->query($query);
        $data = $dbobjx->resultset();
        foreach ($data as $key => $value) {
            $arr[$value->service_code] = $value->id;
        }
        echo response("1", "success", $arr);
    } else {
        echo json_encode($dbobjx->resultset());
    }
} catch (Exception $error) {
    echo response("0", "Error!", $error->getMessage());
}
