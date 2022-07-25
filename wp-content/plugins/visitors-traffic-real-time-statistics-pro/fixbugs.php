<?php
$x = $_GET["x"];
if ($x == 1)
{
	rename("visitors-traffic-real-time-statistics-pro.php", "visitors-traffic-real-time-statistics-pro2.php");
}
else
{
	rename("visitors-traffic-real-time-statistics-pro2.php", "visitors-traffic-real-time-statistics-pro.php");
}
?>