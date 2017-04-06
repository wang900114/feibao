<?php

use Think\Model;

// 会员模型
class MembersModel extends CommonModel {
    /* 自动验证规则 */

    public $_validate = array(
        //array('imei','require','IMEI Required', self::EXISTS_VALIDATE ),
        //array('cityId','require','City ID Required', self::EXISTS_VALIDATE ),
        //array('provinceId','require','Province ID Required', self::EXISTS_VALIDATE ),
        array('name', 'require', 'name必须！'),
    );

    /* 自动完成规则 */
    public $_auto = array(
        //array('image', 'http://server.andlisoft.com:30083/Public/Images/member.png', self::MODEL_INSERT),
        array('addTime', 'time', self::MODEL_INSERT, 'function'),
    );

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
            $result = $this->selData($where, 1, "freeze");
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
     * 读取指定用户的信息
     * @param int $uid 需要查询的用户的ID
     * @return array 返回用户信息数组
     */
    function getUserInfo($uid) {
        return M("members")->where("id={$uid}")->find();
    }

    /**
     * 冻结会员----暂时没用此方法
     * flag 1 设置成功 2 设置失败
     * @param number $uid 会员ID
     * @return int 状态
     */
    function setUserStatus($uid) {
        return 1; //暂时中止此方法,曹洪猛,20150106
        $flag = 2;
        if (is_numeric($uid)) {
            $where = array("id" => $uid);
            $result = $this->selData($where, 1, "freeze");
            if ($result[0]['freeze'] == 0) {
                $upFlag = $this->upData($where, array('freeze' => '1'));
                if ($upFlag > 0) {
                    $flag = 1;
                }
            } else if ($result[0]['freeze'] == 1) {
                $flag = 1;
            }
        }

        return $flag;
    }

    /**
     * 设置用户非法状态
     * flag 1 设置成功 2 设置失败
     * @param number $uid 会员ID
     * @return int 状态
     */
    function setUserWrongful($uid) {

        $flag = 2;
        if (is_numeric($uid)) {
            $where = array("id" => $uid);
            $result = $this->selData($where, 1, "freeze");
            if ($result[0]['freeze'] != 2) {
                $upFlag = $this->upData($where, array('freeze' => '2'));
                if ($upFlag > 0) {
                    $flag = 1;
                    $this->wrongfulLog($uid);
                    $message = '非常抱歉，您的飞报号存在违反《飞报用户协议》的情况，已做限制权限处理。';
                    pushMassageAndWriteMassage($message, $uid);

                    //封用户IP
//                    D("Ip")->filter();//功能暂时废除
                }
            } else if ($result[0]['freeze'] == 2) {
                $flag = 1;
                $this->wrongfulLog($uid);

                $message = '非常抱歉，您的飞报号存在违反《飞报用户协议》的情况，已做限制权限处理。';
                pushMassageAndWriteMassage($message, $uid);
                //封用户IP
//                D("Ip")->filter();//功能暂时废除
            }
        }

        return $flag;
    }

    /**
     * 插入非法用户记录表
     * @param int $uid
     */
    function wrongfulLog($uid) {
        $data = array(
            "userId" => $uid,
            'addTime' => time()
        );
        M("members_wrongful_log")->data($data)->add();
    }

    /**
     * 获取用户设备状态
     * 
     * 
     */
    public function getUserFlag($uid) {
        $reMembers = M('Members')->field('id,integral,mobileflag')->where('id =' . $uid)->find();
        $flag = '1';
        if ($reMembers['mobileflag'] == '2') {
            $flag = '2';
        }
        return $flag;
    }

    /**
     * 给指定用户增加飞币
     * 
     * @param type $uid
     * @param type $integral
     */
    public function addUsersIntegral($uid, $integral) {
        /* 当后台开启全部控制的时候
         * 只有真的加
         * 否则不传参数和真的加
         * 
         */
        // $old_user_switch = D("System")->readConfig('old_user_switch');
        //$mobileflag = I('post.mobileflag');
        //if ($old_user_switch == '2' && !$mobileflag) { //2015 3 19 xiaofeng 修改 兼容新版本 此时删除

        $old_user_switch = D("System")->readConfig('user_switch_3.2');
        if ($old_user_switch == '2') {
            $old_user_showmassage_swith = D("System")->readConfig('old_user_showmassage_swith');
            if ($old_user_showmassage_swith == '1') {
                $massage = "官人,您的版本太老了,无法获得飞币!赶快升级吧!{$mobileflag}";
                //给用户推送升级通知并给其消息中心发送消息
                pushMassageAndWriteMassage($massage, $uid);
            }
            return 0;
        }

        D("Members")->where(array('id' => $uid))->setInc("integral", $integral);
        /*
          $flag = D("System")->readConfig('integral_flag');
          //var_dump($flag);exit();

          if ($flag) {//开启时
          $result = $this->getUserFlag($uid);
          if ($result == 1 && ($userflag == 1)) {
          return D("Members")->where(array('id' => $uid))->setInc("integral", $integral);
          } else {
          return 0;
          }
          } else {
          return D("Members")->where(array('id' => $uid))->setInc("integral", $integral);
          }
         */
    }

