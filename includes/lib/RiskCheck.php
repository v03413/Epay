<?php
namespace lib;

use Exception;

class RiskCheck
{

    public static function execute(){
        global $conf;
        if($conf['auto_check_channel'] == 1){
            self::check_pay_channel();
        }
        if($conf['auto_check_sucrate'] == 1){
            self::check_mch_order_sucrate();
        }
        if($conf['auto_check_complain'] == 1){
            self::check_complain_rate();
        }
        if($conf['auto_check_payip'] == 1){
            self::check_order_payip();
        }
        if($conf['auto_check_payspeed'] == 1){
            self::check_order_payspeed();
        }
    }

    //检测通道下连续未支付订单数量自动关闭支付通道
    public static function check_pay_channel(){
        global $conf, $DB;
        $second = intval($conf['check_channel_second']);
		$failcount = intval($conf['check_channel_failcount']);
		$channelids = trim($conf['check_channel_ids']);
		if($second==0 || $failcount==0){
            echo '未开启支付通道检查功能';
            return;
        }
		if(!empty($channelids)){
			$channels = $DB->getAll("SELECT * FROM pre_channel WHERE id IN ($channelids) AND status=1 ORDER BY id ASC");
		}else{
			$channels = $DB->getAll("SELECT * FROM pre_channel WHERE status=1 ORDER BY id ASC");
		}
		foreach($channels as $channel){
			$channelid = $channel['id'];
			if(strpos($channel['config'], '[') && strpos($channel['config'], ']') && $DB->getCount("SELECT COUNT(*) FROM pre_subchannel WHERE channel='$channelid' AND status=1") > 0){
				$subchannels = $DB->getAll("SELECT * FROM pre_subchannel WHERE channel='$channelid' AND status=1 ORDER BY id ASC");
				foreach($subchannels as $subchannel){
					$subchannelid = $subchannel['id'];
					$orders=$DB->getAll("SELECT trade_no,status FROM pre_order WHERE addtime>=DATE_SUB(NOW(), INTERVAL {$second} SECOND) AND channel='$channelid' AND subchannel='$subchannelid' order by trade_no desc limit {$failcount}");
					if(count($orders)<$failcount)continue;
					$succount = 0;
					foreach($orders as $order){
						if($order['status']>0) $succount++;
					}
					if($succount == 0){
						$DB->exec("UPDATE pre_subchannel SET status=0 WHERE id='$subchannelid'");
						echo '已关闭子通道:'.$subchannel['name'].'<br/>';
						if($conf['check_channel_notice'] == 1){
                            $title = $conf['sitename'].' - 支付通道自动关闭提醒';
                            $content = '尊敬的管理员：支付通道“'.$channel['name'].'”下的子通道“'.$subchannel['name'].'”因在'.$second.'秒内连续出现'.$failcount.'个未支付订单，已被系统自动关闭！<br/>----------<br/>'.$conf['sitename'].'<br/>'.date('Y-m-d H:i:s');
                            if($conf['msgconfig_risk'] == 1 && !empty($conf['msgrobot_url'])){
                                \lib\MsgNotice::robot_webhook($conf['msgrobot_url'], $title, $content, true);
                            }else{
                                $mail_name = $conf['mail_recv']?$conf['mail_recv']:$conf['mail_name'];
							    send_mail($mail_name,$title,$content);
                            }
						}
					}
				}
			}else{
				$orders=$DB->getAll("SELECT trade_no,status FROM pre_order WHERE addtime>=DATE_SUB(NOW(), INTERVAL {$second} SECOND) AND channel='$channelid' order by trade_no desc limit {$failcount}");
				if(count($orders)<$failcount)continue;
				$succount = 0;
				foreach($orders as $order){
					if($order['status']>0) $succount++;
				}
				if($succount == 0){
					$DB->exec("UPDATE pre_channel SET status=0 WHERE id='$channelid'");
					echo '已关闭通道:'.$channel['name'].'<br/>';
					if($conf['check_channel_notice'] == 1){
                        $title = $conf['sitename'].' - 支付通道自动关闭提醒';
                        $content = '尊敬的管理员：支付通道“'.$channel['name'].'”因在'.$second.'秒内连续出现'.$failcount.'个未支付订单，已被系统自动关闭！<br/>----------<br/>'.$conf['sitename'].'<br/>'.date('Y-m-d H:i:s');
                        if($conf['msgconfig_risk'] == 1 && !empty($conf['msgrobot_url'])){
                            \lib\MsgNotice::robot_webhook($conf['msgrobot_url'], $title, $content, true);
                        }else{
						    $mail_name = $conf['mail_recv']?$conf['mail_recv']:$conf['mail_name'];
						    send_mail($mail_name,$title,$content);
                        }
					}
				}
			}
		}
		echo '支付通道检查任务已完成<br/>';
    }

