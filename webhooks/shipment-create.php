<?php
include "../index.php";
$request = json_decode(file_get_contents('F:/laragon/www/oex_citymail_api/getorder.txt'));
include "../functions/siteFunctions.php";
try{

global $dbobjx; 
$company_id = $_REQUEST['company_id'];
$acno = $_REQUEST['acno'];
$shipment_ref = $request->name;
$customer = $request->customer; //customer_details
$customer_details = $request->shipping_address; //customer_shipping_address
$customer_biling_details = $request->billing_address; 

$consingee_name = ((isset($customer_details->first_name) && isset($customer_details->last_name)) ? $customer_details->first_name . " " . $customer_details->last_name : ((isset($customer_billing_address->first_name) && isset($customer_billing_address->last_name)) ? $customer_billing_address->first_name . " " . $customer_billing_address->last_name : $customer->default_address->first_name . " " . $customer->default_address->last_name));
$consignee_phone = ((isset($customer_details->phone)) ? $customer_details->phone : ((isset($customer_billing_address->phone)) ? $customer_billing_address->phone : $customer_details->default_address->phone));
$consingee_email = ($request->customer->email == "") ? "none@gmail.com" : $request->customer->email;
$consingee_address = (($customer_details->address1 != "" && $customer_details->address2 != "") ? $customer_details->address1 . '.' . $customer_details->address2 : (($customer_billing_address->address1 != "" && $customer_billing_address->address2 != "") ? $customer_billing_address->address1 . '.' . $customer_billing_address->address2 : $customer->default_address->address1 . '.' . $customer->default_address->address2));
$country = ($customer_details->country != "" ? $customer_details->country : $customer->default_address->country);
$city = ((isset($customer_details->city)) ? $customer_details->city : ((isset($customer_billing_address->city)) ? $customer_billing_address->city : $customer->default_address->city));
$order_amt = $request->total_outstanding;
$currency_code = $request->current_subtotal_price_set->shop_money->currency_code;
$line_items = $request->line_items;
$product_details = "";
$weight = 0;
$peice = 0;
$comments = $request->note??'';
foreach ($line_items as $val) {    
    if($val->current_quantity > 0){
        $product_details .= "$val->name | ";
        
        $current_wgt = $val->grams * $val->current_quantity / 1000;
        $weight += $current_wgt ?? 0.5;
        
        $peice += $val->current_quantity??1;
    }
}
$product_details = rtrim($product_details,' | ');
// get pickuplocation 
$query = "SELECT `id` FROM `pickup_locations` WHERE `customer_acno` = '$acno' AND `company_id` = '$company_id' AND `is_deleted` = 'N' ORDER BY `id` DESC LIMIT 1";
$dbobjx->query($query); 
$pickup = $dbobjx->single();

if($pickup != '')
{
    if (!empty($consingee_name) && !empty($consignee_phone) && !empty($consingee_email) && !empty($consingee_address))
    {
        $param = [
            "company_id" => $company_id,
            "customer_acno" => "$acno",
            "device" => "3",
            "flag" => "Bulk Uploading",
            "shipments" => [
                [
                    "shipment_ref" => "$shipment_ref",
                    "pickup_location"=> "$pickup->id",
                    "name"=> "$consingee_name",
                    "email"=> "$consingee_email",
                    "phone"=> "$consignee_phone",
                    "address"=> "$consingee_address",
                    "destination_country"=> "$country",
                    "destination_city"=> "$city",
                    "product_detail"=> "$product_details",
                    "service_id"=> "1",
                    "payment_method_id"=> "1",
                    "order_amount"=> "$order_amt",
                    "currency_code"=> "$currency_code",
                    "peices"=> "$peice",
                    "weight"=> "$weight",
                    "fragile"=> "0",
                    "insurance"=> "0",
                    "insurance_amt"=> null,
                    "comments"=> "$comments"
                ]
            ]
        ];

        $result = getAPIdata(API_URL.'shipment/add',$param);
        if($result->status == '1'){
            echo response("1", "Success",$result->payload);
        }else{
            echo response("0", "Error",$result->payload);
            generate_log($company_id,$acno,$request,$param,$result->payload);        
        }
    }else{
        echo response("0", "Error", "Consignee details not found");
        generate_log($company_id,$acno,$request,"","Consignee details not found");        
    }
}else{
    echo response("0", "Error", "Pickup not found");
    generate_log($company_id,$acno,$request,"","Pickup not found");
}

function generate_log($company_id,$acno,$request,$json="",$response=""){
    global $dbobjx;
    $query = "INSERT INTO `webhook_logs` (`company_id`,`customer_acno`,`shopify_request`,`generated_json`,`response`)
     VALUES ('$company_id','$acno','$request','$json','$response')"; 
    $dbobjx->query($query);
    $dbobjx->execute();
}

} catch (Exception $e) {
    echo response("0", "Error", $e);
    generate_log($company_id,$acno,$request,"","$e");
}