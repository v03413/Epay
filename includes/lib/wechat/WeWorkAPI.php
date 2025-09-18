<?php
namespace lib\wechat;

use Exception;

class WeWorkAPI
{
    private $wid;
    private $accessToken;

    public function __construct($id)
    {
        $this->wid = $id;
    }

    public function getAccessToken($force = false)
    {
        global $DB;
        if(!empty($this->accessToken)) return $this->accessToken;
        $DB->beginTransaction();
        try{
            $row = $DB->getRow("SELECT * FROM pre_wework WHERE id='{$this->wid}' LIMIT 1 FOR UPDATE");
            if(!$row) throw new Exception('当前企业微信不存在');
            if($row['access_token'] && strtotime($row['expiretime']) - 200 >= time() && !$force){
                $DB->rollback();
                $this->accessToken = $row['access_token'];
                return $this->accessToken;
            }
            $corpId = $row['appid'];
            $secret = $row['appsecret'];
            $url = "https://qyapi.weixin.qq.com/cgi-bin/gettoken?corpid=".$corpId."&corpsecret=".$secret;
            $output = get_curl($url);
            $res = json_decode($output, true);
            if (isset($res['access_token'])) {
                $this->accessToken = $res['access_token'];
                $expire_time = time() + $res['expires_in'];
                $DB->exec("UPDATE pre_wework SET access_token=:access_token,updatetime=NOW(),expiretime=:expiretime WHERE id=:id", [':access_token'=>$this->accessToken, ':expiretime'=>date("Y-m-d H:i:s", $expire_time), ':id'=>$this->wid]);
            }elseif(isset($res['errmsg'])){
                throw new Exception('AccessToken获取失败：'.$res['errmsg']);
            }else{
                throw new Exception('AccessToken获取失败');
            }
            $DB->commit();
            return $this->accessToken;
        }catch(Exception $e){
            $DB->rollback();
            throw $e;
        }
    }

    //获取客服帐号列表
    public function getKFList()
    {
        $accessToken = $this->getAccessToken();
        $url = 'https://qyapi.weixin.qq.com/cgi-bin/kf/account/list?access_token='.$accessToken;
        $post = ['offset'=>0, 'limit'=>100];
        $response = get_curl($url, json_encode($post));
        $result = json_decode($response, true);
        if ($result['errcode'] == 0) {
            return $result['account_list'];
        }else{
            throw new Exception('客服帐号列表获取失败：'.$result['errmsg']);
        }
    }

    //获取微信客服链接
    public function getKFURL($kfid, $scene)
    {
        $accessToken = $this->getAccessToken();
        $url = 'https://qyapi.weixin.qq.com/cgi-bin/kf/add_contact_way?access_token='.$accessToken;
        $post = ['open_kfid'=>$kfid, 'scene'=>$scene];
        $response = get_curl($url, json_encode($post));
        $result = json_decode($response, true);
        if ($result['errcode'] == 0 && $result['url']) {
            return $result['url'];
        }else{
            throw new Exception('微信客服链接获取失败：'.$result['errmsg']);
        }
    }

    //读取消息
    public function syncMsg($kfid, $token, $cursor = '')
    {
        $accessToken = $this->getAccessToken();
        $url = 'https://qyapi.weixin.qq.com/cgi-bin/kf/sync_msg?access_token='.$accessToken;
        $post = ['cursor'=>$cursor, 'token'=>$token, 'open_kfid'=>$kfid];
        $response = get_curl($url, json_encode($post));
        $result = json_decode($response, true);
        if ($result['errcode'] == 0) {
            return $result;
        }else{
            throw new Exception('客服消息列表获取失败：'.$result['errmsg']);
        }
    }

    //加锁获取最新消息
    public function lockGetMsg($kfid, $token)
    {
        global $DB;
        $this->getAccessToken();
        $DB->beginTransaction();
        $wxkfaccount = $DB->getRow("SELECT `id`,`cursor` FROM pre_wxkfaccount WHERE openkfid=:openkfid LIMIT 1 FOR UPDATE", [':openkfid'=>$kfid]);
        $cursor = $wxkfaccount['cursor'];
        try{
            $result = $this->syncMsg($kfid, $token, $cursor?$cursor:'');
        }catch(Exception $e){
            $DB->rollBack();
            throw $e;
        }
        $cursor = $result['next_cursor'];
        $DB->update('wxkfaccount', ['cursor'=>$cursor], ['id'=>$wxkfaccount['id']]);
        $DB->commit();
        return $result['msg_list'];
    }

