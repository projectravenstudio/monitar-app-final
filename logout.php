<?php
session_start(); 

// Close all data 
session_unset();
session_destroy();

header("Location: ./index.php");
exit;
?>
