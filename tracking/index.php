<?php
include "../index.php";
$registerSchema = json_decode(file_get_contents('../schema/tracking/get.json'));
$request = json_decode(file_get_contents('php://input'));
$valid = json_decode(requestvalidateobject($request, $registerSchema));
include "../functions/siteFunctions.php";
$has_key = authorization();
if ($has_key == 200) {
    $valid_key = authantication($dbobjx);
    if ($valid_key === true) {
        if ($valid->status) {
            $check = null;
            try {
                $array = array();
                $tracking_by_shipment = array();
                $tracking_info = array();

                
                $consignment_no_string = (isset($request->cn_numbers) ? $request->cn_numbers : '');
                $consignment_no_array = explode(',', $consignment_no_string);
                // Clean the CN numbers
                $sanitised_no = array_map(function ($cn_number) {
                    return preg_replace('/[^0-9]/', '', $cn_number);
                }, $consignment_no_array);

                // Filter out empty values
                $cn_numbers = array_filter($sanitised_no);
                $imploded_cn = implode("','", $cn_numbers);

                $query = "SELECT 
                s.consignment_no AS shipment_no, 
                s.weight AS shipment_weight,
                s.peices AS shipment_peices,
                s.weight_charged AS weight_charged,
                s.peices_charged AS peices_charged,
                s.consignee_name AS consignee_name,
                s.consignee_email AS consignee_email,
                s.consignee_phone AS consignee_phone,
                s.consignee_address AS consignee_address,
                s.shipment_referance AS shipment_referance,
                s.shipper_comment AS shipper_comment,
                s.fragile AS fragile,
                s.insurance AS insurance,
                s.insurance_amt AS insurance_amt,
                s.status AS status, 
                s.customer_acno AS customer_acno,
                s.created_at AS created_at,
                c.city AS origin_city,
                pl.phone AS pickup_phone,
                pl.address AS pickup_address,
                destination_city.city AS destination_city,
                customer_services.service_name AS shipment_service,
                customer.business_name AS customer_name,
                customer.acno AS customer_account
            FROM 
                shipments AS s
            LEFT JOIN 
                pickup_locations AS pl ON s.pickup_location_id = pl.id
            LEFT JOIN 
                cities AS c ON pl.city_id = c.id
            LEFT JOIN 
                cities AS destination_city ON s.destination_city_id = destination_city.id
            LEFT JOIN 
                services AS customer_services ON s.service_id = customer_services.id
            LEFT JOIN 
            customers AS customer ON s.customer_acno = customer.acno
            WHERE 
                s.consignment_no IN ('".$imploded_cn."')
                AND s.is_deleted = 'N'
                ";
                $dbobjx->query($query);
                $dbobjx->execute();
                if ($dbobjx->rowCount() > 0) {
                    $primary_data = $dbobjx->resultset();
                  
                    $traking_list = "SELECT 
                    track_status.created_at AS last_updated,
                    track_status.consignment_no AS cn,
                    status_tbl.id AS status_code,
                    status_tbl.name AS status_name,
                    status_tbl.message AS status_message
                     FROM orderstatus_tracking AS track_status LEFT JOIN
                      `delivery_status` AS status_tbl ON track_status.status_id = status_tbl.id  
                     WHERE track_status.consignment_no IN ('". $imploded_cn ."')";
                    $dbobjx->query($traking_list);
                    $traking_data = $dbobjx->resultset();

                    foreach ($traking_data as $track) {
                        if (!isset($tracking_by_shipment[$track->cn])) {
                            $tracking_by_shipment[$track->cn] = array();
                        }
                        $tracking_by_shipment[$track->cn][] = array(
                            'created_datetime' => $track->last_updated,
                            'status_name' => $track->status_name,
                            'status_message' => $track->status_message,
                        );
                    }

                    foreach ($primary_data as $shipment) {
                        $tracking_details = isset($tracking_by_shipment[$shipment->shipment_no]) ? $tracking_by_shipment[$shipment->shipment_no] : array();
                        $collection[] = array(
                            'shipment_no' => $shipment->shipment_no,
                            'booking_date' => $shipment->created_at,
                            'customer_account' => $shipment->customer_account,
                            'customer_name' => $shipment->customer_name,
                            'pickup_address' => $shipment->pickup_address,
                            'pickup_phone' => $shipment->pickup_phone,
                            'consignee_name' => $shipment->consignee_name,
                            'consignee_phone' => $shipment->consignee_phone,
                            'consignee_address' => $shipment->consignee_address,
                            'origin_city' => $shipment->origin_city,
                            'destination_city' => $shipment->destination_city,
                            'service' => $shipment->shipment_service,
                            'peices' => $shipment->peices_charged,
                            'weight' => $shipment->weight_charged,
                            'fragile' => ($shipment->fragile == 0 ? 'No' : 'Yes'),
                            'insurance' => ($shipment->insurance == 0 ? 'No' : 'Yes'),
                            'shipment_referance' => $shipment->shipment_referance,
                            'shipper_comment' => $shipment->shipper_comment,
                            'tracking_info' => $tracking_details,
                        );
                    }
                    // print_r($order_tracking);
                    // die;
                    echo response("1", "Success", $collection);
                } else {
                    echo response("0", "Error", "Invalid consignment no");
                }

            } catch (Exception $e) {
                echo response("0", "Error !", $e);
            }
        } else {
            echo response("0", "Api Error !", $valid->error);
        }
    } else {
        if ($valid_key == 401) {
            echo response("0", "Invalid Secret Key", "Secret key is incorect");
        } elseif ($valid_key == 404) {
            echo response("0", "Authantication faild", "Client Id is not correct");
        }
    }
} else {
    echo response("0", "Unauthorized", $has_key);
}
