<?php
include "../index.php";
$registerSchema = json_decode(file_get_contents('../schema/riders/add.json'));
$request = json_decode(file_get_contents('php://input'));
$valid = json_decode(requestvalidateobject($request, $registerSchema));
include "../functions/siteFunctions.php";
$has_key = authorization();
if ($has_key) {
    $valid_key = authantication($dbobjx);
    if ($valid_key === true) {
        // if ($valid->status) {
        $name = $request->name;
        $company_id = $request->company_id;
        $city_id = $request->city_id;
        try {
            $query = "INSERT INTO `cities_collection`(`company_id`, `name`,`city_ids`) VALUES
            ($company_id,'$name$city_ids')";
            $dbobjx->query($query);
            $dbobjx->execute();
            echo response("1", "Success", "Collection has been added successfully");
        } catch (Exception $e) {
            echo response("0", "Api Error !", $e);
        }
        // } else {
        //     echo response("0", "Error !", $valid->error);
        // }
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