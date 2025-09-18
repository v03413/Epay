<?php

class zhangyishou_plugin
{
	static public $info = [
		'name'        => 'zhangyishou', //支付插件英文名称，需和目录名称一致，不能有重复
		'showname'    => '掌易收聚合支付', //支付插件显示名称
		'author'      => '掌易收', //支付插件作者
		'link'        => 'http://www.zhangyishou.com/', //支付插件作者链接
		'types'       => ['alipay','qqpay','wxpay','bank'], //支付插件支持的支付方式，可选的有alipay,qqpay,wxpay,bank
		'transtypes'  => ['alipay','bank'],
		'inputs' => [ //支付插件要求传入的参数以及参数显示名称，可选的有appid,appkey,appsecret,appurl,appmchid
			'appid' => [
				'name' => '登录账号',
				'type' => 'input',
				'note' => '',
			],
			'appkey' => [
				'name' => '商户密钥',
				'type' => 'input',
				'note' => '',
			],
			'appurl' => [
				'name' => '商户编号',
				'type' => 'input',
				'note' => '',
			],
			'appmchid' => [
				'name' => '通道ID',
				'type' => 'input',
				'note' => '',
			],
		],
		'select' => null,
		'note' => '如果微信通道有扫码和小程序2种，直接在通道ID填写2个ID，用|隔开', //支付密钥填写说明
		'bindwxmp' => false, //是否支持绑定微信公众号
		'bindwxa' => false, //是否支持绑定微信小程序
	];

	static public function submit(){
		global $siteurl, $channel, $order, $ordername, $sitename, $conf;

		/*if(checkwechat()){
			return ['type'=>'jump','url'=>'/pay/wxjspay/'.TRADE_NO.'/'];
		}*/

		return ['type'=>'jump','url'=>'/pay/'.$order['typename'].'/'.TRADE_NO.'/'];
	}

	static public function mapi(){
		global $siteurl, $channel, $order, $conf, $device, $mdevice;

		$typename = $order['typename'];
		return self::$typename();
	}

	//通用扫码
	static public function qrcode($type){
		global $siteurl, $channel, $order, $ordername, $conf, $clientip;

		require(PAY_ROOT."inc/config.php");
		$getwayurl = 'https://apipay.zhangyishou.com/api/Order/AddOrder';
		$params = [
			'MerchantId' => $pay_config['MerchantId'],
			'DownstreamOrderNo' => TRADE_NO,
			'OrderTime' => date('Y-m-d H:i:s'),
			'PayChannelId' => $pay_config['PayChannelId'],
			'AsynPath' => $conf['localurl'].'pay/notify/'.TRADE_NO.'/',
			'OrderMoney' => $order['realmoney'],
			'IPPath' => $clientip,
		];

		$signStr = "";
		foreach($params as $row){
			$signStr .= $row;
		}
		$signStr .= $pay_config['key'];
		$params['MD5Sign'] = md5($signStr);
		$params['MerchantNo'] = $pay_config['MerchantNo'];
		$params['Mproductdesc'] = $ordername;
		if($type == 'qqpay' && checkmobbileqq() || $type == 'wxpay' && checkwechat()){
			$params['ReturnUrl'] = $siteurl.'pay/return/'.TRADE_NO.'/';
		}

		return \lib\Payment::lockPayData(TRADE_NO, function() use($getwayurl, $params) {
			$data = get_curl($getwayurl, json_encode($params), 0, 0, 0, 0, 0, ['Content-Type: application/json; charset=utf-8']);
			$result = json_decode($data, true);

			if($result['Code']=='1009'){
				$code_url = $result['Info'];
			}else{
				//echo json_encode($params);
				throw new Exception('['.$result['Code'].']'.$result['Message'].':'.$result['Info']);
			}

			return $code_url;
		});
	}

	//支付宝扫码支付
	static public function alipay(){
		global $mdevice, $siteurl;
		try{
			$code_url = self::qrcode('alipay');
		}catch(Exception $ex){
			return ['type'=>'error','msg'=>'支付宝支付下单失败！'.$ex->getMessage()];
		}

		if(checkalipay() || $mdevice=='alipay'){
			return ['type'=>'jump','url'=>$code_url];
		}else{
			return ['type'=>'qrcode','page'=>'alipay_qrcode','url'=>$code_url];
		}
	}

