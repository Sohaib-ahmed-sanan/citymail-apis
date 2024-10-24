<?php
include "../index.php";
// $registerSchema = json_decode(file_get_contents('../schema/shipments/add.json'));
$request = json_decode(file_get_contents('php://input'));
// $valid = json_decode(schemaValidator($request, $registerSchema));
include "../functions/siteFunctions.php";
// if ($valid->status) {
    try {
        $success = array();
        $error = array();

        $company_id = isset($request->company_id) ? $request->company_id : '';
        $customer_acno = isset($request->customer_acno) ? $request->customer_acno : 0;
        $flag = isset($request->flag) ? $request->flag : 'Bulk Uploading';
        $device = isset($request->device) ? $request->device : '';
        $shipments = isset($request->shipments) ? $request->shipments : '';
        $shipment_count = count($request->shipments);
        // variables to check the validaty of the data
        $service_codes = [];
        $unique_refs = [];
        // print_r($request->company_id);die;
        $existing_db = [];
        $invalid_pickup_ref = [];

        $city_check = 1;
        $picup_check = 1;
        $check_service = 1;
        $origin_city = '';


        $shipment_refs = array_column($shipments, 'shipment_ref');

        foreach ($shipment_refs as $shipment_ref) {
            if (empty($shipment_ref)) {
                $error[] = [
                    "status" => '0',
                    "message" => 'shipment ref not found',
                    "shipment_ref" => $shipment_ref,
                    "consignment_no" => ''
                ];
            } else {
                if (in_array($shipment_ref, $unique_refs)) {
                    $error[] = [
                        "status" => '0',
                        "message" => 'shipment ref is duplicate',
                        "shipment_ref" => $shipment_ref,
                        "consignment_no" => ''
                    ];
                } else {
                    $unique_refs[] = $shipment_ref;
                }
            }
        }

        $unique_ref_str = "'" . implode("','", $unique_refs) . "'";
        $query = "SELECT shipment_referance FROM `shipments` WHERE customer_acno = '$customer_acno' AND shipment_referance IN($unique_ref_str)";
        $dbobjx->query($query);
        $duplicate_referances = $dbobjx->resultset();

        foreach ($duplicate_referances as $ref) {
            $error[] = [
                "status" => '0',
                "message" => 'shipment ref is duplicate',
                "shipment_ref" => $ref->shipment_referance,
                "consignment_no" => ''
            ];
            $existing_db[] = $ref->shipment_referance;
        }
        $allowed_refs = array_diff($unique_refs, $existing_db);
        if (count($allowed_refs) == 0) {
            echo response("0", "Error", [
                "success" => $success,
                "error" => $error
            ]);
            exit();
        }
        $filtered_shipments = array_filter($shipments, function ($shipment) use ($allowed_refs) {
            return in_array($shipment->shipment_ref, $allowed_refs);
        });

        $pickup_locations_array = array_column($filtered_shipments, 'pickup_location');
        $pickup_locations_str = implode(',', $pickup_locations_array);

        $query = "SELECT id FROM pickup_locations WHERE customer_acno = '$customer_acno' AND id IN ($pickup_locations_str)";

        $dbobjx->query($query);
        $result = $dbobjx->resultset();
        $found_pickups = array_column($result, 'id');
        $missing_pickups = array_diff($pickup_locations_array, $found_pickups);

        if (count($missing_pickups) > 0) {
            foreach ($missing_pickups as $missing_location) {
                foreach ($filtered_shipments as $shipment) {
                    if ($shipment->pickup_location == $missing_location) {
                        $shipment_ref = $shipment->shipment_ref;
                        $invalid_pickup_ref[] = $shipment->shipment_ref;
                        break;
                    }
                }

                $error[] = [
                    "status" => '0',
                    "shipment_ref" => $shipment_ref,
                    "message" => 'pickup not found',
                    "consignment_no" => ''
                ];
            }
        }
        if (count($found_pickups) == 0) {
            echo response("0", "Error", [
                "success" => $success,
                "error" => $error
            ]);
            exit();
        }
        // Re-filter the $filtered_shipments array to exclude invalid shipments
        $filtered_shipments = array_filter($filtered_shipments, function ($shipment) use ($invalid_pickup_ref) {
            return !in_array($shipment->shipment_ref, $invalid_pickup_ref);
        });


        switch ($flag) {
            case 'Bulk Uploading';
                $get_service = getAPIdata(API_URL . 'common/getServices.php', ['customer_acno' => $customer_acno, 'type' => 'add_shipment']);
                $service_codes = (array) $get_service->payload;
                break;
            default:
                break;
        }
        foreach ($filtered_shipments as $key => $data) {
            $service_id = '';
            $shipment_ref = '';
            $name = isset($data->name) ? $data->name : '';
            $email = isset($data->email) ? $data->email : '';
            $phone = isset($data->phone) ? $data->phone : '';
            $address = isset($data->address) ? $data->address : '';
            $destination_country = isset($data->destination_country) ? $data->destination_country : '';
            $destination_city = isset($data->destination_city) ? $data->destination_city : '';
            $pickup_location = isset($data->pickup_location) ? $data->pickup_location : '';
            $product_detail = isset($data->product_detail) ? $data->product_detail : '';
            $amount = isset($data->order_amount) && $data->order_amount != '' ? $data->order_amount : 0;
            $weight = isset($data->weight) ? $data->weight : '';
            $peices = isset($data->peices) ? $data->peices : '';
            $comments = isset($data->comments) ? $data->comments : '';
            $service_id = isset($data->service_id) ? $data->service_id : '';
            $fragile = isset($data->fragile) ? $data->fragile : '';
            $insurance = isset($data->insurance) ? $data->insurance : '';
            $insurance_amt = isset($data->insurance_amt) ? $data->insurance_amt : 0.00;
            $shipment_ref = isset($data->shipment_ref) ? $data->shipment_ref : '';
            $payment_method_id = isset($data->payment_method_id) ? $data->payment_method_id : '';
            $currency_code = isset($data->currency_code) ? $data->currency_code : '';

            if ($payment_method_id == 'COD') {
                $payment_method_id = 1;
            }
            if ($payment_method_id == 'CC') {
                $payment_method_id = 2;
            }
            if ($fragile == 'yes' || $fragile == 'YES') {
                $fragile = 1;
            }
            if ($insurance == 'yes' || $insurance == 'YES') {
                $insurance = 1;
            }
            if ($fragile == 'no' || $fragile == 'NO') {
                $fragile = 0;
            }
            if ($insurance == 'NO' || $insurance == 'no') {
                $insurance = 0;
            }

            switch ($flag) {
                case 'Pushed Bulk':
                    if ($destination_country == '') {
                        $get_country = getAPIdata(API_URL . 'common/getCities.php', ['city_id' => $destination_city]);
                        $destination_country = $get_country->payload->country_id;
                    }
                    break;
                case 'Bulk Uploading';
                    $get_city = getAPIdata(API_URL . 'common/getCities.php', ['city_name' => $destination_city]);
                    // print_r($get_city);die;
                    $check_service = false;
                    $service_id = $service_codes[$service_id];
                    if ($service_id != "") {
                        $check_service = true;
                    }

                    $city_check = $get_city->status;
                    $destination_city = $get_city->payload->id;
                    $destination_country = $get_city->payload->country_id;
                    break;
                default:
                    break;
            }
            if ($weight !== '' && $peices != '') {
                if ($city_check == 1) {  // to check if destination city is found or not
                    if ($check_service) { // to check that service is available or not 
                        $orignal_amt = $amount;
                        $converted_currency = convertCurrency($amount, $currency_code, $company_id);
                        $cn = "INSERT INTO `series`(`cn_number`) VALUES (null)";
                        $dbobjx->query($cn);
                        if ($dbobjx->execute()) {
                            $cn_no = $dbobjx->lastInsertId();
                            $query = "INSERT INTO `shipments`(`company_id`, `consignment_no`, `customer_acno`,`courier_id`, `account_id`, `courier_mapping_id`, `consignee_name`, `consignee_email`,`consignee_phone`, `pickup_location_id`, `consignee_address`,`destination_country`,`destination_city_id`,`shipment_referance`,`parcel_detail`, `order_amount`,`orignal_order_amt`,`currency_code`,`orignal_currency_code`,`peices`, `weight`,`shipper_comment`,`fragile`,`insurance`,`insurance_amt`,`payment_method_id`,`service_id`, `device`, `flag`,`status`)
                                    VALUES ('$company_id','$cn_no','$customer_acno',0,0,0,'$name','$email','$phone','$pickup_location','$address','$destination_country',$destination_city,'$shipment_ref','$product_detail','$converted_currency->converted_amt',$orignal_amt,'$converted_currency->converted_to','$currency_code','$peices','$weight','$comments','$fragile','$insurance','$insurance_amt','$payment_method_id','$service_id','$device','$flag',1)";
                            $dbobjx->query($query);
                            $dbobjx->execute();
                            track_status($dbobjx, $cn_no, 1);
                            $success[] = [
                                "status" => '1',
                                "message" => 'Booking has been created',
                                "shipment_ref" => $shipment_ref,
                                "consignment_no" => $cn_no
                            ];
                        }
                    } else {
                        $error[] = [
                            "status" => '0',
                            "message" => 'service not found',
                            "shipment_ref" => $shipment_ref,
                            "consignment_no" => '',
                        ];
                    }
                } else {
                    $error[] = [
                        "status" => '0',
                        "message" => 'destination city not found',
                        "shipment_ref" => $shipment_ref,
                        "consignment_no" => ''
                    ];
                }
            } else {
                $error[] = [
                    "status" => '0',
                    "message" => 'weight or peices not found',
                    "shipment_ref" => $shipment_ref,
                    "consignment_no" => ''
                ];
            }
        }
        if ($shipment_count === count($success)) {
            echo response("1", "Booking has been created successfully", ["success" => $success, "error" => []]);
        }
        if ((count($success) < $shipment_count) && (count($error) > 0 && count($error) < $shipment_count)) {
            $res_array = [
                "success" => $success,
                "error" => $error
            ];
            echo response("1", "Booking has been created successfully", $res_array);
        }
        if (count($error) === $shipment_count) {
            echo response("0", "Error", $error);
        }
    } catch (Exception $e) {
        echo response("0", "Api Error !", $e);
    }
// } else {
//     echo response("0", "Error !", $valid->error);
// }
