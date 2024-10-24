<?php
include ("../index.php");
$registerSchema = json_decode(file_get_contents('../schema/auth/get-credientials.json'));
$request = json_decode(file_get_contents('php://input'));
try {
    $customer_acno = isset($request->customer_acno) ? $request->customer_acno : '';
    
    if ($customer_acno == '') {
        echo response("0", "Error", "Please provide customer acno");
        exit();
    }
    
    $query = "SELECT `id`,`company_id`,`parent_id`,`acno`,`secret_key` FROM `users` WHERE acno = '$customer_acno'";
    $dbobjx->query($query);
    $result = $dbobjx->single();
    if ($dbobjx->rowCount() > 0) {
        $data = [
            "company_id" => $result->company_id,
            "client_id" => $result->id,
            "secret" => base64_encode('6') . '%' . $result->secret_key . '!' . $result->company_id . ':' . time(),
            "token-timeout" => '1800 s',
        ];   
        $dbobjx->close();     
        echo response("1", "Success", $data);
    }else{
        echo response("0", "Error", "Please provide valid customer acno");
        exit();
    }
} catch (Exception $e) {
    echo response("0", "Something Went Wrong", []);
}
