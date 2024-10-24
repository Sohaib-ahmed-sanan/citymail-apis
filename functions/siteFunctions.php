<?php

function authorization()
{
    $headers = getallheaders();
    if (!isset($headers['Api-Key']) && !isset($headers['Client-Id'])) {
        return 'API key or client ID missing';
    } else {
        list($type, $secret_key) = explode("%", $headers['Api-Key']);
        list($key, $timestamp) = explode(":", $secret_key);
        if (time() - $timestamp > 1800) {
            return "API timeout generate security token again";
        } else {
            return 200;
        }
    }
}
function authantication($dbobjx)
{
    $headers = getallheaders();
    if (isset($headers['Api-Key']) && isset($headers['Client-Id'])) {
        list($type, $secret_key) = explode("%", $headers['Api-Key']);
        list($key, $timestamp) = explode(":", $secret_key);
        $type = base64_decode($type);
        $id = $headers['Client-Id'];
        $exploded = explode('!', $key);
        $query = "SELECT `acno`,`employee_id`,`secret_key` FROM `users` WHERE `id` = '$id' AND `company_id` = '$exploded[1]'";
        $dbobjx->query($query);
        $result = $dbobjx->single();
        if ($result != '') {
            if ($result->secret_key === $exploded[0]) {
                if ($result->acno != "" && in_array($type, ['6', '7', '8'])) {
                    return true;
                } elseif ($result->employee_id != "" && in_array($type, ['1', '2', '3', '4', '5', '9'])) {
                    return true;
                } else {
                    return 401;
                }
            } else {
                return 401;
            }
        } else {
            return 404;
        }
    } else {
        return 500;
    }
}
function track_status($dbobjx, $cn_no, $status_id)
{
    $get_last = "SELECT `status_id` FROM `orderstatus_tracking` WHERE `consignment_no` = '$cn_no' ORDER BY id DESC LIMIT 1";
    $dbobjx->query($get_last);
    $get = $dbobjx->single();

    $last_status = $get->status_id ?? 1;

    $query = "INSERT INTO `orderstatus_tracking`(`consignment_no`, `status_id`,`last_status_id`) VALUES ($cn_no,$status_id,$last_status)";
    $dbobjx->query($query);
    $dbobjx->execute();
    return 200;
}
// create_login($dbobjx,$company_id,$account_id,$customer_id,$user_name,$password, 4);
function create_login($dbobjx, $company_id, $account_id, $customer_id, $user_name, $password, $type_id)
{
    $salt = generatingSalt();
    $hashpassword = encryptString($salt, $password);
    $customer = isset($customer_id) && $customer_id != '' ? $customer_id : 0;
    $login = "INSERT INTO `login_accounts`(`account_id`,`company_id`, `customer_id`, `user_name`, `salt`, `password`, `type_id`)
     VALUES ('$account_id','$company_id','$customer','$user_name','$salt','$hashpassword',$type_id)";
    $dbobjx->query($login);
    if ($dbobjx->execute()) {
        return 200;
    } else {
        return "error in creating login";
    }
}
function update_pass($dbobjx, $account_id, $password)
{
    $salt = generatingSalt();
    $hashpassword = encryptString($salt, $password);
    $query = "UPDATE `users` SET `salt`='$salt',`password`='$hashpassword' WHERE `account_id` = $account_id";
    $dbobjx->query($query);
    if ($dbobjx->execute()) {
        return 200;
    } else {
        return 404;
    }
}
function check_user_name($dbobjx, $new_name, $id = "")
{
    $query = "SELECT id FROM `users` WHERE BINARY `user_name` = '$new_name'";
    $dbobjx->query($query);
    $dbobjx->single();
    if ($dbobjx->rowCount() == 0) {
        return 200;
    } else {
        return 404;
    }
}
