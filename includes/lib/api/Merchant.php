<?php
namespace lib\api;

use Exception;

class Merchant
{
    public static function info(){
        global $conf, $DB, $userrow, $queryArr;

        $pid=intval($queryArr['pid']);
        $orders=$DB->getColumn("SELECT count(*) from pre_order WHERE uid={$pid}");
        $lastday=date("Y-m-d",strtotime("-1 day"));
        $today=date("Y-m-d");
        $order_today=$DB->getColumn("SELECT count(*) from pre_order where uid={$pid} and status=1 and date='$today'");
        $order_lastday=$DB->getColumn("SELECT count(*) from pre_order where uid={$pid} and status=1 and date='$lastday'");
        $order_today_all = round($DB->getColumn("SELECT sum(money) FROM pre_order WHERE uid={$pid} AND status=1 AND date='$today'"),2);
	    $order_lastday_all = round($DB->getColumn("SELECT sum(money) FROM pre_order WHERE uid={$pid} AND status=1 AND date='$lastday'"),2);

        $result = ['code'=>0, 'pid'=>$pid, 'status'=>$userrow['status'], 'pay_status'=>$userrow['pay'], 'settle_status'=>$userrow['settle'], 'money'=>$userrow['money'], 'settle_type'=>$userrow['settle_id'], 'settle_account'=>$userrow['account'], 'settle_name'=>$userrow['username'], 'order_num'=>$orders, 'order_num_today'=>$order_today, 'order_num_lastday'=>$order_lastday, 'order_money_today'=>strval($order_today_all), 'order_money_lastday'=>strval($order_lastday_all)];
        $result = array_filter($result, function($a){return !isEmpty($a);});
        return $result;
    }

    public static function orders(){
        global $conf, $DB, $userrow, $queryArr;

        $pid=intval($queryArr['pid']);
        $limit=isset($queryArr['limit'])?intval($queryArr['limit']):10;
        $offset=isset($queryArr['offset'])?intval($queryArr['offset']):0;
        $status=isset($queryArr['status'])?intval($queryArr['status']):null;
        if($limit>50)$limit=50;

        $sql = " uid='{$pid}'";
        if(isset($queryArr['status'])){
            $status = intval($_GET['status']);
            $sql .= " AND A.status='{$status}'";
        }

        $data = [];
        $rs=$DB->query("SELECT A.*,B.name typename FROM pre_order A LEFT JOIN pre_type B ON A.type=B.id WHERE{$sql} ORDER BY trade_no DESC LIMIT {$offset},{$limit}");
        while($order=$rs->fetch(\PDO::FETCH_ASSOC)){
            $data[]=['trade_no'=>$order['trade_no'],'out_trade_no'=>$order['out_trade_no'],'api_trade_no'=>$order['api_trade_no'],'type'=>$order['typename'],'pid'=>$order['uid'],'addtime'=>$order['addtime'],'endtime'=>$order['endtime'],'name'=>$order['name'],'money'=>$order['money'],'param'=>$order['param'],'buyer'=>$order['buyer'],'clientip'=>$order['ip'],'status'=>$order['status'],'refundmoney'=>$order['refundmoney']];
        }

        $result['code'] = 0;
        $result['data'] = $data;
        return $result;
    }
}