<?php

/**
 * 话费兑换 模型
 * @author Jine <luxikun@andlisoft.com>
 */
class PosterBillLogModel extends CommonModel {

    // protected $tableName = 'bill_log';

    /* 自动验证规则 */
    public $_validate = array(
            //array('imei','require','IMEI Required', self::EXISTS_VALIDATE ),
            //array('cityId','require','City ID Required', self::EXISTS_VALIDATE ),
            //array('provinceId','require','Province ID Required', self::EXISTS_VALIDATE ),
            // array('name','require','name必须！'),
    );

    /* 自动完成规则 */
    public $_auto = array(
            // array('image', 'Public/Images/member.gif', self::MODEL_INSERT),
            // array('addTime', 'time', self::MODEL_INSERT,'function'),
    );

    // public $viewFields = array(
    // 'BillLog'=>array('id','pid','userId'),//表 BillLog 的字段
    // 'Poster'=>array(//表 Poster 的字段（后面是映射）
    // 'title'=>'category_name',
    // '_on'=>'Blog.category_id=Category.id'//关联查询条件
    // ),
    // 'Members'=>array(//表 Members 的字段（后面是映射）
    // 'name'=>'username',
    // '_on'=>'Blog.user_id=User.id'//关联查询条件
    // ),
    // );

    /**
     * 返回某人的兑换记录
     * @param  string $id ID
     * @param  string $userId 用户ID
     * @param  string $type 0刷新；1加载
     * @param  string $pageSize 分页大小
     * @return array
     */
    public function getList($userId) {
        //$userId = I('post.userId');
        $type = I('post.type', 0);
        $pageSize = I('post.pageSize');
        $id = I('post.id', 0);

        $map['userId'] = $userId;
        if (!empty($type))
            $map['id'] = array('lt', $id);
        $order['id'] = 'desc';
        return $this->selData($map, $pageSize, 'id,integral,money,phone,time,status', $order);
    }

    /**
     * 兑换前调用信息
     * @param  string $userId 用户ID
     * @return array
     */
    public function getPreMessage($userId) {
        $re['status'] = $this->getConfig('bill_status');
        $re['startTime'] = $this->getConfig('bill_start_time');
        $re['endTime'] = $this->getConfig('bill_start_time');

        $map['id'] = $userId;
/*        
        //3.11需要添加判断参数
        $phone = I('post.phone');

        if(!empty($phone)){
            $model = D("Members");
            // 验证userId
            $field = 'id,uniqueId';
            $res = $model->checkUserId($phone, $map['id'], $field);
            //var_dump($res['id']);die;
            
            $map['id'] =$res['id'];
            $map['phone'] =$phone;
            //echo $map['id'];die;
        }
*/        
        $user = D('Members')->selData($map, 1, 'integral');
        //echo  D('Members')->getLastSql();die;
        
        $re['integral'] = $user[0]['integral'];

        //计算 单次最大换取值
        $bill_max = $this->getConfig('bill_max'); //后台设置的最大值
        $percent = $this->getConfig('bill_percent'); //兑换比例
        // $surplus = MoneyRange($user[0]['integral']/$percent);//可兑换的区间
        // $re['max'] = ($surplus > $bill_max) ? $bill_max : $surplus ;
        // $re['max'] = floor($user[0]['integral']/$percent) ;
        $re['max'] = $this->getExchangeRange($user[0]['integral']);

        // var_dump($user[0]['integral']/$percent);
        return $re;
    }

