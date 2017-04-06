<?php

/**
 * 密钥系统接口
 * @author Charles <homercharles@qq.com>
 */
use Think\Controller;

class CheckController extends Controller {

    public $check_m;
    //用户类型
    public $user_flag;
    //当前用户访问的控制器
    public $controller_name;
    //当前用户访问的操作
    public $action_name;
    //用户终端类型
    public $mobileflag;

    public function _initialize() {
        //初始化用户访问的控制器
        $this->controller_name = CONTROLLER_NAME;
        //初始化用户访问的操作
        $this->action_name = ACTION_NAME;
        //自动处理IP相关的限制
        $this->check_m = D('Check');
        //确认用户终端类型
        $this->checkUserMobileFlag();
        //确认用户终端类型，已废弃
        //$this->checkUserFlag();
        $acNameArray = array('getShopInfo', 'dataStock', 'NewpreExchange', 'checkExchange', 'friendPosterDetail', 'changeDataId');
        if (in_array(ACTION_NAME, $acNameArray)) {
            //$_POST['userId'] = urldecode($_POST['userId']);
        }

        if (I("post.mobile_platform") == 'android') {
            $mobielType = 1;
        } elseif (I("post.mobile_platform") == 'ios') {
            $mobielType = 2;
        } else {
            $ret['status'] = 10;
            $ret['message'] = '操作失败';
            echo jsonStr($ret);
            exit(0);
        }
        $process_data[] = 'password';
        $process_data[] = 'newPassword';
        $process_data[] = 'newHandlePwd';
        $process_data[] = 'handlePwd';
        $process_data[] = 'userId';
        foreach ($process_data as $key) {
            if ($_POST[$key]) {
                $_POST[$key] = dataDecode($_POST[$key]);
                //echo $_POST[$key];die;
                //$ret['status'] = 10004;
                //$ret['message'] = '您无法兑换话费!'.$_POST[$key];
                //echo jsonStr($ret);
                //exit(0);
            }
        }
        
        //新增加旧版本兑换不可通过，给会员发送通知和失败提醒
        //$this->checkOldBill();
    }
    
     /*
     * 3.2.3上线后，验证兑换接口（除兑换接口外，其他接口均可通过）
     */
    public function checkOldBill(){
        if (
                ($this->controller_name == "Bill" and $this->action_name == "exchange" )//充值兑换接口
                or ( $this->controller_name == "NewBill" and $this->action_name == "exchange" )
        ) {
            $uid = I("post.userId");
            $resUser = M('Members')->field('id')->where('uniqueId="'.$uid.'"')->find();
            if($resUser){
                $content='官人,您的版本太老了,无法兑换话费了呢!赶快升级吧!';
                
                D('Members')->addMemberDope($resUser['id'], $content, '3', 0, 0, '18');
            }
            
            $ret['status'] = 10004;
            $ret['message'] = '您无法兑换话费!';
            echo jsonStr($ret);
            exit(0);
        }
    }

    /**
     * 控制充值接口
     */
    public function ctrlBill_old() {
        if ($this->controller_name == "Personal" and $this->action_name == "invite") {
            //获取当前用户ID当用户接口为邀请接口的时候通过新用户字段获取用户ID
            $uid = I("post.newuserid");
        } else {
            //其他接口通过userId字段获取用户ID
            $uid = $uid ? $uid : I("post.userId");
        }
        $uid = trim($uid);
        //获得用户上传的终端
        $mobileflag = I('post.mobileflag');
        if (
                ($this->controller_name == "Bill" and $this->action_name == "exchange" )//充值兑换接口
                or ( $this->controller_name == "NewBill" and $this->action_name == "exchange" )
        ) {
            if (!$mobileflag) {
                $flag = D("System")->readConfig('old_user_switch');
                if ($flag == '2') {
                    $ret['status'] = -11003;
                    $ret['message'] = '官人,您的版本太老了,无法兑换话费了呢!赶快升级吧!';
                    pushMassageAndWriteMassage($ret['message'], trim($uid));
                    echo jsonStr($ret);
                    exit(0);
                }
            }
            $nuser_flag = $this->user_flag;
            //if ($result[0]['mobileflag']!=1) {
            if ($nuser_flag != 1) {

                $ret['status'] = 10004;
                $ret['message'] = '您无法兑换话费!';
                echo jsonStr($ret);
                exit(0);
            }
        }
    }
    

