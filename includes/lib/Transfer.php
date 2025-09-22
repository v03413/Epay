<?php
namespace lib;

use Exception;

class Transfer
{

    static public $payee_err_code = [ //收款方原因导致的失败编码
		'PAYEE_NOT_EXIST','PAYEE_ACCOUNT_STATUS_ERROR','CARD_BIN_ERROR','PAYEE_CARD_INFO_ERROR','PERM_AML_NOT_REALNAME_REV','PAYEE_USER_INFO_ERROR','PAYEE_ACC_OCUPIED','PERMIT_NON_BANK_LIMIT_PAYEE','PAYEE_TRUSTEESHIP_ACC_OVER_LIMIT','PAYEE_ACCOUNT_NOT_EXSIT','PAYEE_USERINFO_STATUS_ERROR','TRUSTEESHIP_RECIEVE_QUOTA_LIMIT','EXCEED_LIMIT_UNRN_DM_AMOUNT','INVALID_CARDNO','RELEASE_USER_FORBBIDEN_RECIEVE','PAYEE_USER_TYPE_ERROR','PAYEE_NOT_RELNAME_CERTIFY','PERMIT_LIMIT_PAYEE',

		'OPENID_ERROR','NAME_MISMATCH','V2_ACCOUNT_SIMPLE_BAN','MONEY_LIMIT','EXCEED_PAYEE_ACCOUNT_LIMIT','PAYEE_ACCOUNT_ABNORMAL','APPID_OR_OPENID_ERR',

		'REALNAME_CHECK_ERROR','RE_USER_NAME_CHECK_ERROR','ERR_TJ_BLACK','USER_FROZEN','TRANSFER_FAIL','TRANSFER_FEE_LIMIT_ERROR',

		'ACCOUNT_FROZEN','REAL_NAME_CHECK_FAIL','NAME_NOT_CORRECT','OPENID_INVALID','TRANSFER_QUOTA_EXCEED','DAY_RECEIVED_QUOTA_EXCEED','MONTH_RECEIVED_QUOTA_EXCEED','DAY_RECEIVED_COUNT_EXCEED','ID_CARD_NOT_CORRECT','ACCOUNT_NOT_EXIST','TRANSFER_RISK','REALNAME_ACCOUNT_RECEIVED_QUOTA_EXCEED','RECEIVE_ACCOUNT_NOT_PERMMIT','PAYEE_ACCOUNT_ABNORMAL','BLOCK_B2C_USERLIMITAMOUNT_BSRULE_MONTH','BLOCK_B2C_USERLIMITAMOUNT_MONTH',
	];

    //通用转账
    //type alipay:支付宝,wxpay:微信,qqpay:QQ钱包,bank:银行卡
    public static function submit($type, $channel, $out_biz_no, $payee_account, $payee_real_name, $money, $desc = null){
        global $conf;

        $bizParam = [
            'type' => $type,
            'out_biz_no' => $out_biz_no,
            'payee_account' => $payee_account,
            'payee_real_name' => $payee_real_name,
            'money' => $money,
            'transfer_name' => $desc?$desc:$conf['transfer_name'],
            'transfer_desc' => $desc?$desc:$conf['transfer_desc'],
        ];
        return \lib\Plugin::call('transfer', $channel, $bizParam);
    }

