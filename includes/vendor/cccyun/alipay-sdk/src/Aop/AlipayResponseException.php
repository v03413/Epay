<?php

namespace Alipay\Aop;

class AlipayResponseException extends \Exception
{
    private $res = [];
    private $retCode;
    private $errCode;

    /**
     * @param array $res
     */
    public function __construct($res)
    {
        $this->res = $res;
        $this->retCode = $res['code'];
        if (isset($res['sub_msg'])) {
            $this->errCode = $res['sub_code'];
            $message = '['.$res['sub_code'].']'.$res['sub_msg'];
        } elseif (isset($res['msg'])) {
            $message = '['.$res['code'].']'.$res['msg'];
        } else {
            $message = '未知错误';
        }
        parent::__construct($message);
    }

    public function getRetCode()
    {
        return $this->retCode;
    }

    public function getErrCode()
    {
        return $this->errCode;
    }

    public function getResponse(): array
    {
        return $this->res;
    }
}