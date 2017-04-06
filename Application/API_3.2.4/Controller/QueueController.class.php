<?php

/**
 * 话费队列 接口
 * @author ww
 */
class QueueController extends CommonController {    
    
    public function _initialize()
    {
        $ACTION_NAME = strtolower(ACTION_NAME);
//        var_dump($ACTION_NAME);exit;
        if (in_array($ACTION_NAME, array('switchstatus', 'mianecut'))) {//先判断是否需要验证会员信息
        } else {
            parent::_initialize();
            $userId = I('post.userId');
            $phone = I('post.phone');
            $return['success'] = true;
            //var_dump($userId);die;

            if ($phone && $userId) {//判断参数是否为空
                $model = D("Members");
                if ($phone == '12345678900') {
                    $res = $model->getUserDataByPhone($phone, 'id,freeze');
                } else {
                    $res = $model->checkUserId($phone, $userId, 'id,freeze');
                }
                //var_dump($model->getLastSql());die;
                //$res = $model->checkUserId($phone, $userId, 'id,freeze');

                if (empty($res['id'])) {//先判断账号是否存在
                    $return['status'] = 35;
                    $return['message'] = '账号异常，已退出登录！ ';
                    //$return['info'] = array();
                    echo jsonStr($return);
                    exit(0);
                } else {
                    if ($res['freeze'] != '0') {//验证账号是否非法
                        $return['status'] = 33;
                        $return['message'] = '账号非法，暂时无法完成此操作';
                        //$return['info'] = array();
                        echo jsonStr($return);
                        exit(0);
                    } else {
                        if (in_array($ACTION_NAME, array('commentslist'))) {
                            $this->userId = $res['id'];
                        } else {
                            if ($res['id'] == 44427) {
                                $return['status'] = 32;
                                $return['message'] = '请到个人中心登录';
                                //$return['info'] = array();
                                echo jsonStr($return);
                                exit(0);
                            }
                            $this->userId = $res['id'];
                            if (in_array($ACTION_NAME, array('exchange', 'preexchange', 'newslist'))) {


                                $userId = $this->userId;
                                $type = array(
                                    'exchange' => '2',
                                );
                                logAPI($type[$ACTION_NAME], $userId);
                            }
                        }
                    }
                }
            } else {
                $return['message'] = '操作失败';
                $return['status'] = 10;
                //$return['info'] = array();
                echo jsonStr($return);
                exit(0);
            }
        }
    }
    
    /**
     * 统一执行
     */
    public function exec($step = 10)
    {
        $step = $_GET['step'] ? $_GET['step'] : $step;
        
        //获取该步骤下的队列记录
        $where  = array(
            'execStep' => $step,
            'errorCode' => 1
        );
        $queueList  = M('PosterBillQueue')->where($where)->select();
        if($queueList && is_array($queueList))
        {
            foreach($queueList as $k => $v)
            {
                switch($step)
                {
                    case 10: //根据概率得出是否中奖相关处理
                        $prob   = $this->getProbByUserId($v['userId']);
                        $proArr = array(100-$prob, $prob);
                        $isWinning  = $this->getRand($proArr);
                        if($isWinning)
                        {
                            M('PosterBillQueue')->where(array('id' => $v['id']))->save(array('execStep' => 20));
                            $this->probCumsum($v['userId'], -1);
                        }else
                        {
                            M('PosterBillQueue')->where(array('id' => $v['id']))->save(array('errorCode' => 11, 'errorMsg' => '概率中奖失败'));
                            $this->probCumsum($v['userId'], 1);
                        }
                        break;
                    case 20: //判断是否还有资金配额可用于此用户的充值面额进行充值
                        
                        break;
                    default:
                        break;
                }
            }
        }
    }
    
    /**
     * 
     */
    public function denominationCut()
    {
        
    }
    
    /**
     * 根据面额返回余量
     */
    public function getDenominationTotal($denomination = 1)
    {
        //取出当前时间段
        $where['key']  = array(
                                'in', 
                                "exchange_start_time_1,exchange_end_time_1,exchange_start_time_2,exchange_end_time_2,exchange_start_time_3,exchange_end_time_3"
        );
        $rs = M('ConfigMember')->where($where)->select();
        $time = time();
        foreach($rs as $v)
        {
            $arr[$v['key']] = $v;
        }
        
        $period = 0;
        if($time > $arr['exchange_start_time_1'] && $time < $arr['exchange_end_time_1'])
        {
            $period = 1;
        }else if($time > $arr['exchange_start_time_2'] && $time < $arr['exchange_end_time_2'])
        {
            $period = 2;
        }else if($time > $arr['exchange_start_time_3'] && $time < $arr['exchange_end_time_3'])
        {
            $period = 3;
        }
        
        //根据时间段开始时间取出余量，如果没有则取初始量
        unset($where);
        $where['startTime'] = $arr['exchange_start_time_'.$period]['value'];
        $where['denomination'] = $denomination;
        $rs = M('Chongzhiyuliang')->where($where)->find();
        if(!$rs)
        {
            unset($where);
            $where['key'] = 'reserve_'. $period .'_'. $denomination;
            $rs = M('ConfigMember')->where($where)->find();
            $yuliang = $rs['value'];
            
            //将总数添加到余量表
            $data = array(
                'startTime' => $arr['exchange_start_time_'.$period]['value'],
                'total' => $yuliang['value'],
                'denomination' => $denomination
            );
            M('Chongzhiyuliang')->add($data);
        }else
        {
            $yuliang = $rs['total'];
        }
        return $yuliang ? $yuliang : 0;
    }
    
