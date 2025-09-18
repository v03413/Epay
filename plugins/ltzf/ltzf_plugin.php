<?php

class ltzf_plugin
{
	static public $info = [
		'name'        => 'ltzf', //支付插件英文名称，需和目录名称一致，不能有重复
		'showname'    => '蓝兔支付', //支付插件显示名称
		'author'      => '蓝兔支付', //支付插件作者
		'link'        => 'https://www.ltzf.cn/', //支付插件作者链接
		'types'       => ['alipay','wxpay'], //支付插件支持的支付方式，可选的有alipay,qqpay,wxpay,bank
		'inputs' => [ //支付插件要求传入的参数以及参数显示名称，可选的有appid,appkey,appsecret,appurl,appmchid
			'appid' => [
				'name' => '商户号',
				'type' => 'input',
				'note' => '',
			],
			'appkey' => [
				'name' => '商户密钥',
				'type' => 'input',
				'note' => '',
			],
		],
		'select_wxpay' => [
			'1' => '扫码支付',
			'2' => 'H5支付',
			'3' => '公众号支付',
		],
		'select_alipay' => [
			'1' => '扫码支付',
			'2' => 'H5支付',
		],
		'select' => null,
		'note' => '', //支付密钥填写说明
		'bindwxmp' => false, //是否支持绑定微信公众号
		'bindwxa' => false, //是否支持绑定微信小程序
	];

	const API_URL = 'https://api.ltzf.cn';

	static public function submit(){
		global $siteurl, $channel, $order, $sitename;

		if($order['typename']=='alipay'){
			return ['type'=>'jump','url'=>'/pay/alipay/'.TRADE_NO.'/'];
		}elseif($order['typename']=='wxpay'){
			return ['type'=>'jump','url'=>'/pay/wxpay/'.TRADE_NO.'/'];
		}
	}

	static public function mapi(){
		global $siteurl, $channel, $order, $device, $mdevice;

		if($order['typename']=='alipay'){
			return self::alipay();
		}elseif($order['typename']=='wxpay'){
			return self::wxpay();
		}
	}

	static private function make_sign($param, $name, $key){
		ksort($param);
		$signstr = '';
	
		foreach($param as $k => $v){
			if(in_array($k, $name) && $v!==null && $v!==''){
				$signstr .= $k.'='.$v.'&';
			}
		}
		$signstr .= 'key='.$key;
		$sign = strtoupper(md5($signstr));
		return $sign;
	}

	//通用创建订单
	static private function addOrder($path){
		global $siteurl, $channel, $order, $ordername, $conf, $clientip;

		$param = [
			'mch_id' => $channel['appid'],
			'out_trade_no' => TRADE_NO,
			'total_fee' => $order['realmoney'],
			'body' => $ordername,
			'timestamp' => time(),
			'notify_url' => $conf['localurl'].'pay/notify/'.TRADE_NO.'/',
			'return_url' => $siteurl.'pay/ok/'.TRADE_NO.'/',
			'quit_url' => $siteurl.'pay/error/'.TRADE_NO.'/',
		];
		$sign_param = ['mch_id','out_trade_no','total_fee','body','timestamp','notify_url'];
		$param['sign'] = self::make_sign($param, $sign_param, $channel['appkey']);

		$response = get_curl(self::API_URL.$path, http_build_query($param));
		$result = json_decode($response, true);

		if(isset($result["code"]) && $result["code"]==0){
			return $result['data'];
		}else{
			throw new Exception($result["msg"]?$result["msg"]:'返回数据解析失败');
		}
	}

	//支付宝扫码支付
	static public function alipay(){
		global $channel, $device;
		if(in_array('2',$channel['apptype']) && (checkmobile() || $device=='mobile')){
			try{
				$result = self::addOrder('/api/alipay/h5');
				$h5_url = $result['h5_url'];
			}catch(Exception $ex){
				return ['type'=>'error','msg'=>'支付宝下单失败！'.$ex->getMessage()];
			}
			return ['type'=>'jump','url'=>$h5_url];
		}else{
			try{
				$code_img_url = self::addOrder('/api/alipay/native');
			}catch(Exception $ex){
				return ['type'=>'error','msg'=>'支付宝下单失败！'.$ex->getMessage()];
			}
			$code_url = 'data:image/png;base64,'.base64_encode(get_curl($code_img_url));
			return ['type'=>'qrcode','page'=>'alipay_qrcode','url'=>$code_url];
		}
	}

