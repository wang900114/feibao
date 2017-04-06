<?php

/**
 * 话费 接口
 * @author Jine <luxikun@andlisoft.com>
 */
class NewBillController extends CommonController {

    /**
     * 初始化
     */
    public function _initialize() {
        parent::_initialize();
        //var_dump($_SERVER);die;
        //A('API_3.2/Public')->testHTTPS(); //验证 https
        // 记录接口调用日志
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

    /**
     * 3.2兑换记录列表
     * @param  string $id ID
     * @param  string $userId 用户ID
     * @param  string $type 0刷新；1加载
     * @param  string $pageSize 分页大小
     * @return json
     */
    public function newsList() {
        $return['success'] = true;

        $userId = $this->userId;
        $type = I('post.type');
        $pageSize = I('post.pageSize');

        if (is_empty($userId) || is_empty($type) || is_empty($pageSize)) {
            $return['status'] = -888;
            $return['message'] = '传参不完整';
        } else {
            $re = D('PosterBillLog')->getList($userId);
            // echo D('PosterBillLog')->getLastSql();die;
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
        echo jsonStr($return);exit();
    }

    /**
     * 3.1返回兑换前调用信息
     * @param  string $userId 用户ID
     * @return json
     */
    /*
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
      $re['stock'] = 10;
      $return['info'] = $re;
      }
      }
      echo jsonStr($return);
      }
     */

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
     * 正式 兑换--3.1
     * @param  string $userId 用户ID
     * @param  string $phone 用户手机号
     * @param  string $money 用户兑换的面额
     * @return json
     */
    /*
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

      $userId = I('post.userId');
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
      $return['message'] = '兑换失败';
      }
      }
      }
      echo jsonStr($return);
      }
     */

    
    /**
     * 兑换状态
     */
    public function switchStatus()
    {
        $memcache = new Memcache;
        $memcache->connect(MEMCACHE_HOST, 11211);
        echo $memcache->get('mq_switch');exit();
    }
    
    /**
     * 兑换功能状态
     * 内部程序用
     */
    public function checkSwitchStatus() {
        $memcache = new Memcache;
        $memcache->connect(MEMCACHE_HOST, 11211);
        $exchange_switch_status = $memcache->get('mq_switch');
        if ($exchange_switch_status == 1) {
            return TRUE;
        } else {
            return FALSE;
        }
    }
    
    /**
     * 添加队列
     */
    public function checkExchange() {
        //判断当前是否可充值
        if (!$this->checkSwitchStatus()) {
            $ret['status'] = -7001;
            $ret['message'] = '%>_<% 手快有，手慢无，您下次请赶早！';
            echo json_encode($ret);
            exit();
        }
        //判断用户参数完整性
        $userId = $_POST['userId'];
        $phone = $_POST['phone'];
        $ret['success'] = true;
        if (empty($_POST['userId']) || empty($_POST['handlePwd']) || empty($_POST['version']) || empty($_POST['money'])) {
            $ret['status'] = -888;
            $ret['message'] = '%>_<% 请通过合法途径参与活动！';
            echo json_encode($ret);
            exit();
        }

        $data = array();
        $data['mq_name'] = $this->mq_name;
        $data['status'] = '1';
        $data['uniqueId'] = $_POST['userId'];
        $data['handlePassword'] = $_POST['handlePwd'];
        $data['money'] = $money; //兑换的话费
        $res=M('PosterBillQueue')->add($data);
//        $res = $this->db->insert('poster_bill_queue', $data);
        if ($res) {//判断是否添加成功
            $ret['status'] = 1;
            $ret['message'] = ':-D 排队成功，已进入队列请稍后查看';
        } else {
            $ret['status'] = 10;
            $ret['message'] = '%>_<% 排队失败';
        }

        echo json_encode($ret);
        exit();
    }
    /**
     * 3.2-兑换
     * @param  string $userId 用户ID
     * @param  string $phone 用户手机号
     * @param  string $imei 设备号
     * @param  string $rechargePhone 充值的手机号码
     * @param  string $integral 用户飞币
     * @param  string $money 用户兑换的面额
     * @return json
     */
    public function checkExchange_old() {
        exit();
        //检测是否能通过检测
        $this->checkKey();

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

        if (is_empty(I('post.userId')) || is_empty(I('post.handlePwd')) || is_empty(I('post.imei')) || is_empty(I('post.version')) || is_empty(I('post.money')) || is_empty(I('post.phone'))) {
            $ret['status'] = -888;
            $ret['message'] = '传参不完整';
            echo jsonStr($ret);
            exit(0);
        }
        $userId = $this->userId;
        
        if(I('post.rechargePhone')){
            $ret['status'] = 10;
            $ret['message'] = '非法操作';
            
            $resTimes = M('poster_bill_employees_log')->where('status="2" and userId='.$userId)->count('*');
            if($resTimes>=1){
                //设置非法
                //$resCheckRechargePhone=D('Members')->getUserInfo($userId);
                //if($resCheckRechargePhone['freeze']==0){
                    $dataCheckRecharge['freeze'] = '2';
                    M('Members')->where('id='.$userId)->data($dataCheckRecharge)->save();
                //}
            }
            
            //添加记录日志
            $dataChechMembers['userId']=$userId;
            $dataChechMembers['phone']=I('post.rechargePhone');
            $dataChechMembers['money']=I('post.money', '', 'intval');
            $dataChechMembers['addTime']=time();
            $dataChechMembers['status']='2';
                    
            M('poster_bill_employees_log')->data($dataChechMembers)->add();
            
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

        //判断会员当天操作错误次数
        //$wheres['userId'] = $res['id'];
        //$start = mktime(0, 0, 0, date("m"), date("d"), date("Y"));
        //$end = mktime(23, 59, 59, date("m"), date("d"), date("Y"));
        //$field = 'count(id) as total';
        //$wheres['_string'] = " addTime >=" . $start . " and addTime <=" . $end;
        //$resError = M('MembersConvertLog')->field($field)->where($wheres)->find();
        //echo $resError['total'];die;
        $errorNum = $model->getHandleErrorNum($res['id'], '4');

        if ($errorNum > 4) {//判断错误次数
            $ret['status'] = 21;
            $ret['message'] = '您的操作码错误尝试超限，请明天再试';

            echo jsonStr($ret);
            exit(0);
        }

        //判断操作码是否正确
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
                $ret['message'] = '您的操作码错误尝试超限，请明天再试';
            }else{
                $ret['status'] = 20;
                $ret['message'] = '操作码错误，请重新输入（' . (5 - $errorNum) . ')';
            }
           

            echo jsonStr($ret);
            exit(0);
        }

        //$userId = $res['id'];
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

    /**
     * 3.2返回兑换前调用信息
     * @param  string $userId 用户唯一码ID
     * @param  string $phone 手机号码
     * @return json
     */
    public function NewpreExchange() {
        $return['success'] = true;

        $userId = I('post.userId');
        $phone = I('post.phone');

        if (is_empty($userId) || is_empty($phone)) {
            $return['status'] = -888;
            $return['message'] = '传参不完整';
        } else {

            $userId = $this->userId;

            $re = D('PosterBillLog')->getPreMessage($userId);
            //var_dump($re);die;

            if (is_bool($re) && empty($re)) {//判断查询结果
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
        echo jsonStr($return);exit();
    }

    /**
     * 3.2-获取库存信息
     * @param  string $userId 用户唯一码ID
     * @param  string $phone 手机号码
     * @return json
     */
    function dataStock() {
        $return['success'] = true;
        $phone = I('post.phone');
        $userId = I('post.userId');

        if (is_empty($userId) || is_empty($phone)) {
            $return['status'] = -888;
            $return['message'] = '传参不完整';
        } else {
            $userId = $this->userId;

            $model = D("System");
            $billModel = D("PosterBillLog");
            $arrayData = array(
                array("denomination" => 1, "integral" => $model->readConfig("percent_1"), "stock" => $model->readConfig("reserve_1") - $billModel->getCountToday(1)>0 ? $model->readConfig("reserve_1") - $billModel->getCountToday(1) :0),
                array("denomination" => 10, "integral" => $model->readConfig("percent_10"), "stock" => $model->readConfig("reserve_10") - $billModel->getCountToday(10)>0 ? $model->readConfig("reserve_10") - $billModel->getCountToday(10) :0),
                array("denomination" => 30, "integral" => $model->readConfig("percent_30"), "stock" => $model->readConfig("reserve_30") - $billModel->getCountToday(30)>0 ? $model->readConfig("reserve_30") - $billModel->getCountToday(30) :0),
                array("denomination" => 50, "integral" => $model->readConfig("percent_50"), "stock" => $model->readConfig("reserve_50") - $billModel->getCountToday(50)>0 ? $model->readConfig("reserve_50") - $billModel->getCountToday(50) :0),
            );

            //充值时间限制
            $start_time = D('PosterBillLog')->getTodayStart();
            //获取每天开始的时间
            $start_hour = (int)D('PosterBillLog')->getConfig("start_hour");
            //获取每天结束的时间
            $end_hour = (int)D('PosterBillLog')->getConfig("end_hour");
            
            //返回兑换时间是否可用：1：正常，2：关闭
            $return['timeflag'] = 1;
            if (time() < ($start_time + $start_hour * 3600) || time() > ($start_time + $end_hour * 3600)) {
                $return['timeflag'] = 2;
            }


            /*
            $arrayData = array(
                array("denomination" => 1, "integral" => $model->readConfig("percent_1"), "stock" => $model->readConfig("reserve_1") - $billModel->getCountToday(1)),
                array("denomination" => 10, "integral" => $model->readConfig("percent_10"), "stock" => $model->readConfig("reserve_10") - $billModel->getCountToday(10)),
                array("denomination" => 30, "integral" => $model->readConfig("percent_30"), "stock" => $model->readConfig("reserve_30") - $billModel->getCountToday(30)),
                array("denomination" => 50, "integral" => $model->readConfig("percent_50"), "stock" => $model->readConfig("reserve_50") - $billModel->getCountToday(50)),
            );
            */
            $return['status'] = 1;
            $return['message'] = '查询成功';
            $return['info'] = $arrayData;
        }
        echo jsonStr($return);exit();
    }

}