    //发送消息
    public function sendMsg($touser, $kfid, $msgtype, $msgparam)
    {
        $accessToken = $this->getAccessToken();
        $url = 'https://qyapi.weixin.qq.com/cgi-bin/kf/send_msg?access_token='.$accessToken;
        $post = ['touser'=>$touser, 'open_kfid'=>$kfid, 'msgtype'=>$msgtype];
        $post[$msgtype] = $msgparam;
        $response = get_curl($url, json_encode($post));
        $result = json_decode($response, true);
        if ($result['errcode'] == 0) {
            return $result['msgid'];
        }else{
            throw new Exception('发送消息失败：'.$result['errmsg']);
        }
    }

    //发送文本消息
    public function sendTextMsg($touser, $kfid, $content)
    {
        $param = ['content'=>$content];
        return $this->sendMsg($touser, $kfid, 'text', $param);
    }

    //发送菜单消息
    public function sendMenuMsg($touser, $kfid, $head_content, $list, $tail_content = '')
    {
        $param = ['head_content'=>$head_content, 'list'=>$list];
        if($tail_content) $param['tail_content'] = $tail_content;
        return $this->sendMsg($touser, $kfid, 'msgmenu', $param);
    }

    //发送欢迎消息
    public function sendWelcomeMsg($code, $msgtype, $msgparam)
    {
        $accessToken = $this->getAccessToken();
        $url = 'https://qyapi.weixin.qq.com/cgi-bin/kf/send_msg_on_event?access_token='.$accessToken;
        $post = ['code'=>$code, 'msgtype'=>$msgtype];
        $post[$msgtype] = $msgparam;
        $response = get_curl($url, json_encode($post));
        $result = json_decode($response, true);
        if ($result['errcode'] == 0) {
            return $result['msgid'];
        }else{
            throw new Exception('发送消息失败：'.$result['errmsg']);
        }
    }

    //发送欢迎文本消息
    public function sendWelcomeTextMsg($code, $content)
    {
        $param = ['content'=>$content];
        return $this->sendWelcomeMsg($code, 'text', $param);
    }

    //发送欢迎菜单消息
    public function sendWelcomeMenuMsg($code, $head_content, $list, $tail_content = '')
    {
        $param = ['head_content'=>$head_content, 'list'=>$list];
        if($tail_content) $param['tail_content'] = $tail_content;
        return $this->sendWelcomeMsg($code, 'msgmenu', $param);
    }
    
    public function connect($appid, $callback, $agentid){
        $url = 'https://open.weixin.qq.com/connect/oauth2/authorize';
        $param = [
            "appid" => $appid,
            "redirect_uri" => $callback,
            "response_type" => "code",
            "scope" => "snsapi_base",
            "agentid" => $agentid
        ];

        $url .= '?'.http_build_query($param)."#wechat_redirect";
        header("Location: ".$url);
        exit;
    }

    public function get_userinfo($code){
        $accessToken = $this->getAccessToken();
        $url = 'https://qyapi.weixin.qq.com/cgi-bin/auth/getuserinfo';
        $param = [
            "access_token" => $accessToken,
            "code" => $code
        ];

        $url .= '?'.http_build_query($param);
        $response = get_curl($url);
        $arr = json_decode($response, true);
        if(isset($arr['errcode']) && $arr['errcode'] == 0){
            return $arr;
        }else{
            throw new Exception('获取用户信息失败 ['.$arr['errcode'].']'.$arr['errmsg']);
        }
    }

    public function send_message($touser, $agentid, $msgtype, $msgparam){
        $accessToken = $this->getAccessToken();
        $url = 'https://qyapi.weixin.qq.com/cgi-bin/message/send?access_token='.$accessToken;
        $post = [
            'touser' => $touser,
            'agentid' => intval($agentid),
            'msgtype' => $msgtype
        ];
        $post[$msgtype] = $msgparam;
        $response = get_curl($url, json_encode($post));
        $result = json_decode($response, true);
        if ($result['errcode'] == 0) {
            return true;
        }else{
            throw new Exception('发送消息失败：'.$result['errmsg']);
        }
    }
}