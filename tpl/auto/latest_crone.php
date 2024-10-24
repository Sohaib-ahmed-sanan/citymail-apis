<?php
try {
    ini_set('max_execution_time', 0);
    set_time_limit(0);
    ini_set('memory_limit', -1);
    include("../../index.php");
    $request = json_decode(file_get_contents('php://input'));
    // $couriers = [
    //     1, // BLX
    //     2, // TCS
    //     3, // Leopards
    //     4, // MNP
    //     5, // CALLCOURIER
    //     6, // RIDER
    //     7, // TRAX
    //     13, // FLYCOURIER
    //     18, // POSTEX
    //     21, // DAEWOOEXPRESS
    //     22 // TQS
    // ];
    $couriers = [
        22, // TFM
    ];
    $courier_id = $couriers[array_rand($couriers)];
    $where = "";
    switch ($courier_id) {
        case '22': // TFM
            $allow_limit = 20;
            break;
        case '23': // Shipa
            $allow_limit = 20;
            break;
        default:
            break;
    }
    $statuses = [
        9,      //Cancel
        13,     //Deleted
        28,     //Delivered
        29      //Return to Shipper
    ];
    $queryBridge = [];
    $non_applied_status = implode(',', $statuses);
    if (isset($request->start_date) && isset($request->end_date)) {
        $where .= "AND s.created_at BETWEEN '$request->start_date' AND '$request->end_date'";
    }
    if (isset($request->acno)) {
        $where .= "AND s.customer_acno = '$request->acno'";
    }
    if (isset($request->consignment_no)) {
        $where .= "AND s.consignment_no = '$request->consignment_no'";
    }
    $query = "SET sql_mode=''";
    $dbobjx->query($query);
    $dbobjx->execute($query);
    $query = "SELECT count(id) as total_orders FROM shipments s WHERE `status` NOT IN ($non_applied_status)  AND s.courier_id = '$courier_id'";
    $dbobjx->query($query);
    $result = $dbobjx->single();
    if ($dbobjx->rowCount() > 0) {
        $total_orders = $result->total_orders;
        $query = "SELECT `apply_limit` FROM cron_jobs WHERE courier_id='$courier_id' ORDER BY id DESC limit 0,1";
        $dbobjx->query($query);
        $data = $dbobjx->single();
        $apply_limit = $data->apply_limit;
        if ($apply_limit == '') {
            $apply_limit = 0;
            $query = "INSERT INTO cron_jobs(courier_id,allow_limit,total_orders,apply_limit,created_at) VALUES ('$courier_id','$allow_limit','$total_orders','$apply_limit',NOW())";
            $dbobjx->query($query);
            $dbobjx->execute();
        }
        $apply_limit = ($total_orders < $apply_limit) ? 0 : $apply_limit;
        $limit = (isset($request->consignment_no)) ? "" : "limit $apply_limit,$allow_limit";
        $query = "SELECT s.id,s.customer_acno,s.consignment_no,s.thirdparty_consignment_no,s.status as status_id,s.created_at,statues.name as status,
            s.courier_id,s.account_id,cc.user,cc.password,cc.api_key 
            FROM shipments s
                LEFT JOIN delivery_status As statues ON statues.id = s.status 
                LEFT JOIN courier_details cc ON cc.id = s.account_id AND cc.courier_id=s.courier_id
                WHERE status NOT IN ($non_applied_status)
                AND s.courier_id = '$courier_id' $where
                GROUP BY s.id ORDER BY rand() $limit";
        $dbobjx->query($query);
        $result = $dbobjx->resultset();
        if ($dbobjx->rowCount() > 0) {
            //=====================================Process Query==========================================
            function processQuery($shipment_id, $consignment_no, $status_id, $message, $last_status_id, &$queryBridge)
            {
                if ($status_id != '') {
                    $queryBridge["insertOrderStatus"][] = "('$consignment_no','$status_id','$last_status_id','$message',NOW())";
                    $queryBridge["updateShipmentTabel"][] = "WHEN `id` = '$shipment_id' THEN '$status_id'";
                    $queryBridge["shipment_ids"][] = $shipment_id;
                }
            }
            //=====================================Process Query==========================================

            //======================================3PL Status Mapped=====================================

            switch ($courier_id) {
                case '22':
                    foreach ($result as $row) {
                        $last_status_id = $status_id = $message = $status_code = '';
                        $token = generateToken($row->account_id, $row->user, $row->password);
                        $header = array(
                            "Content-Type: application/json",
                            "Authorization: Bearer " . $token->token,
                        );
                        // $url = TFM_URL . 'api/v1/SkyBill/trackbyawb';
                        $url = 'https://customerapi.tfmex.com/api/v1/SkyBill/trackbyawb';
                        $params = [
                            "userId" => $token->userId,
                            "awb" => $row->thirdparty_consignment_no
                        ];
                        $response = json_decode(curlFunction($url, json_encode($params), $header));
                        if ($response->statusCode == 200) {
                            if (isset($response->result) && count($response->result) > 0 && !empty($response->result)) {
                                $shipment_id = $row->id;
                                $last_status_id = $row->status_id;
                                $element = end($response->result);
                                $message = trim($element->comment);
                                $consignment_no = $row->consignment_no;
                                $status_code = trim($element->statusCode);
                                $last_status_date = date("Y-m-d H:i:s", strtotime($element->createdOn));
                            }
                            if ((isset($status_code)) && (!empty($status_code))) {
                                switch ($status_code) {
                                    case 'EXCPA01':
                                        $status_id = 4; //Accepted - Arrival
                                        break;
                                    case 'EXCDRC01':
                                    case 'EXCOFD01':
                                        $status_id = 24; //Out for Delivery - In Transit
                                        break;
                                    case 'EXC29B':
                                    case 'EXC29A':
                                    case 'EXC29C':
                                        $status_id = 11; //Customer Not Answering Phone
                                        break;
                                    case 'EXCPOD01':
                                        $status_id = 14; //Delivered
                                        break;
                                    case 'EXC21R':
                                    case 'EXC21B':
                                    case 'EXCOFR01':
                                    case 'EXC21D':
                                    case 'EXC21A':
                                        $status_id = 16; //Return to Shippers
                                        break;
                                    case 'EXC12B':
                                    case 'EXC12H':
                                    case 'EXC12K':
                                    case 'EXC12F':
                                    case 'EXC12G':
                                    case 'EXC12D':
                                    case 'EXC12E':
                                    case 'EXC12A':
                                        $status_id = 15; //Ready for Return
                                        break;
                                    case 'EXC99A':
                                    case 'EXC99C':
                                    case 'EXC99B':
                                    case 'EXCCH02':
                                    case 'EXCSR02':
                                    case 'EXC99D':
                                        $status_id = 9; //IN TRANSIT
                                        break;
                                    case 'EXC02HCR':
                                    case 'EXC02HPH':
                                    case 'EXC02HW':
                                    case 'EXC02HWT':
                                    case 'EXC02HTR':
                                    case 'EXC02HD':
                                    case 'EXC02HPM':
                                    case 'EXC02HIDa':
                                    case 'EXC02HDR':
                                    case 'EXCR':
                                        $status_id = 8; //On hold
                                        break;
                                    case 'EXC03A':
                                        $status_id = 5; //incomplete Address
                                        break;
                                    default:
                                        $status_id = '';
                                        break;
                                }
                                processQuery($shipment_id, $consignment_no, $status_id, $message, $last_status_id, $queryBridge);
                            }
                        } else {
                            echo $responce;
                        }
                    }
                    break;
                default:
                    break;
            }
            //======================================3PL Status Mapped=====================================
            if (count($queryBridge) > 0 && !empty($queryBridge) && count($queryBridge["shipment_ids"]) > 0 && !empty($queryBridge["shipment_ids"])) {
            //     //===========================Check Already Exists Courier Status===============================
            //     $query = "SELECT `order_id`, SUM(CASE " . implode(' ', $queryBridge["selectOrderTrackingHistory"]) . " ELSE 0 END) AS `row_count`
            //         FROM `order_tracking_history`
            //             WHERE `order_id` IN (" . implode(',', $queryBridge["orderIds"]) . ")
            //         HAVING `row_count` > 1";
            //     $dbobjx->query($query);
            //     $excludedOrders = $dbobjx->resultset();
            //     if (count($excludedOrders) > 0 && !empty($excludedOrders)) {
            //         $excludedOrderIds = array_column($excludedOrders, 'order_id');
            //         $queryBridge["insertTrackingHistory"] = array_filter($queryBridge["insertTrackingHistory"], function ($entry) use ($excludedOrderIds) {
            //             return !in_array(explode("','", trim($entry, "'("))[0], $excludedOrderIds);
            //         });
            //         $queryBridge["insertOrderStatus"] = array_filter($queryBridge["insertOrderStatus"], function ($entry) use ($excludedOrderIds) {
            //             return !in_array(explode("','", trim($entry, "'("))[0], $excludedOrderIds);
            //         });
            //         $queryBridge["updateOrderMaster"] = array_filter($queryBridge["updateOrderMaster"], function ($entry) use ($excludedOrderIds) {
            //             return !in_array(explode("'", (explode("WHEN `id` = '", $entry)[1]))[0], $excludedOrderIds);
            //         });
            //     }
            //     //===========================Check Already Exists Courier Status===============================
            //     //====================================Courier Status Mapped================================
                if (count($queryBridge["insertOrderStatus"]) > 0 && !empty($queryBridge["insertOrderStatus"])) {
                    $query = "INSERT INTO `orderstatus_tracking` (`consignment_no`, `status_id`, `last_status_id`, `tpl_message`, `created_at`) VALUES " . implode(',', $queryBridge["insertOrderStatus"]);
                    $dbobjx->query($query);
                    $dbobjx->execute();
                }
                if (count($queryBridge["updateShipmentTabel"]) > 0 && !empty($queryBridge["updateShipmentTabel"])) {
                    $query = "UPDATE `shipments` SET `status` = CASE " . implode(' ', $queryBridge["updateShipmentTabel"]) . " ELSE `status` END, `updated_at` = NOW() WHERE `id` IN (" . implode(',', $queryBridge["shipment_ids"]) . ")";
                    $dbobjx->query($query);
                    $dbobjx->execute();
                }
            //     //====================================Courier Status Mapped================================
            //     //=======================================Cron Entry======================================
                $apply_limit = (($apply_limit + $allow_limit) > $total_orders) ? 0 : $apply_limit + $allow_limit;
                $query = "INSERT INTO cron_jobs(courier_id,allow_limit,total_orders,apply_limit,created_at) VALUES ('$courier_id','$allow_limit','$total_orders','$apply_limit',NOW())";
                $dbobjx->query($query);
                $dbobjx->execute();
            //     //=======================================Cron Entry======================================
   
            }
            $return = (isset($queryBridge["shipment_ids"]) && count($queryBridge["shipment_ids"]) > 0 && !empty($queryBridge["shipment_ids"])) ? 'STATUS UPDATED SUCCESSFULLY' : 'NO STATUS FOUND!';
            echo response("1", "SUCCESSFULLY OPERATED", [$return]);
        } else {
            echo response("0", "NO DATA!", []);
        }
    } else {
        echo response("0", "NO DATA!", []);
    }
    $dbobjx->close();
} catch (Exception $error) {
    echo response("0", "API ERROR! UNPROCESSABLE ENTITY", $error->getMessage());
}
#code end
#mtech