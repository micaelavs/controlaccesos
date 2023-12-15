<?php
/** @var array $data */
header("Content-Type: application/json;charset=utf-8");
echo json_encode($data, JSON_UNESCAPED_UNICODE);
exit;