    public static function add($uid, $type, $out_biz_no, $payee_account, $payee_real_name, $money, $desc = null, $bookid = null, $channelid = null){
        global $conf, $DB, $userrow, $siteurl;
        $biz_no = $out_biz_no;
        if(strlen($biz_no)!=19 || !is_numeric($biz_no)) $biz_no = date("YmdHis").rand(11111,99999);

        if($uid > 0){
            if($conf['transfer_minmoney']>0 && $money<$conf['transfer_minmoney']) return ['code'=>-1, 'msg'=>'单笔最小代付金额限制为'.$conf['transfer_minmoney'].'元'];
            if($conf['transfer_maxmoney']>0 && $money>$conf['transfer_maxmoney']) return ['code'=>-1, 'msg'=>'单笔最大代付金额限制为'.$conf['transfer_maxmoney'].'元'];
            if($conf['transfer_maxlimit']>0){
                $a_count = $DB->getColumn('SELECT count(*) FROM pre_transfer WHERE uid=:uid AND type=:type AND account=:account AND paytime>=:paytime', [':uid'=>$uid, ':type'=>$type, ':account'=>$payee_account, ':paytime'=>date('Y-m-d').' 00:00:00']);
                if($a_count >= $conf['transfer_maxlimit']){
                    return ['code'=>-1, 'msg'=>'您今天向该账号的转账次数已达到上限'];
                }
            }
        }
        
        if(!$channelid){
            if($type=='alipay'){
                $channelid = $conf['transfer_alipay'];
            }elseif($type=='wxpay'){
                $channelid = $conf['transfer_wxpay'];
            }elseif($type=='qqpay'){
                if (!is_numeric($payee_account) || strlen($payee_account)<6 || strlen($payee_account)>10) return ['code'=>-1, 'msg'=>'QQ号码格式错误'];
                $channelid = $conf['transfer_qqpay'];
            }elseif($type=='bank'){
                $channelid = $conf['transfer_bank'];
            }else{
                return ['code'=>-1, 'msg'=>'type参数错误'];
            }
            if(!$channelid) return ['code'=>-1, 'msg'=>'未开启此转账方式'];
        }
        if($channelid > 0){
            $channel = \lib\Channel::get($channelid, $userrow['channelinfo']);
            if(!$channel) return ['code'=>-1, 'msg'=>'当前支付通道信息不存在'];
        }

        if($uid > 0){
            if(class_exists('\\lib\\AlipaySATF\\AlipaySATF') && $conf['alipay_satf']==1 && ($type=='alipay' || $type=='bank' && $conf['transfer_alipay']==$conf['transfer_bank'])){
                if(!$bookid) $bookid = $DB->findColumn('satf_account_book', 'id', ['uid'=>$uid, 'status'=>1], 'money DESC');
                $satf = new \lib\AlipaySATF\AlipaySATF();
                $params = [
                    'out_biz_no' => $out_biz_no,
                    'account' => $payee_account,
                    'name' => $payee_real_name,
                    'money' => $money,
                    'remark' => $desc,
                ];
                $result = $satf->transfer($bookid, $type=='bank' ? 2 : 1, $params, $uid);
                return $result;
            }
        }

        $trans = $DB->find('transfer', '*', ['out_biz_no' => $out_biz_no, 'uid' => $uid]);
        if($trans) return ['code'=>-1, 'msg'=>'该交易号已存在，请更换交易号'];

        $DB->beginTransaction();
        $need_money = null;
        if($uid > 0){
            $userrow = $DB->getRow('SELECT * FROM pre_user WHERE uid=:uid FOR UPDATE', [':uid'=>$uid]);
            if($userrow['settle']==0){
                $DB->rollback();
                return ['code'=>-1, 'msg'=>'您的商户出现异常，无法使用代付功能'];
            }
            if($conf['settle_type']==1){
                $today=date("Y-m-d").' 00:00:00';
                $order_today=$DB->getColumn("SELECT SUM(realmoney) from pre_order where uid={$uid} and tid<>2 and status=1 and endtime>='$today'");
                if(!$order_today) $order_today = 0;
                $enable_money=round($userrow['money']-$order_today,2);
                if($enable_money<0)$enable_money=0;
            }else{
                $enable_money=$userrow['money'];
            }
            if(!$conf['transfer_rate'])$conf['transfer_rate'] = $conf['settle_rate'];
            $need_money = round($money + $money*$conf['transfer_rate']/100,2);
            if($need_money>$enable_money){
                $DB->rollback();
                return ['code'=>-1, 'msg'=>'需支付金额大于可转账余额'];
            }
        }
        
        if($channelid == -1){
            $result = ['code'=>0, 'status'=>3, 'orderid'=>null, 'biz_no'=>$biz_no, 'out_biz_no'=>$out_biz_no];
        }else{
            $result = self::submit($type, $channel, $biz_no, $payee_account, $payee_real_name, $money, $desc);
            $result['biz_no'] = $biz_no;
            $result['out_biz_no'] = $out_biz_no;
        }

        if($result['code']==0){
            $paytime = $result['status'] == 1 ? 'NOW()' : null;
            $data = ['biz_no'=>$biz_no, 'out_biz_no'=>$out_biz_no, 'uid'=>$uid, 'type'=>$type, 'channel'=>$channelid, 'account'=>$payee_account, 'username'=>$payee_real_name, 'money'=>$money, 'costmoney'=>$need_money??$money, 'addtime'=>'NOW()', 'paytime'=>$paytime, 'pay_order_no'=>$result['orderid'], 'status'=>$result['status'], 'desc'=>$desc];
            if(isset($result['wxpackage'])) $data['ext'] = $result['wxpackage'];
            $id = $DB->insert('transfer', $data);
            if($need_money>0 && $id!==false){
                changeUserMoney2($uid, $userrow['money'], $need_money, false, '代付', $biz_no);
                $result['cost_money'] = $need_money;
            }
            if($result['status'] == 1){
                $result['msg']='转账成功！转账单据号:'.$result['orderid'].' 支付时间:'.$result['paydate'];
            }elseif($result['status'] == 3){
                $result['msg']='提交成功！请等待管理员审核转账。';
            }elseif(isset($result['wxpackage'])){
                $jumpurl = $siteurl.'paypage/wxtrans.php?type=transfer&id='.$id;
                $result['msg']='提交成功！请在微信打开 '.$jumpurl.' 确认收款。转账单据号:'.$result['orderid'].' 支付时间:'.$result['paydate'];
                $result['jumpurl'] = $jumpurl;
            }else{
                $result['msg']='提交成功！转账处理中。转账单据号:'.$result['orderid'].' 支付时间:'.$result['paydate'];
            }
        }
        $DB->commit();
        return $result;
    }

