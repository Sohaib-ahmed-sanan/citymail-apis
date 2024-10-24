<?php
include "../index.php";
$request = json_decode(file_get_contents('php://input'));
// print_r($request);die;
$courier_id = $request->courier_id;
$customer_courier_id = $request->customer_courier_id;
$courier_code = $request->courier_code;
$service_code = $request->service_code;
$fragile = $request->fragile_require;
$insurance = $request->insurance_require;
$insurance_value = $request->insurance_value;
$parcel_type = $request->parcel_type;
$details_arr = isset($request->detail[0]) ? $request->detail[0] : '';
$consignment_no = $request->consignment_no;
$response_arr = array();
$success_arr = array();
$params = array();
$consignment_no_array = array();
foreach ($consignment_no as $key => $cn_no) {
    $result = getAPIdata(API_URL . 'tpl/manual/getData', ['cn_number' => $cn_no]);
    $payload = $result->payload;
    // print_r($payload);die;
    $origin_maping = ['type' => 'origin', 'cn_number' => $cn_no, 'courier_id' => $courier_id, 'customer_courier_id' => $customer_courier_id];
    $origin = getAPIdata(API_URL . 'common/citiesMapping', $origin_maping);
    if (!$origin) {
        $response_arr[] = [
            "reference" => $payload->consignment_no,
            "field" => 'Origin city',
            "message" => 'City not mapped'
        ];
        continue;        
    }
    $destination_maping = ['type' => 'destination', 'customer_courier_id' => $customer_courier_id, 'courier_id' => $courier_id, 'city_id' => $payload->destination_city_id];
    $destination = getAPIdata(API_URL . 'common/citiesMapping', $destination_maping);
    if (!$destination) {
        $response_arr[] = [
            "reference" => $payload->consignment_no,
            "field" => 'Destination city',
            "message" => 'City not mapped'
        ];
        continue;        
    }
    $origin_city_code = $origin->courier_city_code;
    $origin_city_name = $origin->city;
    $origin_country_name = $origin->country_name;
    $origin_country_code = $origin->country_code;

    $destination_city_code = $destination->courier_city_code;
    $destination_city_name = $destination->city;
    $destination_country_name = $destination->country_name;
    $destination_country_code = $destination->country_code;

    if (!in_array($courier_id, ['23', '22'])) {
        $data_arr = array(
            'cn_no' => $payload->consignment_no,
            'acno' => $payload->acno,
            'courier_id' => $courier_id,
            'customer_courier_id' => $customer_courier_id,
            'courier_code' => $courier_code,
            'shipper_name' => $payload->shipper_name,
            'shipper_email' => $payload->shipper_email,
            'shipper_contact' => $payload->shipper_phone,
            'shipper_address' => $payload->shipper_address,
            'consignee_name' => $payload->consignee_name,
            'consignee_email' => $payload->consignee_email,
            'consignee_contact' => $payload->consignee_phone,
            'consignee_address' => $payload->consignee_address,
            'product_details' => $payload->parcel_detail,
            'order_amount' => ($payload->payment_method_id == 1) ? $payload->order_amount : 0,
            'country_code' => "PK",
            'currency_code' => "PK",
            'total_amount' => $payload->order_amount,
            'pickup_location_id' => $payload->pickup_location_id,
            // 'origin_city_id' => $payload->origin_city_id,
            'origin_city_name' => $origin_city_name,
            'origin_city_code' => $origin_city_code,
            'destination_country_name' => $payload->country_name,
            'destination_city_code' => $destination_city_code,
            'destination_city_name' => $destination_city_name,
            'parcel_type' => '1',
            'pieces' => (!empty($payload->peices_charged)) ? $payload->peices_charged : 1,
            'weight' => (round($payload->weight_charged) == 0) ? 0.5 : $payload->weight_charged,
            'service_code' => $service_code,
            'fragile_require' => $fragile,
            'shipper_refrence' => $payload->shipment_referance,
            'insurance_require' => $insurance,
            'insurance_value' => $insurance_value,
            'collection_required' => ($payload->payment_method_id == 1) ? 'Y' : 'N',
            'shipper_comment' => $payload->shipper_comment,
            'payment_method_id' => $payload->payment_method_id,
            'courier_mapping_id' => $payload->courier_mapping_id,
        );
    }
    switch ($courier_id) {
        case '1':
            $responseData = json_decode(CNGenerationBlueEx($data_arr));
            break;
        case '2':
            $responseData = json_decode(CNGenerationTCS($data_arr));
            break;
        case '3':
            $responseData = json_decode(CNGenerationLeopards($data_arr));
            break;
        case '4':
            $responseData = json_decode(CNGenerationMNP($data_arr));
            break;
        case '22':
            $params[] = [
                "shipperId" => 0,
                "subAccountId" => 0,
                "totalWeight" => $payload->weight_charged,
                "totalVolumeWeight" => $payload->weight_charged,
                "shipperReferenceNo" => "$payload->consignment_no",
                "consignee" => "$payload->consignee_name",
                "consigneeAddress" => "$payload->consignee_address",
                "consigneeCountry" => "$payload->country_name",
                "consigneeCity" => "$destination_city_name",
                "consigneeArea" => "$destination_city_name",
                "consigneeEmail" => "$payload->consignee_email",
                "consigneeMobile" => "$payload->consignee_phone",
                "consigneeTelePhone" => "$payload->consignee_phone",
                "consigneeLatitude" => "",
                "consigneeLongitude" => "",
                "pieces" => (!empty($payload->peices_charged)) ? $payload->peices_charged : 1,
                "deliveryServiceCode" => "$service_code",
                "valueAmount" => $payload->orignal_order_amt??0.00,
                "cod" => $payload->orignal_order_amt??0.00,
                "codCurrencyCode" => "$payload->orignal_currency_code",
                "content" => "",
                "shipperRemarks" => "$payload->shipper_comment",
                "handlingPack" => true,
                "handlingCold" => true,
                "handlingFragile" => ($payload->fragile_require == 0 ? false : true),
                "consignment_no" => $payload->consignment_no,
                "courier_id" => $courier_id,
                "customer_courier_id" => $customer_courier_id
            ];
            // print_r($params);die;
            $consignment_no_array[] = $payload->consignment_no;
            break;
        case '23':
            // print_r($payload->shipment_referance);die;
            $params[] = [
                "customerRef" => $cn_no,
                "type" => "Delivery",
                "category" => "$service_code",
                "origin" => [
                    "contactName" => "$payload->shipper_name",
                    "contactNo" => "$payload->shipper_phone",
                    "city" => "$origin_city_code",
                    "country" => "$origin_country_code",
                    "address" => "$payload->shipper_address",
                    "email" => "$payload->shipper_email",
                    "coordinates" => [
                        "latitude" => 0,
                        "longitude" => 0
                    ]
                ],
                "destination" => [
                    "contactName" => "$payload->consignee_name",
                    "contactNo" => "$payload->consignee_phone",
                    "city" => "$destination_city_code",
                    "country" => "$destination_country_code",
                    "address" => "$payload->consignee_address",
                    "email" => "$payload->consignee_email",
                    "type" => "Doorstep",
                    "coordinates" => [
                        "latitude" => 0,
                        "longitude" => 0
                    ]
                ],
                "packages" => [
                    [
                        "name" => "$payload->consignee_name",
                        "customerRef" => "$cn_no",
                        "description" => "$payload->parcel_detail",
                        "goodsValue" => (int) $payload->order_amount,
                        "volumetricWeight" => (int) $payload->weight_charged,
                        "quantity" => (int) $payload->peices_charged,
                        "itemsCode" => "sku"
                    ]
                ]
            ];

            $consignment_no_array[] = $payload->consignment_no;
            break;
    }
}

