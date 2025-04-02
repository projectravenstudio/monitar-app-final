<?php
session_start(); 

// Close all data 
session_unset();
session_destroy();

header("Location: /web/index.php");
exit;
?>
