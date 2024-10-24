<?php
include ("../index.php");
$request = json_decode(file_get_contents('php://input'));
try {
    $acno = $request->acno;
    $query = "SELECT `id` FROM `customers` WHERE `acno` = $acno";
    $dbobjx->query($query);
    $res = $dbobjx->single();
    $query = "SELECT `acno` FROM `customers` WHERE `parent_id` = '$res->id' AND `active` = '1'";
    // print_r($res);die;
    $dbobjx->query($query);
    $result = $dbobjx->resultset();
    $ids = array_column($result, 'acno');
    $acno_srting = implode(',', $ids);
    echo response("1", "Success", $acno_srting);
} catch (Exception $error) {
    echo response("0", "Error!", $error->getMessage());
}

