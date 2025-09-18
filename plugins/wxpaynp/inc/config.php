<?php
//微信支付v3配置文件

$wechatpay_config = [
    //应用ID
    'appid' => $channel['appid'],

    //商户号
    'mchid' => $channel['appmchid'],

    //子商户应用ID（可留空）
    'sub_appid' => isset($channel['sub_appid']) ? $channel['sub_appid'] : '',

    //子商户号
    'sub_mchid' => $channel['appurl'],

    //APIv3密钥
    'apikey' => $channel['appsecret'],

    //「商户API私钥」文件路径
    'merchantPrivateKeyFilePath' => PLUGIN_ROOT.$channel['plugin'].'/cert/apiclient_key.pem',

    //「商户API证书」的「证书序列号」
    'merchantCertificateSerial' => $channel['appkey'],

    //「微信支付公钥」文件路径
    'platformPublicKeyFilePath' => PLUGIN_ROOT.$channel['plugin'].'/cert/pub_key.pem',

    //「微信支付平台证书」文件路径
    'platformCertificateFilePath' => PLUGIN_ROOT.$channel['plugin'].'/cert/cert.pem',

    //微信支付平台公钥ID
    'platformCertificateSerial' => $channel['publickeyid'],

    //是否电商收付通
    'ecommerce' => $channel['appswitch'] == '1',
];

if(file_exists(PLUGIN_ROOT.$channel['plugin'].'/cert/'.$channel['appmchid'].'/apiclient_key.pem')){
    $wechatpay_config['merchantPrivateKeyFilePath'] = PLUGIN_ROOT.$channel['plugin'].'/cert/'.$channel['appmchid'].'/apiclient_key.pem';
	$wechatpay_config['platformPublicKeyFilePath'] = PLUGIN_ROOT.$channel['plugin'].'/cert/'.$channel['appmchid'].'/pub_key.pem';
    $wechatpay_config['platformCertificateFilePath'] = PLUGIN_ROOT.$channel['plugin'].'/cert/'.$channel['appmchid'].'/cert.pem';
}

return $wechatpay_config;