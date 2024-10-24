<?php
include "../index.php";
// $registerSchema = json_decode(file_get_contents('../schema/demanifists/get.json'));
$request = json_decode(file_get_contents('php://input'));
// $valid = json_decode(requestvalidateobject($request, $registerSchema));
include "../functions/siteFunctions.php";
$has_key = authorization();
if ($has_key) {
    $valid_key = authantication($dbobjx);
    if ($valid_key === true) {
        // if ($valid->status) {
        $company_id = isset($request->company_id) ? $request->company_id : '';
        $shipment_no = isset($request->shipment_no) ? $request->shipment_no : '';
        try {
            $query = "SELECT `status`,`origin_city_id` FROM `shipments` WHERE `consignment_no` = '$shipment_no' AND  `is_deleted` = 'N'";
            $dbobjx->query($query);
            $result = $dbobjx->single();
            if ($dbobjx->rowCount() > 0) {
                if (!in_array($result->status, [14,2,16])) {
                    $arival_check = "SELECT `id` FROM `arrivals_details` WHERE  `cn_numbers` = '$shipment_no'";
                    $dbobjx->query($arival_check);
                    $result = $dbobjx->single();
                    if ($dbobjx->rowCount() == '2') {  
                        $data = "SELECT customer.name As customer_name,shipments.peices_charged AS peices_charged,shipments.weight_charged AS weight_charged,
                                shipments.consignee_name As consignee_name,shipments.shipment_referance As ref,shipments.total_charges AS total_charges,status.name AS status,
                                shipments.orignal_currency_code As currency_code,shipments.orignal_order_amt As order_amount,
                                pl.name As shipper_name,cities.city As destination,dsd.remarks as remarks
                                FROM `shipments`
                                LEFT JOIN customers As customer On shipments.customer_acno = customer.acno 
                                LEFT JOIN delivery_status As status On shipments.status = status.id 
                                LEFT JOIN delivery_sheet_details As dsd On shipments.consignment_no = dsd.consignment_no
                                LEFT JOIN `pickup_locations` As pl ON shipments.pickup_location_id = pl.`id`
                                LEFT JOIN `cities` ON shipments.destination_city_id = `cities`.`id`
                                WHERE shipments.consignment_no = '$shipment_no' AND shipments.company_id = '$company_id'";
                        $dbobjx->query($data);
                        $return = $dbobjx->single();
                        echo response("1", "Success", $return);
                    } else {
                        echo response("0", "Error", "Consignment is not in arrival");
                    }
                } else {
                    echo response("0", "Error", "Consignment is already returned or delivered");
                }
            } else {
                echo response("0", "Error", "Enter correct consignment number");
            }
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