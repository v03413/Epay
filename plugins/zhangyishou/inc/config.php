<?php
$pay_config = array (
	//登录账号
	'MerchantId' => $channel['appid'],

	//商户编号
	'MerchantNo' => $channel['appurl'],

	//商户密钥
	'key' => $channel['appkey'],

	//通道ID
	'PayChannelId' => $channel['appmchid'],
);