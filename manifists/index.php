<?php
include "../index.php";
$registerSchema = json_decode(file_get_contents('../schema/manifists/index.json'));
$request = json_decode(file_get_contents('php://input'));
$valid = json_decode(requestvalidateobject($request, $registerSchema));
include "../functions/siteFunctions.php";
$has_key = authorization();
if ($has_key) {
    $valid_key = authantication($dbobjx);
    if ($valid_key === true) {
        if ($valid->status) {
            try {
                $company_id = $request->company_id;
                $end_date = (isset($request->end_date) ? $request->end_date : '');
                $start_date = (isset($request->start_date) ? $request->start_date : '');
                $more = "";
                if (isset($start_date) && $start_date != '') {
                    $more = "AND manifists.created_at BETWEEN '$start_date 00:00:00' AND '$end_date 23:59:59'";
                }
                $check = "SELECT manifists.*,station.name FROM manifists 
                LEFT JOIN stations As station ON manifists.station_id = station.id
                WHERE manifists.company_id = '$company_id' AND manifists.is_deleted = 'N' $more";
                $dbobjx->query($check);
                $result = $dbobjx->resultset();
                echo response("1", "success", $result);
            } catch (Exception $e) {
                echo response("0", "Api Error !", $e);
            }
        } else {
            echo response("0", "Error !", $valid->error);
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