    /**
     * 正式 兑换话费
     * @param  string $userId 用户ID
     * @param  string $phone 代充值手机号
     * @param  string $money 面额
     * @return array
     */
    public function exchange() {
        $userId = I('post.userId');
        $field = 'id,uniqueId';
        $model=D('Members');
        $res=$model->checkUserId(I('post.phone'),I('post.userId'),$field);
        
        if($res['id']){//判断会员id是否有效
            $userId=$res['id'];
            $resPhone=$model->getUserInfo($res['id']);

            //判断会员-员工身份
            if($resPhone){
                $whereCheckMembers='phone ='.$resPhone['phone'].' and status="1"';
                $resCheckMembers = M('members_employees')->field('id')->where($whereCheckMembers)->find();
                //var_dump(M('members_employees')->getLastSql());die;
                
                if($resCheckMembers['id']){
                    //添加记录日志
                    $dataChechMembers['userId']=$res['id'];
                    $dataChechMembers['phone']=$resPhone['phone'];
                    $dataChechMembers['money']=I('post.money', '', 'intval');
                    $dataChechMembers['addTime']=time();
                    $dataChechMembers['status']='1';
                    
                    M('poster_bill_employees_log')->data($dataChechMembers)->add();
                    //echo M('poster_bill_employees_log')->getLastSql();
                    
                    $return['status'] = 10;
                    $return['message'] = '员工账号，不可充值';
                    
                    echo jsonStr($return);exit();
                }
            }
        }else{
            $return =-10;
            return $return;
        }
        
        
        $phone = $resPhone['phone'];
        //$phone = I('post.rechargePhone');
        //if(empty($phone)){
            //$phone = I('post.phone');
        //}
        
        $money = I('post.money', '', 'intval');
        $array = percent();
        $startTime = $this->getConfig('bill_start_time'); //兑换起点
        $endTime = $this->getConfig('bill_end_time'); //兑换终点
        $percent = $this->getConfig('bill_percent'); //兑换比例

        $now = time(); //订单时间
        $orderNumber = 'FB' . mt_rand(1000, 9999) . $now . mt_rand(1000, 9999); //订单号

        $configAll = C('RECHARGE');
        $config = $configAll['OFCARD'];


        if (!is_mobileNumber($phone)) {
            $return = -2532; //手机号不对
        } else if (!in_array($money, $configAll['cardnum'])) {
            $return = -2533; //充值金额错误
        } else if (( $now < $startTime ) || ( $now > $endTime )) {
            $return = -2531; //不在兑换时间段内
        } else {
            //查询用户飞币
            $map['id'] = $userId;
            $user = D('Members')->selData($map, 1, 'integral');

            //看 飞币兑换的金额 ，是否比要 兑换的话费 多
            //$surplus = MoneyRange($user[0]['integral']/$percent) - $money;
            //echo $map['id'];die;

            $surplus = $user[0]['integral'] - $array[$money];

            //var_dump($surplus);die;
            if ($surplus < 0) {
                $return = -2530; //飞币不足
            } else {
                //$bill_max = $this->getConfig('bill_max'); //后台设置的最大值
                // if ($money > $bill_max) {//非法篡改了 单次换取 的最大值
                //   $return = -2533; //充值金额错误
                //  } else {
                //一切正常，开始 充值
                // $rangeOfPhone = $this->roleOfRecharge($phone,$money);//充值规则判断
                /*
                  $rangeOfPhone = 'oufei'; //写死成欧飞的
                  if ($rangeOfPhone == 'oufei') {//欧飞
                  import("Common.Extend.RechargeOF", ROOT);
                  $ob = new RechargeOF($config['userid'], $config['userpws'], $config['version'], $config['cardid'], $configAll['cardnum'], $config['KeyStr'], $configAll['callback']);
                  $return = $ob->sendMoney($phone, $money, $now, $orderNumber);
                  } else if ($rangeOfPhone == 'gaoyang') {//高阳
                  $return = -2534; //运营商充值失败
                  } else {
                  $return = -2534; //运营商充值失败
                  }

                  if ($return["error"] == 1) {//充值成功
                  //扣除飞币
                  $map['id'] = $userId;
                  D('Members')->setColunm($map, 'integral', - $array[$money]);
                  }
                 */

                //扣除飞币
                $map['id'] = $userId;
                $reuslts = D('Members')->setColunm($map, 'integral', - $array[$money]);
                $userData['userId']=$userId;
                $userData['integral']=$array[$money];
                $userData['addTime']=time();

                
                //echo D('Members')->getLastSql();die;

                if ($reuslts) {//判断是否扣除飞币
                    $data['status'] = '0';
                    $data['userId'] = $userId;
                    $data['integral'] = $array[$money]; //兑换的飞币
                    $data['money'] = $money; //兑换的话费
                    $data['phone'] = $phone; //充值的手机号码
                    $data['time'] = $now;
                    $data['orderNumber'] = $orderNumber; //orderNumber
                    $data['err_code'] = '0'; //错误码

                    $result = $this->addData($data);

                    if ($result) {
                        //如果兑换成功,监测手机号码是否写入了手机限值表,如果存在更新最后更新时间,否则插入
                        $module = M("phone_limit");
                        $where = array("phone" => $phone);
                        $result = $module->where($where)->find();
                        $data = array();
                        $data['utime'] = time();
                        if($result){
                            $module->where($where)->save($data);
                        }else{
                            $data['phone'] = $phone;
                            $data['ctime'] = $data['utime'];
                            $data['status'] = 1;
                            $module->add($data);
                        }
                        $return = true;
                    } else {
                        $return = -12;
                    }
                } else {
                    M('MembersBillIntegralLog')->data($userData)->add();
                    $return = -12;
                }
            }
        }
        //var_dump($return);die;
        //将本次记录 插入数据库
        //$data['status'] = ( $return['error'] == 1 ) ? '1' : '-1'; //充值状态；0等待、1成功、-1失败、9撤销



        /*
          //提示语
          if (!empty($return['error'])) {
          $data['err_msg'] = $return['message'];
          } else if ($return == -2530) {
          $data['err_msg'] = '飞币不足';
          } else if ($return == -2531) {
          $data['err_msg'] = '不在兑换时间段内';
          } else if ($return == -2532) {
          $data['err_msg'] = '手机号格式错误';
          } else if ($return == -2533) {
          $data['err_msg'] = '充值金额错误';
          } else if ($return == -2534) {
          $data['err_msg'] = '运营商充值失败';
          } else {
          $data['err_msg'] = '其他错误';
          }
         */


        //if($result){
        //$data['err_msg'] = '添加成功';
        //}else{
        //$data['err_msg'] = '添加失败';
        //}
        //echo $this->getLastSql();die;

        return $return;
    }