	//微信扫码支付
	static public function wxpay(){
		global $channel, $device, $mdevice;

		if(strpos($channel['appmchid'],'|')){
			$appmchid = explode('|',$channel['appmchid']);
			$channel['appmchid'] = $appmchid[0];
            if (checkmobile() && !checkwechat() || $device=='mobile' && $mdevice!='wechat') {
                $channel['appmchid'] = $appmchid[1];
				$isscheme = true;
            }
		}

		try{
			$code_url = self::qrcode('wxpay');
		}catch(Exception $ex){
			return ['type'=>'error','msg'=>'微信支付下单失败！'.$ex->getMessage()];
		}
		
		if($isscheme){
			return ['type'=>'scheme','page'=>'wxpay_mini','url'=>$code_url];
		} elseif(checkwechat() || $mdevice == 'wechat'){
			return ['type'=>'jump','url'=>$code_url];
		} elseif (checkmobile() || $device == 'mobile') {
			return ['type'=>'qrcode','page'=>'wxpay_wap','url'=>$code_url];
		} else {
			return ['type'=>'qrcode','page'=>'wxpay_qrcode','url'=>$code_url];
		}
	}

	static private function wx_get_code($orderno, $redirect_uri){
		global $channel;
		$url = 'https://apipay.zhangyishou.com/api/get/code';
		$params = [
			'Apptype' => '0',
			'Code' => '',
			'MD5Sign' => '',
			'MerchantId' => '',
			'OrderNo' => $orderno,
			'RedirectUri' => $redirect_uri,
			'WayId' => $channel['appmchid'],
		];
		$data = get_curl($url, json_encode($params), 0, 0, 0, 0, 0, ['Content-Type: application/json; charset=utf-8']);
		$result = json_decode($data, true);
		if($result['Code']=='1009'){
			return $result['Info'];
		}else{
			throw new Exception('获取登录地址失败['.$result['Code'].']'.$result['Message'].':'.$result['Info']);
		}
	}

	static private function wx_get_openid($orderno, $code){
		global $channel;
		$url = 'https://apipay.zhangyishou.com/api/get/userId';
		$params = [
			'Apptype' => '0',
			'Code' => $code,
			'MD5Sign' => '',
			'MerchantId' => '',
			'OrderNo' => $orderno,
			'RedirectUri' => '',
			'WayId' => $channel['appmchid'],
		];
		$data = get_curl($url, json_encode($params), 0, 0, 0, 0, 0, ['Content-Type: application/json; charset=utf-8']);
		$result = json_decode($data, true);
		if($result['Code']=='1009'){
			return $result['Info'];
		}else{
			throw new Exception('获取OpenId失败['.$result['Code'].']'.$result['Message'].':'.$result['Info']);
		}
	}

	static private function wx_get_paydata($orderno, $openid){
		global $channel;
		$url = 'https://apipay.zhangyishou.com/api/Order/byOrderNoPay';
		$params = [
			'openId' => $openid,
			'orderNo' => $orderno,
		];
		$data = get_curl($url, json_encode($params), 0, 0, 0, 0, 0, ['Content-Type: application/json; charset=utf-8']);
		$result = json_decode($data, true);
		if($result['Code']=='1009'){
			return $result['Info'];
		}else{
			throw new Exception('获取公众号支付参数失败['.$result['Code'].']'.$result['Message'].':'.$result['Info']);
		}
	}

