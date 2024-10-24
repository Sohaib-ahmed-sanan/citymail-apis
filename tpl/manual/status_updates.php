<?php
try {
    include("../../index.php");
    $request = json_decode(file_get_contents('php://input'));
    try {
        $cn_number = $request->cn_number;
        $query = "SELECT 
        track_status.created_at AS last_updated,
        track_status.consignment_no AS cn,
        status_tbl.id AS status_code,
        status_tbl.name AS status_name,
        status_tbl.message AS status_message
    FROM 
        orderstatus_tracking AS track_status 
    LEFT JOIN
        `delivery_status` AS status_tbl ON track_status.status_id = status_tbl.id  
    WHERE 
        track_status.consignment_no = '$cn_number'
    ORDER BY 
        track_status.created_at DESC 
    LIMIT 1; ";
        $dbobjx->query($query);
        echo json_encode($dbobjx->single());
    } catch (Exception $error) {
        echo response("0", "Error!", $error->getMessage());
    }
} catch (Exception $error) {
    echo response("0", "Api Error!", $error->getMessage());
}
