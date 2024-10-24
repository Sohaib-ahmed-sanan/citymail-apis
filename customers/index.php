<?php
include "../index.php";
$registerSchema = json_decode(file_get_contents('../schema/customers/index.json'));
$request = json_decode(file_get_contents('php://input'));
$valid = json_decode(requestvalidateobject($request, $registerSchema));
include "../functions/siteFunctions.php";
$has_key = authorization();
if ($has_key) {
    $valid_key = authantication($dbobjx);
    if ($valid_key === true) {
        if ($valid->status) {
            try {
                $company_id = (isset($request->company_id) ? $request->company_id : '');
                $sales_person = (isset($request->sales_person) ? $request->sales_person : '');
                $start_date = (isset($request->start_date) ? $request->start_date : '');
                $end_date = (isset($request->end_date) ? $request->end_date : '');
                $type = (isset($request->type) ? $request->type : '');
                if ($company_id != '') {
                    $more = "";
                    if($sales_person != "")
                    {
                        $more .= "AND c.sales_person = '$sales_person'";
                    }
                    if (isset($start_date) && $start_date != '') {
                        $more .= "AND c.created_at BETWEEN '$start_date 00:00:00' AND '$end_date 23:59:59'";
                    }
                    $query = "SELECT c.*,emp.first_name AS sp_first_name,emp.last_name AS sp_last_name 
                    FROM `customers` AS c
                    LEFT JOIN employees AS emp On c.sales_person = emp.id
                    WHERE c.company_id = '$company_id' 
                    AND c.parent_id IS NULL AND c.is_deleted = 'N' $more";
                    $dbobjx->query($query);
                    $result = $dbobjx->resultset();
                    if (isset($start_date) && $start_date != '') {
                        echo response("1", "Success", $result);
                    } else {
                        echo json_encode($result);
                    }
                } else {
                    echo response("0", "Data error", "Please provide all parameters");
                }
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