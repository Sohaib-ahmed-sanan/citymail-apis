<?php
include "../../index.php";
// $registerSchema = json_decode(file_get_contents('../schema/stations/add.json'));
$request = json_decode(file_get_contents('php://input'));
// $valid = json_decode(requestvalidateobject($request, $registerSchema));
include "../../functions/siteFunctions.php";
$has_key = authorization();
if (true) {
    $valid_key = authantication($dbobjx);
    if (true) {
        try {
            $cn_number = $request->cn_number;
            $company_id = $request->company_id;
            $query = "SELECT shipments.id,rules.title,rules.status_id,rules.courier_id,rules.account_id,courier_code.code As courier_code ,rules.service_code,rules.pickup_id,rules.customer_acno
            FROM `rules` 
            LEFT JOIN shipments ON shipments.customer_acno = rules.customer_acno and shipments.consignment_no = '$cn_number' and shipments.company_id = '$company_id'
            LEFT JOIN courier_code ON rules.account_id = courier_code.account_id
            AND (
                CASE 
                    -- Here are all possible combinations of single names:
                    WHEN -- weight
                        (
                            (rules.weight_type = '=' and shipments.weight_charged = rules.weight_value) OR
                            (rules.weight_type = '>' and shipments.weight_charged > rules.weight_value) OR
                            (rules.weight_type = '<' and shipments.weight_charged < rules.weight_value) OR
                            (rules.weight_type = '>=' and shipments.weight_charged >= rules.weight_value) OR
                            (rules.weight_type = '<=' and shipments.weight_charged <= rules.weight_value) 
                        ) and
                        rules.destination_city_id is null and 
                        rules.order_type is null and
                        rules.payment_method_id IS NULL  
                    THEN 1
                    WHEN -- city
                    rules.destination_city_id is not null and rules.destination_city_id = shipments.destination_city_id
                        and 
                        rules.weight_type is null and 
                        rules.order_type is null and
                        rules.payment_method_id is null 
                    THEN 1
                    WHEN -- order amount
                        (
                            (rules.order_type = '=' and shipments.order_amount = rules.order_value) OR
                            (rules.order_type = '>' and shipments.order_amount > rules.order_value) OR
                            (rules.order_type = '<' and shipments.order_amount < rules.order_value) OR
                            (rules.order_type = '>=' and shipments.order_amount >= rules.order_value) OR
                            (rules.order_type = '<=' and shipments.order_amount <= rules.order_value) 
                        )
                        and
                        rules.weight_type is null and 
                        rules.destination_city_id is null and
                        rules.payment_method_id is null
                    THEN 1
                    WHEN -- payment method 
                        rules.payment_method_id = shipments.payment_method_id and 
                        rules.weight_type is null and 
                        rules.destination_city_id is null and 
                        rules.order_type is null
                    THEN 1
                    
                    -- Here are all possible combinations of two names:
                    WHEN -- weight with city
                        (
                            (rules.weight_type = '=' and shipments.weight_charged = rules.weight_value) OR
                            (rules.weight_type = '>' and shipments.weight_charged > rules.weight_value) OR
                            (rules.weight_type = '<' and shipments.weight_charged < rules.weight_value) OR
                            (rules.weight_type = '>=' and shipments.weight_charged >= rules.weight_value) OR
                            (rules.weight_type = '<=' and shipments.weight_charged <= rules.weight_value) 
                        ) 
                        and 
                        rules.payment_method_id is null and 
                        rules.order_type is null
                    THEN 1
                    WHEN -- weight with order_amount
                        (
                            (rules.weight_type = '=' and shipments.weight_charged = rules.weight_value) OR
                            (rules.weight_type = '>' and shipments.weight_charged > rules.weight_value) OR
                            (rules.weight_type = '<' and shipments.weight_charged < rules.weight_value) OR
                            (rules.weight_type = '>=' and shipments.weight_charged >= rules.weight_value) OR
                            (rules.weight_type = '<=' and shipments.weight_charged <= rules.weight_value) 
                        ) 
                        and 
                        (
                            (rules.order_type = '=' and shipments.order_amount = rules.order_value) OR
                            (rules.order_type = '>' and shipments.order_amount > rules.order_value) OR
                            (rules.order_type = '<' and shipments.order_amount < rules.order_value) OR
                            (rules.order_type = '>=' and shipments.order_amount >= rules.order_value) OR
                            (rules.order_type = '<=' and shipments.order_amount <= rules.order_value) 
                        ) and
                        rules.destination_city_id is null and
                        rules.payment_method_id is null
                    THEN 1
                    WHEN -- weight with payment method
                        (
                            (rules.weight_type = '=' and shipments.weight_charged = rules.weight_value) OR
                            (rules.weight_type = '>' and shipments.weight_charged > rules.weight_value) OR
                            (rules.weight_type = '<' and shipments.weight_charged < rules.weight_value) OR
                            (rules.weight_type = '>=' and shipments.weight_charged >= rules.weight_value) OR
                            (rules.weight_type = '<=' and shipments.weight_charged <= rules.weight_value) 
                        ) 
                        and 
                        rules.payment_method_id = shipments.payment_method_id and 
                        rules.destination_city_id is null and
                        rules.order_type is null
                    THEN 1
                    WHEN -- city with order amount
                    rules.destination_city_id is not null and rules.destination_city_id = shipments.destination_city_id
                        and
                        (
                            (rules.order_type = '=' and shipments.order_amount = rules.order_value) OR
                            (rules.order_type = '>' and shipments.order_amount > rules.order_value) OR
                            (rules.order_type = '<' and shipments.order_amount < rules.order_value) OR
                            (rules.order_type = '>=' and shipments.order_amount >= rules.order_value) OR
                            (rules.order_type = '<=' and shipments.order_amount <= rules.order_value) 
                        ) and
                        rules.weight_type is null and 
                        rules.payment_method_id is null
                    THEN 1
                    WHEN -- city with payment method
                    rules.destination_city_id is not null and rules.destination_city_id = shipments.destination_city_id
                        and
                        rules.payment_method_id = shipments.payment_method_id and 
                        rules.weight_type is null and 
                        rules.order_type is null
                    THEN 1
                    WHEN -- order amount with payment method
                        (
                            (rules.order_type = '=' and shipments.order_amount = rules.order_value) OR
                            (rules.order_type = '>' and shipments.order_amount > rules.order_value) OR
                            (rules.order_type = '<' and shipments.order_amount < rules.order_value) OR
                            (rules.order_type = '>=' and shipments.order_amount >= rules.order_value) OR
                            (rules.order_type = '<=' and shipments.order_amount <= rules.order_value) 
                        )
                        and
                        rules.payment_method_id = shipments.payment_method_id and 
                        rules.weight_type is null and 
                        rules.destination_city_id is null
                    THEN 1
                    -- Here are all possible combinations of three names:
                    WHEN -- Weight, City, Order amount
                        (
                            (rules.weight_type = '=' and shipments.weight_charged = rules.weight_value) OR
                            (rules.weight_type = '>' and shipments.weight_charged > rules.weight_value) OR
                            (rules.weight_type = '<' and shipments.weight_charged < rules.weight_value) OR
                            (rules.weight_type = '>=' and shipments.weight_charged >= rules.weight_value) OR
                            (rules.weight_type = '<=' and shipments.weight_charged <= rules.weight_value) 
                        ) 
                        and 
                        rules.destination_city_id is not null and rules.destination_city_id = shipments.destination_city_id
                        and
                        (
                            (rules.order_type = '=' and shipments.order_amount = rules.order_value) OR
                            (rules.order_type = '>' and shipments.order_amount > rules.order_value) OR
                            (rules.order_type = '<' and shipments.order_amount < rules.order_value) OR
                            (rules.order_type = '>=' and shipments.order_amount >= rules.order_value) OR
                            (rules.order_type = '<=' and shipments.order_amount <= rules.order_value) 
                        ) and
                        rules.payment_method_id is null 
                    THEN 1
                    WHEN -- Weight, City, Payment method
                        (
                            (rules.weight_type = '=' and shipments.weight_charged = rules.weight_value) OR
                            (rules.weight_type = '>' and shipments.weight_charged > rules.weight_value) OR
                            (rules.weight_type = '<' and shipments.weight_charged < rules.weight_value) OR
                            (rules.weight_type = '>=' and shipments.weight_charged >= rules.weight_value) OR
                            (rules.weight_type = '<=' and shipments.weight_charged <= rules.weight_value) 
                        ) 
                        and 
                        rules.destination_city_id is not null and rules.destination_city_id = shipments.destination_city_id
                        and
                        rules.payment_method_id = shipments.payment_method_id and 
                        rules.order_type is null
                    THEN 1
                    WHEN -- Weight, Order amount, Payment method
                        (
                            (rules.weight_type = '=' and shipments.weight_charged = rules.weight_value) OR
                            (rules.weight_type = '>' and shipments.weight_charged > rules.weight_value) OR
                            (rules.weight_type = '<' and shipments.weight_charged < rules.weight_value) OR
                            (rules.weight_type = '>=' and shipments.weight_charged >= rules.weight_value) OR
                            (rules.weight_type = '<=' and shipments.weight_charged <= rules.weight_value) 
                        ) 
                        and 
                        (
                            (rules.order_type = '=' and shipments.order_amount = rules.order_value) OR
                            (rules.order_type = '>' and shipments.order_amount > rules.order_value) OR
                            (rules.order_type = '<' and shipments.order_amount < rules.order_value) OR
                            (rules.order_type = '>=' and shipments.order_amount >= rules.order_value) OR
                            (rules.order_type = '<=' and shipments.order_amount <= rules.order_value) 
                        ) and
                        rules.payment_method_id = shipments.payment_method_id and 
                        rules.destination_city_id is null
                    THEN 1
                   
                    WHEN -- City, Order amount, Payment method
                    rules.destination_city_id is not null and rules.destination_city_id = shipments.destination_city_id 
                        and
                        (
                            (rules.order_type = '=' and shipments.order_amount = rules.order_value) OR
                            (rules.order_type = '>' and shipments.order_amount > rules.order_value) OR
                            (rules.order_type = '<' and shipments.order_amount < rules.order_value) OR
                            (rules.order_type = '>=' and shipments.order_amount >= rules.order_value) OR
                            (rules.order_type = '<=' and shipments.order_amount <= rules.order_value) 
                        ) and
                        rules.payment_method_id = shipments.payment_method_id and
                        rules.weight_type is null  
                    THEN 1
                     -- Here are all possible combinations of four names:
                    WHEN -- Weight, City, Order amount, Payment method
                        (
                            (rules.weight_type = '=' and shipments.weight_charged = rules.weight_value) OR
                            (rules.weight_type = '>' and shipments.weight_charged > rules.weight_value) OR
                            (rules.weight_type = '<' and shipments.weight_charged < rules.weight_value) OR
                            (rules.weight_type = '>=' and shipments.weight_charged >= rules.weight_value) OR
                            (rules.weight_type = '<=' and shipments.weight_charged <= rules.weight_value) 
                        ) 
                        and 
                        rules.destination_city_id is not null and rules.destination_city_id = shipments.destination_city_id 
                        and 
                        (
                            (rules.order_type = '=' and shipments.order_amount = rules.order_value) OR
                            (rules.order_type = '>' and shipments.order_amount > rules.order_value) OR
                            (rules.order_type = '<' and shipments.order_amount < rules.order_value) OR
                            (rules.order_type = '>=' and shipments.order_amount >= rules.order_value) OR
                            (rules.order_type = '<=' and shipments.order_amount <= rules.order_value) 
                        ) and
                        rules.payment_method_id = shipments.payment_method_id 
                    THEN 1
                    ELSE 0
                END
            )
            WHERE
                rules.company_id = '$company_id' 
                AND rules.active = '1' 
                AND rules.is_deleted = 'N'
                AND shipments.consignment_no = '$cn_number'";
                // print_r($query);die;
                $dbobjx->query($query);
                $rules_detail = $dbobjx->single();
                if ($dbobjx->rowCount() > 0) {
                    $pickup = $rules_detail->pickup_id;
                $query = "SELECT destination_city_id FROM shipments WHERE `id` = '$rules_detail->id' ";
                $dbobjx->query($query);
                $order_detail = $dbobjx->single();
                if ($pickup == 0) {
                    $query = "SELECT `pickup_location_id` FROM shipments WHERE `id` = '$rules_detail->id'";
                    $dbobjx->query($query);
                    $pickup_result = $dbobjx->single();
                    $pickup = $pickup_result->id;
                }
                $ordersData[] = [
                    "order_id" => (int) $rules_detail->id,
                    "destination_city_id" => (int) $order_detail->destination_city_id ?? null,
                    "pickup_location_id" => (int) $pickup ?? null,
                ];
                if (count($ordersData) > 0) {
                    $file_name = API_URL . 'tpl/createThirdParty';
                    $parcel = "P";
                    $insurance = "N";
                    $fragile = "N";
                    $data = [
                        "consignment_no" => [$cn_number],
                        "customer_acno" => (int) $rules_detail->customer_acno,
                        "courier_id" => (int) $rules_detail->courier_id,
                        "customer_courier_id" => (int) $rules_detail->account_id,
                        "courier_code" => $rules_detail->courier_code,
                        "service_code" => $rules_detail->service_code,
                        "fragile_require" => $fragile,
                        "insurance_require" => $insurance,
                        "insurance_value" => 0,
                        "parcel_type" => $parcel,
                        "detail" => $ordersData,
                    ];

                    $return = getAPIdata($file_name, $data);
                    // print_r($return);die;
                    echo response("1", "Success", [$return]);
                }
            } else {
                echo response("0", "No Record Found!", []);
            }
        } catch (Exception $e) {
            echo response("0", "Api Error !", $e);
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