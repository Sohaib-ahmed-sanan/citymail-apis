<?php
include "../../index.php";
// $registerSchema = json_decode(file_get_contents('../schema/stations/add.json'));
$request = json_decode(file_get_contents('php://input'));
// $valid = json_decode(requestvalidateobject($request, $registerSchema));
include "../../functions/siteFunctions.php";

try {
    $cn_number = (isset($request->cn_number) ? $request->cn_number : '');
    $type = (isset($request->type) ? $request->type : '');
    $join = "";
    $alis = "";
    if ($type == 'void' || $type == 'print') {
        $were = " WHERE shipments.thirdparty_consignment_no = '$cn_number'";
    } else {
        $were = " WHERE shipments.consignment_no = '$cn_number'";
        $join = " LEFT JOIN `pickup_locations` AS pl ON shipments.pickup_location_id = pl.id  LEFT JOIN `customers` AS customer ON shipments.customer_acno = customer.acno LEFT JOIN countries As destination_country On shipments.destination_country = destination_country.id   ";
        $alis = ",pl.name As shipper_name,pl.country_id As origin_country,pl.email As shipper_email,pl.phone As 
                shipper_phone,pl.address As shipper_address,customer.acno As acno,destination_country.country_name";
    }
    $query = "SELECT shipments.* $alis FROM `shipments` $join $were ";
    $dbobjx->query($query);
    $result = $dbobjx->single();
    echo response("1", "Success", $result);

} catch (Exception $e) {
    echo response("0", "Api Error !", $e);
}