    //转账状态刷新
    public static function status($biz_no){
        global $DB;
        $order = $DB->find('transfer', '*', ['biz_no' => $biz_no]);
        if(!$order) return ['code'=>-1, 'msg'=>'付款记录不存在'];
        
        $channelinfo = null;
        if($order['uid'] > 0){
            $channelinfo = $DB->findColumn('user', 'channelinfo', ['uid'=>$order['uid']]);
        }
        $channel = \lib\Channel::get($order['channel'], $channelinfo);
        if(!$channel) return ['code'=>-1, 'msg'=>'支付通道不存在'];

        $result = self::query($order['type'], $channel, $biz_no, $order['pay_order_no']);
        if($result['code'] == 0){
            if($result['status'] == 2){
                if($order['status'] == 0 || $order['status'] == 3){
                    $resCount = $DB->update('transfer', ['status'=>2, 'result'=>$result['errmsg']], ['biz_no' => $biz_no]);
                    if($order['uid'] > 0 && $resCount > 0){
                        changeUserMoney($order['uid'], $order['costmoney'], true, '代付退回', $biz_no);
                    }
                }
                $result['msg'] = '转账失败：'.($result['errmsg']?$result['errmsg']:'原因未知');
            }elseif($result['status'] == 1){
                if($order['status'] == 0 || $order['status'] == 3){
                    $paytime = $result['paydate'] ?? 'NOW()';
                    $DB->update('transfer', ['status'=>1, 'paytime'=>$paytime, 'result'=>''], ['biz_no' => $biz_no]);
                }
                $result['msg'] = '转账成功！';
            }else{
                $result['msg'] = '转账处理中，请稍后查询结果。';
            }
        }
        return $result;
    }

    //转账查询
    //status 0:处理中 1:成功 2:失败
    public static function query($type, $channel, $biz_no, $pay_order_no){
        $bizParam = [
            'type' => $type,
            'out_biz_no' => $biz_no,
            'orderid' => $pay_order_no
        ];
        return \lib\Plugin::call('transfer_query', $channel, $bizParam);
    }

    //撤销转账
    public static function cancel($biz_no){
        global $DB;
        $order = $DB->find('transfer', '*', ['biz_no' => $biz_no]);
        if(!$order) return ['code'=>-1, 'msg'=>'付款记录不存在'];

        $channelinfo = null;
        if($order['uid'] > 0){
            $channelinfo = $DB->findColumn('user', 'channelinfo', ['uid'=>$order['uid']]);
        }
        $channel = \lib\Channel::get($order['channel'], $channelinfo);
        if(!$channel) return ['code'=>-1, 'msg'=>'支付通道不存在'];

        $bizParam = [
            'type' => $order['type'],
            'out_biz_no' => $order['biz_no'],
            'orderid' => $order['pay_order_no'],
        ];
        $result = \lib\Plugin::call('transfer_cancel', $channel, $bizParam);
        if($result['code'] == 0){
            $resCount = $DB->update('transfer', ['status'=>2, 'result'=>'转账已撤销'], ['biz_no' => $biz_no]);
            if($order['uid'] > 0 && $resCount > 0){
                changeUserMoney($order['uid'], $order['costmoney'], true, '代付退回', $biz_no);
            }
            $result['msg'] = '转账已撤销';
        }
        return $result;
    }