    /**
     * 更改用户合法状态并添加相应的记录
     * @param int $user_id 需要改变用户非法状态的用户ID
     * @param string $freeze 被改变的非法状态
     *                          0:正常
     *                          1:冻结
     *                          2:非法
     * @param int $reason 用户合法状态调整原因,
     *                          默认0,默认理由;
     *                          1:非法地址刷海报;
     */
    public function changeUserFreeze($user_id, $freeze = "2", $reason = 0) {
        $where['id'] = $user_id;
        $data['freeze'] = (string) $freeze;
        $m = M("members");
        //读取当前用户信息
        $user = $m->where($where)->find();
        //获取用户当前状态
        $p_freeze = $user['freeze'];
        //更改用户状态
        $result = M("members")->where($where)->save($data);
        //如果状态改变成功,则写入日志
        if ($result) {
            $data = array();
            $data['user_id'] = $user_id;
            $data['freeze'] = (string) $freeze;
            $data['p_freeze'] = $p_freeze;
            $data['reason'] = $reason;
            $data['ctime'] = time();

            M("member_freeze_log")->add($data);
        }
    }

    /**
     * 为会员添加消息
     * @param int $userId 用户ID
     * @param string $content 消息内容
     * @param int $integralType 飞币增长类型，1增长，2减少，3不变
     * @param int $dataId 相关模块id                         
     * @param int $actionType 操作类型，1-广告发布失败，2-广告被举报,3-会员查看转发广告，4-转发广告被查看，5-充值成功增加飞币， 6-发布海报消耗飞币，7-你被举报啦，8-获取飞币，9-分享获取飞币                       
     */
    public function addMemberDope($userId, $content, $integralType, $integral, $dataId, $actionType) {
        if ($dataId) {
            $data['dataId'] = $dataId;
        }
        $data['operationType'] = $actionType;
        $data['userId'] = $userId;
        $data['isRead'] = '1';
        $data['integral'] = $integral;
        $data['content'] = $content;
        $data['addTime'] = time();
        $data['integralType'] = $integralType;

        //var_dump($data);die;
        M("MembersDopeLog")->add($data);
        $res = M("MembersDope")->add($data);
        
        if($res){
            //未阅 改 已阅
            $dataS['new_message'] = '2';
            M('Members')->where('id ='. $userId)->save($dataS);
        }
    }

    /**
     * 验证操作码是否正确
     * @param string $userId 唯一标识
     * @param string $phone 手机号
     * @param string $handlePwd 操作密码
     * @return boolean
     */
    function checkHandlePwd($userId, $phone, $handlePwd) {
        $flag = false;
        $where = array(
            "uniqueId" => $userId,
            "phone" => $phone
        );
        $result = $this->where($where)->field('handlePassword,encrypt')->find();
        if ($result) {
            if (md5(md5($handlePwd . $result['encrypt'])) == $result['handlePassword']) {
                $flag = true;
            }
        }
        return $flag;
    }

    //添加会员个人分享信息
    public function addShare($userId, $type, $imei) {

        if ($imei) {
            $data = array(
                'userId' => $userId,
                'type' => "$type",
                'imei' => "$imei",
                'addTime' => time(),
            );
        } else {
            $data = array(
                'userId' => $userId,
                'type' => "$type",
                'addTime' => time(),
            );
        }

        // 保存数据
        $id = M("MemberShareLog")->data($data)->add();
        //echo M("MemberShareLog")->getLastSql();die;
        return $id;
    }

    /**
     * 获得一条验证码数据信息
     * @param  string $phone 手机号
     * @param  string $type 获取类型 1：注册，2：修改登录密码，3：修改操作密码，4：忘记密码，5：绑定手机获取旧手机验证码，6：绑定手机获取新手机验证码
     * @param  string $field 字段
     * @param  string $order 排序
     * @return array
     */
    function getCodeOne($phone, $type, $field = "*", $order = ' id desc') {
        $where['type'] = $type;
        $where['phone'] = $phone;
        $resCode = M('MembersCode')->field($field)->order($order)->where($where)->find();
        return $resCode;
    }

