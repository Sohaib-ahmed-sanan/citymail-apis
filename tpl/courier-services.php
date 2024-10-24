<?php
include "../index.php";
$request = json_decode(file_get_contents('php://input'));
include "../functions/siteFunctions.php";
try {
    $account_id = $request->account_id;
    $data = ['account_id' => $account_id];

    $result = getAPIdata(API_URL . 'couriers_details/single', $data);
    $courier_id = $result->payload->courier_id;

    // print_r($token);die;
    $payload = array();
    $FetchCredentials = [1, 5, 6, 18];
    if (in_array($courier_id, $FetchCredentials)) {
        $account_no = $result->payload->account_no != '' ? $result->payload->account_no : '';
        $api_key = $result->payload->api_key != '' ? $result->payload->api_key : '';
    }
    switch ($courier_id) {
        case '1':
            $url = 'http://benefitx.blue-ex.com/api/customerportal/services.php?acno=' . $account_no;
            $header = array(
                "Content-Type: application/json"
            );
            $response = curlFunction($url, '', $header);
            $result = json_decode($response);
            if (isset($result->detail) && count($result->detail) > 0) {
                foreach ($result->detail as $service_code) {
                    $payload[] = array(
                        "service_code" => $service_code->service_code,
                        "service_name" => $service_code->service_name
                    );
                }
            }
            break;
        case '2':
            $service_codes = [
                ['service_code' => 'O', 'service_name' => 'Overnight'],
                ['service_code' => 'D', 'service_name' => '2nd Day'],
                ['service_code' => 'S', 'service_name' => 'Same day'],
                ['service_code' => 'OLE', 'service_name' => 'OLE'],
                ['service_code' => 'MYO', 'service_name' => 'MYO']
            ];
            $payload = json_decode(json_encode($service_codes));
            break;
        case '3':
            $service_codes = [
                ['service_code' => 'overnight', 'service_name' => 'Overnight'],
                ['service_code' => 'overland', 'service_name' => 'Overland'],
                ['service_code' => 'detain', 'service_name' => 'Detain'],
            ];
            $payload = json_decode(json_encode($service_codes));
            break;
        case '4':
            $service_codes = [
                ['service_code' => 'O', 'service_name' => 'Overnight'],
                ['service_code' => 'S', 'service_name' => '2nd Day']
            ];
            $payload = json_decode(json_encode($service_codes));
            break;
        case '5':
            $url = 'http://cod.callcourier.com.pk/API/CallCourier/GetServiceType/' . $account_no;

            $response = Http::get($url);
            $result = json_decode($response);
            if ((isset($result)) && (count($result) > 0)) {
                foreach ($result as $service_code) {
                    $payload[] = array(
                        "service_code" => $service_code->ServiceTypeID,
                        "service_name" => $service_code->ServiceType1
                    );
                }
            }
            break;
        case '6':
            $url = 'http://api.withrider.com/rider/v1/GetDeliveryTypes?loginId=' . $account_no . '&ApiKey=' . $api_key;
            $response = Http::get($url);
            $result = json_decode($response);
            if ((isset($result)) && (count($result) > 0)) {
                foreach ($result as $service_code) {
                    $payload[] = array(
                        "service_code" => $service_code->id,
                        "service_name" => $service_code->title
                    );
                }
            }
            break;
        case '7':
            $service_codes = [
                ['service_code' => '1', 'service_name' => 'Regular']
            ];
            $payload = json_decode(json_encode($service_codes));
            break;
        case '12':
            $url = 'https://api.postex.pk/services/partnerintegration/api/lookup/order/types';
            $headers = [
                "Content-type: application/json",
                "token: $api_key"
            ];
            $response = Http::withHeaders($headers)->get($url);
            $result = json_decode($response);
            if ((isset($result)) && ($result->statusCode == '200')) {
                foreach ($result->dist as $service_code) {
                    $payload[] = [
                        "service_code" => $service_code,
                        "service_name" => $service_code
                    ];
                }
            }
            break;
        case '13':
            $service_codes = [
                ['service_code' => 'OE', 'service_name' => 'Overnight Express'],
                ['service_code' => 'OL', 'service_name' => 'Overland']
            ];
            $payload = json_decode(json_encode($service_codes));
            break;
        case '14':
            $service_codes = [
                ['service_code' => 'OE', 'service_name' => 'Overnight'],
                ['service_code' => 'COD', 'service_name' => 'COD']
            ];
            $payload = json_decode(json_encode($service_codes));
            break;
        case '18':
            $url = 'https://api.postex.pk/services/integration/api/order/v1/get-order-types';
            $headers = array(
                "token:" . $api_key
            );
            $response = Http::withHeaders($headers)->get($url);
            $result = json_decode($response);
            if ((isset($result)) && ($result->statusCode == '200')) {
                foreach ($result->dist as $service_code) {
                    $payload[] = array(
                        "service_code" => $service_code,
                        "service_name" => $service_code
                    );
                }
            }
            break;
        case '19':
            $service_codes = [
                ['service_code' => 'OE', 'service_name' => 'Overnight Express'],
                ['service_code' => 'OL', 'service_name' => 'Overland']
            ];
            $payload = json_decode(json_encode($service_codes));
            break;
        case '22': //TFM
            $token = generateToken($account_id);
            $url = 'https://staginggexpshipperapi.azurewebsites.net/api/v1/Lookup/deliveryservice';
            $header = array(
                "Content-Type: application/json",
                "Authorization: Bearer " . $token->token,
            );
            $response = json_decode(curlFunction($url, '', $header));
            if ($response->statusCode == '200') {
                foreach ($response->result as $service) {
                    $payload[] = array(
                        "service_code" => $service->code,
                        "service_name" => $service->name
                    );
                }
            }
            break;
        case '23':
            $service_codes = [
                ['service_code' => 'On Demand', 'service_name' => 'On Demand'],
                ['service_code' => 'Same Day', 'service_name' => 'Same Day'],
                ['service_code' => 'Next Day', 'service_name' => 'Next Day'],
                ['service_code' => 'Cross Border', 'service_name' => 'Cross Border'],
            ];
            $payload = json_decode(json_encode($service_codes));
            break;
        default:
            $payload[] = array(
                "service_code" => 'overnight',
                "service_name" => 'overnight'
            );
            break;
    }

    echo response("1", "Success", $payload);

} catch (Exception $e) {
    echo response("0", "Api Error !", $e);
}