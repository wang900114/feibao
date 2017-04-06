<?php

/**
 * 话费 接口
 * @author Jine <luxikun@andlisoft.com>
 */
class BillController extends CommonController {

    /**
     * 初始化
     */
    public function _initialize() {
        parent::_initialize();
        
        //var_dump($_SERVER);die;
        
        A('API_3.2/Public')->testHTTPS(); //验证 https

        A('API_3.2/Public')->testPersonalToken(); //验证 个人 token
        // 记录接口调用日志
        $ACTION_NAME = strtolower(ACTION_NAME);
        if (in_array($ACTION_NAME, array('exchange'))) {
            $userId = I('post.userId');
            $type = array(
                'exchange' => '2',
            );
            logAPI($type[$ACTION_NAME], $userId);
        }
    }

    /**
     * 兑换记录列表
     * @param  string $id ID
     * @param  string $userId 用户ID
     * @param  string $type 0刷新；1加载
     * @param  string $pageSize 分页大小
     * @return json
     */
    public function newsList() {
        $return['success'] = true;
        //echo I('post.userId'); die;
        $userId = I('post.userId');
        $type = I('post.type');
        $pageSize = I('post.pageSize');

        if (is_empty($userId) || is_empty($type) || is_empty($pageSize)) {
            $return['status'] = -888;
            $return['message'] = '传参不完整';
        } else {
            $re = D('PosterBillLog')->getList();
            // echo D('PosterBillLog')->getLastSql();die;
            // var_dump($re);
            if (is_bool($re) && empty($re)) {
                $return['status'] = -1;
                $return['message'] = '查询失败';
            } else if ((is_array($re) || is_null($re)) && empty($re)) {
                $return['status'] = 0;
                $return['message'] = '没有数据了';
            } else {
                $return['status'] = 1;
                $return['message'] = '查询成功';
                $return['info'] = $re;
            }
        }
        echo jsonStr($return);
    }

    /**
     * 返回兑换前调用信息
     * @param  string $userId 用户ID
     * @return json
     */
    public function preExchange() {
        $return['success'] = true;

        $userId = I('post.userId');

        if (is_empty($userId)) {
            $return['status'] = -888;
            $return['message'] = '传参不完整';
        } else {
            $re = D('PosterBillLog')->getPreMessage();
            if (is_bool($re) && empty($re)) {
                $return['status'] = -1;
                $return['message'] = '查询失败';
            } elseif ((is_array($re) || is_null($re)) && empty($re)) {
                $return['status'] = 0;
                $return['message'] = '没有数据了';
            } else {
                $return['status'] = 1;
                $return['message'] = '查询成功';
                $return['info'] = $re;
            }
        }
        echo jsonStr($return);
    }

    /**
     * 查询会员状态
     * flag 1 被冻结 2 正常 3 非法 4 无用户
     * @param number $uid 会员ID
     * @return int
     */
    function getUserStatus($uid) {
        $flag = 4;
        if (is_numeric($uid)) {
            $where = array("id" => $uid);
            //$result = $this->selData($where, 1, "freeze");
            $result = D('Members')->field('freeze')->where($where)->select();

            if ($result) {
                if ($result[0]['freeze'] == 0) {
                    $flag = 2;
                }
                if ($result[0]['freeze'] == 1) {
                    $flag = 1;
                }
                if ($result[0]['freeze'] == 2) {
                    $flag = 3;
                }
            }
        }
        return $flag;
    }