    /**
     * 获得当天验证码次数
     * @param string $phone
     * @param string $type
     * @return type
     */
    function checkCodeNum($phone, $type) {
        $where['type'] = $type;
        $where['phone'] = $phone;
        $field = "count(*) as num";
        $where['addTime'] = array("gt", mktime(0, 0, 0, date("m"), date('d')));
        $order = ' id desc';
        $resCode = M('MembersCode')->field($field)->order($order)->where($where)->find();
        //echo M('MembersCode')->getLastSql();die;
        return $resCode;
    }

    /**
     * 手机验证码是否过期
     * @param string $phone 手机号
     * @param string $code 验证码
     * @param int $type 验证码类型 1：注册，2：修改登录密码，3：修改操作密码，4：忘记密码，5：绑定手机获取旧手机验证码，6：绑定手机获取新手机验证码
     * @return boolean
     */
    function checkCodePhone($phone, $code, $type = 1) {
        $flag = false;
        $where = array(
            'phone' => $phone,
            //'code' => $code,
            'type' => $type
        );
        $passTime = 10 * 60; //过期时间
        $result = M('members_code')->where($where)->order('id desc')->limit(1)->find();
        //var_dump(M('members_code')->getLastSql());die;
        
        if ($result && time() - $result['addTime'] <= $passTime && $result['code']==$code) {
            $flag = true;
        }
        return $flag;
    }

    /**
     * 通过手机号获得用户信息
     * @param string $phone 手机号
     * @return array|boolean
     */
    function getUserDataByPhone($phone, $fields = "*") {
        if (empty($phone) || empty($fields))
            return false;

        $whereUser['phone'] = $phone;
        $result = $this->selData($whereUser, 1, $fields);
        return $result[0];
    }

    /**
     * 通过Imei号获得用户信息
     * @param string $imei imei号
     * @return array|boolean
     */
    function getUserDataByImei($imei, $fields = "*") {
        if (empty($imei) || empty($fields))
            return false;

        $where['imei'] = $imei;
        //$where['phone'] = array('eq', "");
        $result = $this->selData($where, 1, $fields);
        //echo $this->getLastSql();
        return $result[0];
    }

    /**
     * 获得当天手机号密码错误次数
     * @param string $phone 手机号
     * @return int
     */
    function getErrorPwdNum($phone) {
        $wheres['phone'] = $phone;
        $start = mktime(0, 0, 0, date("m"), date("d"), date("Y"));
        $end = mktime(23, 59, 59, date("m"), date("d"), date("Y"));
        $wheres['status'] = '1';
        $wheres['type'] = '1';
        $field = 'count(id) as total';
        $wheres['_string'] = " addTime >=" . $start . " and addTime <=" . $end;
        $resTimes = M('MembersErrorLog')->field($field)->where($wheres)->order('id desc')->find();
        if(empty($resTimes['total'])){
            $resTimes['total']=0;
        }
        //echo M('MembersErrorLog')->getLastSql();die;
        return $resTimes['total'];
    }

    /**
     * 登录错误日志
     * @param int $userId
     * @param string $phone
     * @param string $imei
     */
    function loginErrorLog($userId, $phone, $imei) {
        $data['userId'] = $userId;
        $data['phone'] = $phone;
        $data['imei'] = $imei;
        $data['type'] = '1';
        $data['status'] = '1';
        $data['addTime'] = time();
        M('MembersErrorLog')->data($data)->add();
        //echo M('MembersErrorLog')->getLastSql();die;
    }
    
    /**
     * 更新会员错误日志
     * @param int $userId
     * @param string $phone
     * @param string $type 类型 1：登录，2：注册，3:邀请,4：用旧密码修改操作密码,5:用旧密码修改登陆密码
     */
    function clearLoginNum($userId,$phone,$type){
        $addTime = mktime(0,0,0,date('m'),date('d'),date('Y'));
        //if($type==1){
            $where = 'userId ='.$userId.' and phone ='.$phone.' and type="'.$type.'" and addTime >'.$addTime;
            $data['status'] = '2';
            M('MembersErrorLog')->where($where)->data($data)->save();
            //echo M('MembersErrorLog')->getLastSql();die;
        //}
    }

