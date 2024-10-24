<?php
ini_set('display_errors', 0);
ini_set('memory_limit', '-1');
date_default_timezone_set('Asia/karachi');
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: *");
header('Cache-Control: max-age=3600, public');
header('Vary: Accept-Encoding');
header('Strict-Transport-Security: max-age=31536000');
header('X-Response-Time: 0.5s');
include("db/config.php");
require 'vendor/autoload.php';

// use JsonSchema\Constraints\Factory;
// use JsonSchema\SchemaStorage;
use JsonSchema\Validator;

$dbobjx = new Database;

    $api_path = 'https://dukan.orio.website/orio_expressAPI/api/';
// $api_path = 'http://oex_citymail_api.test/api/';
define('API_URL', $api_path);
// define('TFM_URL', 'https://customerapi.tfmex.com/');
define('TFM_URL', 'https://staginggexpshipperapi.azurewebsites.net/');
define('SHIPA_URL', 'https://sandbox-api.shipadelivery.com/v2/');
$headers = getallheaders();
//==============================Response==============================
function response($status, $message, $payload = [])
{
    http_response_code($status == 1 ? 200 : 400);
    return json_encode(["status" => (int) $status, "message" => $message, "payload" => $payload]);
}
//==============================Response==============================
//==============================Curl Function==============================
function curlFunction($url, $data, $headers = "", $userpwd = "", $type = "")
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    if ($headers != "") {
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    }
    if ($userpwd != "") {
        curl_setopt($ch, CURLOPT_USERPWD, $userpwd);
    }
    if (in_array($type, ["GET", "PUT", "PATCH", "DELETE"])) {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $type);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    } else if ($type != "") {
        curl_setopt($ch, CURLOPT_POST, 0);
    } else {
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    }
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $result = curl_exec($ch);
    $err = curl_error($ch);
    curl_close($ch);
    if ($err) {
        return $err;
    } else {
        return $result;
    }
}
//==============================Curl Function==============================
//============================== api functions =============================
function getAPIdata($file_name, $data, $headers = "", $userpwd = "", $type = "")
{
    $json = json_encode($data);
    $response = curlFunction($file_name, $json, $headers = "", $userpwd = "", $type = "");
    $result = json_decode($response);
    return $result;
}
function getAPIJson($file_name, $data, $headers = "", $userpwd = "", $type = "")
{
    $json = json_encode($data);
    return curlFunction($file_name, $json, $headers = "", $userpwd = "", $type = "");
}
//==============================Generating Salt==============================
function generatingSalt()
{
    $salt = password_hash('Whatawonderfulday', PASSWORD_DEFAULT);
    return $salt;
}
//==============================Generating Salt==============================
//==============================Encrypt String==============================
function encryptString($salt, $password)
{
    $saltedpass = $password . $salt;
    $hashpassword = hash('sha256', $saltedpass);
    return $hashpassword;
}
//==============================Encrypt String==============================
//==============================Random Password==============================
function randomPassword()
{
    $alphabet = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890';
    $pass = array(); //remember to declare $pass as an array
    $alphaLength = strlen($alphabet) - 1; //put the length -1 in cache
    for ($i = 0; $i < 8; $i++) {
        $n = rand(0, $alphaLength);
        $pass[] = $alphabet[$n];
    }
    return implode($pass); //turn the array into a string
}
//==============================Random Password==============================
//==============================Validator==============================
function requestvalidateobject($request, $schema)
{
    $errors = array();
    $jsonValidator = new Validator();
    $jsonValidator->validate($request, $schema);
    if ($jsonValidator->isValid()) {
        $status = 1;
    } else {
        $status = 0;
        foreach ($jsonValidator->getErrors() as $error) {
            $errors[] = array(
                "property" => $error['property'],
                "message" => $error['message']
            );
        }
    }
    $response = array(
        "status" => $status,
        "error" => $errors
    );
    return json_encode($response);
}

//============================== Randome string =======================
function randomString($length)
{
    return bin2hex(random_bytes($length / 2));
}

