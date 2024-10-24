<?php
include "../index.php";
$request = json_decode(file_get_contents('php://input'));
include "../functions/siteFunctions.php";
$has_key = authorization();
if ($has_key) {
    $valid_key = authantication($dbobjx);
    if ($valid_key === true) {
        try {
            $created_by = get_user_id_header();
            $company_id = $request->company_id;
            $employee_id = $request->employee_id;
            $first_name = $request->name;
            $last_name = '';
            $user_name = $request->user_name ?? '';
            $phone = $request->phone ?? '';
            $email = $request->email ?? '';
            $country_id = $request->country_id ?? '';
            $city_id = $request->city_id ?? '';
            $department_id = $request->department_id ?? '';
            $address = $request->address ?? '';
            $password = $request->password ?? '';
            $salt = generatingSalt();
            $hashed_password = encryptString($salt, $password);

            $more = '';
            if ($user_name != '') {
                $check_availiblity = check_user_name($dbobjx, $user_name, $id);
                if ($check_availiblity == 200) {
                    $more = ",`user_name` = '$user_name'";
                } else {
                    echo response("0", "Username exist", "Username already exist please use different");
                    die;
                }
            }
            if ($password != '') {
                $salt = generatingSalt();
                $hashpassword = encryptString($salt, $password);
                $update = "UPDATE `users` SET `salt`='$salt',`password`='$hashpassword' WHERE `employee_id` = '$employee_id'";
                $dbobjx->query($update);
                $dbobjx->execute();
            }


            $query = "UPDATE `employees` 
                    SET 
                        `department_id` = '$department_id',
                        `first_name` = '$first_name',
                        `last_name` = '$last_name',
                        `email` = '$email',
                        `phone` = '$phone',
                        `address` = '$address',
                        `country_id` = '$country_id',
                        `city_id` = '$city_id'
                        $more
                    WHERE 
                        `id` = '$employee_id';
                    ";
            $dbobjx->query($query);
            if ($dbobjx->execute()) {
                $Query = "UPDATE `users`
                            SET 
                                `first_name` = '$first_name',
                                `last_name` = '$last_name',
                                `email` = '$email',
                                `phone` = '$phone',
                                `address` = '$address',
                                `country_id` = '$country_id',
                                `city_id` = '$city_id'
                                $more
                            WHERE 
                            `employee_id` = '$employee_id'";
                $dbobjx->query($Query);
                $dbobjx->execute();
                echo response("1", "success", "User account has been created successfully");

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
