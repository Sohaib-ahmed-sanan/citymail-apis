<?php
include "../index.php";
$request = json_decode(file_get_contents('php://input'));
// $valid = json_decode(requestvalidateobject($request, $registerSchema));
try {
    $account_id = $request->account_id;
    $query = "SELECT * FROM `courier_details` WHERE `id` = '$account_id' ";
    $dbobjx->query($query);
    $data = $dbobjx->single();
    echo response("1", "Success", $data);
} catch (Exception $e) {
    echo response("0", "Api Error !", $e);
}