//==============================Validator==============================
//==============================BlueX Booking==============================
function CNGenerationBlueEx($payload)
{
    extract($payload);
    $response = getAPIdata(API_URL . 'couriers_details/single.php', ['id' => $customer_courier_id]);
    $url = "benefitx.blue-ex.com/api/post_blueex.php";
    $xml = "<?xml version='1.0' encoding='utf-8'?>
            <BenefitDocument>
            <AccessRequest> 
                <DocumentType>1</DocumentType> 
                <TestTransaction>N</TestTransaction>
                <ShipmentDetail>
                    <ShipperName>" . $shipper_name . "</ShipperName>
                    <ShipperAddress>" . $shipper_address . "</ShipperAddress>
                    <ShipperContact>" . $shipper_contact . "</ShipperContact>
                    <ShipperEmail>" . $shipper_email . "</ShipperEmail>
                    <ConsigneeName>" . $consignee_name . "</ConsigneeName>
                    <ConsigneeAddress>" . $consignee_address . "</ConsigneeAddress>
                    <ConsigneeContact>" . $consignee_contact . "</ConsigneeContact>
                    <ConsigneeEmail>" . $consignee_email . "</ConsigneeEmail>
                    <CollectionRequired>" . $collection_required . "</CollectionRequired>
                    <ProductDetail>" . $product_details . "</ProductDetail>
                    <ProductValue>" . $order_amount . "</ProductValue>
                    <OriginCity>KHI</OriginCity>
                    <DestinationCountry>PK</DestinationCountry>
                    <DestinationCity>KHI</DestinationCity>
                    <ServiceCode>" . $service_code . "</ServiceCode>
                    <ParcelType>" . $parcel_type . "</ParcelType>
                    <Peices>" . $pieces . "</Peices>
                    <Weight>" . $weight . "</Weight>
                    <Fragile>" . $fragile_require . "</Fragile>
                    <ShipperReference>" . $shipper_refrence . "</ShipperReference>
                    <InsuranceRequire>" . $insurance_require . "</InsuranceRequire>
                    <InsuranceValue>0</InsuranceValue>
                    <ShipperComment>" . $shipper_comment . "</ShipperComment> 
                </ShipmentDetail> 
            </AccessRequest>
        </BenefitDocument>";
    $userpwd = $response->payload->user . ":" . $response->payload->password;
    $result = simplexml_load_string(curlFunction($url, array('xml' => $xml), "", $userpwd));
    // return $result;
    if ($result->status == '1') {
        $consigment_no = (string) $result->message;
        $payload['status_id'] = 8;
        $payload['third_consigment_no'] = $consigment_no;
        $update = getAPIdata(API_URL . 'tpl/add.php', $payload);
        $data = ["status" => $update->status, "message" => $update->payload];
    } elseif (isset($result->missing_params)) {
        $missing = implode(', ', (array) $result->missing_params->param);
        $data = ["status" => 0, "message" => $missing . " missing"];
    } else {
        $data = ["status" => 0, "message" => (string) $result->message];
    }
    return json_encode($data);
}
//==============================BlueX Booking==============================
//==============================BlueX Cancel==============================
function CancelBlueExCN($payload)
{
    extract($payload);
    $response = getAPIdata(API_URL . 'couriers_details/single.php', ['id' => $customer_courier_id]);
    if ($response->status == 1) {
        $url = "benefitx.blue-ex.com/api/post.php";
        $userpwd = $response->payload->user . ":" . $response->payload->password;
        $xml = "<?xml version='1.0' encoding='utf-8'?> 
        <BenefitDocument> 
        <AccessRequest> 
                    <DocumentType>12</DocumentType>
                    <TestTransaction></TestTransaction>
                    <ShipmentNumbers>
                        <Number>" . $consigment_no . "</Number>
                    </ShipmentNumbers>
                </AccessRequest> 
                </BenefitDocument>";
        $result = simplexml_load_string(curlFunction($url, array('xml' => $xml), "", $userpwd));
        if ($result->status) {
            if ((string) $result->statusrow->status == "Void") {
                $update = getAPIdata(API_URL . 'tpl/manual/void.php', ['cn_number' => $consigment_no]);
                $data = ["status" => 1, "message" => $update->payload];
            } else {
                $data = ["status" => 0, "message" => "Not Void"];
            }
        } else {
            $data = ["status" => 0, "message" => "Not Void"];
        }
        return json_encode($data);
    } else {
        return json_encode($data);
    }
}
//==============================BlueX Cancel==============================
//==============================BlueX Replacement==============================
function ReplaceCNBlueEx($payload)
{
    extract($payload);
    $url = "http://benefitx.blue-ex.com/api/customerportal/create_replacement.php";
    $response = couriercredentials($acno, $courier_id, $customer_courier_id);
    $data = json_decode($response);
    if ($data->status) {
        $request = [
            'cnno' => $consigment_no,
            'acno' => $data->acno,
            'con_name' => $consignee_name,
            'con_email' => $consignee_email,
            'con_contact' => $consignee_contact,
            'con_add' => $consignee_address,
            'shipper_name' => $shipper_name,
            'shipper_add' => $shipper_email,
            'shipper_contact' => $shipper_contact,
            'shipper_mail' => $shipper_address,
            'dest_country' => $country_code,
            'dest_city' => $destination_city_code,
            'orig_city' => $origin_city_code,
            'insurance' => $insurance,
            'prod_value' => $order_amount,
            'service_code' => $service_code,
            'product_detail' => $product_details,
            'pcs' => $piece,
            'weight' => $weight,
            'faragile' => $fragile,
            'cust_ref' => $order_ref,
            'ptype' => 'P',
            'cbc' => $cbc,
            'coment' => $comments,
            'type' => '1'
        ];
        $requestJson = ['request' => json_encode($request)];
        $result = json_decode(curlFunction($url, $requestJson, ""));
        if ($result->status == 1) {
            $replacement_consigment_no = $result->replacement_cnno;
            $data = ["status" => $result->status, "message" => $result->message, "replacement_cn" => $replacement_consigment_no];
            CourierUpdateReplacementOrder($order_id, $replacement_consigment_no);
        } else {
            $data = ["status" => $result->status, "message" => $result->message];
        }
        return json_encode($data);
    } else {
        return $response;
    }
}
//==============================BlueX Replacement==============================
//==============================TCS Booking==============================
function CNGenerationTCS($payload)
{
    extract($payload);
    $response = getAPIdata(API_URL . 'couriers_details/single.php', ['id' => $customer_courier_id]);
    if ($response->status) {
        $url = "https://apis.tcscourier.com/production/v1/cod/create-order";
        $request = array(
            "userName" => $response->payload->user,
            "password" => $response->payload->password,
            "costCenterCode" => "$courier_code",
            "consigneeName" => $consignee_name,
            "consigneeAddress" => $consignee_address,
            "consigneeMobNo" => $consignee_contact,
            "consigneeEmail" => $consignee_email,
            "originCityName" => "Karachi",
            "destinationCityName" => "Karachi",
            "weight" => $weight,
            "pieces" => $pieces,
            "codAmount" => $order_amount,
            "customerReferenceNo" => $shipper_refrence,
            "services" => $service_code,
            "productDetails" => $product_details,
            "fragile" => ($fragile_require == 'N') ? 'No' : 'Yes',
            "remarks" => $shipper_comment,
            "insuranceValue" => intval($insurance_value)
        );
        // return $request;
        $api_key = $response->payload->api_key;
        $header = array(
            "accept: application/json",
            "content-type: application/json",
            "x-ibm-client-id: $api_key"
        );
        $result = json_decode(curlFunction($url, json_encode($request), $header));
        if ($result->returnStatus->code == "0200") {
            $consigmentNo = $result->bookingReply->result;
            $consigmentNo = explode(":", $consigmentNo);
            $consigment_no = trim($consigmentNo[1]);
            $data = ["status" => 1, "message" => $consigment_no];
            $payload['status_id'] = 8;
            $payload['third_consigment_no'] = $consigment_no;
            // return $payload;exit();
            $update = getAPIdata(API_URL . 'tpl/add.php', $payload);
            $data = ["status" => 1, "message" => $update->payload];
        } else if ($result->returnStatus->code == '0400') {
            $data = ["status" => 0, "message" => $result->returnStatus->message];
        } else {
            $data = ["status" => 0, "message" => 'TCS Courier API Error!'];
        }
        return json_encode($data);
    } else {
        return json_encode($response);
    }
}
//==============================TCS Booking==============================
//==============================TCS Cancel==============================
function CancelTCSCN($payload)
{
    extract($payload);
    $response = getAPIdata(API_URL . 'couriers_details/single.php', ['id' => $customer_courier_id]);
    if ($response->status == 1) {
        $url = "https://apis.tcscourier.com/production/v1/cod/cancel-order";
        $request = array(
            "userName" => $response->payload->user,
            "password" => $response->payload->password,
            "consignmentNumber" => $consigment_no
        );
        $api_key = $response->payload->api_key;
        // return $api_key;
        $header = array(
            "accept: application/json",
            "content-type: application/json",
            "x-ibm-client-id: $api_key"
        );
        $result = json_decode(curlFunction($url, json_encode($request), $header, '', 'PUT'));
        if ($result->returnStatus->code == "0200") {
            $update = getAPIdata(API_URL . 'tpl/manual/void', ['cn_number' => $consigment_no]);
            $data = ["status" => 1, "message" => $update->payload];
        } else {
            $data = ["status" => 0, "message" => $result->returnStatus->message];
        }
        return json_encode($data);
    } else {
        return $response;
    }
}
//==============================TCS Cancel==============================
//==============================Leopards Booking==============================
function CNGenerationLeopards($payload)
{
    extract($payload);
    $response = couriercredentials($acno, $courier_id, $customer_courier_id);
    $data = json_decode($response);
    if ($data->status) {
        $mode = ($acno == 'OR-00009') ? 'Staging' : '';
        $url = "https://merchantapi$mode.leopardscourier.com/api/bookPacket/format/json";

        $request = [
            "api_key" => $data->courier_apikey,
            "api_password" => $data->courier_password,
            "booked_packet_weight" => $weight,
            "booked_packet_vol_weight_w" => "",
            "booked_packet_vol_weight_h" => "",
            "booked_packet_vol_weight_l" => "",
            "booked_packet_no_piece" => $pieces,
            "booked_packet_collect_amount" => $order_amount,
            "booked_packet_order_id" => $shipper_refrence,
            "origin_city" => $origin_city_code,
            "destination_city" => $destination_city_code,
            "shipment_name_eng" => $shipper_name,
            "shipment_email" => $shipper_email,
            "shipment_phone" => $shipper_contact,
            "shipment_address" => clean($shipper_address),
            "consignment_name_eng" => $consignee_name,
            "consignment_email" => $consignee_email,
            "consignment_phone" => $consignee_contact,
            "consignment_phone_two" => "",
            "consignment_phone_three" => "",
            "consignment_address" => clean($consignee_address),
            "special_instructions" => "-",
            "shipment_type" => $service_code
        ];
        $result = json_decode(curlFunction($url, $request));
        if ($result->status == 1) {
            $consigment_no = $result->track_number;
            $data = ["status" => 1, "message" => $consigment_no];
            $payload['status_id'] = 8;
            $payload['consigment_no'] = $consigment_no;
            CourierUpdateOrder($payload);
        } else if ($result->status == 0) {
            $data = ["status" => 0, "message" => $result->error];
        } else {
            $data = ["status" => 0, "message" => 'Leopard Courier API Error!'];
        }
        return json_encode($data);
    } else {
        return $response;
    }
}
//==============================Leopards Booking==============================
//==============================Leopards Cancel==============================
function CancelLeopardsCN($payload)
{
    extract($payload);
    $response = couriercredentials($acno, $courier_id, $customer_courier_id);
    $data = json_decode($response);
    if ($data->status) {
        $mode = ($acno == 'OR-00009') ? 'Staging' : '';
        $url = "https://merchantapi$mode.leopardscourier.com/api/cancelBookedPackets/format/json";
        $data = array(
            'api_key' => $data->courier_apikey,
            'api_password' => $data->courier_password,
            'cn_numbers' => $consigment_no
        );
        $result = json_decode(curlFunction($url, $data));
        if ($result->status == "0") {
            $data = ["status" => 0, "message" => $result->error->$consigment_no];
        } else {
            CancelCNOrder($order_id);
            $data = ["status" => 1, "message" => 'Void'];
        }
        return json_encode($data);
    } else {
        return $response;
    }
}
//==============================Leopards Cancel==============================
//==============================M&P Booking==============================
function CNGenerationMNP($payload)
{
    extract($payload);
    $response = couriercredentials($acno, $courier_id, $customer_courier_id);
    $data = json_decode($response);
    if ($data->status) {
        $url = "http://mnpcourier.com/mycodapi/api/Booking/InsertBookingData";
        $request = array(
            "username" => $data->courier_user,
            "password" => $data->courier_password,
            "consigneeName" => $consignee_name,
            "consigneeAddress" => clean($consignee_address),
            "consigneeMobNo" => $consignee_contact,
            "consigneeEmail" => $consignee_email,
            "destinationCityName" => $destination_city_code,
            "pieces" => $pieces,
            "weight" => $weight,
            "codAmount" => $order_amount,
            "custRefNo" => $shipper_refrence,
            "productDetails" => clean($product_details),
            "fragile" => ($fragile_require == 'N') ? 'No' : 'Yes',
            "service" => 'S',
            "remarks" => $shipper_comment,
            "insuranceValue" => $insurance_require,
            "locationID" => $courier_code
        );
        $header = array(
            "Content-Type: application/json"
        );
        $result = json_decode(curlFunction($url, json_encode($request), $header));
        if ((string) $result[0]->isSuccess === "true") {
            $consigment_no = (string) $result[0]->orderReferenceId;
            $data = ["status" => 1, "message" => $consigment_no];
            $payload['status_id'] = 8;
            $payload['consigment_no'] = $consigment_no;
            CourierUpdateOrder($payload);
        } else {
            $data = ["status" => 0, "message" => (string) $result[0]->message];
        }
        return json_encode($data);
    } else {
        return $response;
    }
}
//==============================M&P Booking==============================
//==============================M&P Cancel==============================
function CancelMNPCN($payload)
{
    extract($payload);
    $response = couriercredentials($acno, $courier_id, $customer_courier_id);
    $data = json_decode($response);
    if ($data->status) {
        $url = 'http://mnpcourier.com/mycodapi/api/Booking/VoidConsignment';
        $request = array(
            'username' => $data->courier_user,
            'password' => urlencode($data->courier_password),
            'consignmentNumberList' => [$consigment_no],
            'locationID' => $courier_code
        );
        $result = json_decode(curlFunction($url, json_encode($request)));
        if ($result[0]->isSuccess) {
            $status = $result[0]->orderReferenceIdList[0];
            if ($status->success) {
                CancelCNOrder($order_id);
                $data = ["status" => 1, "message" => 'Void'];
            } else {
                $data = ["status" => 0, "message" => (string) $status->message];
            }
        } else {
            $data = ["status" => 0, "message" => (string) $result[0]->message];
        }
        return json_encode($data);
    } else {
        return $response;
    }
}
//==============================M&P Cancel==============================