    /**
     * 兑换功能状态
     */
    public function switchStatus()
    {
        $where['key'] = 'exchange_switch_3_2';
        $rs = M('config_member')->field('value')->where($where)->find();
        $exchange_switch_status = $rs['exchange_switch_status'];

        //当系统状态为不正常的时候关闭整个系统
        if ($exchange_switch_status != 1) {
            $ret['status'] = 10002;
            $ret['message'] = '兑换功能暂时关闭';
            echo jsonStr($ret);
            exit(0);
        }
        $ret['status'] = 10000;
        $ret['message'] = '兑换功能开启';
        echo jsonStr($ret);exit();
    }
    
    /**
     * 更新是否可兑换话费   默认更新为否    
     * @param $val int 1:正常,2:关闭,3:暂停
     */
    public function updateSwitchStatus($val = 2)
    {
        $where['key'] = 'exchange_switch_3_2';
        $data['value'] = $val;
        M('config_member')->where($where)->save($data);
    }
    
    /**
     * 兑换自动开关。 
     */
    public function exchangeSwitch()
    {
        //取出当前时间段
        $where['key']  = array(
                                'in', 
                                "exchange_start_time_1,exchange_end_time_1,exchange_start_time_2,exchange_end_time_2,exchange_start_time_3,exchange_end_time_3"
        );
        $rs = M('ConfigMember')->where($where)->select();
        $time = time();
        foreach($rs as $v)
        {
            $arr[$v['key']] = $v;
        }
        
        $period = 0;
        if($time > $arr['exchange_start_time_1'] && $time < $arr['exchange_end_time_1'])
        {
            $period = 1;
        }else if($time > $arr['exchange_start_time_2'] && $time < $arr['exchange_end_time_2'])
        {
            $period = 2;
        }else if($time > $arr['exchange_start_time_3'] && $time < $arr['exchange_end_time_3'])
        {
            $period = 3;
        }
        
        //当前时间在充值时间段内开启可兑换，不在充值时间段内关闭可兑换
        if($period == 0)
        {
            $this->updateSwitchStatus(2);
        }else
        {
            $this->updateSwitchStatus(1);
        }
    }
    
    /**
     * 检查本时段用户提交入库的总记录数，如果大于指定数量则更新是否可充值为否
     */
    public function updateSwitchStatusByTotal()
    {
        //取出当前时间段
        $where['key']  = array(
                                'in', 
                                "exchange_start_time_1,exchange_end_time_1,exchange_start_time_2,exchange_end_time_2,exchange_start_time_3,exchange_end_time_3"
        );
        $rs = M('ConfigMember')->where($where)->select();
        $time = time();
        foreach($rs as $v)
        {
            $arr[$v['key']] = $v;
        }
        
        $period = 0;
        if($time > $arr['exchange_start_time_1'] && $time < $arr['exchange_end_time_1'])
        {
            $period = 1;
        }else if($time > $arr['exchange_start_time_2'] && $time < $arr['exchange_end_time_2'])
        {
            $period = 2;
        }else if($time > $arr['exchange_start_time_3'] && $time < $arr['exchange_end_time_3'])
        {
            $period = 3;
        }
        
        //不在充值时间段
        if($period == 0)
        {
            return false;
        }
        
        //根据时间段开始时间取出余量，如果没有则取初始量
        unset($where);
        $where['ctime']  = array('gt', $arr['exchange_start_time_'.$period]['value']);
        $where['ctime']  = array('lt', $arr['exchange_end_time_'.$period]['value']);
        $total  = M('PosterBillQueue')->where($where)->count();
        if($total > 1000)
        {
            $this->updateSwitchStatus();
        }
    }
    
    /**
     * 根据userId更新或增加兑换概率
     */
    public function probCumsum($userId, $prob)
    {
        $where['userId'] = $userId;
        $M  = M('ExchangeProb');
        $rs = $M->where($where)->count();
        if($rs > 0)
        {
            $data['prob']   = "`prob`+$prob";
            $M->where($where)->save($data);
        }else
        {
            $data['prob']   = $prob;
            $data['userId']   = $userId;
            $M->add($data);
        }
    }
                    
