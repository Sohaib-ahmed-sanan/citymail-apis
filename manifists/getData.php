<?php
include "../index.php";
$registerSchema = json_decode(file_get_contents('../schema/manifists/get.json'));
$request = json_decode(file_get_contents('php://input'));
$valid = json_decode(requestvalidateobject($request, $registerSchema));
include "../functions/siteFunctions.php";
$has_key = authorization();
if ($has_key) {
    $valid_key = authantication($dbobjx);
    if ($valid_key === true) {
        if ($valid->status) {
            try {
                $cn_number = isset($request->cn_number) ? $request->cn_number : '';
                $check_status = "SELECT `status` FROM `shipments` WHERE `consignment_no` = '$cn_number' AND `is_deleted` = 'N'";
                $dbobjx->query($check_status);
                $result = $dbobjx->single();
                if ($dbobjx->rowCount() > 0) {
                    if (!in_array($result->status, [2, 17])) {
                        $check_exists = "SELECT `id` FROM `manifist_details` WHERE `consignment_no` = '$cn_number'";
                        $dbobjx->query($check_exists);
                        $dbobjx->execute();
                        if ($dbobjx->rowCount() == 0) {
                            $check_arrival = "SELECT `id` FROM `arrivals_details` WHERE `cn_numbers` = '$cn_number'";
                            $dbobjx->query($check_arrival);
                            $dbobjx->execute();
                            if ($dbobjx->rowCount() > 0) {
                                $query = "SELECT * FROM `shipments`
                                LEFT JOIN `cities` ON shipments.destination_city_id = `cities`.`id`
                                LEFT JOIN `pickup_locations` ON shipments.pickup_location_id = `pickup_locations`.`id`
                                WHERE shipments.consignment_no = '$cn_number' AND shipments.is_deleted = 'N'";
                                $dbobjx->query($query);
                                $result = $dbobjx->resultset();
                                echo response("1", "Success", $result);
                            } else {
                                echo response("0", "Error", "Arival doesnot exist against this consignment no.");
                            }
                        } else {
                            echo response("0", "Error", "Manifist alredy exist against this consignment no.");
                        }
                    } else {
                        echo response("0", "Error", "Shipment status is in valid");
                    }
                } else {
                    echo response("0", "Error", "Invalid Consignment No");
                }
            } catch (Exception $e) {
                echo response("0", "Api Error !", $e);
            }
        } else {
            echo response("0", "Error !", $valid->error);
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