    /**
     * 充值规则
     * @param  string $phone 代充值手机号
     * @param  string $money 面额
     * @return mixed
      -2534;//'运营商充值失败';
      oufei欧飞；gaoyang高阳
     */
    public function roleOfRecharge($phone, $money) {
        import("Common.Extend.RechargeOF", ROOT);
        $ob = new RechargeOF($config['userid'], $config['userpws'], $config['version'], $config['cardid'], $configAll['cardnum'], $config['KeyStr'], $configAll['callback']);
        $re = $ob->phoneOfWhere(I('post.phone'));
        if (empty($re[2]))
            return -2534; //'运营商充值失败';       
//手机号归属地
        switch ($re[2]) {
            case '移动'://移动
                $who = 1;
                break;
            case '联通'://联通
                $who = 2;
                break;
            case '电信'://电信
                $who = 3;
                break;
        }
        $placeOfPhone = mb_substr($re[1], 0, 2, 'UTF-8'); //省份名称(黑龙江只取前2个字)
        //电信直接选择高阳的充值接口
        if ($who == 3)
            return 'gaoyang';

        //联通
        // 1、10元话费调用高阳的充值接口
        // 2、10元以上的话费调用欧飞的充值接口
        if ($who == 2 && $money == 10)
            return 'gaoyang';
        if ($who == 2 && $money > 10)
            return 'oufei';

        //移动
        //10元、20元和30元话费调用高阳的充值接口
        //30元以上的地区用高阳的地区如下：安徽、甘肃、广东、贵州、海南、河南、湖北、湖南、吉林、江苏、江西、辽宁、内蒙、山西、陕西、天津、西藏、新疆、云南、浙江
        //30元以上的地区用欧飞的地区如下：北京、福建、广西、河北、黑龙江、宁夏、青海、山东、上海、四川、重庆
        if ($who == 1 && ( $money == 10 || $money == 20 || $money == 30 ))
            return 'gaoyang';
        if ($who == 1 && $money > 30) {
            if (in_array($placeOfPhone, array('安徽', '甘肃', '广东', '贵州', '海南', '河南', '湖北', '湖南', '吉林', '江苏', '江西', '辽宁', '内蒙', '山西', '陕西', '天津', '西藏', '新疆', '云南', '浙江'))) {
                return 'gaoyang';
            } else if (in_array($placeOfPhone, array('北京', '福建', '广西', '河北', '黑龙', '宁夏', '青海', '山东', '上海', '四川', '重庆'))) {
                return 'oufei';
            }
        }

        //语法严谨性判断
        return -2534; //'运营商充值失败';
    }

    /**
     * 飞币兑换到钱计算
     * @param int $integral 飞币
     * @return floor
     */
    function getExchangeRange($integral) {
        //$array = C("PERCENT");
        //2015-01-16 xiaofeng 修改 从数据库中读取信息
        $array = percent();
        foreach ($array as $key => $value) {
            $a = $value / $key;
        }
        return floor(($integral / $a) * 100) / 100;
    }

    /**
     * 通过会员Id 得到飞币
     * @param int $uid
     * @return int
     */
    public function getByUserIdIntegral($uid) {
        $data = D("Members")->where("id=" . $uid)->field("integral")->find();
        $integral = 0;
        if ($data['integral']) {
            $integral = $data['integral'];
        }
        return $integral;
    }

    /**
     * 获取当天充值总额
     * 
     * @param int $uid
     * @return int
     */
    public function getTotalMoneyToday() {
        $start_time = $this->getTodayStart();
        $end_time = $start_time + 86400;

        $map = array(
            'time' => array('BETWEEN', "{$start_time}, {$end_time}"),
            'status' => array('IN', '0,1')
        );

        return $this->where($map)->getField('SUM(money)');
    }

    /**
     * 获取今日按面额充值次数
     * 
     * @param int $money
     * @return int
     */
    public function getCountToday($money = 1) {
        $start_time = $this->getTodayStart();
        $end_time = $start_time + 86400;

        $map = array(
            'time' => array('BETWEEN', "{$start_time}, {$end_time}"),
            'money' => $money,
            'status' => array('IN', '0,1')
        );
        return $this->where($map)->getField('COUNT(*)');
    }

    /**
     * 获取今天开始时间
     */
    public function getTodayStart() {
        list($y, $m, $d) = explode(',', date('Y,m,d'));

        return mktime(0, 0, 0, $m, $d, $y);
    }

    /**
     * 获取用户今日充值总飞币
     * 
     * @param int $uid
     * @return int
     */
    public function getUserTotalIntegralToday($uid) {
        $start_time = $this->getTodayStart();
        $end_time = $start_time + 86400;

        $map = array(
            'time' => array('BETWEEN', "{$start_time}, {$end_time}"),
            'status' => array('IN', '0,1'),
            'userId' => $uid
        );

        return $this->where($map)->getField('SUM(integral)');
    }

}
