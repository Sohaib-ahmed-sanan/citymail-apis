<?php
include "../index.php";
$registerSchema = json_decode(file_get_contents('../schema/demanifists/get.json'));
$request = json_decode(file_get_contents('php://input'));
$valid = json_decode(requestvalidateobject($request, $registerSchema));
include "../functions/siteFunctions.php";
$has_key = authorization();
if ($has_key) {
    $valid_key = authantication($dbobjx);
    if ($valid_key === true) {
        if ($valid->status) {
            $cn_number = isset($request->cn_number) ? $request->cn_number : '';
            $company_id = isset($request->company_id) ? $request->company_id : '';
            $seal_no = isset($request->seal_no) ? $request->seal_no : '';
            try {
                $query = "SELECT `id` FROM `manifists` WHERE `seal_no` = '$seal_no' AND `is_deleted` = 'N' AND `company_id` = '$company_id'";
                $dbobjx->query($query);
                $result = $dbobjx->single();
                if ($dbobjx->rowCount() > 0) {
                    $more = "";
                    $if_exist = '0';
                    if($cn_number != '')
                    {
                        $check_if_exist = "SELECT COUNT(id) as count FROM `de_manifist_details` WHERE `consignment_no` = '$cn_number' AND `company_id` = '$company_id'";
                        $dbobjx->query($check_if_exist);
                        $res = $dbobjx->single();
                        $if_exist = $res->count;
                        $more = "AND manifist_details.consignment_no = '$cn_number'";
                    }
                    if ($if_exist == 0) {
                        $id = $result->id;
                        $query = "SELECT manifist_details.*,pl.name As shipper_name,cities.city As destination_city,customer.name As customer_name,
                        shipment.consignee_name As consignee_name,shipment.shipment_referance As ref,shipment.total_charges AS cod_amt,mani.batch_name
                        FROM `manifist_details`
                        LEFT JOIN manifists As mani On mani.id = manifist_details.manifist_id 
                        LEFT JOIN shipments As shipment On manifist_details.consignment_no = shipment.consignment_no 
                        LEFT JOIN customers As customer On shipment.customer_acno = customer.acno 
                        LEFT JOIN `cities` ON shipment.destination_city_id = `cities`.`id`
                        LEFT JOIN `pickup_locations` As pl ON shipment.pickup_location_id = pl.`id`
                        WHERE manifist_details.manifist_id = '$id' $more";
                        $dbobjx->query($query);
                        $data = $dbobjx->resultset();
                        if ($dbobjx->rowCount() > 0) {
                            echo response("1", "Success", $data);
                        } else {
                            echo response("0", "Error", "Consignment number does not exist in this manifist");
                        }
                    } else {
                        echo response("0", "Error", "DeManifist already exist against this consignment no.");
                    }
                } else {
                    echo response("0", "Error", "Invalid Seal No");
                }
            } catch (Exception $e) {
                echo response("0", "Api Error !", $e);
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