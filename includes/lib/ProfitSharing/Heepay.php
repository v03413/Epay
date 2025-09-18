<?php

namespace lib\ProfitSharing;

use Exception;

class Heepay implements IProfitSharing
{

    static $paytype = 'heepay';

    private $channel;

    function __construct($channel){
		$this->channel = $channel;
	}

    //请求分账
    public function submit($trade_no, $api_trade_no, $order_money, $info){
        $allot_data = [];
        $rdata = [];
        foreach($info as $receiver){
            $money = round(floor($order_money * $receiver['rate']) / 100, 2);
            $allot_data[] = $receiver['account'].'^'.($receiver['rate'] == 100 ? 'remain_amt' : sprintf('%.2f' , $money)).'^F';
            $rdata[] = ['account'=>$receiver['account'], 'money'=>$money];
        }

        $apiurl = 'https://pay.heepay.com/API/Payment/GuaranteeAllotSubmit.aspx';
        $param = [
            'version' => '3',
            'agent_id' => $this->channel['appid'],
            'ref_agent_id' => $this->channel['appmchid'],
            'agent_bill_id' => $trade_no,
            'jnet_bill_no' => $api_trade_no,
            'batch_no' => date("YmdHis").rand(11111,99999),
            'confirm_amt' => $order_money,
            'allot_data' => implode('|',$allot_data),
            'timestamp' => getMillisecond(),
            'sign_type' => 'MD5',
        ];
        $signstr = 'version='.$param['version'].'&agent_id='.$param['agent_id'].'&agent_bill_id='.$param['agent_bill_id'].'&ref_agent_id='.$param['ref_agent_id'].'&batch_no='.$param['batch_no'].'&jnet_bill_no='.$param['jnet_bill_no'].'&allot_data='.$param['allot_data'].'&timestamp='.$param['timestamp'].'&key='.$this->channel['appkey'];
        $param['allot_data'] = mb_convert_encoding($param['allot_data'], 'GBK', 'UTF-8');
        $param['sign'] = md5($signstr);
        $data = get_curl($apiurl, http_build_query($param));
		$data = mb_convert_encoding($data,'UTF-8','GBK');
		$arr = explode('|',$data);
        $result = [];
        foreach($arr as $row){
            $row = explode('=',$row);
            if(count($row) == 2){
                $result[$row[0]] = $row[1];
            }
        }

		if(isset($result['ret_code']) && $result['ret_code']=='0001'){
            return ['code'=>0, 'msg'=>'提交分润成功', 'settle_no'=>$result['batch_no'], 'money'=>$result['total_amt'], 'rdata'=>$rdata];
        }else{
            return ['code'=>-1, 'msg'=>$result["ret_msg"]?$result["ret_msg"]:'返回内容解析失败'];
        }
    }

    //查询分账结果
    public function query($trade_no, $api_trade_no, $settle_no){
        $apiurl = 'https://pay.heepay.com/API/Payment/GuaranteeAllotQuery.aspx';
        $param = [
            'version' => '3',
            'agent_id' => $this->channel['appid'],
            'agent_bill_id' => $trade_no,
            'jnet_bill_no' => $api_trade_no,
            'batch_no' => $settle_no,
            'timestamp' => getMillisecond(),
            'sign_type' => 'MD5',
        ];
        $signstr = 'version='.$param['version'].'&agent_id='.$param['agent_id'].'&agent_bill_id='.$param['agent_bill_id'].'&jnet_bill_no='.$param['jnet_bill_no'].'&timestamp='.$param['timestamp'].'&key='.$this->channel['appkey'];
        $param['sign'] = md5($signstr);
        $data = get_curl($apiurl, http_build_query($param));
		$data = mb_convert_encoding($data,'UTF-8','GBK');
		$arr = explode('|',$data);
        $result = [];
        foreach($arr as $row){
            $row = explode('=',$row);
            if(count($row) == 2){
                $result[$row[0]] = $row[1];
            }
        }
        
        if(isset($result['ret_code']) && $result['ret_code']=='0001'){
            if(empty($result['allot_data'])) return ['code'=>-1, 'msg'=>'分润数据为空'];
            $allot_data = json_decode($result['allot_data'], true);
            if($allot_data['remain_amt'] > 0){
                $res = $this->unfreeeze($trade_no, $api_trade_no);
                if($res['code'] == -1){
                    return ['code'=>-1, 'msg'=>'分润结束失败，'.$res['msg']];
                }
            }
            return ['code'=>0, 'status'=>1];
        }else{
            return ['code'=>-1, 'msg'=>$result["ret_msg"]?$result["ret_msg"]:'返回内容解析失败'];
        }
    }

