<?php
include "../index.php";
$registerSchema = json_decode(file_get_contents('../schema/consignment_void/cn_void.json'));
$request = json_decode(file_get_contents('php://input'));
$valid = json_decode(schemaValidator($request, $registerSchema));
include "../functions/siteFunctions.php";
$has_key = authorization();
if ($has_key == 200) {
    $valid_key = authantication($dbobjx);
    if ($valid_key === true) {
        if ($valid->status) {
            // if ($valid) {
            try {
                $check = false;
                $updated_by = $headers['Client-Id'];
                $consignments = $request->consignment_no ?? '';
                // print_r();die;
                if (count($consignments) > 0) {
                    $consignments_str = implode(',', $consignments);
                    $query = "SELECT `consignment_no` FROM `delivery_sheet_details` WHERE `consignment_no` IN($consignments_str)";
                    $dbobjx->query($query);
                    $result = $dbobjx->resultset();
                    $no_allowed_cn = [];
                    foreach ($result as $key => $data) {
                        $no_allowed_cn[] = $data->consignment_no;
                    }
                    $allowed_cn = array_diff($consignments, $no_allowed_cn);
                    $allowed_str = implode(',', $allowed_cn);
                    $query = "SELECT `courier_id`,`account_id`,`thirdparty_consignment_no`,`consignment_no` FROM `shipments` WHERE consignment_no In($allowed_str) AND `status` NOT IN (2, 14, 13, 24, 9)";
                    $dbobjx->query($query);
                    $result = $dbobjx->resultset();
                    $local_shipment = [];
                    $tpl_shipment = [];
                    foreach ($result as $data) {
                        if ($data->thirdparty_consignment_no == '') {
                            $local_shipment[] = $data->consignment_no;
                        } else {
                            $tpl_shipment[] = [
                                "courier_id" => $data->courier_id,
                                "account_id" => $data->account_id,
                                "tpl_consigment_no" => $data->thirdparty_consignment_no,
                                "consignment_no" => $data->consignment_no,
                            ];
                        }
                    }
                    if (count($local_shipment) > 0) {
                        $cn_string = implode(',', $local_shipment);
                        $update = "UPDATE `shipments` SET `status`,`updated_by`='$updated_by',`updated_at`= CURRENT_TIMESTAMP() = 2 WHERE `consignment_no` In($cn_string)";
                        $dbobjx->query($update);
                        $dbobjx->execute();
                        foreach ($local_shipment as $cn_no) {
                            track_status($dbobjx, $cn_no, 2);
                        }
                        $check = true;
                    }
                    if (count($tpl_shipment) > 0) {
                        $res = getAPIdata(API_URL . 'tpl/apply_void', ['void_data' => $tpl_shipment]);
                        if ($res->status == 1) {
                            $shipments = array_column($tpl_shipment, 'consignment_no');
                            $voided_shipments = implode(',', $shipments);
                            $update = "UPDATE `shipments` SET `status` = 2,`updated_by`='$updated_by',`updated_at`= CURRENT_TIMESTAMP() WHERE `consignment_no` In($voided_shipments)";
                            $dbobjx->query($update);
                            $dbobjx->execute();
                            foreach ($shipments as $cn_no) {
                                track_status($dbobjx, $cn_no, 2);
                            }
                            $check = true;
                        }
                    }
                    $dbobjx->close();
                    if ($check == true) {
                        echo response("1", "success", "shipment" . (count($consignments) > 1 ? 's are' : ' is') . " canceled");
                    } else {
                        echo response("0", "Error", "Consignment number cant be cancled");
                    }
                }else {
                    echo response("0", "Error", "Enter valid consignments");
                }
            } catch (Exception $error) {
                echo response("0", "Api Error!", $error->getMessage());
            }
        } else {
            echo response("0", "Error !", $valid->error);
        }
    } else {
        if ($valid_key === 401) {
            echo response("0", "Invalid Secret Key", "Secret key is incorect");
        } elseif ($valid_key === 404) {
            echo response("0", "Authantication faild", "Client Id is not correct");
        }
    }
} else {
    echo response("0", "Unauthorized", $has_key);
}
