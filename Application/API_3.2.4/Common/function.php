<?php

/**
 * 随机头像
 * @access private
 * @return string 头像地址
 */
function randPhoto() {
    $imageArray = array(
        'http://app.feibaokeji.com/userImage/1_03.png',
        'http://app.feibaokeji.com/userImage/2_03.png',
        'http://app.feibaokeji.com/userImage/3_03.png',
        'http://app.feibaokeji.com/userImage/4_03.png',
        'http://app.feibaokeji.com/userImage/5_03.png',
        'http://app.feibaokeji.com/userImage/6_03.png',
        'http://app.feibaokeji.com/userImage/7_03.png',
        'http://app.feibaokeji.com/userImage/8_03.png',
        'http://app.feibaokeji.com/userImage/9_03.png',
        'http://app.feibaokeji.com/userImage/10_03.png',
        'http://app.feibaokeji.com/userImage/11_03.png',
        'http://app.feibaokeji.com/userImage/12_03.png',
        'http://app.feibaokeji.com/userImage/13_03.png',
        'http://app.feibaokeji.com/userImage/14_03.png',
        'http://app.feibaokeji.com/userImage/15_03.png',
        'http://app.feibaokeji.com/userImage/16_03.png',
        'http://app.feibaokeji.com/userImage/17_03.png',
        'http://app.feibaokeji.com/userImage/18_03.png',
        'http://app.feibaokeji.com/userImage/19_03.png',
        'http://app.feibaokeji.com/userImage/20_03.png'
    );
    return $imageArray[array_rand($imageArray)];
}

/**
 * 随机昵称
 * @access private
 * @return string 昵称
 */
function randNickname() {
    $number = mt_rand(0, 44);
    return C('nickname.' . $number);
}

/**
 * 获取随机昵称
 * @access public
 * @return string
 */
function generate_rand($length) {
    $c = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789";
    srand((double) microtime() * 1000000);
    for ($i = 0; $i < $length; $i++) {
        $rand.= $c[rand() % strlen($c)];
    }
    return $rand;
}

/**
 * 生成用户唯一字符串
 * @param int $userId 用户ID
 * @param int $loginTime 最近登录时间 
 * @return string
 */
function createUserOnlyString($userId, $loginTime) {
    return sha1(base_convert(md5(base64_encode($userId . $loginTime)), 36, 10));
}

function utf8_strlen($str) {//获取字符长度
    $count = 0;
    for ($i = 0; $i < strlen($str); $i++) {
        $value = ord($str[$i]);
        if ($value > 127) {
            $count++;
            if ($value >= 192 && $value <= 223)
                $i++;
            elseif ($value >= 224 && $value <= 239)
                $i = $i + 2;
            elseif ($value >= 240 && $value <= 247)
                $i = $i + 3;
            else
                die('Not a UTF-8 compatible string');
        }
        $count++;
    }
    return $count;
}

 /**
     * 读取需要数据和同步数据链路控制
     * 读取需要的数据,如果未发现则同步数据并读取
     * 
     * @param array $where 需要的数据的读取条件
     * @return array 返回需要的数据
     */
    function readDataAndSynchronousDataLinkControl() {
        $config_member = D('config_member');
        $config = D("config");
        //删除缓存表所有数据
        $config_member->where("1")->delete();
        $all_data = $config->select();
        foreach ($all_data as $k => $v) {
            $config_member->add($v);
        }
    }


    /**
     * 检测验证码发送规则
     * 同一手机一分钟之内不得超过1次
     * 同一手机一小时之内不得超过5次
     * 同一手机一天之内不得超过10次
     */
    function checkSendCode($phone){
        $nowTime_ = time();
        $where = array();
        $where['phone'] = $phone;
        $nowTime_m = $nowTime_ - 60;
        $where['addTime'] = array('gt',$nowTime_m);
        $res_m = M('SendCodeLog')->where($where)->count();
        if($res_m){
            return false;
        }
        $where = array();
        $where['phone'] = $phone;
        $nowTime_h = $nowTime_ - 3600;
        $where['addTime'] = array('gt',$nowTime_h);
        $res_h = M('SendCodeLog')->where($where)->count();
        if($res_h > 5){
            return false;
        }
        $where = array();
        $where['phone'] = $phone;
        $nowTime_d = $nowTime_ - 3600*24;
        $where['addTime'] = array('gt',$nowTime_d);
        $res_d = M('SendCodeLog')->where($where)->count();
        if($res_d > 10){
            return false;
        }
        return true;
        
    }

/**
 * 发送验证码
 * @param string $phone 手机号
 * @param int $code  要发送的验证码
 * @return boolean
 */
