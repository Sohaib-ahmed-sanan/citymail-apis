<?php
include "../index.php";
$request = json_decode(file_get_contents('php://input'));

$void_arr = isset($request->void_data) ? $request->void_data : '';
$bulk_array = [];
foreach ($void_arr as $key => $data) {
    $courier_id = isset($data->courier_id) ? $data->courier_id : '';
    $account_id = isset($data->account_id) ? $data->account_id : '';
    $tpl_consigment_no = isset($data->tpl_consigment_no) ? $data->tpl_consigment_no : '';
    $payload = array(
        'customer_courier_id' => $account_id,
        'consigment_no' => $tpl_consigment_no,
    );

    switch ($courier_id) {
        case '1':
            $responseData = json_decode(CancelBlueExCN($payload));
            break;
        case '2':
            $responseData = json_decode(CancelTCSCN($payload));
            break;
        case '3':
            $responseData = json_decode(CancelLeopardsCN($payload));
            break;
        case '22':
            if (!isset($bulk_array[$account_id]['tpl_consignments'])) {
                $bulk_array[$account_id]['tpl_consignments'] = [];
            }
            $bulk_array[$account_id]['tpl_consignments'][] = $tpl_consigment_no;
            break;
        case '23':
            $responseData = json_decode(CancelSHIPA($payload));
            break;
            
    }
}

if(count($bulk_array) > 0)
{ 
    $responseData = json_decode(CancelTFM($bulk_array));
  
}
echo json_encode(["status" => $responseData->status, "payload" => $responseData->message,"message" => ""]);