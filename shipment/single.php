<?php
include "../index.php";
$request = json_decode(file_get_contents('php://input'));
$id = $request->id;
try {
    $query = "SELECT * FROM `shipments` WHERE `id` = $id AND `is_deleted` = 'N'"; 
    $dbobjx->query($query);
    $data = $dbobjx->single();
    echo response("1", "Success", $data);
} catch (Exception $e) {
    echo response("0", "Api Error !", $e);
}