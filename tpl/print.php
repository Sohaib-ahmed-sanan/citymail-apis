<?php
include "../index.php";
$request = json_decode(file_get_contents('php://input'));
include "../functions/siteFunctions.php";
try {
    $courier_id = $request->courier_id;
    $courier_account_id = $request->courier_account_id;
    $cn_number = $request->cn_number;
    $print_type = $request->print_type??"single";
    switch ($courier_id) {
        case '1':
            $path = 'http://benefit.blue-ex.com/customerportal/inc/cnprnb.php?' . $cn_number;
            break;
        case '2':
            $path = 'https://envio.tcscourier.com/BookingReportPDF/GenerateLabels?consingmentNumber=' . $cn_number;
            break;
        case '22':
            $token = generateToken($courier_account_id);
            $header = array(
                "Content-Type: application/json",
                "Authorization: Bearer " . $token->token,
            );
            if($print_type == 'bulk')
            {
                $params = [
                    "awb" => $cn_number,
                    "printMode" => 3
                ];
            }else{
                $params = [
                    "awb" => ["$cn_number"],
                    "printMode" => 3
                ];
            }
            $url = TFM_URL . 'api/v1/SkyBill/printbyawb';
            $result = json_decode(curlFunction($url, json_encode($params, JSON_UNESCAPED_UNICODE), $header));
            if ($result->statusCode == '200') {
                $return = [
                    "type" => 'text',
                    "content" => $result->result
                ];
            }
            break;
        case '23':
            $credientials = get_credientials($courier_account_id);
            $url = SHIPA_URL . 'orders/' . $cn_number . '/pdf?apikey=' . $credientials->api_key . '&mode=stream&template=4x6';
            // $result = curlFunction($url,[],'','','GET');
            $return = [
                "type" => "url",
                "content" => $url
            ];
            break;
    }
    echo response('1', 'succcess', $return);
} catch (Exception $e) {
    echo response("0", "Api Error !", $e);
}
