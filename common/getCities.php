<?php
include ("../index.php");
$request = json_decode(file_get_contents('php://input'));
try {
    $city_id = isset($request->city_id) ? $request->city_id : '';
    $city_name = isset($request->city_name) ? $request->city_name : '';
    $country_id = isset($request->country_id) ? $request->country_id : '';
    $where = "";
    if ($city_name != '') {
        $where .= "AND `city` = '$city_name'";
    } 
    if ($city_id != '') {
        $where .= "AND `id` = '$city_id'";
    } 
    if ($country_id != '') {
        $where .= "AND `country_id` = '$country_id'";
    } 
    $query = "SELECT id,city,country_id,province_id,zone,created_at FROM `cities` Where `is_deleted` = 'N' AND `active` = 1 $where";
    $dbobjx->query($query);
    $dbobjx->execute();
    if ($city_name != '' || $city_id != '') {
        $data = $dbobjx->single();
        if ($dbobjx->rowCount() > 0) {
            echo response("1", "success", $data);
        } else {
            echo response("0", "error", '');
        }
    } else {
        echo json_encode($dbobjx->resultset());
    }
} catch (Exception $error) {
    echo response("0", "Error!", $error->getMessage());
}

