<?php
$alipay_config = [
	//应用ID
	'app_id' => $channel['appid'],

	//支付宝公钥
	'alipay_public_key' => $channel['appkey'],

	//应用私钥
	'app_private_key' => $channel['appsecret'],

	//商户授权token
	'app_auth_token' => $channel['apptoken'],

	//签名方式,默认为RSA2
	'sign_type' => "RSA2",

	//编码格式
	'charset' => "UTF-8",

	//支付宝网关
	'gateway_url' => "https://openapi.alipay.com/gateway.do",

	//日志记录位置
	'log_path' => dirname(__FILE__).'/log/',
];

if(file_exists(PLUGIN_ROOT.$channel['plugin'].'/cert/'.$channel['appid'].'/appCertPublicKey_'.$channel['appid'].'.crt')){
	$alipay_config['app_cert_path'] = PLUGIN_ROOT.$channel['plugin'].'/cert/'.$channel['appid'].'/appCertPublicKey_'.$channel['appid'].'.crt';
	$alipay_config['alipay_cert_path'] = PLUGIN_ROOT.$channel['plugin'].'/cert/'.$channel['appid'].'/alipayCertPublicKey_RSA2.crt';
	$alipay_config['root_cert_path'] = PLUGIN_ROOT.$channel['plugin'].'/cert/'.$channel['appid'].'/alipayRootCert.crt';
}
elseif(file_exists(PLUGIN_ROOT.$channel['plugin'].'/cert/appCertPublicKey_'.$channel['appid'].'.crt')){
	$alipay_config['app_cert_path'] = PLUGIN_ROOT.$channel['plugin'].'/cert/appCertPublicKey_'.$channel['appid'].'.crt';
	$alipay_config['alipay_cert_path'] = PLUGIN_ROOT.$channel['plugin'].'/cert/alipayCertPublicKey_RSA2.crt';
	$alipay_config['root_cert_path'] = PLUGIN_ROOT.$channel['plugin'].'/cert/alipayRootCert.crt';
}
return $alipay_config;