//==============================Trax Booking==============================
function CNGenerationTrax($payload)
{
    extract($payload);
    $response = couriercredentials($acno, $courier_id, $customer_courier_id);
    $data = json_decode($response);
    if ($data->status) {
        $url = "https://sonic.pk/api/shipment/book";
        $request = array(
            'service_type_id' => (int) $service_code,
            'pickup_address_id' => (int) $courier_code,
            'information_display' => '1',
            'consignee_city_id' => $destination_city_code,
            'consignee_name' => $consignee_name,
            'consignee_address' => clean($consignee_address),
            'consignee_phone_number_1' => $consignee_contact,
            'consignee_phone_number_2' => $consignee_contact,
            'consignee_email_address' => $consignee_email,
            'order_id' => $shipper_refrence,
            'item_product_type_id' => 24,
            'item_description' => clean($product_details),
            'item_quantity' => $pieces,
            'item_insurance' => ($insurance_require == 'Y') ? 1 : 0,
            'pickup_date' => date('Y-m-d'),
            'special_instructions' => $order_id,
            'estimated_weight' => $weight,
            'shipping_mode_id' => 1,
            'same_day_timing_id' => '1',
            'payment_mode_id' => ($payment_method_id == 1) ? 1 : 4,
            'amount' => $order_amount,
            'parcel_value' => ($total_amount > 0) ? $total_amount : 10,
            'charges_mode_id' => 4,
            'delivery_type_id' => 1,
        );
        $header = ["Authorization: " . $data->courier_apikey];
        $result = json_decode(curlFunction($url, json_encode($request), $header));
        if ($result->status == "0") {
            $consigment_no = (string) $result->tracking_number;
            $data = ["status" => 1, "message" => $consigment_no];
            $payload['status_id'] = 8;
            $payload['consigment_no'] = $consigment_no;
            CourierUpdateOrder($payload);
        } else if ($result->status == "1") {
            $error_array = array();
            foreach ((array) $result->errors as $error) {
                array_push($error_array, $error);
            }
            $error = (is_array(end($error_array))) ? end($error_array)[0] : end($error_array);
            $data = ["status" => 0, "message" => $error];
        } else {
            $data = ["status" => 0, "message" => 'Trax Courier API Error!'];
        }
        return json_encode($data);
    } else {
        return $response;
    }
}
//==============================Trax Booking==============================
//==============================Trax Cancel==============================
function CancelTraxCN($payload)
{
    extract($payload);
    $response = couriercredentials($acno, $courier_id, $customer_courier_id);
    $data = json_decode($response);
    if ($data->status) {
        $url = "https://sonic.pk/api/shipment/cancel";
        $header = ["Authorization: $data->courier_apikey"];
        $data = ["tracking_number" => $consigment_no];
        $result = json_decode(curlFunction($url, $data, $header));
        if ($result->status == "0") {
            CancelCNOrder($order_id);
            $data = ["status" => 1, "message" => 'Void'];
        } else {
            $data = ["status" => 0, "message" => $result->message];
        }
        return json_encode($data);
    } else {
        return $response;
    }
}
//==============================Trax Cancel==============================
//============================== TFM courier ==============================
function CNGenerationTFM($payload, $customer_courier_id)
{
    $token = generateToken($customer_courier_id);
    $consignmentArray = [];
    if (isset($payload['skybills']) && is_array($payload['skybills'])) {
        foreach ($payload['skybills'] as &$skybill) {
            if (isset($skybill['shipperId']) && $skybill['shipperId'] == 0) {
                $skybill['shipperId'] = $token->userId;
            }
            $shipperReferenceNo = $skybill['shipperReferenceNo'];

            $consignmentArray[$shipperReferenceNo][] = [
                "consignment_no" => $skybill['consignment_no'],
                "courier_id" => $skybill['courier_id'],
                "customer_courier_id" => $skybill['customer_courier_id']
            ];
        }
    }
    $header = array(
        "Content-Type: application/json",
        "Authorization: Bearer " . $token->token,
    );
    $url = TFM_URL . 'api/v1/SkyBill/new_bulk';
    $result = json_decode(curlFunction($url, json_encode($payload, JSON_UNESCAPED_UNICODE), $header));
    
    // return $result;
    $error_arr = [];
    $success_arr = [];
    if ($result->statusCode == '200') {
        $data = $result->result->skybills;
        foreach ($data as $value) {
            $third_consigment_no = (string) $value->awb;
            $shipperReferenceNo = (string) $value->shipperReferenceNo;
            $consignmentData = $consignmentArray[$shipperReferenceNo][0];
            $params = [
                "consignment_no" => $consignmentData['consignment_no'],
                "courier_id" => $consignmentData['courier_id'],
                "customer_courier_id" => $consignmentData['customer_courier_id'],
                "third_consigment_no" => $third_consigment_no,
            ];
            getAPIdata(API_URL . 'tpl/add.php', $params);
            $success_arr[] = ["tpl_consignment_no" => $third_consigment_no, "consignment_no" => $shipperReferenceNo];
        }
        $message = "Success";
    } elseif ($result->statusCode == '401') {
        $message = "Unauthorized";
    } else {
        $details = $result->detail;
        $errors = json_decode(trim(str_replace('Errors:', '', $details)));
        foreach ($errors as $key => $detail) {
            $error_arr[] = [
                "reference" => $detail->ShipperReferenceNo,
                "feild" => $detail->Field,
                "message" => $detail->Message,
            ];
        }
        $message = "Error";
    }
    $data = ["status" => $result->statusCode, "message" => $message, 'payload' => ['success' => $success_arr, 'error' => $error_arr]];

    return json_encode($data);
}

