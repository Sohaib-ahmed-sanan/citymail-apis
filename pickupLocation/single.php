<?php
include "../index.php";
$registerSchema = json_decode(file_get_contents('../schema/pickup_locations/single.json'));
$request = json_decode(file_get_contents('php://input'));
$valid = json_decode(requestvalidateobject($request, $registerSchema));
include "../functions/siteFunctions.php";
// $has_key = authorization();
// if ($has_key) {
//     $valid_key = authantication($dbobjx);
//     if ($valid_key === true) {
if ($valid->status) {
    try {
        $id = isset($request->id) ? $request->id : 0;
        $query = "SELECT * FROM `pickup_locations` WHERE `id` = '$id' AND `is_deleted` = 'N'";
        $dbobjx->query($query);
        $data = $dbobjx->single();
        if ($dbobjx->rowCount() > 0) {
            echo response("1", "Success", $data);
        } else {
            echo response("0", "error", "not found");
        }
    } catch (Exception $e) {
        echo response("0", "Api Error !", $e);
    }
} else {
    echo response("0", "Error !", $valid->error);
}
//     } else {
//         if ($valid_key === 401) {
//             echo response("0", "Invalid Secret Key", "Secret key is incorect");
//         } elseif ($valid_key === 404) {
//             echo response("0", "Authantication faild", "Client Id is not correct");
//         }
//     }
// } else {
//     echo response("0", "Unauthorized", $has_key);
// }