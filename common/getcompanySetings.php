<?php
include ("../index.php");
$registerSchema = json_decode(file_get_contents('../schema/auth/login.json'));
$request = json_decode(file_get_contents('php://input'));
$valid = json_decode(requestvalidateobject($request, $registerSchema));
try{
    $company_id = $request->company_id;
    $query = "SELECT companies.*,c.code As currency_code
    FROM `companies` 
    LEFT JOIN `currencies` As c On c.code = companies.currency_code 
    WHERE companies.company_id = '$company_id'";
    $dbobjx->query($query);
    $set = $dbobjx->single();
    if($dbobjx->rowCount() > 0)
    {
        $company_name = $set->name;
        $base_currency = $set->currency_code;
        $company_logo = $set->logo;
        $prefix = $set->prefix;
        $primary_color = $set->primary_color;
        $secondary_color = $set->secondary_color;
        $office_address = $set->headoffice_address;
        $font_color = $set->font_color;
        $company_type = $set->company_type;
        $data[] = array(
            "company_logo" => $company_logo,
            "base_currency" => $base_currency,
            "company_name" => $company_name,
            "prefix" => $prefix,
            "primary_color" => $primary_color,
            "secondary_color" => $secondary_color,
            "office_address" => $office_address,
            "font_color" => $font_color,
            "company_type" => $company_type
        );
        echo response("1", "success", $data);
    }else{
        echo response("0", "error", 'No company found');
    }
    // exit;
    $dbobjx->close();
} catch (Exception $error) {
    echo response("0", "Error!", $error->getMessage());
}