function CancelTFM($payload)
{
    foreach ($payload as $key => $value) {
        if (isset($value['tpl_consignments'])) {
            $tpl_consignments = $value['tpl_consignments'];
        }
        $token = generateToken($key);
        $header = array(
            "Content-Type: application/json",
            "Authorization: Bearer " . $token->token,
        );
        $params = [
            "userId" => $token->userId,
            "remarks" => "",
            "awb" => $tpl_consignments
        ];
        $url = TFM_URL . 'api/v1/SkyBill/revoke';
        $result = json_decode(curlFunction($url, json_encode($params, JSON_UNESCAPED_UNICODE), $header));

        if ($result->statusCode == '200' && $result->isError == false) {
            foreach ($result->result as $res) {
                $update = getAPIdata(API_URL . 'tpl/auto/void.php', ['tpl_consignment' => $res->awb]);
                $data = ["status" => 1, "message" => $update->payload];
            }
            $data = ["status" => '1', "message" => "Shipments has been cancled", 'payload' => []];
        } else {
            $data = ["status" => '0', "message" => "$result->title", 'payload' => []];
        }
        return json_encode($data);
    }
}

//=================================== SHIPA courier ============
function CNGenerationSHIPA($payload, $customer_courier_id)
{
    $credientials = get_credientials($customer_courier_id);
    // print_r(json_encode($payload, JSON_UNESCAPED_UNICODE));die;
    $url = SHIPA_URL . 'orders/bulk?apikey=' . $credientials->api_key;
    $result = json_decode(curlFunction($url, json_encode($payload, JSON_UNESCAPED_UNICODE)));
    $error_arr = [];
    $success_arr = [];
    $message = "";
    foreach ($result as $res) {
        if (isset($res->code) && $res->code == '50013') {
            $errors = $res->errors;
            foreach ($errors as $detail) {
                $feild = "";
                preg_match('/^(\S+)/', $detail->message, $firstWord);
                if (!empty($firstWord[1])) {
                    $feild = $firstWord[1];
                }
                $error_arr[] = [
                    "reference" => $res->customerRef,
                    "feild" => $feild,
                    "message" => $detail->message,
                ];
            }
            $message = "Error";
        } else if (isset($res->shipaRef)) {
            $third_consigment_no = $res->shipaRef;

            $params = [
                "consignment_no" => $res->customerRef,
                "courier_id" => '23',
                "customer_courier_id" => $customer_courier_id,
                "third_consigment_no" => $third_consigment_no,
            ];
            getAPIdata(API_URL . 'tpl/add.php', $params);
            $success_arr[] = ["tpl_consignment_no" => $third_consigment_no, "consignment_no" => $res->customerRef];
            $message = "Success";
        }
    }
    $data = ["status" => $result->statusCode, "message" => $message, 'payload' => ['success' => $success_arr, 'error' => $error_arr]];
    return json_encode($data);
}
function CancelSHIPA($payload)
{
    extract($payload);
    $credientials = get_credientials($customer_courier_id);
    $url = SHIPA_URL . 'orders/' . $consigment_no . '/cancel?apikey=' . $credientials->api_key;
    $result = json_decode(curlFunction($url, json_encode($payload, JSON_UNESCAPED_UNICODE)));
    if ($result->code != '50008') {
        $update = getAPIdata(API_URL . 'tpl/auto/void.php', ['tpl_consignment' => $consigment_no]);
        $data = ["status" => '1', "message" => "Shipments has been cancled", 'payload' => []];
    } else {
        $data = ["status" => '0', "message" => "Shipment Already Cancled", 'payload' => []];
    }
    return json_encode($data);
}

