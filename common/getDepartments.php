<?php

include ("../index.php");
$request = json_decode(file_get_contents('php://input'));
try {
    $company_id = $request->company_id;
   
    $query = "SELECT * FROM `departments` WHERE `active` = '1'";
    $dbobjx->query($query);
    $dbobjx->execute();
    echo json_encode($dbobjx->resultset());
} catch (Exception $error) {
    echo response("0", "Error!", $error->getMessage());
}