    /**
     * 控制充值接口
     */
    public function ctrlBill() {
        //获得用户上传的终端
        $mobileflag = trim(I('post.mobileflag'));
        if (
                ($this->controller_name == "Bill" and $this->action_name == "exchange" )//充值兑换接口
                or ( $this->controller_name == "NewBill" and $this->action_name == "exchange" )
        ) {
            //判断是否传递了用户终端类型
            if (!$mobileflag) {
                //如果没有传递用户终端类型，检测后台老用户使用的开关。
                $flag = D("System")->readConfig('old_user_switch');
                //如果状态为关闭，则直接返回错误并推送升级通知
                if ($flag == '2') {
                    $ret['status'] = -11003;
                    $ret['message'] = '官人,您的版本太老了,无法兑换话费了呢!赶快升级吧!';
                    pushMassageAndWriteMassage($ret['message'], trim($uid));
                    echo jsonStr($ret);
                    exit(0);
                }
            }
        }
    }

    /**
     * 确认用户种类
     */
    public function checkUserFlag($uid = 0) {
        //只处理新兑换、老兑换、揭海报、分享海报、邀请、快速体验等六个接口
        if (
                ($this->controller_name == "Bill" and $this->action_name == "exchange" )//充值兑换接口
                or ( $this->controller_name == "NewBill" and $this->action_name == "exchange" )//新充值接口
                or ( $this->controller_name == "Posters" and ( $this->action_name == "expose" or $this->action_name == "share"))//揭海报\分享海报接口
                or ( $this->controller_name == "Personal" and ( $this->action_name == "invite" or $this->action_name = "fastExperience"))//邀请接口\快速体验接口
//                or ( $controller_name == "Found" and ( $action_name == "addFound" or $action_name == "addFounds"))//发布发现接口
//                or ( $controller_name == "Comments" and $action_name == "addComments")//评论接口
        ) {
            //如果未传递UID则通过参数获取用户ID，否则从参数中获得参数
            if (!$uid) {
                if ($this->controller_name == "Personal" and $this->action_name == "invite") {
                    //获取当前用户ID当用户接口为邀请接口的时候通过新用户字段获取用户ID
                    $uid = I("post.newuserid");
                } else {
                    //其他接口通过userId字段获取用户ID
                    $uid = I("post.userId");
                }
                $uid = trim($uid);

                //当找到UID的时候执行,否则跳过,快速体验等接口将不会获得用户的ID
                if (!$uid) {
                    $this->user_flag = 1;
                    return;
                }
            }



            $uid = (string) trim($uid);
            //终端类型加密字段
            $mobileflag = (string) trim(I("post.mobileflag"));
            //获取私钥
            $private_key = trim($_GET['private_key']);
            $secret_key = trim($_GET['secret_key']);
            if (!is_numeric($mobileflag) && $mobileflag) {
                if ($mobileflag == md5("{$secret_key}2")) {
                    $mobileflag = '2';
                }
            }
            //虚拟机在用户停止任何需要验证接口的执行
            if ($mobileflag == '2') {
                jsonMassageReturn();
            }

            //如果获得了用户ID,则读取用户数据库
            if ($uid) {
                $reMembers = M('Members')->field('id,integral,mobileflag')->where('id =' . $uid)->find();
            }


            //var_dump($reMembers);die;
            //判断会员当前是否是虚拟机
            if ($mobileflag == '2' && ($reMembers && ($reMembers['mobileflag'] == '1'))) {
                $datas['mobileflag'] = '2';
//            $reIntegral = M('Members')->where('id =' . $uid)->save($datas);
            }

            if ($mobileflag == '2' or ( $reMembers && $reMembers['mobileflag'] == '2')) {
                $this->user_flag = 2;
            } else {
                $this->user_flag = 1;
            }
        }
    }

