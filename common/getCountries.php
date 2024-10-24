<?php

include ("../index.php");
$request = json_decode(file_get_contents('php://input'));
try {
    $company_id = $request->company_id;
    $query = "SELECT `company_type` FROM `companies` WHERE `company_id` = '$company_id'";
    $dbobjx->query($query);
    $data = $dbobjx->single();
    $where = null;
    if ($data->company_type == 'D') {
        $where = "Where `id` = '449'"; // Get pakistan only
    }
    $country = "SELECT * FROM `countries` $where";
    // print_r($query);die;
    $dbobjx->query($country);
    $dbobjx->execute();

    echo json_encode($dbobjx->resultset());

} catch (Exception $error) {
    echo response("0", "Error!", $error->getMessage());
}

