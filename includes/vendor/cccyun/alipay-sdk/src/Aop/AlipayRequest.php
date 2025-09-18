<?php

namespace Alipay\Aop;

class AlipayRequest
{
    protected $notifyUrl;

    protected $returnUrl;

    protected $terminalType;

    protected $terminalInfo;

    protected $prodCode;

    protected $authToken;

    protected $appAuthToken;

    protected $bizContent;

    protected $apiMethodName;

    public function setOtherParams($params = [])
    {
        foreach ($params as $key => $value) {
            $this->{$key} = $value;
        }
    }

    /**
     * 获取用于发起请求的“时间戳”.
     *
     * @return string
     */
    public static function getTimestamp(): string
    {
        return date('Y-m-d H:i:s');
    }

    /**
     * 根据类名获取 API 方法名.
     *
     * @return string
     */
    public function getApiMethodName(): string
    {
        return $this->apiMethodName;
    }

    public function setApiMethodName($apiMethodName): AlipayRequest
    {
        $this->apiMethodName = $apiMethodName;

        return $this;
    }

    public function getNotifyUrl()
    {
        return $this->notifyUrl;
    }

    public function setNotifyUrl($notifyUrl): AlipayRequest
    {
        $this->notifyUrl = $notifyUrl;

        return $this;
    }

    public function getReturnUrl()
    {
        return $this->returnUrl;
    }

    public function setReturnUrl($returnUrl): AlipayRequest
    {
        $this->returnUrl = $returnUrl;

        return $this;
    }

    public function getTerminalType()
    {
        return $this->terminalType;
    }

    public function setTerminalType($terminalType): AlipayRequest
    {
        $this->terminalType = $terminalType;

        return $this;
    }

    public function getTerminalInfo()
    {
        return $this->terminalInfo;
    }

    public function setTerminalInfo($terminalInfo): AlipayRequest
    {
        $this->terminalInfo = $terminalInfo;

        return $this;
    }

    public function getProdCode()
    {
        return $this->prodCode;
    }

    public function setProdCode($prodCode): AlipayRequest
    {
        $this->prodCode = $prodCode;

        return $this;
    }

    public function getAuthToken()
    {
        return $this->authToken;
    }

    public function setAuthToken($authToken): AlipayRequest
    {
        $this->authToken = $authToken;

        return $this;
    }

    public function getAppAuthToken()
    {
        return $this->appAuthToken;
    }

    public function setAppAuthToken($appAuthToken): AlipayRequest
    {
        $this->appAuthToken = $appAuthToken;

        return $this;
    }

    public function getBizContent()
    {
        if (is_array($this->bizContent)) {
            return json_encode($this->bizContent, JSON_UNESCAPED_UNICODE);
        }
        return $this->bizContent;
    }

    public function setBizContent($bizContent = []): AlipayRequest
    {
        $this->bizContent = $bizContent;

        return $this;
    }

}
