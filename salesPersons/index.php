<?php
include "../index.php";
$request = json_decode(file_get_contents('php://input'));
// $valid = json_decode(requestvalidateobject($request, $registerSchema));
include "../functions/siteFunctions.php";
$has_key = authorization();
if ($has_key) {
    $valid_key = authantication($dbobjx);
    if ($valid_key === true) {
        try {
            $company_id = (isset($request->company_id) ? $request->company_id : '');
            $start_date = (isset($request->start_date) ? $request->start_date : '');
            $end_date = (isset($request->end_date) ? $request->end_date : '');
            $more = "";
            if (isset($start_date) && $start_date != '') {
                $more = "AND `created_at` BETWEEN '$start_date 00:00:00' AND '$end_date 23:59:59'";
            }
            $query = "SELECT * FROM `employees` WHERE `company_id` = '$company_id' AND `department_id` = '3' AND  `is_deleted` = 'N' $more";
            $dbobjx->query($query);
            $result = $dbobjx->resultset();
            if (isset($start_date) && $start_date != '') {
                echo response("1", "Success", $result);
            } else {
                echo json_encode($dbobjx->resultset());
            }
        } catch (Exception $e) {
            echo response("0", "Api Error !", $e);
        }
    } else {
        if ($valid_key == 401) {
            echo response("0", "Invalid Secret Key", "Secret key is incorect");
        } elseif ($valid_key == 404) {
            echo response("0", "Authantication faild", "Client Id is not correct");
        }
    }
} else {
    echo response("0", "Unauthorized", $has_key);
}