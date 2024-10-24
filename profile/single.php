<?php
include "../index.php";
$request = json_decode(file_get_contents('php://input'));
include "../functions/siteFunctions.php";
$has_key = authorization();
if ($has_key) {
    $valid_key = authantication($dbobjx);
    if ($valid_key === true) {
        try {
            $id = $request->id;
            // $query = "SELECT * FROM `employees` WHERE `id` = '$id' AND `is_deleted` = 'N'";
            $query = "SELECT `employee_id`,employees.*
            FROM `users`
            LEFT JOIN `employees` ON users.employee_id = employees.id
            WHERE users.id = '$id' ";
            $dbobjx->query($query);
            $data = $dbobjx->single();
            if($dbobjx->rowCount() > 0)
            {
                echo response("1", "Success", $data);                
            }else{
                echo response("0", "Error", '');                
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