// generate token
function generateToken($account_id = "", $user = "", $pwd = "")
{
    if ($user == "" && $pwd == "") {
        $result = getAPIdata(API_URL . 'couriers_details/single', ['account_id' => $account_id]);
        $payload = $result->payload;
        $user = $payload->user;
        $data = ['userName' => $payload->user, 'password' => $payload->password];
        $directive = '';
    }else{
        $directive = '../';
        $data = ['userName' => $user, 'password' => $pwd];
    }
    $filename = $directive.'Token/' . $user . '.txt';
    $headers = array(
        'Content-Type: application/json'
    );
    $url = TFM_URL.'api/v1/Token';
    if (!file_exists($filename)) {
        $result = json_decode(curlFunction($url, json_encode($data), $headers));
        generateFile($filename, json_encode($result->result));
        $return = json_decode(file_get_contents($filename));
    } else {
        $filetime = filemtime($filename);
        if (round((time() - $filetime) / (60 * 60)) >= 2) {
            $result = json_decode(curlFunction($url, json_encode($data), $headers));
            generateFile($filename, json_encode($result->result));
            $return = json_decode(file_get_contents($filename));
        } else {
            $return = json_decode(file_get_contents($filename));
        }
    }
    return $return;
}

function get_credientials($account_id)
{
    $result = getAPIdata(API_URL . 'couriers_details/single', ['account_id' => $account_id]);
    return $result->payload;
}
//==============================Calculate tariff=========================
function calculate_tariff($params)
{
    $result = getAPIdata(API_URL . 'arrival-origin/tariffCalculation', $params);
    // $result = getAPIJson(API_URL . 'arrival-origin/tariffCalculation', $params);
    return $result;
}
//==============================Calculate tariff=========================
//==============================Auto tpl tariff=========================
function auto_thirdparty($params)
{
    $url = API_URL . 'tpl/auto/apply_rules';
    // $result = getAPIdata($url, $params);
    $result = getAPIJson($url, $params);
    return $result;
}
//==============================Auto tpl tariff=========================

