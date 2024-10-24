<?php
include "../index.php";
$registerSchema = json_decode(file_get_contents('../schema/pickup_locations/update.json'));
$request = json_decode(file_get_contents('php://input'));
$valid = json_decode(requestvalidateobject($request, $registerSchema));
if ($valid->status) {
    try {
        $id = $request->id;
        $name = isset($request->name) ? $request->name : '';
        $email = isset($request->email) ? $request->email : '';
        $phone = isset($request->phone) ? $request->phone : '';
        $address = isset($request->address) ? $request->address : '';
        $destination = isset($request->destination) ? $request->destination : '';
        $customer_acno = isset($request->customer_acno) ? $request->customer_acno : '';
        $pickup_location = isset($request->pickup_location) ? $request->pickup_location : '';
        $parcel_detail = isset($request->parcel_detail) ? $request->parcel_detail : '';
        $payment_method_id = isset($request->payment_method_id) ? $request->payment_method_id : '';
        $order_amount = isset($request->order_amount) ? $request->order_amount : '';
        $weight = isset($request->weight) ? $request->weight : '';
        $peices = isset($request->peices) ? $request->peices : '';
        $comments = isset($request->comments) ? $request->comments : '';
        $fragile = isset($request->fragile) ? $request->fragile : '';
        $insurance = isset($request->insurance) ? $request->insurance : '';
        $device = isset($request->device) ? $request->device : '';
        $flag = isset($request->flag) ? $request->flag : '';
        $shipment_ref = isset($request->shipment_ref) ? $request->shipment_ref : '';
        $service_id = isset($request->service_id) ? $request->service_id : '';
        $destination_country = isset($request->destination_country) ? $request->destination_country : '';
        $currency_code = isset($request->currency_code) ? $request->currency_code : '';
        $comments = isset($request->comments) ? $request->comments : '';
        $check = $query = "SELECT `status` FROM `shipments` WHERE `id` = $id";
        $dbobjx->query($check);
        $validate = $dbobjx->single();
        if($validate->status != 4)
        {
            $query = "UPDATE `shipments` SET `customer_acno`='$customer_acno',`consignee_name`='$name',`consignee_email`='$email',`consignee_phone`='$phone',`pickup_location_id`='$pickup_location',`service_id`='$service_id',`consignee_address`='$address',`destination_country` = '$destination_country',`destination_city_id`='$destination',`shipment_referance`='$shipment_ref',`parcel_detail`='$parcel_detail',`peices`='$peices',`weight`='$weight',`order_amount`='$order_amount',`payment_method_id`=$payment_method_id,`shipper_comment`='$comments',`fragile`='$fragile',`insurance`='$insurance',`updated_at`= CURRENT_TIMESTAMP() WHERE `id` = $id";
            $dbobjx->query($query);
            $dbobjx->execute();
            echo response("1", "Success", "Manual booking has been updated successfully !");
        }else{
            echo response("0", "Error", "Shipmant cannot be edited after arrival");
        }

    } catch (Exception $e) {
        echo response("0", "Api Error !", $e);
    }

} else {
    echo response("0", "Error !", $valid->error);
}