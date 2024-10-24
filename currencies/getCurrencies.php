<?php
include "../index.php";
$request = json_decode(file_get_contents('php://input'));
try {
    $convert_from = $request->convert_from;
    $convert_to = $request->convert_to;
    $company_id = $request->company_id;

    $data = json_encode(convertCurrency($convert_from,$convert_to,$company_id));
    echo response('1','sucsess',$data);
} catch (Exception $e) {
    echo response("0", "Api Error !", $e);
}