	static public function wxjspay(){
		global $channel, $siteurl;

		try{
			$code_url = self::qrcode('wxpay');
		}catch(Exception $ex){
			return ['type'=>'error','msg'=>'微信支付下单失败！'.$ex->getMessage()];
		}
		$orderno = substr($code_url, strpos($code_url, 'OrderNo=')+8);

		if(!isset($_GET['code'])){
			$redirect_uri = $siteurl.'pay/wxjspay/'.TRADE_NO.'/';
			$jump_url = self::wx_get_code($orderno, $redirect_uri);
			$jump_url = str_replace('pay.html', 'skip.html', $jump_url);
			return ['type'=>'jump','url'=>$jump_url];
		}
		$code = trim($_GET['code']);
		$openid = self::wx_get_openid($orderno, $code);
		$paydata = self::wx_get_paydata($orderno, $openid);

		if($_GET['d']==1){
			$redirect_url='data.backurl';
		}else{
			$redirect_url='\'/pay/ok/'.TRADE_NO.'/\'';
		}
		return ['type'=>'page','page'=>'wxpay_jspay','data'=>['jsApiParameters'=>$paydata, 'redirect_url'=>$redirect_url]];
	}

	//QQ扫码支付
	static public function qqpay(){
		try{
			$code_url = self::qrcode('qqpay');
		}catch(Exception $ex){
			return ['type'=>'error','msg'=>'QQ钱包支付下单失败！'.$ex->getMessage()];
		}

		if(checkmobbileqq()){
			return ['type'=>'jump','url'=>$code_url];
		} elseif(checkmobile() && !isset($_GET['qrcode'])){
			return ['type'=>'qrcode','page'=>'qqpay_wap','url'=>$code_url];
		} else {
			return ['type'=>'qrcode','page'=>'qqpay_qrcode','url'=>$code_url];
		}
	}

	//云闪付扫码支付
	static public function bank(){
		try{
			$code_url = self::qrcode('bank');
		}catch(Exception $ex){
			return ['type'=>'error','msg'=>'云闪付下单失败！'.$ex->getMessage()];
		}

		return ['type'=>'qrcode','page'=>'bank_qrcode','url'=>$code_url];
	}

	//异步回调
	static public function notify(){
		global $channel, $order;

		require(PAY_ROOT."inc/config.php");
		$json = file_get_contents("php://input");
		$data = json_decode($json, true);
		if(!$data) return ['type'=>'html','data'=>'data error'];

		$signStr = $data['MerchantId'].$data['DownstreamOrderNo'].$pay_config['key'];
		$sign = md5($signStr);

		if($sign === $data['Signature']){
			if($data['OrderState'] == 1){
				$trade_no = $data['OrderNo'];
				if($data['DownstreamOrderNo'] == TRADE_NO && round($data['OrderMoney'],2)==round($order['realmoney'],2)){
					processNotify($order, $trade_no);
				}
				return ['type'=>'html','data'=>'OK'];
			}
		}
		return ['type'=>'html','data'=>'ERROR'];
	}

	//支付返回页面
	static public function return(){
		return ['type'=>'page','page'=>'return'];
	}

	//退款
	static public function refund($order){
		global $channel;
		if(empty($order))exit();

		require(PAY_ROOT."inc/config.php");
		$getwayurl = 'https://apipay.zhangyishou.com/api/OrderRefund/Refund';
		$params = [
			'MerchantId' => $pay_config['MerchantId'],
			'MerchantOrder' => $order['trade_no'],
			'RefundAmount' => $order['refundmoney'],
		];

		$signStr = "";
		foreach($params as $row){
			$signStr .= $row;
		}
		$signStr .= $pay_config['key'];
		$params['MD5Sign'] = md5($signStr);

		$data = get_curl($getwayurl, json_encode($params), 0, 0, 0, 0, 0, ['Content-Type: application/json; charset=utf-8']);

		$result = json_decode($data, true);

		if($result['Code']=='1009'){
			$result = ['code'=>0, 'trade_no'=>TRADE_NO, 'refund_fee'=>$order['refundmoney']];
		}else{
			$result = ['code'=>-1, 'msg'=>$result["Message"]];
		}
		return $result;
	}