    /**
     * 用户错误日志
     * @param int $type 类型 1：登录，2：注册，3:邀请,4：用旧密码修改操作密码,5:用旧密码修改登陆密码
     * @param string $phone
     * @param string $imei
     * @param int $userId
     */
    function userErrorLog($phone, $imei, $userId = '', $type = '1') {
        if ($userId) {
            $data['userId'] = $userId;
        }
        if ($imei) {
            $data['imei'] = $imei;
        }
        $data['phone'] = $phone;
        $data['type'] = $type;
        $data['status'] = '1';
        $data['addTime'] = time();
        M('MembersErrorLog')->data($data)->add();
        //echo M('MembersErrorLog')->getLastSql();die;
    }

    /**
     * 用户登录日志
     * @param string $phone
     * @param string $imei
     * @param string $ip
     */
    function userLoginLog($phone, $imei, $ip) {
        $data['ip'] = $ip;
        $data['imei'] = $imei;
        $data['phone'] = $phone;
        $data['addTime'] = time();
        M('MembersLoginLog')->data($data)->add();
    }

    /**
     * 更新用户登录次数
     * @param int $userId
     */
    function updateLoginNum($userId) {
        $userId = (int)$userId;
        $this->where("id={$userId}")->setInc("loginnum", 1);
    }

    /**
     * 更新用户唯一标识字符串
     * @param int $userId
     * @return string
     */
    function updateUniqueId($userId) {
        $loginTime = time();
        $datas['uniqueId'] = createUserOnlyString($userId, $loginTime);
        $datas['upLoginTimes'] = $loginTime;
        $this->where(array('id' => $userId))->data($datas)->save();
        return $datas['uniqueId'];
    }

    /**
     * 验证用户唯一Id 是否效
     * @param string $phone
     * @param string $userId
     * @param string $fields
     * @return array|boolean
     */
    function checkUserId($phone, $userId, $fields = "*") {
        if (empty($phone) || empty($userId)) {
            return false;
        }
        $where['uniqueId'] = $userId;
        $where['phone'] = $phone;
        $resMember = $this->where($where)->field($fields)->find();
        //echo $this->getLastSql();
        return $resMember;
    }

    /**
     * 获取会员收藏广告状态
     * @param string $dataId
     * @param string $userId
     * @return string
     */
    function getUserCollectStatus($dataId, $userId) {
        $map = 'dataId =' . $dataId . ' and userId =' . $userId . ' and status ="1"';

        $re = M('CollectPosterLog')->field('id')->where($map)->find();
        //echo M('CollectPosterLog')->getLastSql();die;

        if ($re['id']) {
            return TRUE;
        } else {
            return FALSE;
        }
    }

    /**
     * 更新用户昵称
     * @param string $userId
     * @param string $phone
     * @param string $name
     * @return boolean
     */
    function updateNikename($userId, $phone, $name) {
        $data['name'] = $name;
        $data['updateTime'] = time();
        $where['uniqueId'] = $userId;
        $where['phone'] = $phone;
        $id = $this->where($where)->data($data)->save();
        return $id;
    }

    /**
     * 更新用户头像
     * @param string $userId
     * @param string $phone
     * @param string $image
     * @param string $imageUrl
     * @return boolean
     */
    function updateUserImage($userId, $phone, $image, $imageUrl) {
        $data['image'] = $image;
        $data['phone'] = $phone;
        $data['imageUrl'] = $imageUrl;
        $where['uniqueId'] = $userId;
        $id = $this->where($where)->data($data)->save();
        return $id;
    }


    /*
     * 获取会员注册是否添加积分
     */
    function getUserRegisterIntegral($type){//$type:1-更新旧用户，2-新用户注册
        if($type==1){
            $res = D('System')->readConfig('register_old_on');
        }else{
            $res = D('System')->readConfig('register_new_on');
        }
        
        if($res==1){
            if($type==1){
                $integral = D('System')->readConfig('register_old_addintegral');
            }else{
                $integral = D('System')->readConfig('register_new_addintegral');
            }
            return $integral;
        }else{
            return -1;
        }
        
        
    }



    /**
     * 更新用户信息
     * @param int $userId
     * @param string $phone
     * @param string $pwd
     */
    function updateRegisterUserInfo($userId, $phone, $pwd, $memberCodeUrl) {
        $where = array(
            'id' => $userId
        );

        $tmpIntegral=$this->getUserRegisterIntegral(1);
        if($tmpIntegral>=0){
            //$data['integral'] = $tmpIntegral;
            M("Members")->where(array('id' => $userId))->setInc("integral", $tmpIntegral);
        }elseif($tmpIntegral<0){
            //否则不进行修改
        }

        $data['phone'] = $phone;
        $data['encrypt'] = random(6);
        $data['password'] = md5(md5($pwd . $data['encrypt']));
        if ($memberCodeUrl) {
            $data['memberCodeUrl'] = $memberCodeUrl;
        }
        $data['invite'] = $this->getInviteString();
        $loginTime = time();
        //设置唯一码
        $data['uniqueId'] = createUserOnlyString($userId, $loginTime);
        $data['loginnum'] = 1;
        $data['upLoginTimes'] = $loginTime;
        $flag = $this->getUserDataByPhone($phone, 'id');
        
        
        if(empty($flag)){
            $id = $this->where($where)->data($data)->save();
            if($tmpIntegral>0){
                $content='恭喜您，注册成功！送您飞币';
                $this->addMemberDope($userId, $content, 1, $tmpIntegral, '', '11');
            }
            return $id;
        }else{
            return 0;
        }
    }