    /**
     * 正式 兑换
     * @param  string $userId 用户ID
     * @param  string $phone 用户手机号
     * @param  string $money 用户兑换的面额
     * @return json
     */
    public function exchange() {
        exit();
        //判断兑换功能状态
        $system = D('system');
        $exchange_switch_status = $system->exchangeSwitchStatus();
        //当系统状态为不正常的时候关闭整个系统
        if ($exchange_switch_status != 1) {
            $ret['status'] = 10002;
            $ret['message'] = '兑换功能暂时关闭';
            echo jsonStr($ret);
            exit(0);
        }
        //判断验证码合法性
        $this->checkKey();

        //判断非法ip
       /* $result = D('Ip')->filtersel($_SERVER['SERVER_ADDR']);
        if ($result) {
            $ret['status'] = 10003;
            $ret['message'] = '非法ip';
            echo jsonStr($ret);
            exit(0);
        }*/
        $userId = I('post.userId');
        
        $old_user_switch = D("System")->readConfig('user_switch_3.2');
        //获取用户上传的手机种类
        $mobileflag = I('post.mobileflag');
        //如果老用户关闭充值功能并且用户没有传递手机种类(老版本不传递此参数)
        if ($old_user_switch == '2' && !$mobileflag) {
            $old_user_showmassage_swith = D("System")->readConfig('old_user_showmassage_swith');
            if ($old_user_showmassage_swith == '1') {
                $massage = "官人,您的版本太老了,无法兑换话费了呢!赶快升级吧!";
                //给用户推送升级通知并给其消息中心发送消息
                pushMassageAndWriteMassage($massage, trim($userId));
            }
            jsonMassageReturn(-11003, $message);
        }
        
//        //从此处废除虚拟用户的判断
//        $reMembers = M('Members')->field('id,integral,mobileflag')->where('id ='.$userId)->select();
//
//        //判断会员当前是否是虚拟机//曹洪猛增加,$mobileflag的来源是什么?此处还能写入多字节的中文空格!
//        if($mobileflag=='2' && ($reMembers[0]['mobileflag']=='1')){
//        	$datas['mobileflag']='2';
//            $reIntegral = M('Members')->where($map1)->save($datas);
//        }
//        
//        $result = M('Members')->field('mobileflag')->where('id ='.$userId)->select();
//        if ($result[0]['mobileflag']) {
//            $ret['status'] = 10004;
//            $ret['message'] = '虚拟用户';
//            echo jsonStr($ret);
//            exit(0);
//        }

        $return['success'] = true;

        
        $phone = I('post.phone');
        $money = I('post.money');

        if (is_empty($userId) || is_empty($phone) || is_empty($money)) {
            $return['status'] = -888;
            $return['message'] = '传参不完整';
        } else {
            //获取用户当前状态
            /*$flag = $this->getUserStatus($userId);

            //echo $flag;die;

            if ($flag != 2) {//判断会员状态
                $return['status'] = -100078;
                $return['message'] = '非法用户';
                //$this->DwzCallback('充值失败', '', 300);
                echo jsonStr($return);
                die;
            }*/
            
            /////////////此处有判断方法 1：池，2：对应值上限，3：飞币上限
            $flag = D('Common')->ck_reserve($userId, $money);
            //var_dump($flag);die;

            if ($flag['status'] != 1) {//判断充值限额
                $return['status'] = $flag['status'];
                $return['message'] = $flag['msg'];
                //$this->DwzCallback('充值失败', '', 300);
                echo jsonStr($return);
                die;
            }

            $result = 1;

            if ($result) {
                $re = D('PosterBillLog')->exchange();

                if ($re === true) {
                    $return['status'] = 1;
                    $return['message'] = '兑换成功';
                } else if ($re == -2532) {
                    $return['status'] = -2532;
                    $return['message'] = '手机号格式错误';
                } else if ($re == -2533) {
                    $return['status'] = -2533;
                    $return['message'] = '充值金额错误';
                } else if ($re == -2531) {
                    $return['status'] = -2531;
                    $return['message'] = '不在兑换时间段内';
                } else if ($re == -2530) {
                    $return['status'] = -2530;
                    $return['message'] = '飞币不足';
                } else if ($re == -2534) {
                    $return['status'] = -2534;
                    $return['message'] = '运营商充值失败';
                } else {
                    if ($re["error"] == 1) {
                        $return['status'] = 1;
                        $return['message'] = '兑换成功';
                    } else {
                        $return['status'] = -1;
                        // $return['message'] = '兑换失败:'.$re["error"].':'.$re["message"];
                        $return['message'] = '兑换失败:' . $re["message"];
                    }
                }
            } else {
                //推送消息
                $m = D('Members');
                $uid = $m->where('id=' . $userId)->getField('id');
                if ($uid) {
                    $jpush = A('Admin_3.2/JPush');
                    $content = '亲，您的账号过于频繁';
                    $res = $jpush->pushNoticeWithOutDB($uid, $content);
                }

                $return['status'] = -1;
                $return['message'] = '用户失败信息';
            }
        }
        echo jsonStr($return);
    }
    
    
    /**
     * 正式 兑换
     * @param  string $userId 用户ID
     * @param  string $phone 用户手机号
     * @param  string $money 用户兑换的面额
     * @return json
     */
    public function exchangeTest() {
        die;
        //判断兑换功能状态
        $system = D('system');
        $exchange_switch_status = $system->exchangeSwitchStatus();
        //当系统状态为不正常的时候关闭整个系统
        if ($exchange_switch_status != 1) {
            $ret['status'] = 10002;
            $ret['message'] = '兑换功能暂时关闭';
            echo jsonStr($ret);
            exit(0);
        }
        //判断验证码合法性
//        $this->checkKey();

        //判断非法ip
       /* $result = D('Ip')->filtersel($_SERVER['SERVER_ADDR']);
        if ($result) {
            $ret['status'] = 10003;
            $ret['message'] = '非法ip';
            echo jsonStr($ret);
            exit(0);
        }*/
        $userId = I('post.userId');
        
        $result = M('Members')->filed('mobileflag')->where('id ='.$userId)->select();
        if ($result[0]['mobileflag'] == "2") {
            $ret['status'] = 10004;
            $ret['message'] = '虚拟用户';
            echo jsonStr($ret);
            exit(0);
        }

        $return['success'] = true;

        
        $phone = I('post.phone');
        $money = I('post.money');

        if (is_empty($userId) || is_empty($phone) || is_empty($money)) {
            $return['status'] = -888;
            $return['message'] = '传参不完整';
        } else {
            //获取用户当前状态
            /*$flag = $this->getUserStatus($userId);

            //echo $flag;die;

            if ($flag != 2) {//判断会员状态
                $return['status'] = -100078;
                $return['message'] = '非法用户';
                //$this->DwzCallback('充值失败', '', 300);
                echo jsonStr($return);
                die;
            }*/
            
            /////////////此处有判断方法 1：池，2：对应值上限，3：飞币上限
            $flag = D('Common')->ck_reserve($userId, $money);
            //var_dump($flag);die;

            if ($flag['status'] != 1) {//判断充值限额
                $return['status'] = $flag['status'];
                $return['message'] = $flag['msg'];
                //$this->DwzCallback('充值失败', '', 300);
                echo jsonStr($return);
                die;
            }

            $result = 1;

            if ($result) {
                $re = D('PosterBillLog')->exchange();

                if ($re === true) {
                    $return['status'] = 1;
                    $return['message'] = '兑换成功';
                } else if ($re == -2532) {
                    $return['status'] = -2532;
                    $return['message'] = '手机号格式错误';
                } else if ($re == -2533) {
                    $return['status'] = -2533;
                    $return['message'] = '充值金额错误';
                } else if ($re == -2531) {
                    $return['status'] = -2531;
                    $return['message'] = '不在兑换时间段内';
                } else if ($re == -2530) {
                    $return['status'] = -2530;
                    $return['message'] = '飞币不足';
                } else if ($re == -2534) {
                    $return['status'] = -2534;
                    $return['message'] = '运营商充值失败';
                } else {
                    if ($re["error"] == 1) {
                        $return['status'] = 1;
                        $return['message'] = '兑换成功';
                    } else {
                        $return['status'] = -1;
                        // $return['message'] = '兑换失败:'.$re["error"].':'.$re["message"];
                        $return['message'] = '兑换失败:' . $re["message"];
                    }
                }
            } else {
                //推送消息
                $m = D('Members');
                $uid = $m->where('id=' . $userId)->getField('id');
                if ($uid) {
                    $jpush = A('Admin_3.1/JPush');
                    $content = '亲，您的账号过于频繁';
                    $res = $jpush->pushNoticeWithOutDB($uid, $content);
                }

                $return['status'] = -1;
                $return['message'] = '用户失败信息';
            }
        }
        echo jsonStr($return);
    }

}