    //解冻剩余资金
    public function unfreeeze($trade_no, $api_trade_no){
        $apiurl = 'https://Pay.Heepay.com/API/Payment/GuaranteeAllotFinish.aspx';
        $param = [
            'version' => '2',
            'agent_id' => $this->channel['appid'],
            'ref_agent_id' => $this->channel['appmchid'],
            'agent_bill_id' => $trade_no,
            'jnet_bill_no' => $api_trade_no,
            'batch_no' => date("YmdHis").rand(11111,99999),
            'timestamp' => getMillisecond(),
            'sign_type' => 'MD5',
        ];
        $signstr = 'version='.$param['version'].'&agent_id='.$param['agent_id'].'&agent_bill_id='.$param['agent_bill_id'].'&ref_agent_id='.$param['ref_agent_id'].'&batch_no='.$param['batch_no'].'&jnet_bill_no='.$param['jnet_bill_no'].'&description='.$param['description'].'&timestamp='.$param['timestamp'].'&key='.$this->channel['appkey'];
        $param['sign'] = md5($signstr);
        $data = get_curl($apiurl, http_build_query($param));
		$data = mb_convert_encoding($data,'UTF-8','GBK');
		$arr = explode('|',$data);
        $result = [];
        foreach($arr as $row){
            $row = explode('=',$row);
            if(count($row) == 2){
                $result[$row[0]] = $row[1];
            }
        }
        
        if(isset($result['ret_code']) && $result['ret_code']=='0001'){
            return ['code'=>0, 'msg'=>'解冻剩余资金成功', 'settle_no'=>$param['batch_no']];
        }else{
            return ['code'=>-1, 'msg'=>$result["ret_msg"]?$result["ret_msg"]:'返回内容解析失败'];
        }
    }

    //分账回退
    public function return($trade_no, $api_trade_no, $rdata){
        $allmoney = 0;
        foreach($rdata as $row){
            $allmoney += $row['money'];
        }
        if(isset($_POST['money']) && round($_POST['money'], 2) < round($allmoney, 2)){
            $allot_data = [];
            $leftmoney = round($_POST['money'], 2);
            foreach($rdata as $row){
                $money = $row['money'] > $leftmoney ? $leftmoney : $row['money'];
                $allot_data[] = 'F^'.$row['account'].'^'.sprintf('%.2f' , $money).'^R';
                $leftmoney -= $row['money'];
                if($leftmoney <= 0) break;
            }
            $apiurl = 'https://pay.heepay.com/API/Payment/GuaranteeAllotRefundSubmit.aspx';
            $param = [
                'version' => '3',
                'agent_id' => $this->channel['appid'],
                'ref_agent_id' => $this->channel['appmchid'],
                'agent_bill_id' => $trade_no,
                'jnet_bill_no' => $api_trade_no,
                'batch_no' => date("YmdHis").rand(11111,99999),
                'allot_data' => implode('|',$allot_data),
                'timestamp' => getMillisecond(),
                'sign_type' => 'MD5',
            ];
            $signstr = 'version='.$param['version'].'&agent_id='.$param['agent_id'].'&agent_bill_id='.$param['agent_bill_id'].'&ref_agent_id='.$param['ref_agent_id'].'&batch_no='.$param['batch_no'].'&jnet_bill_no='.$param['jnet_bill_no'].'&allot_data='.$param['allot_data'].'&timestamp='.$param['timestamp'].'&key='.$this->channel['appkey'];
            $param['sign'] = md5($signstr);
            //print_r($param);
        }else{
            $apiurl = 'https://pay.heepay.com/API/Payment/GuaranteeAllotFullRefundSubmit.aspx';
            $param = [
                'version' => '3',
                'agent_id' => $this->channel['appid'],
                'ref_agent_id' => $this->channel['appmchid'],
                'agent_bill_id' => $trade_no,
                'jnet_bill_no' => $api_trade_no,
                'batch_no' => date("YmdHis").rand(11111,99999),
                'timestamp' => getMillisecond(),
                'sign_type' => 'MD5',
            ];
            $signstr = 'version='.$param['version'].'&agent_id='.$param['agent_id'].'&agent_bill_id='.$param['agent_bill_id'].'&ref_agent_id='.$param['ref_agent_id'].'&batch_no='.$param['batch_no'].'&jnet_bill_no='.$param['jnet_bill_no'].'&timestamp='.$param['timestamp'].'&key='.$this->channel['appkey'];
            $param['sign'] = md5($signstr);
        }
        $data = get_curl($apiurl, http_build_query($param));
		$data = mb_convert_encoding($data,'UTF-8','GBK');
		$arr = explode('|',$data);
        $result = [];
        foreach($arr as $row){
            $row = explode('=',$row);
            if(count($row) == 2){
                $result[$row[0]] = $row[1];
            }
        }
        
        if(isset($result['ret_code']) && $result['ret_code']=='0001'){
            return ['code'=>0, 'msg'=>'分润退回成功'];
        }else{
            return ['code'=>-1, 'msg'=>$result["ret_msg"]?$result["ret_msg"]:'返回内容解析失败'];
        }
    }

    //添加分账接收方
    public function addReceiver($account, $name = null){
        return ['code'=>0, 'msg'=>'添加分账接收方成功'];
    }

    //删除分账接收方
    public function deleteReceiver($account){
        return ['code'=>0, 'msg'=>'删除分账接收方成功'];
    }
}