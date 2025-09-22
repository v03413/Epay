<?php
/* *
 * 彩虹易支付SDK服务类
 * 说明：
 * 包含发起支付、查询订单、回调验证等功能
 */

class EpayCore
{
    private $apiurl;
    private $pid;
    private $platform_public_key;
    private $merchant_private_key;
    
    private $sign_type = 'RSA';

    function __construct($config){
        $this->apiurl = $config['apiurl'];
        $this->pid = $config['pid'];
        $this->platform_public_key = $config['platform_public_key'];
        $this->merchant_private_key = $config['merchant_private_key'];
    }

    // 发起支付（页面跳转）
    public function pagePay($param_tmp, $button='正在跳转'){
        $requrl = $this->apiurl.'api/pay/submit';
        $param = $this->buildRequestParam($param_tmp);

        $html = '<form id="dopay" action="'.$requrl.'" method="post">';
        foreach ($param as $k=>$v) {
            $html.= '<input type="hidden" name="'.$k.'" value="'.$v.'"/>';
        }
        $html .= '<input type="submit" value="'.$button.'"></form><script>document.getElementById("dopay").submit();</script>';

        return $html;
    }

    // 发起支付（获取链接）
    public function getPayLink($param_tmp){
        $requrl = $this->apiurl.'api/pay/submit';
        $param = $this->buildRequestParam($param_tmp);
        $url = $requrl.'?'.http_build_query($param);
        return $url;
    }

    // 发起支付（API接口）
    public function apiPay($params){
        return $this->execute('api/pay/create', $params);
    }

    // 发起API请求
    public function execute($path, $params){
        $path = ltrim($path, '/');
        $requrl = $this->apiurl.$path;
        $param = $this->buildRequestParam($params);
        $isMultipart = false;
        foreach ($param as $v) {
            if($v instanceof \CURLFile){
                $isMultipart = true;
                break;
            }
        }
        $response = $this->getHttpResponse($requrl, $isMultipart ? $param : http_build_query($param));
        $arr = json_decode($response, true);
        if($arr && $arr['code'] == 0){
            if(!$this->verify($arr)){
                throw new \Exception('返回数据验签失败');
            }
            return $arr;
        }else{
            throw new \Exception($arr ? $arr['msg'] : '请求失败');
        }
    }

    // 回调验证
    public function verify($arr){
        if(empty($arr) || empty($arr['sign'])) return false;

        if(empty($arr['timestamp']) || abs(time() - $arr['timestamp']) > 300) return false;

        $sign = $arr['sign'];
        
        return $this->rsaPublicVerify($this->getSignContent($arr), $sign);
    }

    // 查询订单支付状态
    public function orderStatus($trade_no){
        $result = $this->queryOrder($trade_no);
        if($result && $result['status']==1){
            return true;
        }else{
            return false;
        }
    }

    // 查询订单
    public function queryOrder($trade_no){
        $params = [
            'trade_no' => $trade_no,
        ];
        return $this->execute('api/pay/query', $params);
    }

    // 订单退款
    public function refund($out_refund_no, $trade_no, $money){
        $params = [
            'trade_no' => $trade_no,
            'money' => $money,
            'out_refund_no' => $out_refund_no,
        ];
        return $this->execute('api/pay/refund', $params);
    }

    // 订单退款查询
    public function refundquery($out_refund_no){
        $params = [
            'out_refund_no' => $out_refund_no,
        ];
        return $this->execute('api/pay/refundquery', $params);
    }

    private function buildRequestParam($params){
        $params['pid'] = $this->pid;
        $params['timestamp'] = time().'';
        $mysign = $this->getSign($params);
        $params['sign'] = $mysign;
        $params['sign_type'] = $this->sign_type;
        return $params;
    }

    // 生成签名
    private function getSign($params){
        return $this->rsaPrivateSign($this->getSignContent($params));
    }

    // 获取待签名字符串
    private function getSignContent($params){
        ksort($params);
        $signstr = '';
        foreach ($params as $k => $v) {
            if($v instanceof \CURLFile || is_array($v) || $this->isEmpty($v) || $k == 'sign' || $k == 'sign_type') continue;
            $signstr .= '&' . $k . '=' . $v;
        }
        $signstr = substr($signstr, 1);
        return $signstr;
    }

    private function isEmpty($value)
    {
        return $value === null || trim($value) === '';
    }

    // 商户私钥签名
    private function rsaPrivateSign($data){
        $key = "-----BEGIN PRIVATE KEY-----\n" .
            wordwrap($this->merchant_private_key, 64, "\n", true) .
            "\n-----END PRIVATE KEY-----";
        $privatekey = openssl_get_privatekey($key);
        if(!$privatekey){
            throw new \Exception('签名失败，商户私钥错误');
        }
        openssl_sign($data, $sign, $privatekey, OPENSSL_ALGO_SHA256);
        return base64_encode($sign);
    }

    // 平台公钥验签
    private function rsaPublicVerify($data, $sign){
        $key = "-----BEGIN PUBLIC KEY-----\n" .
            wordwrap($this->platform_public_key, 64, "\n", true) .
            "\n-----END PUBLIC KEY-----";
        $publickey = openssl_get_publickey($key);
        if (!$publickey) {
            throw new \Exception("验签失败，平台公钥错误");
        }
        $result = openssl_verify($data, base64_decode($sign), $publickey, OPENSSL_ALGO_SHA256);
        return $result === 1;
    }

    // 请求外部资源
    private function getHttpResponse($url, $post = false, $timeout = 10){
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        $httpheader[] = "Accept: */*";
        $httpheader[] = "Accept-Language: zh-CN,zh;q=0.8";
        $httpheader[] = "Connection: close";
        curl_setopt($ch, CURLOPT_HTTPHEADER, $httpheader);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        if($post){
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
        }
        $response = curl_exec($ch);
        curl_close($ch);
        return $response;
    }
}
