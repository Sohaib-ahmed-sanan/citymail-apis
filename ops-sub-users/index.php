<?php
include "../index.php";
$request = json_decode(file_get_contents('php://input'));
include "../functions/siteFunctions.php";
$has_key = authorization();
if ($has_key) {
    $valid_key = authantication($dbobjx);
    if ($valid_key === true) {
        try {
            $company_id = $request->company_id;
            $start_date = $request->start_date;
            $end_date = $request->end_date;
            $parent_id = get_user_id_header();
            if (isset($start_date) && $start_date != '') {
                $more = "AND emp.created_at BETWEEN '$start_date 00:00:00' AND '$end_date 23:59:59'";
            } else {
                $more = "";
            }
            $query = "SELECT users.employee_id,users.company_id,emp.*,dep.name As department
             FROM `users` 
             LEFT JOIN `employees` AS emp ON emp.id = users.employee_id   
             LEFT JOIN `departments` AS dep ON dep.id = emp.department_id   
             WHERE users.company_id = '$company_id' AND users.parent_id = '$parent_id' AND emp.department_id IN('4','5','9')";
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
