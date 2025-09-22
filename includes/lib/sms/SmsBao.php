<?php
namespace lib\sms;

class SmsBao {
    private $user;
    private $pass;

    function __construct($user, $pass){
        $this->user = $user;
        $this->pass = $pass;
    }

    public function send($phone, $param, $moban, $sign){
        if(empty($this->user)||empty($this->pass))return false;
        $statusStr = array(
            "0" => "短信发送成功",
            "-1" => "参数不全",
            "-2" => "服务器空间不支持",
            "30" => "密码错误",
            "40" => "账号不存在",
            "41" => "余额不足",
            "42" => "帐户已过期",
            "43" => "IP地址限制",
            "50" => "内容含有敏感词"
        );
        foreach($param as $k=>$v){
            $moban = str_replace('{'.$k.'}',$v,$moban);
        }
        $content = '【'.$sign.'】'.$moban;
        $sendurl = "http://api.smsbao.com/sms?u=".$this->user."&p=".md5($this->pass)."&m=".$phone."&c=".urlencode($content);
        $result = get_curl($sendurl) ;
        if ($result == '0'){
            return true;
        }else{
            return isset($statusStr[$result])?$statusStr[$result]:('CODE:'.$result);
        }
    }
}