    /**
     * 确认用户终端类型
     * 
     * 根据参数判断用户终端类型；
     */
    public function checkUserMobileFlag() {
        //终端类型加密字段
        $mobileflag = (string) trim(I("post.mobileflag"));
        $this->user_flag = 1;
        //获取私钥
//        $private_key = trim($_GET['private_key']);
        //获取密钥
        $secret_key = trim($_GET['secret_key']);
        //如果终端类型不是数字或者数字组成的字符串，并且真实传递了终端类型参数
        if (!is_numeric($mobileflag) && $mobileflag) {
            //判断终端类型和加密结果是否匹配，如果匹配则
            if ($mobileflag == md5("{$secret_key}2")) {
                $mobileflag = '2';
                $this->user_flag = 2;
            }
        }
        //判断当钱终端类型值，如果不是字符串2则设定终端类型为1
        if ($mobileflag == '2') {
            jsonMassageReturn(-1, "失败");
        }
    }

    /**
     * 展示各个阶段需要产生的值
     */
    public function show() {

        header("Content-type: text/html; charset=utf-8");
        $time = "a";
//        echo "3.1版本：<br>";
//        echo "时间戳:";
//        echo $time;
//        echo "<br>";
//        echo "私钥:";
//        echo $private_key = strrev(base64_encode(strrev(base64_encode(strrev($time)))));
//        echo "<br>";
//        echo "私钥解密成时间戳:";
//        echo $this->privateKeyDecode($private_key);
//        echo "<br>";
//        echo "私钥加密成密钥:";
//        echo $this->secretKey($private_key);
//        echo "<br>";
        echo authcodeIos("8962YTg3YTYwNDk4NjhiYWVhY2JmYzY5YWMxOWI5OTUyOWFlNmNmNjdjMjExMWEyZmEwOTY4OTU3YWY3MmQyNTc4YTIzNjc3MDc1M2ZhOWE5ZGNjYzM0", "DECODE");
        echo authcodeIos("1234NzZhM2Q0YmNhZTUxYzYxYjdjMGNlNTc1MDhlM2UwZGVlOGQ5OTM2ZWZiOTViMGNlZmY3MzUwNjZiZDljMGY0MWQ1ODUwMGU2MDViMTM0ZWIyMTk0", "DECODE");
        EXIT;

        echo base64_encode("v£Ô¼®QÆ|²pèã´ÜÂ9úÂãþ%?VÏÄ");
        echo "<br>3.2版本：<br>";
        echo "时间戳:";
        echo $time;
        echo "<br>";
        echo "私钥:";
        echo $private_key = authcodeTest($time, 'ENCODE');
        echo authcodeTest($private_key, "DECODE");
        echo "<br>private_key:{$private_key}";
        $str = "dsKjw5TCvMKuUcOGG3wMw6MiU8OiwrHDn8Kxw5zClT/Cr8KSw6bCm8O/cMKCwr9X";
        $str = base64_decode($str);
        echo "<br>str:{$str}";

        $str = base64_encode($str);
        echo "<br>str:{$str}";
        exit;
        echo "<br>";
        echo "私钥解密成时间戳:";
        echo $time_p = authcodeTest($private_key, "DECODE");
        echo "<br>";
        echo "时间戳重新封装新城新的私钥并加上时效特性，默认4秒:";
        echo $private_key = authcodeTest($time_p, "ENCODE", null, $time_p + 4);
//        sleep(3);
        echo "<br>";
        echo "私钥解密:";
        echo authcodeTest($private_key, "DECODE");
        echo "<br>";
        echo "私钥加密成密钥:";
        echo secretCode($private_key);
        echo "<br><br><br>";

        secretCode(time());
    }

    /**
     * 产生私钥信息
     */
    public function privateKey() {
        $private_key = strrev(base64_encode(strrev(base64_encode(strrev(time())))));
        $return['private_key'] = $private_key;
        echo json_encode($return);exit();
    }