//==============================Generate File=========================
function generateFile($path, $data)
{
    $fw = fopen($path, 'w');
    if ($fw === false) {
        return false;
    }
    fwrite($fw, $data);
    fclose($fw);
    return true;
}
//==============================Generate File=========================
//==============================Generate Dir=========================
function generateDir($path, $port, $bool)
{
    if (!dir($path) && !is_readable($path)) {
        mkdir($path, $port, $bool);
        return true;
    }
}
//==============================Generate Dir=========================

//==============================Match Cities==============================
// function convertCurrency($convert_from,$convert_to,$company_id){
//     $url = 'https://v6.exchangerate-api.com/v6/d5874d1190e9c070afff5f57/latest/'.$convert_from;
//     $filename = $convert_from.'-'.$convert_to.'.txt';
//     if (!file_exists($filename)) {
//         $result = json_decode(curlFunction($url,'','','','GET'));
//         generateFile($filename,json_encode($result));
//         $return = json_decode(file_get_contents($filename));
//     } else {
//         $filetime = filemtime($filename);
//         if (round((time() - $filetime) / (60 * 60 * 24)) >= 1) {
//             $result = json_decode(curlFunction($url,'','','','GET'));
//             generateFile($filename,json_encode($result));
//             $return = json_decode(file_get_contents($filename));
//         } else {
//             $return = json_decode(file_get_contents($filename));
//         }
//     }
//     return $return;
// }
//==============================Match Cities==============================

