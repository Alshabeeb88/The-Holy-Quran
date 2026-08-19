<?php
header("Content-Type: application/json; charset=UTF-8");
require_once('quran-api.php');
$Q = new QuranForAll_API();
echo json_encode( $Q->output() );
?>
