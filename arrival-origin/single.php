<?php
include "../index.php";
$registerSchema = json_decode(file_get_contents('../schema/arrivals/single.json'));
$request = json_decode(file_get_contents('php://input'));
$valid = json_decode(requestvalidateobject($request, $registerSchema));
include "../functions/siteFunctions.php";
$has_key = authorization();
if ($has_key) {
    $valid_key = authantication($dbobjx);
    if ($valid_key == 200) {
        if ($valid->status) {
            try {
                $company_id = $request->company_id;
                $arrival_no = $request->arrival_no;
                $check = "SELECT arrivals.*,arrivals_details.*,shipments.consignee_name, shipments.shipment_referance,pl.name As shipper_name,pl.city_id As origin,shipments.destination_city_id As destination
                ,shipments.order_amount As cod_amt,shipments.service_id As service_id,employees.first_name As rider_name , stations.name As station_name,routes.address As route
                FROM arrivals
                LEFT JOIN `employees` ON arrivals.rider_id = employees.id
                LEFT JOIN `stations` ON arrivals.station_id = stations.id
                LEFT JOIN `routes` ON arrivals.route_id = routes.id
                LEFT JOIN `arrivals_details` ON arrivals_details.arrival_id = arrivals.arrival_no
                LEFT JOIN `shipments` AS shipments ON arrivals_details.cn_numbers = shipments.consignment_no
                LEFT JOIN `pickup_locations` AS pl ON shipments.pickup_location_id = pl.id
                WHERE arrivals.company_id = $company_id AND arrivals.arrival_no = $arrival_no;
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