    /**
     * 根据userId返回用户充值概率     基础概率+概率表用户概率小于等于最大概率
     */
    public function getProbByUserId($userId)
    {
        //用户概率
        $where['userId'] = $userId;
        $M  = M('ExchangeProb');
        $rs = $M->where($where)->find();
        
        $userProb = $rs['prob'] ? $rs['prob'] : 0;
        
        //用户基础概率
        unset($where);
        $where['key'] = 'exchangeBaseProb';
        $rs = M('ConfigMember')->where($where)->find();
        
        $baseProb = $rs['value'] ? $rs['value'] : 0;
        
        $prob = $userProb + $baseProb;
        
        //用户最大概率
        unset($where);
        $where['key'] = 'exchangeMaxProb';
        $rs = M('ConfigMember')->where($where)->find();
        if(!empty($rs['value']) && $prob > $rs['value'])
        {
            $prob = $rs['value'];
        }
        return $prob;
    }
    
    
    
    /**
     * 添加队列
     */
    public function checkExchangeNew()
    {
        //判断验证码合法性
        //$this->checkKey();
        //echo I('post.userId').'-'.I('post.handlePwd').'-'.I('post.version').'-'.I('post.money').'-'.I('post.phone');die;
        if (is_empty(I('post.userId')) || is_empty(I('post.handlePwd')) || is_empty(I('post.version')) || is_empty(I('post.money')) || is_empty(I('post.phone'))) {
            $ret['status'] = -888;
            $ret['message'] = '传参不完整';
            echo jsonStr($ret);
            exit(0);
        }
        
        $userId = I('post.userId');
        $money = I('post.money', '', 'intval');
        $array = percent();
        
        $data['status'] = 1;
        $data['userId'] = $userId;
        $data['integral'] = $array[$money]; //兑换的飞币
        $data['money'] = $money; //兑换的话费
        $data['phone'] = I('post.phone'); //充值的手机号码
        $data['param']  = json_encode($_POST);
        $data['cdate']  = date('Ymd', time());
        $data['ctime']  = time();
        M('PosterBillQueue')->add($data);
        
        $ret['status'] = 1;
        $ret['message'] = '参数完整';
        echo jsonStr($ret);exit();
    }
    