    //账户余额查询
    public static function balance($type, $channel, $user_id = null){
        $bizParam = [
            'type' => $type,
            'user_id' => $user_id
        ];
        return \lib\Plugin::call('balance_query', $channel, $bizParam);
    }

    //转账凭证查询
    public static function proof($biz_no){
        global $DB;
        $order = $DB->find('transfer', '*', ['biz_no' => $biz_no]);
        if(!$order) return ['code'=>-1, 'msg'=>'付款记录不存在'];
        
        $channelinfo = null;
        if($order['uid'] > 0){
            $channelinfo = $DB->findColumn('user', 'channelinfo', ['uid'=>$order['uid']]);
        }
        $channel = \lib\Channel::get($order['channel'], $channelinfo);
        if(!$channel) return ['code'=>-1, 'msg'=>'支付通道不存在'];

        $bizParam = [
            'type' => $order['type'],
            'out_biz_no' => $biz_no,
            'orderid' => $order['pay_order_no']
        ];
        return \lib\Plugin::call('transfer_proof', $channel, $bizParam);
    }

    //转账回调处理
    public static function processNotify($biz_no, $status, $errmsg = null){
        global $DB;
        $order = $DB->find('transfer', '*', ['biz_no' => $biz_no]);
        if(!$order) {
            $order = $DB->find('settle', '*', ['transfer_no' => $biz_no]);
            if(!$order) return;
            if($status == 2 && $order['transfer_status'] == 1){
                $DB->update('settle', ['transfer_status'=>2, 'transfer_result'=>$errmsg, 'status'=>3, 'result'=>$errmsg], ['id' => $order['id']]);
            }elseif($status == 1 && $order['transfer_status'] == 2){
                $DB->update('settle', ['transfer_status'=>1, 'status'=>1, 'result'=>''], ['biz_no' => $biz_no]);
            }
            return;
        }
        if($status == 2 && $order['status'] == 0){ //转账失败
            $data = ['status'=>2];
            if($errmsg) $data['result'] = $errmsg;
            $resCount = $DB->update('transfer', $data, ['biz_no' => $biz_no]);
            if($order['uid'] > 0 && $resCount > 0){
                changeUserMoney($order['uid'], $order['costmoney'], true, '代付退回', $biz_no);
            }
        }elseif($status == 1 && $order['status'] == 0){ //转账成功
            $DB->update('transfer', ['status'=>1, 'paytime'=>'NOW()', 'result'=>''], ['biz_no' => $biz_no]);
        }
    }

