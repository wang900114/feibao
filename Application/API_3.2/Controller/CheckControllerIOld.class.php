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
//        $this->checkUserFlag();
        //控制充值接口
        $this->ctrlBill();
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
                $flag = D("System")->readConfig('user_switch_3.2');
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
                $flag = D("System")->readConfig('user_switch_3.2');
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
        echo "时间戳:";
        echo time();
        echo "<br>";
        echo "私钥:";
        echo $private_key = strrev(base64_encode(strrev(base64_encode(strrev(time())))));
        echo "<br>";
        echo "私钥解密成时间戳:";
        echo $this->privateKeyDecode($private_key);
        echo "<br>";
        echo "私钥加密成密钥:";
        echo $this->secretKey($private_key);
        echo "<br>";
    }

    /**
     * 产生私钥信息
     */
    public function privateKey() {
        $private_key = strrev(base64_encode(strrev(base64_encode(strrev(time())))));
        $return['private_key'] = $private_key;
        echo json_encode($return);
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
        //获取私钥
        $private_key = trim($_GET['private_key']);
        //获取密钥
        $secret_key = trim($_GET['secret_key']);
        //获得接口请求的时候的得到的时间戳
        $time = $this->privateKeyDecode($private_key);
        //验证时效性
        $timex = time() - $time;
        //时间验证未通过则返回2
//        if ($timex > 4) {
//            $ret['status'] = -1;
//            $ret['message'] = "合法性验证未通过";
//            echo jsonStr($ret);
//            exit(0);
//        }
        //验证合法性
        $chk_secret_key = $this->secretKey($private_key);
        //如果验证没通过返回3
        if ($chk_secret_key <> $secret_key) {
            $ret['status'] = -2;
            $ret['message'] = '合法性验证未通过';
            echo jsonStr($ret);
            exit(0);
        }
        //所有验证均通过则返回1
        return 1;
    }

    public function test() {
        $sys = D('system');
        test($sys->systemSwitch());
    }

}
