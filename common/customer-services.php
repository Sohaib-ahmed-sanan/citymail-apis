<?php
include("../index.php");
$request = json_decode(file_get_contents('php://input'));
$registerSchema = json_decode(file_get_contents('../schema/customers/get-service.json'));
$request = json_decode(file_get_contents('php://input'));
$valid = json_decode(schemaValidator($request, $registerSchema));
include "../functions/siteFunctions.php";
$has_key = authorization();
if ($has_key == 200) {
    $valid_key = authantication($dbobjx);
    if ($valid_key === true) {
        if ($valid->status) {
            try {
                $customer_acno = isset($request->customer_acno) ? $request->customer_acno : 0;
                if ($customer_acno != "") {
                    $get_parent = "SELECT parent_id,id 
                    FROM customers
                    WHERE acno = '$customer_acno'";
                    $dbobjx->query($get_parent);
                    $get =  $dbobjx->single();
                    if($dbobjx->rowCount() > 0)
                    {
                        if ($get->parent_id == '') {
                            $acno = $customer_acno;
                        } else {
                            $get_acno = "SELECT acno 
                            FROM customers
                            WHERE id = '$get->parent_id'";
                            $dbobjx->query($get_acno);
                            $get =  $dbobjx->single();
                            $acno = $get->acno;
                        }
                    }
                    
                    if($acno != "")
                    {
                        $query = "SELECT * FROM `customer_services` WHERE `acno` = '$acno'";
                        $dbobjx->query($query);
                        $dbobjx->execute();
                        $res = $dbobjx->single();
                        $ids = $res->service_id;
                        $arr = [];
                        $query = "SELECT * FROM `services` WHERE `id` IN ($ids) AND `active` = '1'";
                        $dbobjx->query($query);
                        $data = $dbobjx->resultset();
                        $dbobjx->close();
                        foreach ($data as $key => $value) {
                            $arr[] = $value->service_code;
                        }
                        if(count($arr) > 0)
                        {
                            echo response("1", "success", $arr);
                            exit();
                        }
                    }
                    echo response("0", "Error", "No assigned services found against this account number");                    
                    exit();
                }else{
                    echo response("0", "Error", "Please enter valid account number");                    
                }
            } catch (Exception $error) {
                echo response("0", "Error!", $error->getMessage());
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
