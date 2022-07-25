<?php ob_start(); session_start();
header ('Content-Type: text/html; charset=utf-8');
header("Cache-Control: no-cache, must-revalidate"); 

require_once('../../../../wp-load.php');
$ahcpro_countOnlineusers = ahcpro_countOnlineusers();
if(strlen($ahcpro_countOnlineusers) > 10)
{
echo 'x';	
}else{
echo $ahcpro_countOnlineusers;	
}
?>