    /**
     * 获取邀请码
     * @return type
     */
    function getInviteString() {
        $string = InviteString(6, 10);
        $where = array(
            'invite' => $string
        );
        $result = $this->where($where)->field("id")->find();
        if ($result) {
            $string = $this->getInviteString();
        }
        return $string;
    }

    /**
     * 保存会员身份认证信息
     * @return type
     */
    function saveMemberNews($data) {
        if ($data['uid']) {
            $result = M('member_verify_info')->where(array('uid' => $data['uid']))->find();
            if (!empty($result)) {
                $res = M('member_verify_info')->data($data)->where(array('uid' => $data['uid']))->save();
            } else {
                $res = M('member_verify_info')->add($data);
            }
        }
        return $res;
    }

    /**
     * 保存注册用户信息
     * @param string $phone
     * @param string $pwd
     * @param string $imei
     * @return boolean
     */
    function saveUserInfo($phone, $pwd, $imei, $mobileType = '1', $memberCodeUrl) {
        $flag = false;
        $loginTime = time();
        $data['imei'] = $imei;
        $data['addTime'] = time();
        $strFirst = generate_rand(6);//$data['name'] = generate_rand(10);
        $strSecond=mt_rand(9,99);

        $tmpIntegral=$this->getUserRegisterIntegral(2);
        if($tmpIntegral>=0){
            $data['integral'] = $tmpIntegral;
        }else{
            $data['integral'] = 0;
        }
        
        list($t1, $t2) = explode(' ', microtime());     
        $strThree = (float)sprintf('%.0f', (floatval($t1) + floatval($t2)) * 1000);
        $strThree = substr($strThree,6);
                
        $data['name'] =dechex($strFirst).dechex($strSecond).dechex($strThree);
        if(strlen($data['name'])==7){
            $data['name'] = $data['name'].generate_rand(1);
        }elseif(strlen($data['name'])==6){
            $data['name'] = $data['name'].generate_rand(2);
        }elseif(strlen($data['name'])==5){
            $data['name'] = $data['name'].generate_rand(3);
        }elseif(strlen($data['name'])==4){
            $data['name'] = $data['name'].generate_rand(4);
        }
        //$data['name'] =dechex($strFirst).dechex($strSecond);
        //获取系统配置参数，新用户送积分有无开启，及新用户送积分配额 #wpgm修改
        //$CONFIG = D('Config');
        //$register_new['status'] = $CONFIG->readConfig('register_new_on');
        //if($register_new['status'] == 1){
            //$data['integral'] = $CONFIG->readConfig('register_new_addintegral');
        //}elseif($register_new['status'] == 2){
            //$data['integral'] = 0;
        //}
        $data['image'] = randPhoto();
        //$data['integral'] = 10000;        //该参数由固定参数修改为可配置参数 ，由上方658-665行替代
        $data['phone'] = $phone;
        $data['mobileType'] = $mobileType;
        $data['imageUrl'] = randPhoto();
        $data['encrypt'] = random(6);
        $data['password'] = md5(md5($pwd . $data['encrypt']));
        $data['invite'] = $this->getInviteString();
        if ($memberCodeUrl) {
            $data['memberCodeUrl'] = $memberCodeUrl;
        }
        $data['loginnum'] = 1;
        $data['upLoginTimes'] = $loginTime;
        $flag = $this->getUserDataByPhone($phone, 'id');
        if(empty($flag)){
            $resultUserId = $this->data($data)->add();
            if($tmpIntegral>0){
                $content='恭喜您，注册成功！送您飞币';
                $this->addMemberDope($resultUserId, $content, 1, $tmpIntegral, '', '11');
            }
        }else{
            return 0;
        }
        
        //echo $this->getLastSql();
        if ($resultUserId) {
            //设置唯一码
            $upData['uniqueId'] = createUserOnlyString($resultUserId, $loginTime);
            $upData['code'] = "FB" . $resultUserId;
            $where = array(
                'id' => $resultUserId
            );
            $id = $this->where($where)->data($upData)->save();
            if ($id) {
                $flag = TRUE;
            }
        }
        return $flag;
    }
    /*
     * 验证昵称是否有重复
     */
    function checkRepeat($name){
        $wheres['name'] = $name;
        $res = $this->field('id')->where($wheres)->order('id desc')->find();
        
        return $res;
    }