	//转账
	static public function transfer($channel, $bizParam){
		global $clientip, $conf;
		if(empty($channel) || empty($bizParam))exit();

		if($bizParam['type'] == 'alipay'){
			$PayChannelId = '12002';
			$PaymentType = '3';
			if(is_numeric($bizParam['payee_account']) && substr($bizParam['payee_account'],0,4)=='2088')$AccountNumberType = '2';
			elseif(strpos($bizParam['payee_account'], '@')!==false || is_numeric($bizParam['payee_account']))$AccountNumberType = '1';
			else $AccountNumberType = '3';
		}else{
			$PayChannelId = '12001';
			$PaymentType = '2';
			$AccountNumberType = '1';
		}

		require(PLUGIN_ROOT.'zhangyishou/inc/config.php');
		$getwayurl = 'https://apipay.zhangyishou.com/api/Order/AddOrder';
		$params = [
			'MerchantId' => $pay_config['MerchantId'],
			'DownstreamOrderNo' => $bizParam['out_biz_no'],
			'OrderTime' => date('Y-m-d H:i:s'),
			'PayChannelId' => $PayChannelId,
			'AsynPath' => $conf['localurl'].'pay/transfernotify/'.$channel['id'].'/',
			'OrderMoney' => sprintf('%.2f', $bizParam['money']),
			'IPPath' => $clientip,
		];

		$signStr = "";
		foreach($params as $row){
			$signStr .= $row;
		}
		$signStr .= $pay_config['key'];
		$params += [
			'MD5Sign' => md5($signStr),
			'MerchantNo' => $pay_config['MerchantNo'],
			'PaymentType' => $PaymentType,
			'AccountNumber' => $bizParam['payee_account'],
			'AccountNumberType' => $AccountNumberType,
			'AccountName' => $bizParam['payee_real_name'],
			'PaymentRemark' => $bizParam['transfer_desc'],
			'ReasonPayment' => $bizParam['transfer_desc'],
			'Mproductdesc' => $bizParam['transfer_desc'],
		];

		$data = get_curl($getwayurl, json_encode($params), 0, 0, 0, 0, 0, ['Content-Type: application/json; charset=utf-8']);

		$result = json_decode($data, true);
		if($result['Code']=='1009'){
			$info = json_decode($result['Info'], true);
			$order_id = $info['alipay_fund_trans_uni_transfer_response']['out_biz_no'];
			return ['code'=>0, 'status'=>0, 'orderid'=>$order_id, 'paydate'=>date('Y-m-d H:i:s')];
		}else{
			return ['code'=>-1, 'msg'=>$result["Message"]?$result["Message"]:'返回数据解析失败'];
		}
	}

	//异步回调
	static public function transfernotify(){
		global $channel, $order;

		require(PAY_ROOT."inc/config.php");
		$json = file_get_contents("php://input");
		$data = json_decode($json, true);
		if(!$data) return ['type'=>'html','data'=>'data error'];

		$signStr = $data['MerchantId'].$data['DownstreamOrderNo'].$pay_config['key'];
		$sign = md5($signStr);

		if($sign === $data['Signature']){
			$errmsg = null;
			if($data['OrderState'] == '1'){
				$status = 1;
			}else{
				$status = 2;
				$errmsg = $data['Remark'];
			}
			processTransfer($data['DownstreamOrderNo'], $status, $errmsg);
			return ['type'=>'html','data'=>'OK'];
		}
		else {
			return ['type'=>'html','data'=>'ERROR'];
		}
	}

	//余额查询
	static public function balance_query($channel, $bizParam){
		if(empty($channel))exit();

		require(PLUGIN_ROOT.'zhangyishou/inc/config.php');
		$getwayurl = 'https://apipay.zhangyishou.com/query/bookQuery';
		$params = [
			'userName' => $pay_config['MerchantId'],
			'merchantNo' => $pay_config['MerchantNo'],
		];
		$signStr = "";
		foreach($params as $row){
			$signStr .= $row;
		}
		$signStr .= $pay_config['key'];
		$params['MD5Sign'] = md5($signStr);

		$data = get_curl($getwayurl, json_encode($params), 0, 0, 0, 0, 0, ['Content-Type: application/json; charset=utf-8']);

		$result = json_decode($data, true);
		if($result['Code']=='1009'){
			return ['code'=>0, 'amount'=>$result['Info']];
		}else{
			return ['code'=>-1, 'msg'=>$result["Message"]?$result["Message"]:'返回数据解析失败'];
		}
	}

}