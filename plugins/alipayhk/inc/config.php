<?php
$alipay_config = [
    //PID
    'partner' => $channel['appid'],

    //MD5密钥
    'key' => $channel['appkey'],

    //签名方式
    'sign_type' => 'MD5',
];
return $alipay_config;