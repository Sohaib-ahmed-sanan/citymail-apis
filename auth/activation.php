<?php
include("../index.php");
$registerSchema = json_decode(file_get_contents('../schema/auth/login.json'));
$request = json_decode(file_get_contents('php://input'));
$token = $request->token;
$tabel = $request->tabel;
$query = "SELECT details.id,details.is_otp,details.active 
FROM `users` 
LEFT JOIN $tabel as details ON users.user_name = details.user_name
WHERE users.token = '$token'";
$dbobjx->query($query);
$res = $dbobjx->single();
if ($dbobjx->rowCount() > 0) {
    $active = $res->active;
    if ($active == "0") {
        try {
            $update_query = "UPDATE $tabel SET `active`='1',`is_otp`='Y' WHERE `id` = '$res->id'";
            $dbobjx->query($update_query);
            $update_queryrun = $dbobjx->execute();
            echo response("1", "Verify Successfully", []);
                
        } catch (Exception $e) {
            echo response("0", "Verification Fail", []);
        }
    }
    else {
        echo response("1", "Already Verify", []);
    }
} else {
    echo response("0", "Account Not Exist", []);
}
$dbobjx->close();
