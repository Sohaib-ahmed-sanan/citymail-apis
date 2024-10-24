<?php
include "../index.php";
$registerSchema = json_decode(file_get_contents('../schema/manifists/single.json'));
$request = json_decode(file_get_contents('php://input'));
$valid = json_decode(requestvalidateobject($request, $registerSchema));
include "../functions/siteFunctions.php";
$has_key = authorization();
if ($has_key) {
    $valid_key = authantication($dbobjx);
    if ($valid_key === true) {
if ($valid->status) {
    try {
        $manifist_id = $request->manifist_id;
        $company_id = $request->company_id;
        $check = "SELECT manifist_details.*,manifist.batch_name,manifist.station_id As station_id,manifist.seal_no,manifist.rider_id As rider_id,shipments.consignee_name, shipments.shipment_referance,pl.name As shipper_name,pl.city_id As oigin,
        shipments.order_amount As cod_amt,shipments.destination_city_id As destination,shipments.weight_charged As weight,shipments.peices_charged As peices,shipments.thirdparty_consignment_no,
        stations.name As station_name
        FROM manifists As manifist
        LEFT JOIN `manifist_details` ON manifist_details.manifist_id = manifist.id
        LEFT JOIN `stations` AS stations ON manifist.station_id = stations.id
        LEFT JOIN `shipments` AS shipments ON manifist_details.consignment_no = shipments.consignment_no
        LEFT JOIN `pickup_locations` AS pl ON shipments.pickup_location_id = pl.id
        WHERE manifist.id = '$manifist_id' AND manifist.is_deleted = 'N' AND manifist.company_id = '$company_id' ;
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