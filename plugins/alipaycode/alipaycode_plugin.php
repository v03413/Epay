<?php

class alipaycode_plugin
{
	static public $info = [
		'name'        => 'alipaycode', //支付插件英文名称，需和目录名称一致，不能有重复
		'showname'    => '支付宝免签约码支付', //支付插件显示名称
		'author'      => '支付宝', //支付插件作者
		'link'        => 'https://open.alipay.com/', //支付插件作者链接
		'types'       => ['alipay'], //支付插件支持的支付方式，可选的有alipay,qqpay,wxpay,bank
		'inputs' => [ //支付插件要求传入的参数以及参数显示名称，可选的有appid,appkey,appsecret,appurl,appmchid
			'appid' => [
				'name' => '应用APPID',
				'type' => 'input',
				'note' => '',
			],
			'appkey' => [
				'name' => '支付宝公钥',
				'type' => 'textarea',
				'note' => '填错也可以支付成功但会无法回调，如果用公钥证书模式此处留空',
			],
			'appsecret' => [
				'name' => '应用私钥',
				'type' => 'textarea',
				'note' => '',
			],
			'appmchid' => [
				'name' => '支付宝UID',
				'type' => 'input',
				'note' => '2088开头的16位纯数字',
			],
			'apptoken' => [
				'name' => '商户授权token',
				'type' => 'input',
				'note' => '只有第三方应用需要填写，非第三方应用必须留空',
			],
			'appswitch' => [
                'name' => '支付类型',
                'type' => 'select',
                'options' => [0 => '普通转账', 1 => '转账确认单'],
            ],
		],
		'note' => '<p>可不签约支付产品，支付宝开放平台应用需要已上线，不能开启余额宝自动转入。如果是第三方应用类型，还需要填写商户授权token。</p><p>需添加守护进程，运行目录：<u>[basedir]plugins/alipaycode/</u> 启动命令：<u>php server.php [channel]</u> </p>', //支付密钥填写说明
		'bindwxmp' => false, //是否支持绑定微信公众号
		'bindwxa' => false, //是否支持绑定微信小程序
	];

	static public function submit(){
		return ['type'=>'jump','url'=>'/pay/qrcode/'.TRADE_NO.'/'];
	}

	static public function mapi(){
		return ['type'=>'jump','url'=>'/pay/qrcode/'.TRADE_NO.'/'];
	}

	//扫码支付
	static public function qrcode(){
		global $siteurl, $cdnpublic, $order, $conf;

		$code_url = $siteurl.'pay/pay/'.TRADE_NO.'/';

		$paytime = strtotime($order['addtime']) + 300 - time();

		if(!empty($conf['alipay_qrcode_url'])){
			$code_url = $conf['alipay_qrcode_url'].'pay/pay/'.TRADE_NO.'/';
		}

		include PAY_ROOT.'inc/qrcode.page.php';
	    exit;
	}

	static public function pay()
	{
		global $siteurl, $order, $channel, $conf;

		if($conf['alipay_wappaylogin']==1){
			$alipay_config = require(PAY_ROOT.'inc/config.php');
			[$user_type, $user_id] = alipay_oauth($alipay_config);
			$blocks = checkBlockUser($user_id, TRADE_NO);
			if($blocks) return $blocks;
		}

		if($channel['appswitch'] == 1){
			$params = [
				'productCode' => 'TRANSFER_TO_ALIPAY_ACCOUNT',
				'bizScene' => 'YUEBAO',
				'transAmount' => $order['realmoney'],
				'remark' => $order['trade_no'],
				'businessParams' => [
					'returnUrl' => 'alipays://platformapi/startapp?appId=2021001167654035&nbupdate=syncforce',
				],
				'payeeInfo' => [
					'identity' => $channel['appmchid'],
					'identityType' => 'ALIPAY_USER_ID',
				],
			];
			$url = 'https://render.alipay.com/p/yuyan/180020010001206672/rent-index.html?formData='.rawurlencode(json_encode($params, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
			header("Location: $url");
			exit;
		}

		include PAY_ROOT.'inc/pay.page.php';
	    exit;
	}

}