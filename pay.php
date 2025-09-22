<?php
$nosession = true;
$s = isset($_GET['s'])?$_GET['s']:exit('404 Not Found');
unset($_GET['s']);
include("./includes/common.php");

if (function_exists("set_time_limit"))
{
	@set_time_limit(0);
}
if (function_exists("ignore_user_abort"))
{
	@ignore_user_abort(true);
}

$sitename=isset($_GET['sitename'])?base64_decode($_GET['sitename']):'';
$submit2=true;

try{
	$result = \lib\Plugin::loadForPay($s);
	\lib\Payment::echoDefault($result);
}catch(Exception $e){
	sysmsg($e->getMessage());
}