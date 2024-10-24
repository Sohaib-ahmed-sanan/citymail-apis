<?php
try {
    ini_set('max_execution_time', 0);
    set_time_limit(0);
    ini_set('memory_limit', -1);
    include("../../index.php");
    $request = json_decode(file_get_contents('php://input'));
    $where = "";
    $courier_id = 23;
    $allow_limit = 20;
    $statuses = [
        3,      //Cancel
        17,     //Deleted
        14,     //Delivered
        15,     //Ready to Return
        16      //Return to Shipper
    ];
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
        $query = "SELECT s.id,s.customer_acno,s.consignment_no,s.thirdparty_consignment_no,s.status,s.created_at,statues.name as status,
            s.courier_id,s.account_id,cc.user,cc.password,cc.api_key 
            FROM shipments s
                LEFT JOIN delivery_status As statues ON statues.id = s.status 
                LEFT JOIN courier_details cc ON cc.id = s.account_id AND cc.courier_id=s.courier_id
                WHERE status NOT IN ($non_applied_status)
                AND s.courier_id = '$courier_id' $where
                GROUP BY s.id ORDER BY rand() $limit";
        $dbobjx->query($query);
        $result = $dbobjx->resultset();
        // output 
        // [id] => 9
        // [customer_acno] => 1000003
        // [consignment_no] => 111000878
        // [status] => Arrival
        // [created_at] => 2024-08-19 15:32:47
        // [courier_id] => 22
        // [account_id] => 3
        // [user] => apitest2798
        // [password] => Z;,tW+-OMbOAr5q
        // [api_key] => 
        if ($dbobjx->rowCount() > 0) {
            $success = 0;
            foreach ($result as $row) {
                $status_id = $status_code = '';
                $credientials = get_credientials($row->account_id);
                $url = SHIPA_URL.'orders/story?apikey=Glg6OvkaGUyI9TwUGBG5cDe77qzkHnZT&ref=SBSD0468663';
                $response = json_decode(curlFunction($url,['type'=>'track'],'','','GET'));
                print_r($response);die;
                if (count($response) > 1) {
                    if (isset($response->result) && count($response->result) > 0 && !empty($response->result)) {
                        $shipment_id = $row->id;
                        $element = end($response->result);
                        $message = trim($element->comment);
                        $consignment_no = $element->tracking_no;
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
                                $status_id = 8; //On hold
                                break;
                            default:
                                $status_id = '';
                                break;
                        }
                        if ($status_id != '') {
                            $test = "";
                            $query = "SELECT * FROM orderstatus_tracking WHERE `consignment_no`='$row->consignment_no'";
                            $dbobjx->query($query);
                            $get_track = $dbobjx->single();
                            if ($get_track != "") {
                                $query = "INSERT INTO `orderstatus_tracking`(`consignment_no`, `status_id`, `last_status_id`, `tpl_message`, `created_at`) VALUES('$row->consignment_no','$status_id','$get_track->status_id','$message',CURRENT_TIMESTAMP())";
                                $dbobjx->query($query);
                                $dbobjx->execute();
                                $query = "UPDATE shipments SET `status`='$status_id',updated_at = NOW() WHERE `id`='$shipment_id'";
                                $dbobjx->query($query);
                                $dbobjx->execute();
                                $success = 1;
                            }
                        }
                    }
                }else{
                    echo response("0", "TFM API ERROR", $response->title);
                    exit();
                }
            }
            if ($success == 1) {
                $apply_limit = (($apply_limit + $allow_limit) > $total_orders) ? 0 : $apply_limit + $allow_limit;
                $query = "INSERT INTO cron_jobs(courier_id,allow_limit,total_orders,apply_limit,created_at) VALUES ('$courier_id','$allow_limit','$total_orders','$apply_limit',NOW())";
                $dbobjx->query($query);
                $dbobjx->execute();
            }
            $return = ($success) ? 'Status Updated Successfully!' : 'No Status Found';
            echo response("1", "SUCCESFULLY OPERATED", [$return]);
        } else {
            echo response("0", "No Record Found!", []);
        }
    } else {
        echo response("0", "No Record Found!", []);
    }
    $dbobjx->close();
} catch (Exception $error) {
    echo response("0", "Api Error!", $error->getMessage());
}
