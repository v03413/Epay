<?php
namespace lib;

use Exception;

class ApiHelper
{
    //无需签名验证的接口
    private static $exclude_list = [
        'pay/submit',
        'pay/create',
        'complain/image'
    ];

    public static function load_api($s){
        if(preg_match('/^(.[a-zA-Z0-9\_]+)\/(.[a-zA-Z0-9\_]+)$/',$s, $matchs)){
            $class = $matchs[1];
            $func = $matchs[2];
            $classname = '\\lib\\api\\'.ucfirst($class).'';
            if (class_exists($classname) && method_exists($classname, $func)) {
                try{
                    if(in_array($class.'/'.$func, self::$exclude_list)){
                        $classname::$func();
                    }else{
                        self::verify();
                        $result = $classname::$func();
                        $result['timestamp'] = time().'';
                        $result['sign_type'] = 'RSA';
                        $result['sign'] = \lib\Payment::makeSign($result, null);
                        echojson($result);
                    }
                }catch(Exception $e){
                    $code = $e->getCode();
                    echojsonmsg($e->getMessage(), $code != 0 ? $code : -1);
                }
            }else{
                echojsonmsg('接口方法不存在', -5);
            }
        }else{
            echojsonmsg('URL Error!', -5);
        }
    }

    private static function verify(){
        global $DB, $conf, $userrow, $queryArr;

        if(isset($_POST['pid'])){
            $queryArr=$_POST;
        }else{
            throw new Exception('未传入任何参数', -4);
        }

        $pid=intval($queryArr['pid']);
        if(empty($pid))throw new Exception('商户ID不能为空');
        $userrow=$DB->getRow("SELECT `uid`,`gid`,`key`,`money`,`channelinfo`,`keytype`,`publickey`,`status`,`pay`,`settle`,`refund`,`transfer` FROM `pre_user` WHERE `uid`='{$pid}' LIMIT 1");
        if(!$userrow)throw new Exception('商户不存在！');
        if($userrow['status']==0)throw new Exception('商户已被封禁');

        try{
            self::api_verify($userrow, $queryArr);
        }catch(Exception $e){
            throw new Exception($e->getMessage(), -3);
        }
    }

    //API签名校验
    static public function api_verify($userrow, $queryArr, $forceRsa = false){
        if($forceRsa && $queryArr['sign_type'] != 'RSA')throw new Exception('该接口只能使用RSA签名类型');
        if($userrow['keytype'] == 1 && $queryArr['sign_type'] != 'RSA')throw new Exception('该商户只能使用RSA签名类型');
        if(defined('API_INIT') || $forceRsa){
            if(empty($queryArr['timestamp']))throw new Exception('时间戳(timestamp)字段不能为空');
            if(abs(time() - $queryArr['timestamp']) > 300)throw new Exception('时间戳字段不正确，请检查服务器时间');
        }
        $sign_type = $queryArr['sign_type'] ? $queryArr['sign_type'] : 'MD5';
        if(!\lib\Payment::verifySign($queryArr, $userrow['key'], $userrow['publickey']))throw new Exception($sign_type.'签名校验失败');
    }
}