function get_user_id_header()
{
    $headers = getallheaders();
    return $headers['Client-Id'];
}

function convertCurrency($amount, $from_currency, $company_id)
{
    global $dbobjx;
    // Fetch currency rates from the database
    $query = "SELECT `currency_code`, pkr, aed, usd,sar FROM `companies` WHERE `company_id` = '$company_id'";
    $dbobjx->query($query);
    $res = $dbobjx->single();

    $company_currency = $res->currency_code;

    // Define conversion rates
    $rates = [
        'PKR' => $res->pkr,
        'AED' => $res->aed,
        'USD' => $res->usd,
        'SAR' => $res->sar
    ];

    // If the base currency is different from the company's currency, adjust the amount
    if ($company_currency !== $from_currency) {
        // Convert the amount to the company's base currency
        $amount_in_company_currency = $amount * $rates[$from_currency];
        // return $amount_in_company_currency;
    } else {
        $amount_in_company_currency = $amount;
    }
    // Convert the company's base currency to the target currency
    $converted_amount = $amount_in_company_currency / $rates[$company_currency];

    return json_decode(json_encode(["converted_amt" => $converted_amount, "converted_to" => $company_currency]));
}

//=============================Schema Json Validator=================================
function schemaValidator($data, $schema, $path = ''){
    static $errors = [];
    if (!isset($schema) || gettype($schema) === NULL) {
        $errors[] = [
            "property" => "Request",
            "message" => "Wrong path or invalid json."
        ];
        return json_encode(["status" => "0", "error" => $errors]);
    }
    if (!is_object($schema) && !isset($schema->properties)) {
        $errors[] = [
            "property" => "Request",
            "message" => "Invalid schema format."
        ];
        return json_encode(["status" => "0", "error" => $errors]);
    }
    if (isset($schema->properties)) {
        foreach ((array) $schema->properties as $key => $propertySchema) {
            $property = $path ? "$path.$key" : $key;
            if ($data !== NULL && is_object($data)) {
                if (isset($data->$key)) {
                    if (gettype($data->$key) === $propertySchema->type) {
                        if ((in_array($propertySchema->type, ["string", "integer"])) && (isset($propertySchema->nullable) || trim($data->$key) || in_array($data->$key, ["0", 0]))) {
                            if (isset($propertySchema->minLength) && strlen($data->$key) < $propertySchema->minLength) {
                                $errors[] = [
                                    "property" => $property,
                                    "message" => "String length is less than the minimum: {$propertySchema->minLength}"
                                ];
                            }
                            if (isset($propertySchema->maxLength) && strlen($data->$key) > $propertySchema->maxLength) {
                                $errors[] = [
                                    "property" => $property,
                                    "message" => "String length is more than the maximum: {$propertySchema->maxLength}"
                                ];
                            }
                            if (isset($propertySchema->minimum) && $data->$key < $propertySchema->minimum) {
                                $errors[] = [
                                    "property" => $property,
                                    "message" => "Value is less than the minimum: {$propertySchema->minimum}"
                                ];
                            }
                            if (isset($propertySchema->maximum) && $data->$key > $propertySchema->maximum) {
                                $errors[] = [
                                    "property" => $property,
                                    "message" => "Value is greater than the maximum: {$propertySchema->maximum}"
                                ];
                            }
                            if (isset($propertySchema->enum) && !in_array($data->$key, $propertySchema->enum)) {
                                $errors[] = [
                                    "property" => $property,
                                    "message" => "Value is not one of the allowed values: " . implode(", ", $propertySchema->enum)
                                ];
                            }
                            if (isset($propertySchema->format)) {
                                if ($propertySchema->format === "email" && !filter_var($data->$key, FILTER_VALIDATE_EMAIL)) {
                                    $errors[] = [
                                        "property" => $property,
                                        "message" => "Invalid email format."
                                    ];
                                }
                                if ($propertySchema->format === "uri" && !filter_var($data->$key, FILTER_VALIDATE_URL)) {
                                    $errors[] = [
                                        "property" => $property,
                                        "message" => "Invalid URI format."
                                    ];
                                }
                                if ($propertySchema->format === "phone" && !preg_match('/^03\d{9}$/', $data->$key)) {
                                    $errors[] = [
                                        "property" => $property,
                                        "message" => "Invalid phone number format."
                                    ];
                                }
                            }
                        } elseif ($propertySchema->type === "array") {
                            if (isset($propertySchema->minItems) && count($data->$key) < $propertySchema->minItems) {
                                $errors[] = [
                                    "property" => $property,
                                    "message" => "Array has fewer items than the minimum: {$propertySchema->minItems}"
                                ];
                            }
                            if (isset($propertySchema->maxItems) && count($data->$key) > $propertySchema->maxItems) {
                                $errors[] = [
                                    "property" => $property,
                                    "message" => "Array has more items than the maximum: {$propertySchema->maxItems}"
                                ];
                            }
                        } else {
                            $errors[] = ["property" => $property, "message" => "Empty value found, but a {$propertySchema->type} is required."];
                        }
                    } else {
                        $errors[] = [
                            "property" => $property,
                            "message" => gettype($data->$key) . ' value found, but an ' . $propertySchema->type . ' is required.'
                        ];
                    }
                } else {
                    if (isset($schema->required) && in_array($key, $schema->required)) {
                        $errors[] = [
                            "property" => $property,
                            "message" => "The property {$property} is required"
                        ];
                    }
                }
            } else {
                $errors[] = [
                    "property" => "Request",
                    "message" => gettype($data) . ' value found, but an object is required.'
                ];
                return json_encode(["status" => "0", "error" => $errors]);
            }
        }
    }
    return empty($errors) ? json_encode(["status" => "1", "error" => []]) : json_encode(["status" => "0", "error" => $errors]);
}
//=============================Schema Json Validator=================================