    /**
     * @param type $userId
     * @return boolean
     */
    public function step1()
    {
        $ACTION_NAME = strtolower(ACTION_NAME);
        $userId = I('post.userId');
        $phone = I('post.phone');
        $return['success'] = true;

        if ($phone && $userId) {//判断参数是否为空  
            $model = D("Members");
            if ($phone == '12345678900') {
                $res = $model->getUserDataByPhone($phone, 'id,freeze');
            } else {
                $res = $model->checkUserId($phone, $userId, 'id,freeze');
            }
            //$res = $model->checkUserId($phone, $userId, 'id,freeze');

            if (empty($res['id'])) {//先判断账号是否存在
                $return['status'] = 35;
                $return['message'] = '账号异常，已退出登录！ ';
                echo jsonStr($return);
                exit(0);
            } else {
                if ($res['freeze'] != '0') {//验证账号是否非法
                    $return['status'] = 33;
                    $return['message'] = '账号非法，暂时无法完成此操作';
                    echo jsonStr($return);
                    exit(0);
                } else {
                    if (in_array($ACTION_NAME, array('commentslist'))) {
                        $this->userId = $res['id'];
                    } else {
                        if ($res['id'] == 44427) {
                            $return['status'] = 32;
                            $return['message'] = '请到个人中心登录';
                            //$return['info'] = array();
                            echo jsonStr($return);
                            exit(0);
                        }
                        $this->userId = $res['id'];
                        if (in_array($ACTION_NAME, array('exchange', 'preexchange', 'newslist'))) {


                            $userId = $this->userId;
                            $type = array(
                                'exchange' => '2',
                            );
                            logAPI($type[$ACTION_NAME], $userId);
                        }
                    }
                }
            }
        } else {
            $return['message'] = '操作失败';
            $return['status'] = 10;
            //$return['info'] = array();
            echo jsonStr($return);
            exit(0);
        }
        
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
        //$this->checkKey();

        if (is_empty(I('post.userId')) || is_empty(I('post.handlePwd')) || is_empty(I('post.rechargePhone')) || is_empty(I('post.version')) || is_empty(I('post.money')) || is_empty(I('post.phone'))) {
            $ret['status'] = -888;
            $ret['message'] = '传参不完整';
            echo jsonStr($ret);
            exit(0);
        }
        
        // 查询
        $field = 'id,uniqueId,name,jpush,image,imageUrl,encrypt,integral,cityId,provinceId,freeze,handlePassword,type';
        $model = D('Members');
        $res = $model->checkUserId(I('post.phone'), $_POST['userId'], $field);
        //echo $res['id'];die;
        if (is_empty($res['id'])) {//判断唯一码是否有效
            $ret['status'] = 10;
            $ret['message'] = '操作失败';
            echo jsonStr($ret);
            exit(0);
        }
        
        $userId = $this->userId;
        //判断会员当天操作错误次数
        $errorNum = $model->getHandleErrorNum($res['id'], '4');
        if ($errorNum > 4) {//判断错误次数
            $ret['status'] = 21;
            $ret['message'] = '您的兑换密码错误尝试超限，请明天再试';
            echo jsonStr($ret);
            exit(0);
        }

        //判断兑换密码是否正确
        $handlePassword = I('post.handlePwd');
        if (md5(md5($handlePassword . $res['encrypt'])) != $res['handlePassword']) {

            //$data['imei'] = I('post.imei');
            //$data['uniqueId'] = $res['uniqueId'];
            $data['userId'] = $userId;
            $data['integral'] = I('post.integral');
            $data['money'] = I('post.money');
            $data['phone'] = I('post.phone');
            $data['status'] = '4';
            $data['addTime'] = time();

            //M('MembersConvertLog')->data($data)->add();
            $model->addHandleErrorLog($data);

            $errorNum = $model->getHandleErrorNum($res['id'], '4');
            if($errorNum==5){
                $ret['status'] = 21;
                $ret['message'] = '您的兑换密码错误尝试超限，请明天再试';
            }else{
                $ret['status'] = 20;
                $ret['message'] = '兑换密码错误，请重新输入（' . (5 - $errorNum) . ')';
            }
           

            echo jsonStr($ret);
            exit(0);
        }
        
        $flag = D('Members')->getUserStatus($userId);
        if ($flag != 2) {//判断会员是否正常
            $return['status'] = -100;
            $return['message'] = '抱歉，您的飞报号权限受限，暂时无法完成此操作！';
            echo jsonStr($return);
            die;
        }

        $this->ret['info'] = '0';
        $return['success'] = true;
        $phone = I('post.phone');
        $money = I('post.money');
        if (is_empty($userId) || is_empty($phone) || is_empty($money)) {
            $return['status'] = -888;
            $return['message'] = '传参不完整';
        } else {
            /////////////此处有判断方法 1：池，2：对应值上限，3：飞币上限
            $flag = D('Common')->ck_reserve($userId, $money);
            if ($flag['status'] != 1) {//判断充值限额
                $return['status'] = $flag['status'];
                $return['message'] = $flag['msg'];
                echo jsonStr($return);
                die;
            }
            $re = D('PosterBillLog')->exchange();

            if ($re === true) {


                $model->setHandleErrorStatus($res['id'], '4');

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
            } else if ($re == -10) {
                $return['status'] = 10;
                $return['message'] = '操作失败';
            } else {
                if ($re["error"] == 1) {
                    $return['status'] = 1;
                    $return['message'] = '兑换成功';
                } else {
                    $return['status'] = -1;
                    $return['message'] = '兑换失败';
                }
            }
        }
        echo jsonStr($return);exit();
    }
    
    
    
    public function verify($userId = NULL)
    {
        if(empty($userId) || !$userId || intval($userId) <= 0)
        {
            return false;   //参数错误
        }
        
        $M  = new PosterBillQueueModel();
        $rs = $M->verify($userId);
        
        return $rs;
    }
    
    public function addQueue($userId = NULL, $status = 1, $logId = NULL)
    {
        if(!$userId || !$logId)
        {
            return false; //参数错误
        }
        
        $M  = new PosterBillQueueModel();
        $rs = $M->addQueue($userId, $status, $logId);
        
        return $rs;
    }
    
    public function updQueue($id = NULL, $status = NULL)
    {
        if(!$id || !$status)
        {
            return false; //参数错误
        }
        $data['id'] = $id;
        $data['status'] = $status;
        
        $M  = new PosterBillQueueModel();
        $rs = $M->updQueue($data);
        
        return $rs;
    }
    
    /**
     * 概率随机
     */
    public function getRand($proArr) { 
        $result = ''; 
        //概率数组的总概率精度 
        $proSum = array_sum($proArr); 
        //概率数组循环 
        foreach ($proArr as $key => $proCur) { 
            $randNum = mt_rand(1, $proSum);             //抽取随机数
            if ($randNum <= $proCur) { 
                $result = $key;                         //得出结果
                break; 
            } else { 
                $proSum -= $proCur;                     
            } 
        } 
        unset ($proArr); 
        return $result; 
    }
}