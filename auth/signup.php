<?php
include "../index.php";
$registerSchema = json_decode(file_get_contents('../schema/auth/login.json'));
$request = json_decode(file_get_contents('php://input'));
$valid = json_decode(requestvalidateobject($request, $registerSchema));
include "../functions/siteFunctions.php";
// if ($valid->status) {
$first_name = $request->first_name;
$last_name = $request->last_name;
$email = $request->email;
$user_name = $request->user_name;
$password = $request->password;
$salt = generatingSalt();
$hashed_password = encryptString($salt, $password);
$Query = "SELECT `id` FROM `users` WHERE BINARY `user_name`='$user_name'";
$dbobjx->query($Query);
$result = $dbobjx->single();
$secret = randomString(50);
$token = randomString(25);  
if ($dbobjx->rowCount() === 0) {
    try {
        $query = "INSERT INTO `companies`(`logo`,`prefix`,`primary_color`,`secondary_color`,`font_color`) 
        VALUES ('orio-logo.svg','orio-check','#292868','#e4e6e9','#3b3e66')";
        $dbobjx->query($query);
        if ($dbobjx->execute()) {
            $company_id = $dbobjx->lastInsertId();
            $Query = "INSERT INTO  `employees` (`department_id`,`first_name`, `last_name`,`email`,`user_name`,`company_id`,`created_by`) VALUES 
            ('1','$first_name','$last_name','$email','$user_name','$company_id','By Self')";
            $dbobjx->query($Query);
            $dbobjx->execute();
            $employee_id = $dbobjx->lastInsertId();
            $Query = "INSERT INTO `users`(`employee_id`,`company_id`,`user_name`,`first_name`,`last_name`,`email`, `token`, `secret_key`, `salt`, `password`)
            VALUES ('$employee_id','$company_id','$user_name','$first_name','$last_name','$email','$token','$secret','$salt','$hashed_password')";
            $dbobjx->query($Query);
            if($dbobjx->execute()){
                $account_id = $dbobjx->lastInsertId();
                $rights_query = "INSERT INTO `user_menus`(`account_id`,`menue`) VALUES ('$account_id','[1,2,3,4,5,6,7,9,12,11,13,14,15,16,17,18,19,22,23,28,24,25,26,31,32,33]')";
                $dbobjx->query($rights_query);
                $dbobjx->execute();
                echo response("1", "success", "Company has been registered successfully");
            } else {
                $del = "DELETE FROM `companies` WHERE `company_id` = '$company_id'";
                $dbobjx->query($del);
                $del2 = "DELETE FROM `users` WHERE `user_name` = '$user_name'";
                $dbobjx->query($del2);
                $dbobjx->execute();
                echo response("0", "Something Went Wrong", "Unexpected error occur while registring the company");
            }
        } else {
            echo response("0", "error", "Something went wrong");
        }
        $dbobjx->close();

    } catch (Exception $e) {
        echo response("0", "Something Went Wrong", $e);
    }
} else {
    echo response("0", "error", "User with this username already exists.");
}
// } else {
//     echo response("0", "Error!", $valid->error);
// }