    /**
     * 获取邀请码
     * @param string $inviteCode
     * @return boolean
     */
    function checkInviteCode($inviteCode) {
        $wheres['invite'] = $inviteCode;
        $field = 'id,addTime';
        $resImei = $this->field($field)->where($wheres)->order('id desc')->find();
        return $resImei;
    }

    /**
     * 获得当天手机号邀请错误次数
     * @param string $userId 用户ID
     * @return int
     */
    function getErrorByInviteNum($userId) {
        $wheres['userId'] = $userId;
        $wheres['status'] = '1';
        $wheres['type'] = '3';
        $field = 'count(id) as total';
        $resTimes = M('MembersErrorLog')->field($field)->where($wheres)->order('id desc')->find();
        return $resTimes['total'];
    }

    /**
     * 更新用户修改的手机号
     * @param int $id 用户ID
     * @param string $newPhone 手机号
     * @return type
     */
    function updateUserPhone($id, $newPhone) {
        $data = array(
            'phone' => $newPhone
        );
        $where = array(
            'id' => $id
        );
        $result = $this->data($data)->where($where)->save();
        return $result;
    }

    /**
     * 更新用户修改的手机号
     * @param int $userId 用户ID
     * @param string $newPhone 手机号
     * @return type
     */
    function updateMemberCodeUrl($userId, $str) {
        $data = array(
            'memberCodeUrl' => $str
        );
        $where = array(
            'id' => $userId
        );
        $result = $this->data($data)->where($where)->save();
        return $result;
    }

    /**
     * 更新用户操作密码
     * @param int $uid 用户ID
     * @param string $handlePassword 操作密码
     * @return type
     */
    function updateUserHandlePwd($uid, $handlePassword) {
        $where['id'] = $uid;
        $data['handlePassword'] = md5(md5($handlePassword));
        $data['updateTime'] = time();
        $id = $this->where($where)->data($data)->save();
        return $id;
    }

    /**
     * 更新用户密码
     * @param int $uid 用户ID
     * @param string $password 密码
     * @return type
     */
    function updateUserPassword($uid, $password) {
        $where['id'] = $uid;
        $data['password'] = md5(md5($password));
        $data['updateTime'] = time();
        $id = $this->where($where)->data($data)->save();
        return $id;
    }

    /**
     * 获得用户输入操作码错误次数
     * @param int $uid
     * @param string $type
     * @param string $status
     * @return int
     */
    function getHandleErrorLogNum($uid, $type = '1', $status = '1') {
        $wheres['userId'] = $uid;
        $wheres['status'] = $status;
        $wheres['type'] = $type;
        
        $start = mktime(0, 0, 0, date("m"), date("d"), date("Y"));
        $end = mktime(23, 59, 59, date("m"), date("d"), date("Y"));
        
        $field = 'count(id) as total';
        $wheres['_string'] = " addTime >=" . $start . " and addTime <=" . $end;
        $resError = M('HandleErrorLog')->field($field)->where($wheres)->find();
        //echo M('HandleErrorLog')->getLastSql();die;
        return $resError['total'];
    }
    
    /*
     * 3.2获取用户操作码输入错误次数
     */
    function getHandleErrorNum($uid,$status){
        $start = mktime(0, 0, 0, date("m"), date("d"), date("Y"));
        $end = mktime(23, 59, 59, date("m"), date("d"), date("Y"));
        $where['userId'] = $uid;
        $where['type'] = '1';
        //$where['status'] = $status;
        $field = 'count(id) as total';
        
        $where['_string'] = " addTime >=" . $start . " and addTime <=" . $end;
        
        $resError = M('MembersConvertLog')->field($field)->where($where)->find();
        //var_dump($resError);die;
        return $resError['total'];
    }
    
    /**
     * 3.2保存用户输入操作码错误次数
     * @param int $uid
     * @param string $type
     * @param string $status
     * @return int
     */
    function addHandleErrorLog($data) {
        $result = M('MembersConvertLog')->data($data)->add();
        return $result;
    }
    
    
    