    public static function red_add($uid, $type, $out_biz_no, $money, $desc = null, $channelid = null){
        global $conf, $DB, $userrow;
        $biz_no = $out_biz_no;
        if(strlen($biz_no)!=19 || !is_numeric($biz_no)) $biz_no = date("YmdHis").rand(11111,99999);

        if($uid > 0){
            if($conf['transfer_minmoney']>0 && $money<$conf['transfer_minmoney']) return ['code'=>-1, 'msg'=>'单笔最小代付金额限制为'.$conf['transfer_minmoney'].'元'];
            if($conf['transfer_maxmoney']>0 && $money>$conf['transfer_maxmoney']) return ['code'=>-1, 'msg'=>'单笔最大代付金额限制为'.$conf['transfer_maxmoney'].'元'];
        }
        
        if(!$channelid){
            if($type=='alipay'){
                $channelid = $conf['transfer_alipay'];
            }elseif($type=='wxpay'){
                $channelid = $conf['transfer_wxpay'];
            }else{
                return ['code'=>-1, 'msg'=>'type参数错误'];
            }
            if(!$channelid) return ['code'=>-1, 'msg'=>'未开启此转账方式'];
        }
        $channel = \lib\Channel::get($channelid, $userrow['channelinfo']);
        if(!$channel) return ['code'=>-1, 'msg'=>'当前支付通道信息不存在'];

        $trans = $DB->find('transfer', '*', ['out_biz_no' => $out_biz_no, 'uid' => $uid]);
        if($trans) return ['code'=>-1, 'msg'=>'该交易号已存在，请更换交易号'];

        $DB->beginTransaction();
        $need_money = null;
        if($uid > 0){
            $userrow = $DB->getRow('SELECT * FROM pre_user WHERE uid=:uid FOR UPDATE', [':uid'=>$uid]);
            if($userrow['settle']==0){
                $DB->rollback();
                return ['code'=>-1, 'msg'=>'您的商户出现异常，无法使用代付功能'];
            }
            if($conf['settle_type']==1){
                $today=date("Y-m-d").' 00:00:00';
                $order_today=$DB->getColumn("SELECT SUM(realmoney) from pre_order where uid={$uid} and tid<>2 and status=1 and endtime>='$today'");
                if(!$order_today) $order_today = 0;
                $enable_money=round($userrow['money']-$order_today,2);
                if($enable_money<0)$enable_money=0;
            }else{
                $enable_money=$userrow['money'];
            }
            if(!$conf['transfer_rate'])$conf['transfer_rate'] = $conf['settle_rate'];
            $need_money = round($money + $money*$conf['transfer_rate']/100,2);
            if($need_money>$enable_money){
                $DB->rollback();
                return ['code'=>-1, 'msg'=>'需支付金额大于可转账余额'];
            }
        }
        
        $jumpurl = self::red_url($biz_no);
        $result = ['code'=>0, 'status'=>4, 'biz_no'=>$biz_no, 'out_biz_no'=>$out_biz_no, 'jumpurl'=>$jumpurl];

        $data = ['biz_no'=>$biz_no, 'out_biz_no'=>$out_biz_no, 'uid'=>$uid, 'type'=>$type, 'channel'=>$channelid, 'account'=>'', 'username'=>'', 'money'=>$money, 'costmoney'=>$need_money??$money, 'addtime'=>'NOW()', 'status'=>$result['status'], 'desc'=>$desc];
        $id = $DB->insert('transfer', $data);
        if($need_money>0 && $id!==false){
            changeUserMoney2($uid, $userrow['money'], $need_money, false, '代付', $biz_no);
            $result['cost_money'] = $need_money;
        }
        $typename = $type == 'alipay' ? '支付宝' : ($type == 'wxpay' ? '微信' : '未知');
        $result['msg']='红包创建成功！请在'.$typename.'打开 '.$jumpurl.' 确认收款。';
        $DB->commit();
        return $result;
    }

    public static function red_receive($biz_no, $openid){
        global $conf, $DB;

        $func = function() use ($biz_no, $openid){
            global $DB, $userrow;
            $trans = $DB->getRow("SELECT * FROM pre_transfer WHERE biz_no=:biz_no FOR UPDATE", [':biz_no'=>$biz_no]);
            if(!$trans) return ['code'=>-1, 'msg'=>'红包不存在'];
            if($trans['status'] != 4) return ['code'=>-1, 'msg'=>$trans['status']==1?'红包已领取':'红包状态异常，无法领取'];
            $channel = \lib\Channel::get($trans['channel'], $userrow['channelinfo']);
            if(!$channel) return ['code'=>-1, 'msg'=>'当前支付通道信息不存在'];

            $result = self::submit($trans['type'], $channel, $biz_no, $openid, '', $trans['money'], $trans['desc']);
            if($result['code']==0){
                $data = ['account'=>$openid, 'status'=>$result['status'], 'paytime'=>'NOW()', 'pay_order_no'=>$result['orderid'], 'result'=>''];
                if(isset($result['wxpackage'])){
                    $data['ext'] = $result['wxpackage'];
                    $wxinfo = \lib\Channel::getWeixin($channel['appwxmp']);
                    $result['wxtransfer'] = [
                        'mchId' => $channel['appmchid'],
                        'appId' => $wxinfo['appid'],
                        'package' => $result['wxpackage'],
                    ];
                }
                $DB->update('transfer', $data, ['biz_no' => $biz_no]);
            }
            return $result;
        };

        $DB->beginTransaction();
        $result = $func();
        if($result['code'] == 0){
            $DB->commit();
        }else{
            $DB->rollback();
        }
        return $result;
    }

    public static function red_url($biz_no){
        global $siteurl;
        $t = time().'';
        $s = md5(SYS_KEY.$biz_no.$t.SYS_KEY);
        return $siteurl.'paypage/red.php?n='.$biz_no.'&t='.$t.'&s='.$s;
    }
}