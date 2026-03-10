<?php
namespace lib;

use Exception;

class VerifyCode{

    const SMS_PHONE_DAYLY_TIME = 3; //短信验证码每个手机号每天最多发送次数
    const SMS_IP_DAYLY_TIME = 6; //短信验证码每个IP每天最多发送次数
    const EMAIL_ADDRESS_DAYLY_TIME = 7; //邮箱验证码每个邮箱每天最多发送次数
    const EMAIL_IP_DAYLY_TIME = 11; //邮箱验证码每个IP每天最多发送次数

    private static $regcodeid;

    /**
     * 发送验证码
     * @param string $scene 验证码场景
     * @param int $type 验证码类型:0邮件,1手机
     * @param string $sendto 接收人
     * @param int $uid 用户ID
     * @return mixed
     */
    public static function send_code($scene, $type, $sendto, $uid = 0){
        global $DB, $conf, $clientip;

        if($type == 1){
            $phone = $sendto;
            $row=$DB->getRow("select * from pre_regcode where `to`=:phone order by id desc limit 1", [':phone'=>$phone]);
            if($row['time']>time()-60){
                return '两次发送短信之间需要相隔60秒！';
            }
            $count=$DB->getColumn("select count(*) from pre_regcode where `to`=:phone and time>'".(time()-3600*24)."'", [':phone'=>$phone]);
            if($count>=self::SMS_PHONE_DAYLY_TIME){
                return '该手机号码发送次数过多，请更换号码！';
            }
            $count=$DB->getColumn("select count(*) from pre_regcode where ip=:ip and time>'".(time()-3600*24)."'", [':ip'=>$clientip]);
            if($count>=self::SMS_IP_DAYLY_TIME){
                return '你今天发送次数过多，请明天再试！';
            }
            $code = rand(111111,999999);
            $result = send_sms($phone, $code, $scene);
            if($result===true){
                if($DB->insert('regcode', ['uid'=>$uid, 'scene'=>$scene, 'type'=>$type, 'code'=>$code, 'to'=>$phone, 'time'=>time(), 'ip'=>$clientip, 'status'=>0])){
                    return true;
                }else{
                    return '写入数据库失败。'.$DB->error();
                }
            }else{
                return '短信发送失败 '.$result;
            }
        }else{
            $email = $sendto;
            $row=$DB->getRow("select * from pre_regcode where `to`=:email order by id desc limit 1", [':email'=>$email]);
            if($row['time']>time()-60){
                return '两次发送邮件之间需要相隔60秒！';
            }
            $count=$DB->getColumn("select count(*) from pre_regcode where `to`=:email and time>'".(time()-3600*24)."'", [':email'=>$email]);
            if($count>=self::EMAIL_ADDRESS_DAYLY_TIME){
                return '该邮箱发送次数过多，请更换邮箱！';
            }
            $count=$DB->getColumn("select count(*) from pre_regcode where ip=:ip and time>'".(time()-3600*24)."'", [':ip'=>$clientip]);
            if($count>=self::EMAIL_IP_DAYLY_TIME){
                return '你今天发送次数过多，请明天再试！';
            }
            $code = rand(1111111,9999999);
            $result = self::send_mail_code($email, $code, $scene);
            if($result===true){
                if($DB->insert('regcode', ['uid'=>$uid, 'scene'=>$scene, 'type'=>$type, 'code'=>$code, 'to'=>$email, 'time'=>time(), 'ip'=>$clientip, 'status'=>0])){
                    return true;
                }else{
                    return '写入数据库失败。'.$DB->error();
                }
            }else{
                return '邮件发送失败 '.$result;
            }
        }
    }

    private static function send_mail_code($email, $code, $scene){
        global $conf;
        $title = $conf['sitename'].' - 验证码获取';
        if($scene == 'reg'){
            $body = '您的验证码是：'.$code.'，您正在注册成为'.$conf['sitename'].'的用户，如非本人操作请忽略。';
        }elseif($scene == 'login'){
            $body = '您的验证码是：'.$code.'，用于'.$conf['sitename'].'登录验证，请勿泄露验证码，如非本人操作请忽略。';
        }elseif($scene == 'find'){
            $body = '您的验证码是：'.$code.'，用于'.$conf['sitename'].'重置密码，请勿泄露验证码，如非本人操作请忽略。';
        }elseif($scene == 'edit'){
            global $situation;
            if($situation=='settle')$body = '您正在修改结算账号信息，验证码是：'.$code;
            elseif($situation=='mibao')$body = '您正在修改密保邮箱，验证码是：'.$code;
            elseif($situation=='bind')$body = '您正在绑定新邮箱，验证码是：'.$code;
            else $body = '您的验证码是：'.$code;
        }
        return send_mail($email, $title, $body);
    }

    /**
     * 验证验证码
     * @param string $scene 验证码场景
     * @param int $type 验证码类型:0邮件,1手机
     * @param string $sendto 接收人
     * @param string $code 验证码
     * @param int $uid 用户ID
     * @return mixed
     */
    public static function verify_code($scene, $type, $sendto, $code, $uid = 0){
        global $DB;
        $where = ['scene'=>$scene, 'type'=>$type, 'to'=>$sendto];
        if($uid > 0) $where['uid'] = $uid;
        $row = $DB->find('regcode', '*', $where, 'id DESC', 1);
        if (!$row) {
            return '请重新获取验证码！';
        }elseif($row['time']<time()-3600 || $row['status']>0 || $row['errcount']>=5){
            return '验证码已失效，请重新获取';
        }elseif($row['code']!=$code){
            $DB->exec("update `pre_regcode` set `errcount`=`errcount`+1 where `id`='{$row['id']}'");
            return '验证码不正确！';
        }
        self::$regcodeid = $row['id'];
        return true;
    }

    //作废验证码
    public static function void_code(){
        global $DB;
        if(self::$regcodeid){
            $DB->exec("update `pre_regcode` set `status`='1' where `id`=:id", [':id'=>self::$regcodeid]);
            self::$regcodeid = null;
        }
    }

}