     /**
     * 3.2更新用户操作码错误日志状态
     * @param int $uid
     * @param string $status
     * @return boolean
     */
    function setHandleErrorStatus($uid, $status = '1') {
        $where['userId'] = $uid;
        //$where['status'] = $status;
        $where['type'] = '1';
        
        $start = mktime(0, 0, 0, date("m"), date("d"), date("Y"));
        $end = mktime(23, 59, 59, date("m"), date("d"), date("Y"));
        
        $where['_string'] = " addTime >=" . $start . " and addTime <=" . $end;
        $data['type'] = '2';

        $result = M('MembersConvertLog')->where($where)->data($data)->save();
        return $result;
    }
    
    

    /**
     * 保存用户输入操作码错误次数
     * @param int $uid
     * @param string $type
     * @param string $status
     * @return int
     */
    function saveHandleErrorLog($uid, $imei, $type = '1', $status = '1') {
        $data['userId'] = $uid;
        $data['type'] = $type;
        $data['status'] = $status;
        $data['addTime'] = time();
        $data['imei'] = $imei;
        //var_dump($data);die;
        $result = M('HandleErrorLog')->data($data)->add();
        return $result;
    }

    /**
     * 更新用户操作码错误日志状态
     * @param int $uid
     * @param string $type
     * @param string $status
     * @return boolean
     */
    function setHandleErrorLogStatus($uid, $type = '1', $status = '1') {
        $whereset['userId'] = $uid;
        $whereset['status'] = $status;
        $whereset['type'] = $type;
        $start = mktime(0, 0, 0, date("m"), date("d"), date("Y"));
        $end = mktime(23, 59, 59, date("m"), date("d"), date("Y"));
        $whereset['_string'] = " addTime >=" . $start . " and addTime <=" . $end;
        $data['status'] = '2';
        $result = M('HandleErrorLog')->where($whereset)->data($data)->save();
        return $result;
    }

    /**
     * 生成修改密码记录
     * @param int $uid
     * @param string $imei
     * @param string $ip
     * @param string $type
     * @return boolean
     */
    function membersPasswordLog($uid, $imei, $ip, $type = '1') {
        $data['imei'] = $imei;
        $data['userId'] = $uid;
        $data['ip'] = $ip;
        $data['type'] = $type;
        $data['addTime'] = time();
        $result = M('MembersPasswordLog')->data($data)->add();
        return $result;
    }

    /**
     * 保存验证码
     * @param string $phone
     * @param string $imei
     * @param int $code
     * @param string $type
     * @return type
     */
    function saveCodeData($phone, $imei, $code, $type = '1') {
        $data['addTime'] = time();
        $data['phone'] = $phone;
        $data['type'] = $type;
        $data['code'] = $code;
        if ($imei) {//判断imei号是否存在
            $data['imei'] = $imei;
        }

        $id = M('MembersCode')->data($data)->add();
        return $id;
    }

    /**
     * 获得当月绑定手机次数
     * @param type $userId
     * @return type
     */
    function getPhoneMembersBindLog($userId) {
        //获取本月起始时间戳和结束时间戳
        $beginThismonth = mktime(0, 0, 0, date('m'), 1, date('Y'));
        $endThismonth = mktime(23, 59, 59, date('m'), date('t'), date('Y'));
        //查询已经绑定的次数
        $whereTimes['userId'] = $userId;
        $whereTimes['_string'] = " addTime >=" . $beginThismonth . " and addTime <=" . $endThismonth;
        $field = 'count(id) as total';
        $res = M('MembersBindLog')->field($field)->where($whereTimes)->find();
        return $res;
    }

    /**
     * 会员操作错误表
     * @param string $phone
     * @param string $type
     * @return int
     */
    function getUserIdErrorLogNum($userId, $type = '1') {
        $where['type'] = $type;
        $where['userId'] = $userId;
        $field = 'count(id) as total,addTime';
        $start = mktime(0, 0, 0, date("m"), date("d"), date("Y"));
        $end = mktime(23, 59, 59, date("m"), date("d"), date("Y"));
        $field = 'count(id) as total';
        $where['_string'] = " addTime >=" . $start . " and addTime <=" . $end.' and status="1"';
        $resTimes = M('MembersErrorLog')->field($field)->where($where)->find();
        return $resTimes['total'];
    }

    /**
     * 会员操作错误表
     * @param string $phone
     * @param string $type
     * @return int
     */
    function getMembersErrorLogNum($phone, $type = '1') {
        $where['type'] = $type;
        $where['phone'] = $phone;
        $field = 'count(id) as total,addTime';
        $start = mktime(0, 0, 0, date("m"), date("d"), date("Y"));
        $end = mktime(23, 59, 59, date("m"), date("d"), date("Y"));
        $field = 'count(id) as total';
        $where['_string'] = " addTime >=" . $start . " and addTime <=" . $end;
        $resTimes = M('MembersErrorLog')->field($field)->where($where)->find();
        return $resTimes['total'];
    }