    /**
     * 解析私钥信息为时间戳
     * 
     * @param string $private_key 需要解密的私钥信息
     * @return int 返回时间戳
     */
    public function privateKeyDecode($private_key) {
        $private_key = $private_key ? $private_key : $_GET['private_key'];
        $private_key = strrev(base64_decode(strrev(base64_decode(strrev($private_key)))));
        return $private_key;
    }

    /**
     * 产生3.2版本私钥信息
     */
    public function authCode() {
        if (trim($_POST['mobile_platform']) != "ios") {
            //安卓平台算法
            $return['private_key'] = authcodeAndroid(time(), "ENCODE");
        } else {
            //IOS平台算法
            $return['private_key'] = authcodeIos(time(), "ENCODE");
        }
        echo json_encode($return);exit();
    }

    public function authCodeIos() {
        $return['private_key'] = authcodeIos(time(), "ENCODE");

        echo json_encode($return);exit(0);
    }

    public function authCodeAndroid() {
        $return['private_key'] = authcodeIos(time(), "ENCODE");

        echo json_encode($return);
    }

    /**
     * 解析3.2版本私钥信息为时间戳
     * 
     * @param string $private_key 需要解密的私钥信息
     * @return int 返回时间戳
     */
    public function authCodeDecode($private_key) {
        $private_key = $private_key ? $private_key : $_GET['private_key'];
        $private_key = urldecode($private_key);
//        $private_key = "8e3gHachfLdxBMqci8x5GZXk1bsvEcIfRwflK6Elf+6PyzIR";
        echo $private_key;
        echo "<br>";
        $private_key = authcode($private_key, "DECODE");
        echo $private_key;
        return $private_key;
    }

    /**
     * 私钥信息解密并加密产生密钥信息
     * 
     * @param string $private_key 需要处理的私钥信息
     * @return string 返回加密后的密钥
     */
    public function secretKey($private_key) {
        //还原私钥内容;
        $key = $this->privateKeyDecode($private_key);
        //获得干扰码获取长度
        $str_len = $key % 32;
        //获取干扰码
        $interference_code = substr(md5($private_key), 0, $str_len);
        //确定加密干扰循环次数
        $circulation_num = $key % 10 + 1;
        //设定备选加密算法列表
        $func_list = "md5,sha1";
        //生成加密算法数组
        $func_arr = explode(',', $func_list);
        //获得加密算法数组数量
        $func_num = count($func_arr);
        //设定初始密钥为时间戳
        $secret_key = $key;
        //循环多次加密
        for ($i = 0; $i < $circulation_num; $i++) {
            //获取本轮加密所使用的方法
            $func = $func_arr[$i % $func_num];
            //进行加密操作
            $secret_key = $func($secret_key . $interference_code);
        }
        return $secret_key;
    }

    /**
     * 验证密钥系统的测试方法
     * 此方法仅用于测试阶段
     */
    function checkTest() {
        $private_key = $_GET['private_key'];
        $secret_key = $_GET['secret_key'];
        $chk_secret_key = $this->secretKey($private_key);
        echo $chk_secret_key == $secret_key ? "success" : "failure";
    }

    /**
     * 验证私钥和密钥的合法性,时效性
     * 
     * @return int 返回验证状态码
     *                  1:验证通过
     *                  2:时效性验证未通过
     *                  3:合法性验证未通过
     */
    function checkCode() {

        //获取手机平台
        $mobile_platform = trim($_POST['mobile_platform']);
        //获取通讯协议版本
        $agreement = trim($_POST['agreement']);
        //新版本加密算法
        if ($agreement == "3.2") {
            $return = $this->checkCode_3_2();
        } else {
            //老版本加密算法
            $return = $this->checkCode_3_1();
        }


        //如果验证没通过返回3
        if ($return !== 1) {
            $ret['ret'] = $return;
            $ret['status'] = -2;
            $ret['message'] = '合法性验证未通过';
            echo jsonStr($ret);
            exit(0);
        }
        //所有验证均通过则返回1
        return 1;
    }

