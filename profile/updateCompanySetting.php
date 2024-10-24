<?php
include ("../index.php");
$registerSchema = json_decode(file_get_contents('../schema/auth/updateProfle.json'));
$request = json_decode(file_get_contents('php://input'));
$valid = json_decode(requestvalidateobject($request, $registerSchema));
include "../functions/siteFunctions.php";
$has_key = authorization();
if ($has_key) {
    $valid_key = authantication($dbobjx);
    if ($valid_key === true) {
        try {
            $company_id = $request->company_id;
            $name = $request->name;
            $office_address = $request->office_address;
            $primary_color = $request->primary_color;
            $secondary_color = $request->secondary_color;
            $font_color = $request->font_color;
            $logo_image = $request->logo_image;
            $currency_code = $request->base_currency;
            $pkr = isset($request->pkr) ? $request->pkr : 1;
            $aed = isset($request->aed) ? $request->aed : 1;
            $usd = isset($request->usd) ? $request->usd : 1;
            $sar = isset($request->sar) ? $request->sar : 1;
            $more = '';
            if($currency_code != '')
            {
                $more .= ",`currency_code` = '$currency_code'";
            }
            $updated_by = get_user_id_header();
            $query = "UPDATE `companies` SET `name`='$name',`headoffice_address`='$office_address',
                `primary_color`='$primary_color',`secondary_color`='$secondary_color',`font_color`='$font_color',`logo`='$logo_image',
                 `updated_at` = CURRENT_TIMESTAMP(),`pkr`='$pkr',`aed`='$aed',`usd`='$usd',`sar`='$sar',`updated_by`='$updated_by' $more WHERE `company_id` = '$company_id'";
            $dbobjx->query($query);
            if ($dbobjx->execute()) {
                $data = json_encode([
                    "updated_by" => $updated_by,
                    "company_name" => $name,
                    "headoffice_address" => $office_address,
                    "primary_color" => $primary_color,
                    "secondary_color" => $secondary_color,
                    "font_color" => $font_color,
                    "logo_image" => $logo_image,
                    "currency_code" => $currency_code,
                    "PKR" => $pkr,
                    "AED" => $aed,
                    "USD" => $usd,
                    "SAR" => $sar,
                ]);
                $log = "INSERT INTO `company_logs`(`company_id`, `user_id`, `updated_data`) VALUES ('$company_id','$updated_by','$data')";
                $dbobjx->query($log);
                $dbobjx->execute();
                echo response("1", "Company Settings has been updated successfully");
            } else {
                echo response("0", "Something went wrong");
            }

            $dbobjx->close();
        } catch (Exception $error) {
            echo response("0", "Api Error!", $error->getMessage());
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