if (count($params) > 0) {
    switch ($courier_id) {
        case '22':
            $data['skybills'] = $params;
            $data['printType'] = 3;
            $responseData = json_decode(CNGenerationTFM($data, $customer_courier_id));
            // $responseData = CNGenerationTFM($data, $customer_courier_id);
            if (count($responseData->payload->success) > 0) {
                foreach ($responseData->payload->success as $detail) {
                    $success_arr[] = [
                        "tpl_consignment_no" => $detail->tpl_consignment_no,
                        "reference" => $detail->consignment_no,
                        "message" => 'TPL Created'
                    ];
                }
            }
            if (count($responseData->payload->error) > 0) {
                foreach ($responseData->payload->error as $detail) {
                    if(isset($detail->reference) && $detail->reference != ""){
                        $ref = $detail->reference;
                    }else{
                        preg_match('/Skybills\[(\d+)\]\./', $detail->feild, $matches);
                        $index = $matches[1];
                        $ref = $consignment_no_array[$index];
                    }
                    $response_arr[] = [
                        'reference' => $ref,
                        "field" => preg_replace('/Skybills\[\d+\]\./', '', $detail->feild),
                        "message" => $detail->message
                    ];
                }
            }
            break;
        case '23':
            $responseData = json_decode(CNGenerationSHIPA($params, $customer_courier_id));
            if (count($responseData->payload->error) > 0) {
                $error_data = $responseData->payload->error;
                $response_arr = array_merge($response_arr, array_map(function ($item) {
                    return (object) $item;
                }, $error_data));
                // $response_arr = array_merge($response_arr, array_map(fn($item) => (object) $item, $error_data));
            }
            if (count($responseData->payload->success) > 0) {
                foreach ($responseData->payload->success as $detail) {
                    $success_arr[] = [
                        "tpl_consignment_no" => $detail->tpl_consignment_no,
                        "reference" => $detail->consignment_no,
                        "message" => 'TPL Created'
                    ];
                }
            }
            break;
    }
}
if(count($success_arr) > 0  &&  count($response_arr) == 0){
    echo response("1", "TPL has been created successfully ", ['success' => $success_arr, 'error' => []]);
}elseif (count($success_arr) > 0  &&  count($response_arr) > 0) {
    echo response("1", "TPL has been created successfully some shipments has error", ['success' => $success_arr, 'error' => $response_arr]);
} else {
    echo response("0", "Error", ['success' => [], 'error' => $response_arr]);
}

