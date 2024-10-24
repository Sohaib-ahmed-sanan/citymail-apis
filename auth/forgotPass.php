<?php
include ("../index.php");
$registerSchema = json_decode(file_get_contents('../schema/auth/login.json'));
$request = json_decode(file_get_contents('php://input'));
$user_name = $request->user_name;
$Query = "SELECT `acno`,`employee_id`,`id` FROM users WHERE `user_name`='$user_name'";
$dbobjx->query($Query);
$result = $dbobjx->single();
if ($dbobjx->rowCount() !== 0) {
    try {
        if($result->acno == "" && !empty($result->employee_id))
        {
            $table = 'employees';
            $columns = '`id`, `first_name` As name,`last_name`,`email`';
            $where = "`user_name`='$user_name'";
        }else{
            $table = 'customers';
            $columns = '`name`,`email`';
            $where = "`user_name` = '$user_name'";
        }
        $info = "SELECT $columns FROM $table";
        if (!empty($where)) {
            $info .= " WHERE $where";
        }
        $dbobjx->query($info);
        $get_info = $dbobjx->single();
        echo response("1", "Success", $get_info);
        $dbobjx->close();
    } catch (Exception $e) {
        echo response("0", "Something Went Wrong", []);
    }
} else {
    echo response("0", "Account Not exists.");
}
