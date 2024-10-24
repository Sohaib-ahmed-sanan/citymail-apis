<?php
include "../index.php";
$request = json_decode(file_get_contents('php://input'));
include "../functions/siteFunctions.php";
$has_key = authorization();
if ($has_key) {
    $valid_key = authantication($dbobjx);
    if ($valid_key === true) {
        try {
            $company_id = (isset($request->company_id) ? $request->company_id : '');
            $start_date = (isset($request->start_date) ? $request->start_date : '');
            $end_date = (isset($request->end_date) ? $request->end_date : '');
            $city_id = (isset($request->city_id) ? $request->city_id : '');
            if (isset($start_date) && $start_date != '') {
                $more = "AND stations.created_at BETWEEN '$start_date 00:00:00' AND '$end_date 23:59:59'";
            } else {
                $more = "AND stations.active = '1'";
            }
            if($city_id != '')
            {
                $more = "AND stations.city_id IN($city_id)";
            }
            $query = "SELECT  stations.*,cities.city 
            FROM `stations` AS stations 
            LEFT JOIN `cities` ON stations.city_id = `cities`.`id`
            WHERE stations.company_id = $company_id AND stations.is_deleted = 'N' $more";
            $dbobjx->query($query);
            $result = $dbobjx->resultset();
            if (isset($start_date) && $start_date != '') {
                echo response("1", "Success", $result);
            } else {
                echo json_encode($result);
            }
        } catch (Exception $e) {
            echo response("0", "Api Error !", $e);
        }
    } else {
        if ($valid_key === 401) {
            echo response("0", "Invalid Secret Key", "Secret key is incorect");
        } elseif ($valid_key === 404) {
            echo response("0", "Authantication faild", "Client Id is not correct");
        }
    }
} else {
    echo response("0", "Unauthorized", $has_key);
}