<?php
include("./inc.php");
@header('Content-Type: application/json; charset=UTF-8');

if(!checkRefererHost())exit('{"code":403}');

$biz_no = trim($_POST['n']);
$time = trim($_POST['t']);
$sign = trim($_POST['s']);
$openid = trim($_POST['openid']);
if(empty($biz_no) || empty($time) || empty($sign) || empty($openid)) showerrorjson('参数错误');
if(md5(SYS_KEY.$biz_no.$time.SYS_KEY) != $sign) showerrorjson('签名错误');
if($time < time() - 86400) showerrorjson('红包已过期');

$result = \lib\Transfer::red_receive($biz_no, $openid);
if($result['code'] == 0){
    $result['redirect_url'] = './red.php?do=success&n='.$biz_no.'&t='.$time.'&s='.$sign;
}
exit(json_encode($result));