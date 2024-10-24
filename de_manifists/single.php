<?php
include "../index.php";
$registerSchema = json_decode(file_get_contents('../schema/demanifists/single.json'));
$request = json_decode(file_get_contents('php://input'));
$valid = json_decode(requestvalidateobject($request, $registerSchema));
include "../functions/siteFunctions.php";
$has_key = authorization();
if ($has_key) {
    $valid_key = authantication($dbobjx);
    if ($valid_key === true) {
        if ($valid->status) {
            $company_id = $request->company_id;
            $id = $request->id;
            try {
                $query = "SELECT de_manifist.*,details.consignment_no,stations.name As station_name,
                    shipment.consignee_name As consignee_name,shipment.shipment_referance,shipment.thirdparty_consignment_no,
                    pl.city_id As origin,shipment.destination_city_id As destination,shipment.peices_charged As peices,shipment.weight_charged As weight
                    FROM 
                    de_manifist
                    LEFT JOIN de_manifist_details AS details ON details.de_manifist_id = de_manifist.id
                    LEFT JOIN `stations` AS stations ON de_manifist.station_id = stations.id
                    LEFT JOIN `shipments` AS shipment ON details.consignment_no = shipment.consignment_no
                    LEFT JOIN `pickup_locations` AS pl ON shipment.pickup_location_id = pl.id
                    WHERE de_manifist.company_id = '$company_id' AND de_manifist.id = '$id'
                ";
                $dbobjx->query($query);
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