    //检测商户订单支付成功率自动关闭商户支付权限
    public static function check_mch_order_sucrate(){
        global $conf, $DB;
        $second = intval($conf['check_sucrate_second']);
		$count = intval($conf['check_sucrate_count']);
		$sucrate = floatval($conf['check_sucrate_value']);
		if($second==0 || $count==0 || $sucrate==0){
            echo '未开启商户订单成功率检查功能';
            return;
        }
		//统计指定时间内每个商户的总订单数量
		$user_all_stats_rows=$DB->getAll("SELECT uid,count(*) ordernum FROM pre_order WHERE addtime>=DATE_SUB(NOW(), INTERVAL {$second} SECOND) GROUP BY uid");
		//统计指定时间内每个商户的成功订单数量
		$user_suc_stats_rows=$DB->getAll("SELECT uid,count(*) ordernum FROM pre_order WHERE addtime>=DATE_SUB(NOW(), INTERVAL {$second} SECOND) and status>0 GROUP BY uid");
		$user_suc_stats = [];
		foreach($user_suc_stats_rows as $row){
			if(!$row['uid']) continue;
			$user_suc_stats[$row['uid']] = $row['ordernum'];
		}
		foreach($user_all_stats_rows as $row){
			if(!$row['uid']) continue;
			$total_num = intval($row['ordernum']);
			$succ_num = intval($user_suc_stats[$row['uid']]);
			$user_rate = round($succ_num * 100 / $total_num, 2);
			if($total_num >= $count && $user_rate < $sucrate){
				$userrow = $DB->find('user', 'uid,email,pay', ['uid'=>$row['uid']]);
				if($userrow['pay'] == 1){
					$DB->exec("UPDATE pre_user SET pay=0 WHERE uid='{$row['uid']}'");
					echo 'UID:'.$row['uid'].' 订单成功率'.$user_rate.'%（'.$succ_num.'/'.$total_num.'），已关闭支付权限<br/>';
					$DB->exec("INSERT INTO `pre_risk` (`uid`, `type`, `content`, `date`) VALUES (:uid, 1, :content, NOW())", [':uid'=>$row['uid'],':content'=>$user_rate.'%（'.$succ_num.'/'.$total_num.'）']);
					if($conf['check_sucrate_notice'] == 1 && !empty($userrow['email'])){
						send_mail($userrow['email'],$conf['sitename'].' - 商户支付权限关闭提醒','尊敬的用户：你的商户ID '.$userrow['uid'].' 因在'.$second.'秒内订单支付成功率低于'.$sucrate.'%，已被系统自动关闭支付权限！如有疑问请联系网站客服。<br/>当前订单支付成功率：'.$user_rate.'%（总订单数：'.$succ_num.'，成功订单数：'.$total_num.'）<br/>----------<br/>'.$conf['sitename'].'<br/>'.date('Y-m-d H:i:s'));
					}
				}
			}
		}
		echo '商户订单成功率检查任务已完成<br/>';
    }

    //检测商户订单投诉率自动关闭商户支付权限
    public static function check_complain_rate(){
        global $conf, $DB;
        $complain_rate = floatval($conf['check_complain_rate']);
        if($complain_rate <= 0){
            return;
        }
		$complain_stats_rows = $DB->getAll("SELECT uid,count(*) num FROM pre_complain WHERE addtime>=DATE_SUB(NOW(), INTERVAL 7 DAY) AND uid<>0 GROUP BY uid");
		if(!empty($complain_stats_rows)){
			$complain_stats = [];
			foreach($complain_stats_rows as $row){
				$complain_stats[$row['uid']] = $row['num'];
			}
			$uids = [];
			foreach($complain_stats_rows as $row){
				$uids[] = $row['uid'];
			}
			$user_order_stats_rows=$DB->getAll("SELECT uid,count(*) ordernum FROM pre_order WHERE addtime>=DATE_SUB(NOW(), INTERVAL 7 DAY) and status>0 and uid in (".implode(',',$uids).") GROUP BY uid");
			foreach($user_order_stats_rows as $row){
				if(!isset($complain_stats[$row['uid']])) continue;
				$total_num = intval($row['ordernum']);
				if($total_num == 0) continue;
				$complain_num = intval($complain_stats[$row['uid']]);
				$user_rate = round($complain_num * 100 / $total_num, 2);
				if($complain_num > 0 && $user_rate > $complain_rate){
					$userrow = $DB->find('user', 'uid,email,pay', ['uid'=>$row['uid']]);
					if($userrow['pay'] == 1){
						$DB->exec("UPDATE pre_user SET pay=0 WHERE uid='{$row['uid']}'");
						echo 'UID:'.$row['uid'].' 投诉率'.$user_rate.'%（'.$complain_num.'/'.$total_num.'），已关闭支付权限<br/>';
						$DB->exec("INSERT INTO `pre_risk` (`uid`, `type`, `content`, `date`) VALUES (:uid, 3, :content, NOW())", [':uid'=>$row['uid'],':content'=>$user_rate.'%（'.$complain_num.'/'.$total_num.'）']);
						if($conf['check_complain_notice'] == 1 && !empty($userrow['email'])){
							send_mail($userrow['email'],$conf['sitename'].' - 商户支付权限关闭提醒','尊敬的用户：你的商户ID '.$userrow['uid'].' 因在7天内订单投诉率高于'.$complain_rate.'%，已被系统自动关闭支付权限！如有疑问请联系网站客服。<br/>当前订单投诉率：'.$user_rate.'%（7天总订单数：'.$total_num.'，投诉订单数：'.$complain_num.'）<br/>----------<br/>'.$conf['sitename'].'<br/>'.date('Y-m-d H:i:s'));
						}
					}
				}
			}
		}
		echo '商户订单投诉率检查任务已完成<br/>';
    }

