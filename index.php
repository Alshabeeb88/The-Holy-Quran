<?php
// Public pages only read single values. Array parameters such as ?l[]=x are
// dropped up front so they cannot reach string functions and crash a page.
foreach( $_GET as $qfa_key => $qfa_value ){ if( !is_scalar($qfa_value) ) unset($_GET[$qfa_key]); }
foreach( $_POST as $qfa_key => $qfa_value ){ if( !is_scalar($qfa_value) ) unset($_POST[$qfa_key]); }
foreach( $_REQUEST as $qfa_key => $qfa_value ){ if( !is_scalar($qfa_value) ) unset($_REQUEST[$qfa_key]); }
unset($qfa_key, $qfa_value);
include("includes/function.php");
echo $quranforall->output();
?>
