<?php
include "../index.php";
$registerSchema = json_decode(file_get_contents('../schema/arrivals/get.json'));
$request = json_decode(file_get_contents('php://input'));
$valid = json_decode(requestvalidateobject($request, $registerSchema));
include "../functions/siteFunctions.php";
$has_key = authorization();
if ($has_key) {
    $valid_key = authantication($dbobjx);
    if ($valid_key == true) {
        if ($valid->status) {
            try {
                $company_id = isset($request->company_id) ? $request->company_id : '';
                $cn_number = isset($request->cn_number) ? $request->cn_number : '';
                $status_check = "SELECT `status` FROM `shipments` WHERE `consignment_no` = '$cn_number' AND `company_id` = $company_id AND`is_deleted` = 'N'";
                $dbobjx->query($status_check);
                $data = $dbobjx->single();
                if ($dbobjx->rowCount() > 0) {
                    if (!in_array($result->status, [4, 2, 17])) {
                        $delivery_check = "SELECT `id` FROM `delivery_sheet_details` WHERE `consignment_no` = '$cn_number'";
                        $dbobjx->query($delivery_check);
                        $dbobjx->single();
                        if ($dbobjx->rowCount() == 0) {
                            $arrival_check = "SELECT COUNT(id) As row_count 
                            FROM `arrivals_details`
                            WHERE cn_numbers = '$cn_number'";
                            $dbobjx->query($arrival_check);
                            $res = $dbobjx->single();
                            // to check that on arival destination only that cn will be scaned whose pickup is created
                            if($res->row_count == '0')
                            {
                                echo response("0", "Error", "Consignment pickup doesnot exist");
                                exit;
                            }
                            
                            if ($res->row_count == 1) {
                                $query = "SELECT * FROM `shipments`
                                    LEFT JOIN `cities` ON shipments.destination_city_id = `cities`.`id`
                                    LEFT JOIN `pickup_locations` ON shipments.pickup_location_id = `pickup_locations`.`id`
                                    WHERE shipments.consignment_no = '$cn_number' AND shipments.is_deleted = 'N' AND shipments.company_id = $company_id ";
                                $dbobjx->query($query);
                                $result = $dbobjx->resultset();
                                echo response("1", "Success", $result);

                            } else {
                                echo response("0", "Error", "Consignment already exist");
                            }
                        } else {
                            echo response("0", "Error", "Consignment already delivered");
                        }
                    } else {
                        echo response("0", "Error", "Consignment status is invalid");
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
