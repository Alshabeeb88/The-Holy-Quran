<?php
header("Content-Type: application/json; charset=UTF-8");
require_once( 'book-api.php' );
$ML = new MUSLIM_LIBRARY();
echo json_encode( $ML->output() );
?>