function sendCode($phone, $code,$type='') {
    $re = checkSendCode($phone);
    if(!$re){
        return 2;
    }
    $messageInfo = M('config_member')->where(array("`key`='message_type'"))->field('value')->find();
    if(!$messageInfo){
        readDataAndSynchronousDataLinkControl();
        $messageInfo = M('config_member')->where(array("`key`='message_type'"))->field('value')->find();
        if(!$messageInfo){
            $messageInfo['value'] = '1';
        }
    }
    //如果是3，则随机选择短信发送平台
    if($messageInfo['value'] == 3){
        $rand = mt_rand(100, 999);
        $messageType = ($rand % 2) + 1;
    }else{
        $messageType = $messageInfo['value'];
    }
    
    //echo '方式：'. $messageType ."<br>";
    //echo '内容:' .$code . "<br>";die;
    
    if($messageType == 1){              //掌讯群发短信接口
        $flag = sendCodeByZhangXun($phone,$code,$type);
    }elseif($messageType == 2){         //合肥创瑞短信接口
        $flag = sendCodeByChuangRui($phone, $code,$type);
    }
    $status = $flag == 1 ? 1 : 4; 
    //记录发送日志
    $data = array(
        'phone' => (string)$phone,      //电话号码
        'type'  => (string)$messageType,    //发送方式 1 掌讯 2 创瑞
        'messageType'   => $messageInfo['value'], //后台配置发送方式
        'status'        => (string)$status,                 //发送状态：1 成功 4 失败
        'addTime'       => time(),
    );
    if($type == 2){
        $data['code'] = $code;              //短信发送内容
        $data['codeType'] = '2';            //短信发送内容类型 1 短信，2 消息
    }else{
        $data['code'] = '你的短信验证码为：'.$code.'，请勿将验证码提供给他人。';
        $data['codeType'] = '1';
    }
    M('send_code_log')->data($data)->add();
//    dump($data);
//    echo M('send_code_log')->getLastSql();
    return $flag;
}

//掌讯群发短信接口
function sendCodeByZhangXun($phone,$code,$type){
    $flag = 0;
    $url = "http://sms.sms666.cn/WebAPI/SmsAPI.asmx/SendSmsExt?user=feibaokeji&pwd=msUFRcPAiX&mobiles=" . $phone . "&contents=你的短信验证码为:" . $code . "，请勿将验证码提供给他人。【飞报APP】&chid=0&sendtime=";
    if($type==2){
        $url = "http://sms.sms666.cn/WebAPI/SmsAPI.asmx/SendSmsExt?user=feibaokeji&pwd=msUFRcPAiX&mobiles=" . $phone . "&contents=" . $code . "。【飞报APP】&chid=0&sendtime=";
    }
    //echo $url;die;
    $string = file_get_contents($url);
    $array = xml_to_array($string);
    if ($array['APIResult']['Code'] == 0) {
        $flag = 1;
    }
    return $flag;
}

//合肥创瑞短信接口
function sendCodeByChuangRui($phone,$code,$type){
    header("Content-Type: text/html; charset=UTF-8");
    $flag = 0; 
    $params='';//要post的数据 
    if($type == 2){
        $content = $code;
    }else{
        $content = '你的短信验证码为：'.$code.'，请勿将验证码提供给他人。';
//        $content = $code;
    }
    //以下信息自己填以下
    $argv = array( 
            'name'      =>'13339083570',     //必填参数。用户账号
            'pwd'       =>'AED53B02020D62B4FC28DFC04D8A',     //必填参数。（web平台：基本资料中的接口密码）
            'content'   => $content,   //必填参数。发送内容（1-500 个汉字）UTF-8编码
            'mobile'    => $phone,   //必填参数。手机号码。多个以英文逗号隔开
            'stime'     =>'',   //可选参数。发送时间，填写时已填写的时间发送，不填时为当前时间发送
            'sign'      =>'飞报',    //必填参数。用户签名。
            'type'      =>'pt',  //必填参数。固定值
            'extno'     =>''    //可选参数，扩展码，用户定义扩展码，只能为数字
    ); 
    foreach ($argv as $key=>$value) { 
            if ($flag!=0) { 
                    $params .= "&"; 
                    $flag = 1; 
            } 
            $params.= $key."="; $params.= urlencode($value);// urlencode($value); 
            $flag = 1; 
    }
    $url = "http://web.cr6868.com/asmx/smsservice.aspx?".$params; //提交的url地址
    $return = file_get_contents($url);

    $con= substr( $return, 0, 1 );  //获取信息发送后的状态
    //var_dump();die;
    if($con == 0){
        return 1;
    }else{
        return 0;
    }
}

/**
 * 将XML转成数组
 * @param string $xml
 * @return array
 */
function xml_to_array($xml) {
    $reg = "/<(\w+)[^>]*>([\\x00-\\xFF]*)<\\/\\1>/";
    if (preg_match_all($reg, $xml, $matches)) {
        $count = count($matches[0]);
        for ($i = 0; $i < $count; $i++) {
            $subxml = $matches[2][$i];
            $key = $matches[1][$i];
            if (preg_match($reg, $subxml)) {
                $arr[$key] = xml_to_array($subxml);
            } else {
                $arr[$key] = $subxml;
            }
        }
    }
    return $arr;
}
