<?php
include ("../index.php");
$request = json_decode(file_get_contents('php://input'));
try {
    $acc_type = isset($request->acc_type) ? $request->acc_type : '';
    $company_id = isset($request->company_id) ? $request->company_id : '';
    $customer_acno = isset($request->customer_acno) ? $request->customer_acno : '';
    $start_date = isset($request->start_date) ? $request->start_date : '';
    $end_date = isset($request->end_date) ? $request->end_date : '';
    $more = "";
    $bypas = "SET sql_mode ='';";
    $dbobjx->query($bypas);
    $dbobjx->execute();

    switch ($acc_type) {
        case '1':
            $more = "customers.parent_id IS NULL AND";
            $fetch = "customers.business_name,
            customers.phone,
            customers.email,";
            $join = "LEFT JOIN 
            `customers` ON shipments.customer_acno = customers.acno";
            break;
        case '6':
            $query = "SELECT id FROM `customers` WHERE `acno` = '$customer_acno' AND `active` = '1'";
            $dbobjx->query($query);
            $parent = $dbobjx->single();
            $query = "SELECT acno FROM `customers` WHERE `parent_id` = '$parent->id' AND `active` = '1'";
            $dbobjx->query($query);
            $result = $dbobjx->resultset();
            $acnos = array_column($result, 'acno');
            $acnosString = implode(',', $acnos);
            if ($acnosString != '') {
                $customer_acnos = $acnosString;
                $more = "shipments.customer_acno IN($customer_acnos) AND";
            }
            $fetch = "sub_acc.name As business_name,
            sub_acc.phone,
            sub_acc.email,";
            $join = "LEFT JOIN 
            `customers` As sub_acc ON shipments.customer_acno = sub_acc.acno";
            break;
    }
    $query = "SELECT 
            shipments.customer_acno AS customer_acno,
            COUNT(*) AS total_shipments,
            destination_city_id,
            COUNT(destination_city_id) AS city_count,
            $fetch
            SUM(shipments.service_charges) AS total_charges,
            cities.city As customer_city
         FROM 
            `shipments` 
        $join
        LEFT JOIN 
            `cities` ON shipments.destination_city_id = cities.id
        WHERE $more
            shipments.active = '1' 
            AND shipments.is_deleted = 'N' 
            AND shipments.created_at >= '$start_date 00:00:00' 
            AND shipments.created_at <= '$end_date 23:59:00'
        GROUP BY 
            shipments.customer_acno,destination_city_id;
        ";
    $dbobjx->query($query);
    $result = $dbobjx->resultset();

    $customer_details = array();
    $cities_list = ['655', '791', '538', '1015', '009']; // As per the graph cities order
    $cities_count = array_fill_keys($cities_list, 0);
    foreach ($result as $key => $data) {
        if (array_key_exists($data->customer_id, $customer_details)) {
            $customer_details[$data->customer_id]['total_charges'] += $data->total_charges;
            $customer_details[$data->customer_id]['total_shipments'] += $data->total_shipments;
        } else {
            $customer_details[$data->customer_id] = [
                "business_name" => $data->business_name,
                "phone" => $data->phone,
                "email" => $data->email,
                "total_charges" => $data->total_charges,
                "total_shipments" => $data->total_shipments,
            ];
        }
        if (in_array($data->destination_city_id, $cities_list)) {
            $cities_count[$data->destination_city_id] = $data->city_count;
        } else {
            $cities_count['009'] = $data->city_count;
        }
    }
    $cities_arranged = [];
    foreach ($cities_list as $city) {
        $cities_arranged[] = (int) $cities_count[$city];
    }
    // Converting the associative array to an indexed array
    $customers_indexed = [];
    foreach ($customer_details as $key => $customer) {
        $customers_indexed[] = array_merge(['id' => $key], $customer);
    }
    $return = array(
        "customers" => $customers_indexed,
        "cities_array" => $cities_arranged,
    );
    echo response("1", "success", $return);
} catch (Exception $error) {
    echo response("0", "Error!", $error->getMessage());
}
