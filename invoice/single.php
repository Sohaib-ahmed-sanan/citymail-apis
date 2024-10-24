<?php
include "../index.php";
$request = json_decode(file_get_contents('php://input'));
include "../functions/siteFunctions.php";
$has_key = authorization();
if ($has_key == 200) {
    $valid_key = authantication($dbobjx);
    if ($valid_key == 200) {
        try {
            $company_id = $request->company_id;
            $invoice_id = $request->invoice_id;

            $query = "SELECT cbc_invoice.*,customers.business_name As customer_name,shipments.consignee_name,shipments.consignee_phone,
            shipments.consignee_email,shipments.consignee_address,shipments.courier_id,shipments.account_id,shipments.service_id,shipments.parcel_detail,
            shipments.created_at As booked_date,shipments.weight,shipments.peices,shipments.weight_charged,shipments.peices_charged,
            shipments.handling_charges,shipments.service_charges,shipments.rto_charges,shipments.sst_charges,shipments.gst_charges,shipments.bac_charges,
            shipments.total_charges,shipments.order_amount,shipments.currency_code,shipments.consignment_no,shipments.shipment_referance,shipments.status  FROM `cbc_invoice` 
            LEFT JOIN `customers` ON cbc_invoice.customer_acno = customers.acno 
            LEFT JOIN `invoice_details` ON invoice_details.invoice_id = cbc_invoice.id
            LEFT JOIN `shipments` ON shipments.consignment_no = invoice_details.consignment_no
            WHERE cbc_invoice.invoice_no = $invoice_id AND cbc_invoice.company_id = $company_id";
            $dbobjx->query($query);
            $result = $dbobjx->resultset();
            echo response("1", "Success", $result);
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