	//微信扫码支付
	static public function wxpay(){
		global $channel, $device, $mdevice;
		if(in_array('3',$channel['apptype']) && (checkwechat() || $mdevice=='wechat')){
			try{
				$result = self::addOrder('/api/wxpay/jsapi_convenient');
				$jump_url = $result['order_url'];
			}catch(Exception $ex){
				return ['type'=>'error','msg'=>'微信支付下单失败！'.$ex->getMessage()];
			}
			return ['type'=>'jump','url'=>$jump_url];
		}
		elseif(in_array('2',$channel['apptype']) && (checkmobile() && !checkwechat() || $device=='mobile' && $mdevice!='wechat')){
			try{
				$jump_url = self::addOrder('/api/wxpay/jump_h5');
			}catch(Exception $ex){
				return ['type'=>'error','msg'=>'微信支付下单失败！'.$ex->getMessage()];
			}
			return ['type'=>'jump','url'=>$jump_url];
		}
		elseif(in_array('1',$channel['apptype'])){
			try{
				$result = self::addOrder('/api/wxpay/native');
				$code_url = $result['code_url'];
			}catch(Exception $ex){
				return ['type'=>'error','msg'=>'微信支付下单失败！'.$ex->getMessage()];
			}
		}
		elseif(in_array('3',$channel['apptype'])){
			try{
				$result = self::addOrder('/api/wxpay/jsapi_convenient');
				$code_url = $result['order_url'];
			}catch(Exception $ex){
				return ['type'=>'error','msg'=>'微信支付下单失败！'.$ex->getMessage()];
			}
		}
		if (checkmobile()) {
			return ['type'=>'qrcode','page'=>'wxpay_wap','url'=>$code_url];
		} else {
			return ['type'=>'qrcode','page'=>'wxpay_qrcode','url'=>$code_url];
		}
	}

	//异步回调
	static public function notify(){
		global $channel, $order;

		$arr = $_POST;
		$sign_param = ['code','timestamp','mch_id','order_no','out_trade_no','pay_no','total_fee'];
		$sign = self::make_sign($arr, $sign_param, $channel['appkey']);

		if($sign===$arr["sign"]){
			if($arr['code'] == '0'){
				$out_trade_no = $arr['out_trade_no'];
				$trade_no = $arr['order_no'];

				if ($out_trade_no == TRADE_NO) {
					processNotify($order, $trade_no, $arr['openid']);
				}
				return ['type'=>'html','data'=>'SUCCESS'];
			}else{
				return ['type'=>'html','data'=>'FAIL'];
			}
		}else{
			return ['type'=>'html','data'=>'FAIL'];
		}
	}

	//支付返回页面
	static public function return(){
		return ['type'=>'page','page'=>'return'];
	}

	//支付成功页面
	static public function ok(){
		return ['type'=>'page','page'=>'ok'];
	}

	//支付失败页面
	static public function error(){
		return ['type'=>'page','page'=>'error'];
	}

	//退款
	static public function refund($order){
		global $channel;
		if(empty($order))exit();

		if($order['type'] == 2){
			$path = '/api/wxpay/refund_order';
		}else{
			$path = '/api/alipay/refund_order';
		}

		$param = [
			'mch_id' => $channel['appid'],
			'out_trade_no' => $order['trade_no'],
			'out_refund_no' => $order['refund_no'],
			'timestamp' => time(),
			'refund_fee' => $order['refundmoney'],
		];
		$sign_param = ['mch_id','out_trade_no','out_refund_no','timestamp','refund_fee'];
		$param['sign'] = self::make_sign($param, $sign_param, $channel['appkey']);

		$response = get_curl(self::API_URL.$path, http_build_query($param));
		$result = json_decode($response, true);

		if(isset($result["code"]) && $result["code"]==0){
			return ['code'=>0, 'trade_no'=>$order['data']['out_trade_no'], 'refund_fee'=>$order['refundmoney']];
		}else{
			return ['code'=>-1, 'msg'=>$result["msg"]?$result["msg"]:'返回数据解析失败'];
		}
	}
}