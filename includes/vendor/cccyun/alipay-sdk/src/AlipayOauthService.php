<?php

namespace Alipay;

use Exception;

/**
 * 支付宝快捷登录服务类
 * @see https://opendocs.alipay.com/open/repo-01480o
 */
class AlipayOauthService extends AlipayService
{
    /**
     * @param array $config 支付宝配置信息
     */
    public function __construct(array $config)
    {
        if(isset($config['app_auth_token'])) unset($config['app_auth_token']);
        parent::__construct($config);
    }

    /**
     * 跳转支付宝授权页面
     * @param string $redirect_uri 回调地址
     * @param string $state
     * @param string $scope 授权范围(auth_base,auth_user)
     * @param bool $is_get_url 是否只返回url
     * @return void|string
     */
    public function oauth(string $redirect_uri, $state = null, $scope = 'auth_base', bool $is_get_url = false)
    {
        $param = [
            'app_id' => $this->appId,
            'scope' => $scope,
            'redirect_uri' => $redirect_uri,
        ];
        if($state) $param['state'] = $state;

        $url = 'https://openauth.alipay.com/oauth2/publicAppAuthorize.htm?'.http_build_query($param);

        if ($is_get_url) {
            return $url;
        }

        header("Location: $url");
        exit();
    }

	/**
	 * 换取授权访问令牌
	 * @param string $code 授权码或刷新令牌
	 * @param string $grant_type 授权方式(authorization_code,refresh_token)
	 * @return mixed {"user_id":"支付宝用户的唯一标识","open_id":"支付宝用户的唯一标识","access_token":"访问令牌","expires_in":"3600","refresh_token":"刷新令牌","re_expires_in":"3600"}
	 * @throws Exception
	 */
    public function getToken(string $code, string $grant_type = 'authorization_code')
    {
        $apiName = 'alipay.system.oauth.token';
        $params = [];
        $params['grant_type'] = $grant_type;
        if($grant_type == 'refresh_token'){
            $params['refresh_token'] = $code;
        }else{
            $params['code'] = $code;
        }
        return $this->aopExecute($apiName, null, $params);
    }

	/**
	 * 支付宝会员授权信息查询
	 * @param string $accessToken 用户授权令牌
	 * @return mixed {"code":"10000","msg":"Success","user_id":"支付宝用户的userId","avatar":"用户头像地址","city":"市名称","nick_name":"用户昵称","province":"省份名称","gender":"性别MF"}
	 * @throws Exception
	 */
    public function userinfo(string $accessToken)
    {
        $apiName = 'alipay.user.info.share';
        $params = [
            'auth_token' => $accessToken
        ];

        return $this->aopExecute($apiName, null, $params);
    }


    /**
     * 跳转支付宝第三方应用授权页面
     * @param string $redirect_uri 回调地址
     * @param $state
     * @param bool $is_get_url 是否只返回url
     * @return void|string
     */
    public function appOauth(string $redirect_uri, $state = null, bool $is_get_url = false)
    {
        $param = [
            'app_id' => $this->appId,
            'redirect_uri' => $redirect_uri,
        ];
        if($state) $param['state'] = $state;

        $url = 'https://openauth.alipay.com/oauth2/appToAppAuth.htm?'.http_build_query($param);

        if ($is_get_url) {
            return $url;
        }

        header("Location: $url");
        exit();
    }

    /**
     * 跳转支付宝指定应用授权页面
     * @param string $redirect_uri 回调地址
     * @param array $app_types 对商家应用的限制类型
     * @param $state
     * @return array [PC端url, APP端url]
     */
    public function appOauthAssign(string $redirect_uri, array $app_types, $state = null): array
    {
        $param = [
            'platformCode' => 'O',
            'taskType' => 'INTERFACE_AUTH',
            'agentOpParam' => [
                'redirectUri' => $redirect_uri,
                'appTypes' => $app_types,
                'isvAppId' => $this->appId,
                'state' => $state
            ],
        ];

        $biz_data = json_encode($param, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
        $pc_url = 'https://b.alipay.com/page/message/tasksDetail?bizData='.rawurlencode($biz_data);
        $app_url = 'alipays://platformapi/startapp?appId=2021003130652097&page=pages%2Fauthorize%2Findex%3FbizData%3D'.rawurlencode($biz_data);

        return [$pc_url, $app_url];
    }

	/**
	 * 换取授权访问令牌
	 * @param string $code 授权码或刷新令牌
	 * @param string $grant_type 授权方式(authorization_code,refresh_token)
	 * @return mixed {"user_id":"授权商户的user_id","auth_app_id":"授权商户的appid","app_auth_token":"应用授权令牌","app_refresh_token":"刷新令牌","re_expires_in":"3600"}
	 * @throws Exception
	 */
    public function getAppToken(string $code, string $grant_type = 'authorization_code')
    {
        $apiName = 'alipay.open.auth.token.app';
        $bizContent = [
            'grant_type' => $grant_type,
        ];
        if($grant_type == 'refresh_token'){
            $bizContent['refresh_token'] = $code;
        }else{
            $bizContent['code'] = $code;
        }
        return $this->aopExecute($apiName, $bizContent);
    }

	/**
	 * 查询授权商家信息
	 * @param string $appAuthToken 应用授权令牌
	 * @return mixed {"user_id":"授权商户的user_id","auth_app_id":"授权商户的appid","expires_in":31536000,"auth_methods":[],"auth_start":"授权生效时间","auth_end":"授权失效时间","status":"valid/invalid","is_by_app_auth":true}
	 * @throws Exception
	 */
    public function appQuery(string $appAuthToken)
    {
        $apiName = 'alipay.open.auth.token.app.query';
        $bizContent = [
            'app_auth_token' => $appAuthToken,
        ];
        return $this->aopExecute($apiName, $bizContent);
    }

    public function decryptMobile(array $response, string $key)
    {
        if(is_string($response['response'])){
            /*if(!$this->client->rsaPubilcVerify('"'.$response['response'].'"', $response['sign'])){
                throw new Exception('手机号码数据验签失败');
            }*/
            $data = $this->client->aesDecrypt($response['response'], $key);
            if(!$data) {
                throw new Exception('手机号码数据解密失败');
            }
            $result = json_decode($data, true);
            if($result['code'] == '10000'){
                return $result['mobile'];
            }elseif(isset($result['subMsg'])){
                throw new Exception($result['subMsg']);
            }else{
                throw new Exception('手机号码数据解密失败 '.$result['msg']);
            }
        }elseif(isset($response['response']['subCode']) && isset($response['response']['subMsg'])){
            throw new Exception('['.$response['response']['subCode'].']'.$response['response']['subMsg']);
        }else{
            throw new Exception('手机号码加密数据错误');
        }
    }
}