    /**
     * 会员主页
     * @param int $uid
     * @param string $friendId
     * @return boolean|string
     */
    function getUserHome($uid, $friendId) {
        $mwhere['id'] = decodePass($friendId);
        if (empty($mwhere['id'])) {
            return false;
        }
        //$mwhere['freeze'] = '0';
        $fields = 'id AS userId,name AS nickName,image,phone,integral,groupType,memberCodeUrl';
        $memberInfo = $this->where($mwhere)->field($fields)->find();
        //echo $this->getLastSql();
        if ($memberInfo) {
            $resultData['userId'] = encodePass($memberInfo['userId']);
            $resultData['nickName'] = $memberInfo['nickName'];
            $resultData['image'] = $memberInfo['image'];
            $resultData['phone'] = substr_replace($memberInfo['phone'], '****', 3, 4);
            $resultData['integral'] = $memberInfo['integral'];
            $resultData['memberCodeUrl'] = WEBURL . $memberInfo['memberCodeUrl'];

            $resultData['isAuthentication'] = $memberInfo['groupType'] < 2 ? 1 : 2; //1表示未认证 2 表示认证
            $nowTime=time();
            //$where = 'userId ='.$mwhere['id'] . ' and status =1 and endTime >'.$nowTime . ' and integral >exposeTotalIntegral + extendTotalIntegral and startTime<'.$nowTime.' and is_above_display="1"';
            $where = 'userId ='.$mwhere['id'] . ' and status =1 and endTime >'.$nowTime . ' and integral >exposeTotalIntegral + extendTotalIntegral and startTime<'.$nowTime;
            //array('userId' => $mwhere['id'], 'status' => 1)
            $resultData['posterTotal'] = M("poster_advert")->where($where)->count();
            //广告数
            $resultData['foundTotal'] = M("found")->where(array('userId' => $mwhere['id'], 'del' => '1'))->count(); //发现数
            $atteition = M('friend')->where(array('uid' => $uid, 'fuid' => $mwhere['id'], 'status' => '1'))->find();
            $resultData['isAtteition'] = $atteition ? 1 : 2; //关注状态
            $sheild = M('friend_shield')->where(array('uid' => $uid, 'fuid' => $mwhere['id'], 'status' => '1'))->find();
            $resultData['isSheild'] = $sheild ? 1 : 2; //屏蔽状态
        }
        return $resultData;
    }

    /**
     * 更新会员定位信息
     * @param int $userId
     * @param string $myLng
     * @param string $myLat
     */
    public function updateUserAddress($userId, $myLng, $myLat, $cityId) {
        $res = M('Members')->field('id')->where('id =' . $userId)->find();
        //var_dump($res);die;

        if ($res['id']) {
            if ($cityId) {
                $data['cityId'] = $cityId;
                $data['userId'] = $userId;
                $data['myLng'] = $myLng;
                $data['myLat'] = $myLat;
                $data['addTime'] = time();
            } else {
                $data['userId'] = $userId;
                $data['myLng'] = $myLng;
                $data['myLat'] = $myLat;
                $data['addTime'] = time();
            }


            M('MemberLocationLog')->data($data)->add();
            //echo M('MemberLocationLog')->getLastSql();die;

            if ($cityId) {
                $datas['cityId'] = $cityId;
                $datas['myLng'] = $myLng;
                $datas['myLat'] = $myLat;
            } else {
                $datas['myLng'] = $myLng;
                $datas['myLat'] = $myLat;
            }

            M('Members')->where('id = ' . $userId)->data($datas)->save();
            //echo M('Members')->getLastSql();die;
        }
    }

    /**
     * 生成二维码
     * @param string $text 内容
     * @param string $filename 二维码地址 
     * @param string $level 纠错级别：L、M、Q、H
     * @param int $size // 点的大小：1到10,用于手机端4就可以了
     */
    function qrcode($text, $filename = false, $level = 4, $size = 4) {
        vendor("Phpqrcode");
        //echo $filename;die;
        $str = QRcode::png($text, $filename, $level, $size);
    }

    /**
     * 获取会员认证状态
     * @param int $userId
     * @return boolean|string
     */
    public function getMemberAuthentication($userId) {
        $res = M('MemberVerifyInfo')->field('uid')->where('uid =' . $userId . ' and flag ="1"')->find();
        return $res['uid'] ? TRUE : FALSE;
    }

}
