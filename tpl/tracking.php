<?php
include "../index.php";
$request = json_decode(file_get_contents('php://input'));
include "../functions/siteFunctions.php";
try {
    $courier_id = $request->courier_id;
    $account_id = $request->account_id;
    $tpl_cn_number = $request->tpl_cn_number;
    $payload = array(
        'customer_courier_id' => $account_id,
        'consigment_no' => $tpl_cn_number
    );
    $response_arr = [];
    switch ($courier_id) {
        case '1':
            // BX
            $url = 'http://benefitx.blue-ex.com/api/customerportal/multi_tracking.php';
            $request = array(
                'user' => 'tracking.user',
                'password' => '1e34a1a1f2386e28bd4c4bf920cd8653',
                'ShipmentNumbers' => [$cn_number],
            );
            $requestJson = array(
                'request' => json_encode($request)
            );
            $result = json_decode(curlFunction($url, $requestJson));
            if (isset($result)) {
                foreach ($result[0]->cnDetail as $value) {
                    $date = date("Y-m-d", strtotime($value->statusdate));
                    $dateTime = $date . ' ' . $value->statustime;
                    $response_arr[] = array(
                        "dateTime" => date('F jS, Y H:i:s', strtotime($dateTime)),
                        "status" => $value->statusmessage
                    );
                }
            }
            break;

        case '2':
            // TCS
            $api_key = $response->payload->api_key;
            $url = 'https://apis.tcscourier.com/production/track/v1/shipments/detail?consignmentNo=' . $cn_number;
            // dd($url);
            $headers = array(
                "accept: application/json",
                "x-ibm-client-id: $api_key"
            );
            $result = json_decode(curlFunction($url, "", $headers, "", "GET"));
            // dd($result);
            $TrackDetailReply = $result->TrackDetailReply;
            if (isset($TrackDetailReply)) {
                if (!empty($TrackDetailReply->Checkpoints) && is_array($TrackDetailReply->Checkpoints) && (count($TrackDetailReply->Checkpoints)) > 0) {
                    foreach (array_reverse($TrackDetailReply->Checkpoints) as $keys => $trackvalue) {
                        $status = $trackvalue->status;
                        $recievedBy = !empty($trackvalue->recievedBy) ? ' - ' . $trackvalue->recievedBy : '';
                        $date_time = $trackvalue->dateTime;
                        $updated_at = date("Y-m-d H:i:s", strtotime($date_time));
                        $response_arr[] = array(
                            "dateTime" => date('F jS, Y H:i:s', strtotime($updated_at)),
                            "status" => $status . $recievedBy
                        );
                    }
                }
                if (!empty($TrackDetailReply->DeliveryInfo) && is_array($TrackDetailReply->DeliveryInfo) && (count($TrackDetailReply->DeliveryInfo)) > 0) {
                    foreach (array_reverse($TrackDetailReply->DeliveryInfo) as $keys => $trackvalue) {
                        $status = $trackvalue->status;
                        $date_time = $trackvalue->dateTime;
                        $updated_at = date("Y-m-d H:i:s", strtotime($date_time));
                        $response_arr[] = array(
                            "dateTime" => date('F jS, Y H:i:s', strtotime($updated_at)),
                            "status" => $status
                        );
                    }
                } else {
                    $response_arr[] = array(
                        "dateTime" => '',
                        "status" => ''
                    );
                }
            }
            break;
        case '22':
            $token = generateToken($account_id);
            // $token = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJodHRwOi8vc2NoZW1hcy54bWxzb2FwLm9yZy93cy8yMDA1LzA1L2lkZW50aXR5L2NsYWltcy9uYW1laWRlbnRpZmllciI6IjE3NjkiLCJodHRwOi8vc2NoZW1hcy54bWxzb2FwLm9yZy93cy8yMDA1LzA1L2lkZW50aXR5L2NsYWltcy9uYW1lIjoiVVNFUlIxMTk5MiIsIlN1YkFjY291bnRJZCI6IjAiLCJTdWJBY2NvdW50IjoiIiwiU3ViQWNjb3VudHMiOiJbe1wiVGV4dFwiOlwiXCIsXCJWYWx1ZVwiOjB9XSIsImp0aSI6IjM2MDkxZTk5LTZkNmQtNDQxMS05MDg1LThkYzJjYTI5YjcwMSIsImh0dHA6Ly9zY2hlbWFzLm1pY3Jvc29mdC5jb20vd3MvMjAwOC8wNi9pZGVudGl0eS9jbGFpbXMvdXNlcmRhdGEiOiJ7XCJTaGlwcGVySWRcIjoxMTk5MixcIlNoaXBwZXJBY2NvdW50TnVtYmVyXCI6XCJSMTE5OTJcIixcIldlaWdodFR5cGVcIjoxLFwiQ2hhcmdlVHlwZVwiOjEsXCJQaWNrdXBBZGRyZXNzXCI6XCJEZWlyYSBEdWJhaS5VQUVcIixcIlNoaXBwZXJHcm91cElkXCI6MyxcIkRlbGl2ZXJ5U2VydmljZXNcIjpbMTksMjEsMiwxLDEzLDE2LDIzLDI5LDMyXSxcIlBhcmVudEFjY291bnRJZFwiOjAsXCJCdWxrQ29sbGVjdFwiOnRydWUsXCJQcmljaW5nSWRcIjowLFwiSWRcIjoxNzY5LFwiTGVkZ2VySWRcIjoyMTQ4MzUsXCJVc2VyTmFtZVwiOlwiVXNlclIxMTk5MlwiLFwiRW1haWxcIjpcImNpdHltYWlscGFraXN0YW5AZ21haWwuY29tXCIsXCJQaG9uZU51bWJlclwiOlwiMDA0NDc4Njg3NjhcIixcIlBob25lTnVtYmVyQ29uZmlybWVkXCI6dHJ1ZSxcIlR3b0ZhY3RvckVuYWJsZWRcIjp0cnVlLFwiRGF0ZU9mQmlydGhcIjpcIjIwMjMtMDEtMDFUMDA6MDA6MDBcIixcIk5hbWVcIjpcIkNpdHlNYWlsXCIsXCJBZGRyZXNzXCI6XCJEZWlyYSBEdWJhaS5VQUVcIixcIkNvdW50cnlOYW1lXCI6XCJVbml0ZWQgQXJhYiBFbWlyYXRlc1wiLFwiQ291bnRyeUlkXCI6MjMxLFwiU3RhdGVPclByb3ZpbmNlTmFtZVwiOlwiXCIsXCJDaXR5TmFtZVwiOlwiRHViYWlcIixcIkNpdHlJZFwiOjQ3OSxcIk5hdGlvbmFsaXR5TmFtZVwiOlwiXCIsXCJTdGF0aW9uSWRcIjowLFwiU3RhdGlvbkNvZGVcIjpcIlwiLFwiU3RhdGlvbk5hbWVcIjpcIlwiLFwiU3RhdHVzSWRcIjozLFwiRGV2aWNlVG9rZW5cIjpcIlwiLFwiR29vZ2xlTWFwQXBpXCI6e1wiVVJMXCI6XCJcIixcIktleVwiOlwiXCJ9LFwiVG9rZW5cIjp7XCJVc2VySWRcIjowLFwiVG9rZW5cIjpcIlwiLFwiRXhwaXJlc1wiOlwiMDAwMS0wMS0wMVQwMDowMDowMFwiLFwiUmVmcmVzaFRva2VuXCI6XCJcIixcIkV4cGlyZXNVbml4VGltZVN0YW1wXCI6MCxcIlRva2VuVHlwZVwiOlwiXCJ9LFwiQ3VycmVuY3lDb2RlXCI6XCJBRURcIixcIk1hbnVhbEFXQlByZWZpeFwiOlwiOTcxMVwiLFwiSXNSaWRlclwiOmZhbHNlfSIsImV4cCI6MTcyNDQyMzY2NCwiaXNzIjoiaHR0cHM6Ly9jdXN0b21lcmFwaS50Zm1leC5jb20vIiwiYXVkIjoiaHR0cHM6Ly9jdXN0b21lcmFwaS50Zm1leC5jb20vIn0._tSYTLh7E1kyCEnCG4TI9cpq6_7fd3BQoHpOdyG740Q';
            $header = array(
                "Content-Type: application/json",
                "Authorization: Bearer " . $token->token,
            );
            $params = [
                "userId" => $token->userId,
                "awb" => $tpl_cn_number
            ];
            $url = TFM_URL.'api/v1/SkyBill/trackbyawb';
            $result = json_decode(curlFunction($url, json_encode($params, JSON_UNESCAPED_UNICODE), $header));
            if ($result->statusCode == '200' && $result->isError == false) {
                foreach ($result->result as $value) {
                    $response_arr[] = array(
                        "dateTime" => date('F jS, Y H:i:s', strtotime($value->createdOn)),
                        "status" => $value->subStatus
                    );
                }
            }else{
                $response_arr[] = array(
                    "dateTime" => "",
                    "status" => ""
                );
            }
            break;
        case '23':
            $credientials = get_credientials($courier_account_id);
            $url = SHIPA_URL.'orders/'.$tpl_cn_number.'/tracking?apikey='.$credientials->api_key;
            $result = json_decode(curlFunction($url,[],'','','GET'));
            if ($result->statusCode == '200' && $result->isError == false) {
                foreach ($result->result as $value) {
                    $response_arr[] = array(
                        "dateTime" => date('F jS, Y H:i:s', strtotime($value->createdOn)),
                        "status" => $value->subStatus
                    );
                }
            }else{
                $response_arr[] = array(
                    "dateTime" => "",
                    "status" => ""
                );
            }
            break;
    }
    echo response('1', 'success', $response_arr);
} catch (Exception $e) {
    echo response("0", "Api Error !", $e);
}