    /**
     * 验证私钥和密钥的合法性,时效性
     * 
     * @return int 返回验证状态码
     *                  1:验证通过
     *                  2:时效性验证未通过
     *                  3:合法性验证未通过
     */
    private function checkCode_3_2() {
        $flag = (int) D("System")->readConfig('checkCode_3_2');
        if ($flag == 2) {
            return 2;
        }
        $private_key = (string) trim($_POST['private_key']);
        $mobile_platform = trim($_POST['mobile_platform']);
        if ($mobile_platform == "android") {
//            return urldecode($private_key);
            //安卓平台算法
//            $private_key = urldecode($private_key);
            $time = authcodeAndroid($private_key, "DECODE");
//            return $time;
        } else {
            //IOS平台算法
            $time = authcodeIos($private_key, "DECODE");
        }

        //如果解码失败则返回失败
        if (!$time) {
            return 3;
        }
        //进行校验
        $secret_key = trim($_POST['secret_key']);
//        return $secret_key;
        $chk_secret_key = secretCode($private_key, $mobile_platform);
//        return $chk_secret_key . ' xxxx ' . $secret_key . ' ' . $mobile_platform . ' ' . $_POST['agreement'];
        //如果验证没通过返回3
        if ($chk_secret_key <> $secret_key) {
            return 4 . $chk_secret_key . ' xxxx ' . $secret_key . ' ' . $mobile_platform . ' ' . $_POST['agreement'];
        }
        //所有验证均通过则返回1
        return 1;
    }

    /**
     * 验证私钥和密钥的合法性,时效性
     * 
     * @return int 返回验证状态码
     *                  1:验证通过
     *                  2:时效性验证未通过
     *                  3:合法性验证未通过
     */
    private function checkCode_3_1() {
        $flag = (int) D("System")->readConfig('checkCode_3_1');
        //功能未开启
        if ($flag == 2) {

            return 2;
        }
        //获取私钥
        $private_key = trim($_GET['private_key']);
        //获得接口请求的时候的得到的时间戳
        $time = $this->privateKeyDecode($private_key);
        //验证时效性
        $timex = time() - $time;
        //时间验证未通过则返回3
        if ($timex > 40) {
            return 3;
        }
        //获取密钥
        $secret_key = trim($_GET['secret_key']);
        //验证合法性
        $chk_secret_key = $this->secretKey($private_key);
        //如果验证没通过返回4
        if ($chk_secret_key <> $secret_key) {
            return 4;
        }
        //所有验证均通过则返回1
        return 1;
    }

    public function androidTest() {
        exit;
        $private_key = "nx2agIhSUq4X4h3vn7TuI5FaWxY+U7GuHjHn5udSczuLSG2/MZKY";
        $time = authcodeAndroid($private_key, "DECODE");
        echo "{$time}<br>";

        $secret_key = "ac3ba640038ded637fe52f405c55acdf";

        echo "{$secret_key}<br>";

        $chk_secret_key = secretCode($private_key, "android");

        echo "{$chk_secret_key}<br>";
    }

    public function iostest() {
        exit;
        $time = 1429269798;

        echo "时间戳加密:" . $private_key = authcodeIos($time, "ENCODE");
        echo "<br>";
        echo "时间戳校验:" . secretCode("60d9NjM2Yjg3NzA5ZmE1Y2M1ZjEwNWJjN2E1YmQ1ZGIxNDNjMTQ4ZWJiZTdkNDhkNTE2NjA3NTE3YTI4NTk5M2E2Y2RjYjA0YzgwNDczNmYxYmZkN2U4");
    }

    public function test() {
        exit('');
        $sys = D('system');
        test($sys->systemSwitch());

//            const char * string_char =[string UTF8String];
//    NSLog(@"%s", string_char);
//    long count = strlen(string_char);
//    NSLog(@"count:%lu", (unsigned long)count);
    }

}
