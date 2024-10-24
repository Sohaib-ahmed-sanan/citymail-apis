<?php

include ("../index.php");
$registerSchema = json_decode(file_get_contents('../schema/common/updateSataus.json'));
$request = json_decode(file_get_contents('php://input'));
$valid = json_decode(requestvalidateobject($request, $registerSchema));
include "../functions/siteFunctions.php";
$has_key = authorization();
if ($has_key) {
    $valid_key = authantication($dbobjx);
    if ($valid_key === true) {
        if ($valid->status) {
            try {
                $id = $request->id;
                $status = $request->status;
                $table = $request->table;
                $status = ($status == '0') ? '0' : '1';
                if($table == 'shipments')
                {
                    $column = "`status`='$status'"; 
                }else{
                    $column = "`active`='$status'";                     
                }
                $query = "UPDATE `$table` SET $column where id = '$id'";
                $dbobjx->query($query);
                $dbobjx->execute();
                echo response("1", "Updated Successfull");
            } catch (Exception $error) {
                echo response("0", "Error!", $error->getMessage());
            }
        } else {
            echo response("0", "Error!!!!", $valid->error);
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
