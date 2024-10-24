<?php
include "../index.php";
$request = json_decode(file_get_contents('php://input'));
include "../functions/siteFunctions.php";
$has_key = authorization();
if ($has_key) {
    $valid_key = authantication($dbobjx);
    if ($valid_key === true) {
        try {
            $company_id = (isset($request->company_id) ? $request->company_id : '');
            $query = "SELECT rules.*,pl.name As pickup_location_name,pl.id As pickup_location_id FROM rules 
            LEFT JOIN `pickup_locations` As pl On rules.pickup_id = pl.id
            WHERE rules.company_id = '$company_id' AND rules.is_deleted = 'N'";
            $dbobjx->query($query);
            $result = $dbobjx->resultset();
            echo json_encode($result);

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