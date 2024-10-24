<?php
include ("../index.php");
$registerSchema = json_decode(file_get_contents('../schema/auth/login.json'));
$request = json_decode(file_get_contents('php://input'));
include "../functions/siteFunctions.php";

try {
    $old = isset($request->old) ? $request->old : '';
    $new = isset($request->new) ? $request->new : '';
    $reset = isset($request->reset) ? $request->reset : '';
    $user_name = isset($request->user_name) ? $request->user_name : '';
    $account_id = isset($request->id) ? $request->id : '';
    $confirm = isset($request->confirm) ? $request->confirm : '';
    if ($account_id != '') {
        $where = "`account_id` = '$account_id'";
        $type = "";
    }
    if ($reset != '' && $user_name != '') {
        $where = "`user_name` = '$user_name'";
        $type = "forgot";
    }
    $check = "SELECT `salt`,`password` FROM `users` WHERE $where";
    $dbobjx->query($check);
    $result = $dbobjx->single();
    if ($dbobjx->rowCount() > 0) {
        $hashold = encryptString($result->salt, $old);
        if ($hashold == $result->password || $type != '') {
            $salt = generatingSalt();
            $hashpassword = encryptString($salt, $new);
            $query = "UPDATE `users` SET `password`='$hashpassword',`salt`='$salt' WHERE  $where";
            $dbobjx->query($query);
            if ($dbobjx->execute()) {
                $dbobjx->close();
                echo response("1", "Password has been updated successfully", []);
            } else {
                echo response("0", "something went wrong", []);
            }
            exit;
        } else {
            echo response("0", "Old pasword does not match", []);
        }
    }
} catch (Exception $e) {
    echo response("0", "Something Went Wrong", []);
}
