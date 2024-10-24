<?php
include ("../index.php");
$request = json_decode(file_get_contents('php://input'));
include "../functions/siteFunctions.php";
$has_key = authorization();
if ($has_key) {
    $valid_key = authantication($dbobjx);
    if ($valid_key === true) {
        try {
            $type = ($request->type == '1' ? 'company' : $request->type);
            $account_id = isset($request->account_id) && $request->account_id != '' ? $request->account_id : 0;
            $query = "SELECT * FROM `user_types` WHERE `name` = '$type'";
            $dbobjx->query($query);
            $result = $dbobjx->single();
            if ($dbobjx->rowCount() > 0) {
                $where = "WHERE `type_id` = '$result->id' AND `account_id` = '$account_id'";
                $fetch_roles = "SELECT * FROM `user_menus` $where";
                $dbobjx->query($fetch_roles);
                $return = $dbobjx->single();
                $menue_arr = $return->menue;
                $menue_ids = implode(',', json_decode($menue_arr));
                $fetch_menues = "SELECT * FROM `menu` WHERE `id` IN ($menue_ids) AND `active` = 1 ORDER BY `sorting` ASC";
                $dbobjx->query($fetch_menues);
                $data = $dbobjx->resultset();
                echo response("1", "success", json_encode($data));
            } else {
                echo response("0", "Type error", "no menues found against this type");
            }
        } catch (Exception $error) {
            echo response("0", "Error!", $error->getMessage());
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