    //检测单个IP连续未支付订单数量自动封禁IP
    public static function check_order_payip(){
        global $conf, $DB;
        $second = intval($conf['check_payip_second']);
		$count = intval($conf['check_payip_count']);
		if($second==0 || $count==0){
            echo '未开启单个IP连续未支付订单数量检查功能';
            return;
        }
		//统计指定时间内每个IP的总订单数量
		$ip_all_stats_rows=$DB->getAll("SELECT ip,count(*) ordernum FROM pre_order WHERE addtime>=DATE_SUB(NOW(), INTERVAL {$second} SECOND) GROUP BY ip");
		//统计指定时间内每个IP的成功订单数量
		$ip_suc_stats_rows=$DB->getAll("SELECT ip,count(*) ordernum FROM pre_order WHERE addtime>=DATE_SUB(NOW(), INTERVAL {$second} SECOND) and status>0 GROUP BY ip");
		$ip_suc_stats = [];
		foreach($ip_suc_stats_rows as $row){
			if(!$row['ip']) continue;
			$ip_suc_stats[$row['ip']] = $row['ordernum'];
		}
		foreach($ip_all_stats_rows as $row){
			if($row['ordernum'] < $count) continue;
			$succ_num = intval($ip_suc_stats[$row['ip']]);
			if($succ_num > 0) continue;
			$black = $DB->getRow("select * from pre_blacklist where type=1 and content=:content limit 1", [':content'=>$row['ip']]);
			if($black){
				if(!$black['endtime'] || strtotime($black['endtime'])>strtotime('+4 days')) continue;
				$DB->update('blacklist', ['endtime'=>date('Y-m-d H:i:s', strtotime('+5 days')), 'remark'=>'连续'.$row['ordernum'].'个订单未支付'], ['id'=>$black['id']]);
			}else{
				$DB->insert('blacklist', ['type'=>1, 'content'=>$row['ip'], 'addtime'=>'NOW()', 'endtime'=>date('Y-m-d H:i:s', strtotime('+5 days')), 'remark'=>'连续'.$row['ordernum'].'个订单未支付']);
			}
			echo 'IP:'.$row['ip'].' 连续'.$row['ordernum'].'个订单未支付，已加入黑名单<br/>';
		}
		echo '单个IP连续未支付订单数量检查任务已完成<br/>';
    }

	//检测单个支付账号订单支付速度自动封禁账号
    public static function check_order_payspeed(){
        global $conf, $DB;
        $second = intval($conf['check_payspeed_second']);
		$count = intval($conf['check_payspeed_count']);
		$days = intval($conf['check_payspeed_days']);
		if($second==0 || $count==0){
            echo '未开启单个支付账号订单支付速度检查功能';
            return;
        }
		//统计指定时间内每个支付账号的成功订单数量
		$buyer_stats_rows=$DB->getAll("SELECT buyer,count(*) ordernum FROM pre_order WHERE addtime>=DATE_SUB(NOW(), INTERVAL {$second} SECOND) and status>0 GROUP BY buyer");
		foreach($buyer_stats_rows as $row){
			if(!$row['buyer']) continue;
			if($row['ordernum'] < $count) continue;
			
			$reason = $second.'秒内支付'.$row['ordernum'].'个订单';
			$black = $DB->getRow("select * from pre_blacklist where type=0 and content=:content limit 1", [':content'=>$row['buyer']]);
			if($black){
				if(!$black['endtime'] || strtotime($black['endtime'])>strtotime('+'.$days.' days')) continue;
				$DB->update('blacklist', ['endtime'=>$days==0?null:date('Y-m-d H:i:s', strtotime('+'.$days.' days')), 'remark'=>$reason], ['id'=>$black['id']]);
			}else{
				$DB->insert('blacklist', ['type'=>0, 'content'=>$row['buyer'], 'addtime'=>'NOW()', 'endtime'=>$days==0?null:date('Y-m-d H:i:s', strtotime('+'.$days.' days')), 'remark'=>$reason]);
			}
			echo '支付账号:'.$row['buyer'].' '.$reason.'，已加入黑名单<br/>';
		}
		echo '单个支付账号订单支付速度检查任务已完成<br/>';
    }
}