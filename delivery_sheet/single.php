<?php
include "../index.php";
$registerSchema = json_decode(file_get_contents('../schema/delivery_sheet/single.json'));
$request = json_decode(file_get_contents('php://input'));
$valid = json_decode(requestvalidateobject($request, $registerSchema));
include "../functions/siteFunctions.php";
$has_key = authorization();
if ($has_key) {
    $valid_key = authantication($dbobjx);
    if ($valid_key === true) {
        if ($valid->status) {
            try {
                $company_id = isset($request->company_id) ? $request->company_id : '';
                $sheet_no = $request->sheet_no;
                $check = "SELECT 
            delivery_sheet.*,details.consignment_no,details.remarks As sheet_remarks,details.status_id as details_status,customer.business_name As business_name,customer.acno As acno,
            customer.address As customer_address,customer.phone As customer_phone,shipment.consignee_name,shipment.consignee_phone,
            shipment.weight_charged,shipment.status,shipment.peices_charged,shipment.total_charges,shipment.order_amount,shipment.orignal_currency_code,shipment.orignal_order_amt,shipment.service_charges,
            shipment.gst_charges,shipment.handling_charges,shipment.sst_charges,shipment.bac_charges,shipment.rto_charges,
            shipment.shipment_referance,city.city As destination_city,riders.first_name As rider_first_name,riders.last_name As rider_last_name,routes.address As route_address,pl.name as shipper_name
            FROM 
                delivery_sheet
            LEFT JOIN 
                delivery_sheet_details AS details ON delivery_sheet.sheet_no = details.sheet_id
            LEFT JOIN 
                shipments AS shipment ON details.consignment_no = shipment.consignment_no
            LEFT JOIN 
                customers AS customer ON shipment.customer_acno = customer.acno
            LEFT JOIN 
            cities AS city ON shipment.destination_city_id = city.id
            LEFT JOIN 
            employees AS riders ON delivery_sheet.rider_id = riders.id
            LEFT JOIN 
            routes AS routes ON delivery_sheet.route_id = routes.id
            LEFT JOIN 
            pickup_locations AS pl ON shipment.pickup_location_id = pl.id
            WHERE 
            delivery_sheet.sheet_no = $sheet_no
            ";
                $dbobjx->query($check);
                $result = $dbobjx->resultset();
                echo response("1", "success", $result);
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