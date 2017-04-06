<?php
use Think\Controller;
class PersonalController extends BaseController {

    /**
     * 控制器初始化
     * @access public
     * @author FrankKung <kongfanjian@andlisoft.com>
     */
    public function _initialize() {
        
        parent::_initialize();
        
        $ACTION_NAME = strtolower(ACTION_NAME);
        
        if (in_array($ACTION_NAME, array('getcode', 'registerphone', 'registerpwd', 'registerinvite', 'registerhandlepassword',
                    'memberlogin', 'memberloginversion','registermergeversion','checkforgetpasswordcode', 'checkeditforgetpassword', 'getmessage', 'customerservice','registerorlogin','register'))) {//先判断是否需要验证会员信息
                    //echo 'test';die;
        } else {
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
                //var_dump($res);die;

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
//                            if ($res['id'] == 44427) {
//                                $return['status'] = 32;
//                                $return['message'] = '请到个人中心登录';
//                                //$return['info'] = array();
//                                echo jsonStr($return);
//                                exit(0);
//                            }
                            $this->userId = $res['id'];
                            //echo $this->userId;die;
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

        // 初始化模型
        $this->_modelName = 'Members';
        $this->_model = D($this->_modelName);
        $this->_map = $this->_search($this->_modelName);
        //var_dump($this->_model);
        //var_dump($this->_map);die;
    }
    
    /*
     * 添加员工推广统计
     */
    public function addEmployeesCount(){
        $return['success'] = true;
        $phone = I('post.phone');
        $number = I('post.code');
        //echo encodePass('100071');die;
        //$imei = I('post.imei');
        
        if(is_empty($_POST['phone']) || is_empty($number) || is_empty($_POST['userId'])){//判断参数是否为空
            $return['status'] = 10;
            $return['message'] = '扫码失败';
        }else{
            $userId = $this->userId;
            $resUser=D('Members')->getUserInfo($userId);
            if($resUser['isEmployees']==1){//判断会员是否是员工
                $return['status'] = 10;
                $return['message'] = '扫码失败';
                echo jsonStr($return); exit(0);
            }
            //$number = decodePass($number);
            
            $res = M('MembersEmployees')->field('id')->where('code='.$number)->find();
            //var_dump(M('MembersEmployees')->getLastSql());die;
            if($res){
                $data['userId']=$userId;
                $data['code']=$number;
                $data['addTime']=time();
                $result = M('EmployeesExtendCountLog')->data($data)->add();
                //var_dump(M('EmployeesExtendCountLog')->getLastSql());die;
                //添加记录
                if($result){
                    $return['status'] = 1;
                    $return['message'] = '扫码成功';
                }else{
                    $return['status'] = 10;
                    $return['message'] = '扫码失败';
                }
            }else{
                $return['status'] = 10;
                $return['message'] = '扫码失败';
            }
        }
        echo jsonStr($return); exit(0);
    }
            

    /**
     * 获取验证码
     * @access public
     * @return true/false
     */
    public function getCode() {

        //验证相关密钥
        //$this->checkKey();
        //接收参数
        $phone = I('post.phone');
        $type = I('post.type');
        $imei = I('post.imei');
        $ret['success'] = true;
        if (is_empty($_POST['phone']) || is_empty($_POST['type'])) {//判断参数是否为空
            $return['status'] = -888;
            $return['message'] = '手机号码不能为空';
        } else {
            preg_match('/1[0-9]{10}/', $phone, $matches);
            if ((strlen($phone) == '11') && $matches[0]) {//验证手机号码规则
                $model = D("Members");
                $resCode = $model->getCodeOne($phone, $type, 'count(id) as total,addTime');
                //获取会员信息
                $res = $model->getUserDataByPhone($phone, 'id,handlePassword,encrypt');
                $userId = $res['id'];
                //var_dump(M('Members')->getLastSql());die;
                //判断时间间隔是否在60秒以内
                if (time() - $resCode['addTime'] >= 60) {
                    $resCodeData = $model->checkCodeNum($phone, $type);
                    //echo $resCodeData['num'];die;
                    switch ($type) {
                        case '5':
                            $ret['status'] = 41;
                            $ret['message'] = '为确保您的账户安全，请更新版本！';
                            $this->apiCallback($ret);
                            exit();
                            
                            $res = $model->getPhoneMembersBindLog($userId);
                            if ($res['total'] >= 2) {//判断次数是否超限
                                $ret['status'] = 41;
                                $ret['message'] = '每个自然月内只能修改2次';
                                $this->apiCallback($ret);
                                exit();
                            }
                            break;
                        case '6':
                            if ($res['id']) {
                                $ret['status'] = 19;
                                $ret['message'] = '亲，该手机号码已绑定其他用户';
                                $this->apiCallback($ret);
                                exit();
                            }
                            $res = $model->getPhoneMembersBindLog($userId);
                            if ($res['total'] >= 2) {//判断次数是否超限
                                $ret['status'] = 41;
                                $ret['message'] = '每个自然月内只能修改2次';
                                $this->apiCallback($ret);
                                exit();
                            }
                            break;
                    }

                    if ($resCodeData['num'] < 5) {

                        switch ($type) {
                            case '1':
                                if ($userId) {
                                    $ret['status'] = 9;
                                    $ret['message'] = '亲，你的手机号已注册，请直接登录';
                                    $this->apiCallback($ret);
                                    exit();
                                }
                                break;
                            case '2':
                                break;
                            case '3':
                                break;
                            case '4':
                                //$resCodeStatus = $model->getUserDataByPhone($phone,'id');
                                if (empty($userId)) {
                                    $ret['status'] = 27;
                                    $ret['message'] = '手机号尚未注册，请先注册';
                                    $this->apiCallback($ret);
                                    exit();
                                }
                                break;
                            case '5':
                                break;
                            case '6':
                                break; 
                            case '7':
                                $handlePwd = I('post.handlePwd');
                                //var_dump($handlePwd);die;
                                if ($handlePwd) {//判断操作密码
                                    if ($res['handlePassword'] !== md5(md5($handlePassword . $res['encrypt']))) {//验证通过后，重置错误次数状态
                                        $model->setHandleErrorLogStatus($res['id'], '3');
                                    } else {
                                        $ret['status'] = 10;
                                        $ret['message'] = '操作失败';
                                        $this->apiCallback($ret);
                                        exit();
                                    }
                                } else {
                                    $ret['status'] = 10;
                                    $ret['message'] = '操作失败';
                                    $this->apiCallback($ret);
                                    exit();
                                }
                                break;
                            default :
                                $ret['status'] = 10;
                                $ret['message'] = '操作失败';
                                $this->apiCallback($ret);
                                exit();
                        }

                        $code = mt_rand(99999, 999999);
                        //echo $code;
                        //发送短信验证码
                        $codeStatus = sendCode($phone, $code);
                        //$codeStatus =1;
                        //var_dump($codeStatus);
                        if ($codeStatus === 1) {
                            $id = $model->saveCodeData($phone, $imei, $code, $type);
                            if ($type == '1') {
                                $resTimes = $model->getMembersErrorLogNum($phone, '2');
                                $ret['getTimes'] = $resTimes['total'];
                            }
                            if ($id) {//判断添加信息是否成功
                                $ret['status'] = 1;
                                $ret['message'] = '已发送注意查收';
                            } else {
                                $ret['status'] = 10;
                                $ret['message'] = '操作失败';
                            }
                        }elseif($codeStatus === 2){
                            $ret['status'] = 10;
                            $ret['message'] = '稍后再试';
                        } else {
                            $ret['status'] = 10;
                            $ret['message'] = '操作失败';
                        }
                    } else {
                        $ret['status'] = 40;
                        $ret['message'] = '获取验证码次数超限';
                    }
                } else {
                    $ret['status'] = 10;
                    $ret['message'] = '操作失败';
                }
            } else {
                $ret['status'] = 8;
                $ret['message'] = '你输入的手机号码不存在';
            }
        }
        $this->apiCallback($ret);exit();
    }
    
     /**
     * 3.2身份认证提交
     * @access public
     * @return array/false
     */
    public function checkMemberNews() {
        $ret['success'] = true;

        $version = I('post.version');
        $imei = I('post.imei');
        $userId = I('post.userId');
        $phone = I('post.phone');
        $code = I('post.code');

        $name = I('post.name');
        $handlePassword = I('post.handlePwd');
        $identityCard = I('post.identityCard');
        $email = I('post.email');
        //echo $email;die;
        //var_dump($_FILES['imageFirst']);die;
        
        if (is_empty($handlePassword) || is_empty($version) || is_empty($code) || is_empty($name) || is_empty($identityCard) || is_empty($_FILES['imageFirst']['name']) || is_empty($_FILES['imageSecond']['name']) || is_empty($_FILES['imageThree']['name'])) {//判断参数是否为空
            $ret['status'] = 29;
            $ret['message'] = '操作失败';
        } else {

            //验证唯一码是否正确
            $model = D('Members');
            $res = $model->checkUserId($phone, $userId, 'id,handlePassword,encrypt,groupType');
            if ($res && $res['groupType'] != 2) {

                $resError = $model->getHandleErrorNum($res['id'], '2');
                //var_dump($resError);die;
                if ($resError < 5) {//验证输入错误的次数
                    //验证操作密码是否正确
                    if ($res['handlePassword'] == md5(md5($handlePassword . $res['encrypt']))) {
                        //验证通过后，重置错误次数状态
                        $model->setHandleErrorStatus($res['id'], '2');

                        //再次验证短信验证码
                        $resCode = $model->checkCodePhone($phone, $code, '7');
                        if ($resCode) {//判断验证码是否存在
                            //组织信息提交
                            $data['uid'] = $res['id'];
                            $data['username'] = $name;
                            $data['addTime'] = time();
                            $data['idcard'] = $identityCard;
                            $data['email'] = $email;
                            $data['flag'] = '1';
                            //判断图片是否上传
                            $p = D("Picture");
                            $info = $p->upload($_FILES, C('PICTURE_UPLOAD'), C('PICTURE_UPLOAD_DRIVER'), C("UPLOAD_LOCAL_CONFIG"));
                            /*
                            if(empty($info['imageFirst']['path']) || empty($info['imageSecond']['path']) || empty($info['imageThree']['path'])){
                                $ret['status'] = 10;
                                $ret['message'] = '图片超过3M，无法上传';
                                $this->apiCallback($ret);
                            }*/
                            $data['image1'] = $info['imageFirst']['path'];
                            $data['image2'] = $info['imageSecond']['path'];
                            $data['image3'] = $info['imageThree']['path'];

                            //保存会员认证信息
                            $result = $model->saveMemberNews($data);
                            if ($result) {//判断操作是否成功
                                $ret['status'] = 1;
                                $ret['message'] = '已提交验证，等待审核';
                            } else {
                                $ret['status'] = 10;
                                $ret['message'] = '操作失败';
                            }
                        } else {
                            $ret['status'] = 10;
                            $ret['message'] = '验证码错误，请重新输入';
                            //$ret['status'] = 14;
                            //$ret['message'] = '验证码错误，请重新输入';
                        }
                    } else {
                        //验证不通过时，记录失败次数.添加错误日志
                        //$model->saveHandleErrorLog($res['id'], $imei, '3');
                        $model->setHandleErrorStatus($res['id'], $imei, '2');

                        $ret['status'] = 14;
                        $ret['message'] = '密码输入错误！' . (5 - $resError);
                        $ret['getTimes'] = 5 - $resError;
                    }
                } else {
                    $ret['status'] = 21;
                    $ret['message'] = '您的兑换密码错误尝试超限，请明天再试';
                    $ret['getTimes'] = 0;
                }
            } else {
                $ret['status'] = 10;
                $ret['message'] = '操作失败';
            }
        }
        $this->apiCallback($ret);exit();
    }


    /**
     * 3.2.3身份认证提交
     * @access public
     * @return array/false
     */
    public function checkMemberNewsNew() {
        $ret['success'] = true;

        $version = I('post.version');
        $imei = I('post.imei');
        $userId = I('post.userId');
        $phone = I('post.phone');
        $code = I('post.code');

        $name = I('post.name');
        $handlePassword = I('post.handlePwd');
        $identityCard = I('post.identityCard');
        $email = I('post.email');
        
        $imageFirst = I('post.imageFirst');
        $imageSecond = I('post.imageSecond');
        $imageThree = I('post.imageThree');
        $imagefour = I('post.imageFour');
        $type = I('post.type');
        //echo $email;die;
        //var_dump($_FILES['imageFirst']);die;
        
        //if (is_empty($handlePassword) || is_empty($version) || is_empty($code) || is_empty($name) || is_empty($identityCard) || is_empty($_FILES['imageFirst']['name']) || is_empty($_FILES['imageSecond']['name']) || is_empty($_FILES['imageThree']['name'])) {//判断参数是否为空
        if($type==1 or $type==2){
            if($type==1){
                if (is_empty($handlePassword) || is_empty($version) || is_empty($email) || is_empty($code) || is_empty($name) || is_empty($identityCard) || is_empty($imageFirst) || is_empty($imageSecond) || is_empty($imageThree)) {//判断参数是否为空    
                    $ret['status'] = 29;
                    $ret['message'] = '操作失败1';
                    $this->apiCallback($ret);exit();
                }
                
                $data['image1'] = $imageFirst;
                $data['image2'] = $imageSecond;
                $data['image3'] = $imageThree;
                $FileSystem = A('FileSystem');
                $FileSystem->filePathDispose($imageFirst);
                $FileSystem->filePathDispose($imageSecond);
                $FileSystem->filePathDispose($imageThree);
                $data['type'] = '1';
            }else{
                if (is_empty($handlePassword) || is_empty($version) || is_empty($email) || is_empty($code) || is_empty($name) || is_empty($identityCard) || is_empty($imagefour) ) {//判断参数是否为空    
                    $ret['status'] = 29;
                    $ret['message'] = '操作失败2';
                    $this->apiCallback($ret);exit();
                }
                $resIdent = preg_match("/^[0-9a-zA-Z]*$/",$identityCard);
                if($resIdent==0){
                    $ret['status'] = 29;
                    $ret['message'] = '操作失败3';
                    $this->apiCallback($ret);exit();
                }
                $data['image4'] = $imagefour;
                $data['type'] = '2';
            }
            
            //验证唯一码是否正确
            $model = D('Members');
            $res = $model->checkUserId($phone, $userId, 'id,handlePassword,encrypt,groupType');
            //var_dump($res);die;
            if ($res && $res['groupType'] != 2) {

                $resError = $model->getHandleErrorNum($res['id'], '2');
                if ($resError < 5) {//验证输入错误的次数
                    //验证操作密码是否正确
                    if ($res['handlePassword'] == md5(md5($handlePassword . $res['encrypt']))) {
                        //验证通过后，重置错误次数状态
                        $model->setHandleErrorStatus($res['id'], '2');

                        //再次验证短信验证码
                        $resCode = $model->checkCodePhone($phone, $code, '7');
                        if ($resCode) {//判断验证码是否存在
                            //组织信息提交
                            $data['uid'] = $res['id'];
                            $data['username'] = $name;
                            $data['addTime'] = time();
                            $data['idcard'] = $identityCard;
                            $data['email'] = $email;
                            $data['flag'] = '1';

                            
                            //图片上传暂时停止
                            //判断图片是否上传
                            //$p = D("Picture");
                            //$info = $p->upload($_FILES, C('PICTURE_UPLOAD'), C('PICTURE_UPLOAD_DRIVER'), C("UPLOAD_LOCAL_CONFIG"));
                            /*
                            if(empty($info['imageFirst']['path']) || empty($info['imageSecond']['path']) || empty($info['imageThree']['path'])){
                                $ret['status'] = 10;
                                $ret['message'] = '图片超过3M，无法上传';
                                $this->apiCallback($ret);
                            }*/
                            //$data['image1'] = $info['imageFirst']['path'];
                            //$data['image2'] = $info['imageSecond']['path'];
                            //$data['image3'] = $info['imageThree']['path'];

                            //保存会员认证信息
                            $result = $model->saveMemberNews($data);
                            //echo $model->getLastSql();die;
                            if ($result) {//判断操作是否成功
                                $ret['status'] = 1;
                                $ret['message'] = '已提交验证，等待审核';
                            } else {
                                $ret['status'] = 10;
                                $ret['message'] = '操作失败4';
                            }
                        } else {
                            $ret['status'] = 10;
                            $ret['message'] = '验证码错误，请重新输入';
                        }
                    } else {
                        //验证不通过时，记录失败次数.添加错误日志
                        //$model->saveHandleErrorLog($res['id'], $imei, '3');
                        $model->setHandleErrorStatus($res['id'], $imei, '2');

                        $ret['status'] = 14;
                        $ret['message'] = '密码输入错误！' . (5 - $resError);
                        $ret['getTimes'] = 5 - $resError;
                    }
                } else {
                    $ret['status'] = 21;
                    $ret['message'] = '您的兑换密码错误尝试超限，请明天再试';
                    $ret['getTimes'] = 0;
                }
            } else {
                $ret['status'] = 10;
                $ret['message'] = '操作失败5';
            }
        }else{
            $ret['status'] = 10;
            $ret['message'] = '操作失败6';
        }
        $this->apiCallback($ret);exit();
    }

    /**
     * 3.2修改密码-验证短信验证码
     * @access public
     * @return array/false
     */
    public function checkEditPhoneCode() {

        //验证相关密钥
        //$this->checkKey();

        $ret['success'] = true;
        $version = I('post.version');
        $userId = I('post.userId');
        $phone = I('post.phone');
        $code = I('post.code');
        $type = I('post.type');

        if (is_empty($userId) || is_empty($phone) || is_empty($code) || is_empty($type)) {//判断参数是否为空
            $ret['status'] = 29;
            $ret['message'] = '参数不完整';
        } else {
            if (($type == 1) || ($type == 2)) {//判断type值是否正确
                preg_match('/1[0-9]{10}/', $phone, $matches);
                if ((strlen($phone) == '11') && $matches[0]) {//验证手机号码规则
                    $model = D("Members");
                    //验证会员唯一码是否正确
                    $resMember = $model->checkUserId($phone, $userId, "id,encrypt");
                    if ($resMember['id']) {//判断会员唯一码是否失效
                        //验证验证码是否失效
                        $typeString = 3; //获取类型 1：注册，2：修改登录密码，3：修改操作密码，4：忘记密码，5：绑定手机获取旧手机验证码，6：绑定手机获取新手机验证码
                        if ($type == 1) {
                            $typeString = 2;
                        }
                        $resCode = $model->checkCodePhone($phone, $code, $typeString);
                        if ($resCode) {//判断验证码是否存在
                            $ret['status'] = 1;
                            $ret['message'] = '验证成功';
                        } else {
                            $ret['status'] = 14;
                            $ret['message'] = '验证码错误，请重新输入';
                        }
                    } else {
                        $ret['status'] = 10;
                        $ret['message'] = '操作失败';
                    }
                } else {
                    $ret['status'] = 8;
                    $ret['message'] = '你输入的手机号码不存在';
                }
            } else {
                $ret['status'] = 10;
                $ret['message'] = '操作失败';
            }
        }

        $this->apiCallback($ret);exit();
    }

    /**
     * 3.2修改密码-验证短信验证码通过后，修改密码
     * @access public
     * @return array/false
     */
    public function editPasswordPhoneCode() {

        //验证相关密钥
        //$this->checkKey();

        $ret['success'] = true;
        $version = I('post.version');
        $userId = I('post.userId');
        $phone = I('post.phone');
        $code = I('post.code');
        $type = I('post.type');
        $password = I('post.password');
        $imei = I('post.imei');

        if (is_empty($userId) || is_empty($phone) || is_empty($code) || is_empty($type) || is_empty($password)) {//判断参数是否为空
            $ret['status'] = 29;
            $ret['message'] = '参数不完整';
        } else {
            if (($type == 1) || ($type == 2)) {//判断type值是否正确
                preg_match('/1[0-9]{10}/', $phone, $matches);
                if ((strlen($phone) == '11') && $matches[0]) {//验证手机号码规则
                    //验证会员唯一码是否正确
                    $model = D("Members");
                    $resMember = $model->checkUserId($phone, $userId, "id,encrypt");
                    if ($resMember['id']) {//判断会员唯一码是否失效
                        $typeString = 3; //获取类型 1：注册，2：修改登录密码，3：修改操作密码，4：忘记密码，5：绑定手机获取旧手机验证码，6：绑定手机获取新手机验证码
                        if ($type == 1) {
                            $typeString = 2;
                        }
                        $resCode = $model->checkCodePhone($phone, $code, $typeString);
                        //验证验证码是否失效
                        if ($resCode) {//判断验证码是否存在
                            if (strlen($password) == 32) {//验证密码长度
                                $newPwd = $password . $resMember['encrypt'];
                                if ($type == 1) {//判断密码类型
                                    $resultStatus = $model->updateUserPassword($resMember['id'], $newPwd);
                                } else if ($type == 2) {
                                    $resultStatus = $model->updateUserHandlePwd($resMember['id'], $newPwd);
                                }
                                if ($resultStatus) {//判断验证是否成功
                                    //获取用户IP
                                    $ip = $_SERVER['HTTP_X_REAL_IP'];
                                    //如果获取不到说明没有走代理,通过普通方式获取IP
                                    $ip = $ip ? $ip : $_SERVER['REMOTE_ADDR'];
                                    $model->membersPasswordLog($resMember['id'], $imei, $ip, $type);
                                    $ret['status'] = 1;
                                    $ret['message'] = '修改成功';
                                } else {
                                    $ret['status'] = 10;
                                    $ret['message'] = '操作失败';
                                }
                            } else {
                                $ret['status'] = 22;
                                $ret['message'] = '密码不足6位';
                            }
                        } else {
                            $ret['status'] = 14;
                            $ret['message'] = '验证码错误，请重新输入';
                        }
                    } else {
                        $ret['status'] = 10;
                        $ret['message'] = '操作失败';
                    }
                } else {
                    $ret['status'] = 8;
                    $ret['message'] = '你输入的手机号码不存在';
                }
            } else {
                $ret['status'] = 10;
                $ret['message'] = '操作失败';
            }
        }
        $this->apiCallback($ret);exit();
    }

    /**
     * 3.2注册1-验证手机号码和短信验证码
     * @access public
     * @return true/false
     */
    public function registerPhone() {
        //验证相关密钥
        //$this->checkKey();

        $ret['success'] = true;
        $imei = I('post.imei');
        $phone = I('post.phone');
        $code = I('post.code');
        if (is_empty($phone) || is_empty($code)) {//判断参数是否为空
            $ret['status'] = 29;
            $ret['message'] = '参数不完整';
        } else {
            preg_match('/1[0-9]{10}/', $phone, $matches);
            if ((strlen($phone) == '11') && $matches[0]) {//验证手机号码规则
                $model = D('Members');
                //查询手机号码是否已注册
                $res = $model->getUserDataByPhone($phone, 'id');
                if ($res['id']) {//判断会员是否已注册
                    $ret['status'] = 9;
                    $ret['message'] = '亲，你的手机号已注册，请直接登录';
                } else {
                    //查询验证码是否正确、过期
                    $resCode = $model->checkCodePhone($phone, $code);
                    if ($resCode) {//判断返回信息状态
                        $ret['status'] = 1;
                        $ret['message'] = '成功';
                    } else {
                        //验证失败时，入日志库
                        $model->userErrorLog($phone, $imei, '', '2');
                        $ret['status'] = 14;
                        $ret['message'] = '验证码错误，请重新输入';
                    }
                }
            } else {
                $ret['status'] = 0;
                $ret['message'] = '你输入的手机号码不存在';
            }
        }
        $this->apiCallback($ret);exit();
    }

    /**
     * 3.2注册2-验证登录密码
     * @access public
     * @return true/false
     */
    public function registerPwd() {
        //验证相关密钥
        //$this->checkKey();
        $ret['success'] = true;
        $imei = I('post.imei');
        $phone = I('post.phone');
        $code = I('post.code');
        $mobileType = I('post.mobileType');
        $pwd = I('post.password');
        //echo $phone.'-'.$code.'-'.$pwd.'-'.$mobileType.'-'.$imei;
        
        if (is_empty($phone) || is_empty($code) || is_empty($pwd) || is_empty($mobileType)) {//判断参数是否为空
            $ret['status'] = 29;
            $ret['message'] = '参数不完整';
        } else {
            //获取会员信息
            $model = D('Members');
            //查询手机号码是否已注册
            $res = $model->getUserDataByPhone($phone, 'id');
            if (empty($res)) {//判断会员是否已注册
                $resCode = $model->checkCodePhone($phone, $code);
                if ($resCode) {//判断验证码信息是否存在               
                    $pwdLength = strlen($pwd);
                    if ($pwdLength == 32) {//判断密码长度
                        
                        if($imei){//判断imei是否为空
                            $resImei = $model->getUserDataByImei($imei, 'id,addTime,password,freeze');

                            if ($resImei['id'] && empty($resImei['password']) && $resImei['freeze']=='0') {//进行编辑
                                $result = $model->updateRegisterUserInfo($resImei['id'], $phone, $pwd, '');
                            } else {
                                //只存用户信息
                                $result = $model->saveUserInfo($phone, $pwd, $imei, $mobileType, '');
                            }
                        }else{
                            $result = $model->saveUserInfo($phone, $pwd, $imei, $mobileType, '');
                        }

                        if ($result) {//判断操作是否成功
                            $field = 'id,phone,imageUrl,image,freeze,type,name,integral,invite,uniqueId as userId,cityId,provinceId,handlePassword,groupType,memberCodeUrl';
                            $result = $model->getUserDataByPhone($phone, $field);
                            $result['code'] = base64_encode($result['id']); //加密userId

                            $text = C('DOWNLOAD_ADDRESS');
                            $tmpJson['fb_type'] = 1;
                            $tmpJson['userId'] = encodePass($result['id']);

                            $val = urlencode(base64_encode(jsonStr($tmpJson)));
                            $text .= '?' . $val; //二维码内信息
                            
                            //获取年月日
                            //$nowDay=date("Y-m-d");
                            $nowDay=date("Y-m-d");
                            //$file = '/home/wwwroot/dev/Uploads/memberCode/'.$nowDay.'/';
                            $file = 'Uploads/memberCode/'.$nowDay.'/';
                            
                            if(!is_dir($file)){//判断目录是否存在
                                mkdir($file);
                            }
                            $url = 'Uploads/memberCode/'.$nowDay.'/'; //存储地址
                            
                            $urlLast = encodePass($result['id']) . time() . '.jpg';
                            $model->qrcode($text, ROOT .'/'. $url . $urlLast, 'H', '5');

                            $str = $url . $urlLast;
                            $model->updateMemberCodeUrl($result['id'], $str);
                            $result['memberCodeUrl'] = WEBURL .'/'. $str;


                            //判断邀请开关是否开启 iosInviteStatus：ios邀请开关,1:开启，2：关闭
                            $system = D('system');
                            $system_switch_status = $system->iosInviteStatus();
                            ($system_switch_status == 1) ? ($result['switchOn'] = 1) : ($result['switchOn'] = 2);
                            //判断是否是老用户
                            $result['isNewUser'] = 2;
                            if (in_array($result['type'], array('3.0', '3.1'))) {
                                $result['isNewUser'] = 1;
                            }
                            //判断用户状态
                            if ($result['freeze'] == '0') {
                                $result['userStatus'] = 1;
                            } elseif ($result['freeze'] == '1') {
                                $result['userStatus'] = 2;
                            } elseif ($result['freeze'] == '2') {
                                $result['userStatus'] = 3;
                            }
                            //判断操作密码状态
                            $result['handlePwdStatus'] = 2;
                            if ($result['handlePassword']) {
                                $result['handlePwdStatus'] = 1;
                            }
                            unset($result['handlePassword']);
                            unset($result['type']);
                            unset($result['freeze']);

                            if ($result['groupType'] == 2) {//1表示未认证 2 表示认证
                                $result['isAuthentication'] = 2;
                            } else {
                                $flag = $model->getMemberAuthentication($res['id']);
                                $result['isAuthentication'] = $flag ? 3 : 1;
                            }
                            //$result['isAuthentication'] = $result['groupType'] < 2 ? 1 : 2; //1表示未认证 2 表示认证
                            
                            if ($resImei['id'] && empty($resImei['password']) && $resImei['freeze']=='0') {//进行编辑

                            } else {
                                //11-注册送飞币 10000
                                //$content = '恭喜你，注册成功！送您飞币';
                                //$model->addMemberDope($result['id'], $content, '1', 10000, '', '11');
                            }
                            /*
                            if (empty($resImei['id'])) {//发送消息
                                //11-注册送飞币 10000
                                $content = '恭喜你，注册成功！送您飞币';
                                $model->addMemberDope($result['id'], $content, '1', 10000, '', '11');
                            }
                             */
                            
                            $ret['info'] = $result;
                            $ret['status'] = 1;
                            $ret['message'] = '成功';
                        } else {
                            $ret['status'] = 10;
                            $ret['message'] = '操作失败';
                        }
                    } else {
                        $ret['status'] = 11;
                        $ret['message'] = '请输入6到16的小写字母、数字或下划线';
                    }
                } else {
                    //验证失败时，入日志库
                    $model->userErrorLog($phone, $imei, '', '2');
                    $ret['status'] = 14;
                    $ret['message'] = '验证码错误，请重新输入';
                }
            } else {
                $ret['status'] = 9;
                $ret['message'] = '亲，你的手机号已注册，请直接登录';
            }
        }
        $this->apiCallback($ret);exit();
    }

    public function test() {
//        $phone = '18910149105';
//        $code = mt_rand(99999, 999999);
//        //发送短信验证码
//        $codeStatus = sendCode($phone, $code);
//        dump($codeStatus);
    }

    /**
     * 3.2注册3-验证邀请人
     * @access public
     * @return true/false
     */
    public function registerInvite() {

        //验证相关密钥
        $this->checkKey();
        //判断系统状态
        $system = D('system');
        $invite_switch_status = $system->inviteSwitchStatus();
        //当系统状态为不正常的时候关闭整个系统
        if ($invite_switch_status != 1) {
            $ret['status'] = 10002;
            $ret['message'] = '邀请功能暂时关闭';
            echo jsonStr($ret);
            exit(0);
        }
        $ret['success'] = true;
        $imei = I('post.imei');
        $phone = I('post.phone');
        $userId = I('post.userId');
        $inviteCode = I('post.inviteCode');

        if ( is_empty($phone) || is_empty($userId) || is_empty($inviteCode)) {//判断参数是否为空
            $ret['status'] = 29;
            $ret['message'] = '参数不完整';
        } else {
            $model = D("Members");
            //验证手机号和唯一码
            $resmember = $model->getUserDataByPhone($phone, 'id,addTime,imei,uniqueId');
            ///print_r($resmember);
            if (empty($resmember)) {
                $ret['status'] = 10;
                $ret['message'] = '操作失败';
                echo jsonStr($ret);
                exit(0);
            } elseif ($resmember['uniqueId'] != $userId) {//唯一码与IMEI号是否正确
                $ret['status'] = 10;
                $ret['message'] = '操作失败';
                echo jsonStr($ret);
                exit(0);
            }
            $newUserId = $resmember['id'];
            $flag = D('Members')->getUserStatus($newUserId);
            if ($flag != 2) {//判断会员是否正常
                $return['status'] = -100;
                $return['message'] = '抱歉，您的飞报号权限受限，暂时无法完成此操作！';
                echo jsonStr($return);
                die;
            }

            //验证会员邀请次数是否受限
            $resTimes = $model->getErrorByInviteNum($newUserId);
            if ($resTimes > 5) {
                $ret['status'] = 16;
                $ret['message'] = '对不起，邀请码错误尝试已超限';
                echo jsonStr($ret);
                exit(0);
            }

            //验证邀请码的唯一码是否有效
            $resImei = $model->checkInviteCode($inviteCode);
            //print_r($resImei);
            if (empty($resImei)) {//判断邀请码不存在
                //验证失败时，入日志库
                $model->userErrorLog($phone, $imei, $newUserId, '3');
                $resError = $model->getErrorByInviteNum($newUserId);
                if ($resError >= 5) {
                    $ret['status'] = 16;
                    $ret['message'] = '对不起，邀请码错误尝试已超限';
                    $ret['getTimes'] = 0;
                } else {
                    $ret['status'] = 15;
                    //$ret['message'] = '密码输入错误!'.(3 - $resError);
                    $ret['message'] = '邀请码有误，请重新填写（' . (5 - $resError) . '）';
                    $ret['getTimes'] = 5 - $resError;
                }
                //$ret['status'] = 15;
                //$ret['message'] = '邀请码有误，请重新输入';
                echo jsonStr($ret);
                exit(0);
            }


            $oldUserId = $resImei['id'];
            if ($oldUserId == $newUserId) {
                //如果自己邀请自己,返回操作失败
                $this->ret['status'] = -14;
                $this->ret['message'] = '操作失败';
            } elseif ($oldUserId > $newUserId) {
                //如果邀请人注册时间晚于被邀请人,则返回失败
                $this->ret['status'] = -19;
                $this->ret['message'] = '操作失败';
            } else {
                if ($oldUserId && $newUserId) {

                    $this->ret['info'] = '0';
                    $nuser_flag = D('Members')->getUserFlag($newUserId);
                    $user_flag = D('Members')->getUserFlag($oldUserId);
                    //var_dump($user_flag);die;
                    //当用户邀请自己的时候直接返回错误
                    if ($oldUserId == $nuser_flag) {
                        $result = 5;
                    } else {
                        $result = D('invite')->inviteSaveData($oldUserId, $user_flag, $newUserId, $nuser_flag);
                    }
                }
                if ($result == 1) {
                    $this->ret['status'] = -888;
                    $this->ret['message'] = '传参不完整';
                }
                if ($result == 2) {
                    //如果当前用户的终端类型为模拟器
                    if ($user_flag == "2" && $nuser_flag == '1') {
                        $this->ret['status'] = -13000;
                    } else {
                        $this->ret['status'] = 1;
                    }
                    $this->ret['message'] = '操作成功';
                }
                if ($result == 3) {
                    $this->ret['status'] = -1;
                    $this->ret['message'] = '操作失败';
                }
                if ($result == 4) {
                    $this->ret['status'] = -13;
                    $this->ret['message'] = '数据已存在';
                }
                if ($result == 5) {
                    $this->ret['status'] = -10;
                    $this->ret['message'] = '非法传参';
                }
                if ($result == 6) {
                    $this->ret['status'] = -15;
                    $this->ret['message'] = '操作频繁';
                }
                if ($result == 7) {
                    $this->ret['status'] = -16;
                    $this->ret['message'] = '邀请用户非正常状态';
                }
                if ($result == 8) {
                    $this->ret['status'] = -17;
                    $this->ret['message'] = '新用户非正常状态';
                }
                if ($result == 9) {
                    $this->ret['status'] = -18;
                    $this->ret['message'] = '已被对方邀请,无法互相邀请!';
                }
            }

            //生成记录
            D('invite')->recordOperatingLog(array("uid" => $oldUserId, 'nuid' => $newUserId, "remark" => $ret['message'] . ",result:" . $result . ",status:" . $ret['status']));
        }

        $this->apiCallback($this->ret);exit();
    }

    /**
     * 3.2注册4-设置操作密码
     * @access public
     * @return true/false
     */
    public function registerHandlePassword() {
        //验证相关密钥
        //$this->checkKey();

        $ret['success'] = true;
        $imei = I('post.imei');
        $phone = I('post.phone');
        $userId = I('post.userId'); //唯一码
        $handlePassword = I('post.handlePwd');

        if (is_empty($phone) || is_empty($userId) || is_empty($handlePassword)) {//判断参数是否为空
            $ret['status'] = 29;
            $ret['message'] = '参数不完整';
        } else {
            $model = D("Members");
            //判断帐号是否存在
            $resmember = $model->checkUserId($phone, $userId, 'id,addTime,encrypt');
            if ($resmember) {//判断帐号是否存在
                $id = $resmember['id'];
                //验证密码格式
                //preg_match('/[0-9]{6,16}/', $handlePassword, $matches);
                if (strlen($handlePassword) == 32) {//判断密码格式是否符合要求
                    //修改帐号信息，重置操作密码状态
                    $id = $model->updateUserHandlePwd($id, $handlePassword . $resmember['encrypt']);
                    if ($id) {//判断添加是否成功
                        $ret['status'] = 1;
                        $ret['message'] = '成功';
                        $ret['handlePwdStatus'] = 1; //操作密码状态
                    } else {
                        $ret['status'] = 10;
                        $ret['message'] = '操作失败';
                    }
                } else {
                    $ret['status'] = 22;
                    $ret['message'] = '密码不足32位';
                }
            } else {
                $ret['status'] = 10;
                $ret['message'] = '操作失败';
            }
        }
        $this->apiCallback($ret);exit();
    }

    /**
     * 3.2会员登录
     * @access public
     * @return array/false
     */
    public function memberLogin() {
        //验证相关密钥
        //$this->checkKey();

        $ret['success'] = true;
        $phone = I('post.phone');
        $pwd = I('post.password');
        $imei = I('post.imei');

        if (is_empty($phone) || is_empty($pwd)) {//验证参数是否为空
            $ret['status'] = 29;
            $ret['message'] = '参数不完整';
        } else {
            //验证手机号码格式、长度
            preg_match('/1[0-9]{10}/', $phone, $matches);
            if ((strlen($phone) == '11') && $matches[0]) {//验证手机号码规则
                //验证密码长度
                $pwdLength = strlen($pwd);
                //if ($pwdLength == 32) {//判断密码长度
                $model = D("Members");
                $field = 'id,name,jpush,phone,image,imageUrl,integral,cityId,provinceId,freeze,handlePassword,invite,type,password,encrypt,groupType,memberCodeUrl';
                $res = $model->getUserDataByPhone($phone, $field);
                if ($res) {//判断手机号码是否注册
                    $userId = $res['id'];

                    if ($res['freeze'] != 0) {//判断账号是否非法
                        $return['status'] = 33;
                        $return['message'] = '账号非法，暂时无法完成此操作';

                        echo jsonStr($return);
                        exit(0);
                    }
                    //手机号码当天是否受限
                    $resTimes = $model->getErrorPwdNum($phone);
                    //var_dump($resTimes);die;
                    if ($resTimes < 5) {
                        //验证密码是否正确,正确时返回会员信息，错误时入错误日志
                        if ($res['password'] == md5(md5($pwd . $res['encrypt']))) {

                            //更新用户唯一标识
                            $res['userId'] = $model->updateUniqueId($userId);
                            //更新登陆次数
                            $model->updateLoginNum($userId);
                            //获取返回参数
                            //loginCount：登录操作失败次数
                            $res['loginCount'] = $model->getErrorPwdNum($phone);
                            //isNewUser:是否是老用户，1：老用户，2：新用户
                            (($res['type'] == '3.0') or ($res['type'] == '3.1')) ? ($res['isNewUser'] = 1) : ($res['isNewUser'] = 2);
                            //switchOn：ios邀请开关,1:开启，2：关闭
                            $system = D('system');
                            $system_switch_status = $system->iosInviteStatus();
                            ($system_switch_status == 1) ? ($res['switchOn'] = 1) : ($res['switchOn'] = 2);
                            //操作密码状态
                            empty($res['handlePassword']) ? ($res['handlePwdStatus'] = 2) : ($res['handlePwdStatus'] = 1);
                            //会员状态
                            $res['userStatus'] = intval($res['freeze']) + 1;
                            //$res['id'] = encodePass($res['id']);//加密userId
                            $res['code'] = base64_encode($res['id']); //加密userId
                            //获取用户IP
                            $ip = $_SERVER['HTTP_X_REAL_IP'];
                            //如果获取不到说明没有走代理,通过普通方式获取IP
                            $ip = $ip ? $ip : $_SERVER['REMOTE_ADDR'];
                            unset($res['password']);
                            unset($res['handlePassword']);
                            unset($res['encrypt']);
                            //添加登陆日志表
                            $model->userLoginLog($phone, $imei, $ip);
                            if ($res['groupType'] == 2) {//1表示未认证 2 表示认证
                                $res['isAuthentication'] = 2;
                            } else {
                                $flag = $model->getMemberAuthentication($res['id']);
                                $res['isAuthentication'] = $flag ? 3 : 1;
                            }

                            $model->clearLoginNum($res['id'], $phone, 1);

                            $tmpJson['fb_type'] = 1;
                            $tmpJson['userId'] = encodePass($res['id']);

                            $val = urlencode(base64_encode(jsonStr($tmpJson)));
                            
                            if (empty($res['memberCodeUrl'])) {
                                $text = C('DOWNLOAD_ADDRESS');

                                $text .= '?' . $val; //二维码内信息
                                $nowDay=date("Y-m-d");
                                //$file = '/home/wwwroot/dev/Uploads/memberCode/'.$nowDay.'/';
                                $file = 'Uploads/memberCode/'.$nowDay.'/';
                                
                                if(!is_dir($file)){//判断目录是否存在
                                    mkdir($file);
                                }
                                
                                $url = '/Uploads/memberCode/'.$nowDay.'/'; //存储地址
                                $urlLast = encodePass($res['id']) . time() . '.jpg';
                                $model->qrcode($text, ROOT . $url . $urlLast, 'H', '5');

                                $str = $url . $urlLast;
                                $model->updateMemberCodeUrl($res['id'], $str);
                                $res['memberCodeUrl'] = WEBURL . $str;
                            } else {
                                $res['memberCodeUrl'] = WEBURL . $res['memberCodeUrl'];
                            }

                            //$res['isAuthentication'] = $res['groupType'] < 2 ? 1 : 2; 
                            $ret['info'] = $res;
                            $ret['status'] = 1;
                            $ret['message'] = '成功';
                        } else {
                            //验证失败时，入日志库
                            $model->loginErrorLog($userId, $phone, $imei);
                            $resTimes = $model->getErrorPwdNum($phone);
                            //echo $resTimes;
                            if ($resTimes >= 5) {
                                $ret['status'] = 24;
                                $ret['message'] = '你的密码错误尝试超限，请明天再试';
                            } else {
                                $ret['status'] = 31;
                                $ret['message'] = '密码输入错误，还剩' . (5 - $resTimes) . '次机会';
                            }
                        }
                    } else {
                        $ret['status'] = 24;
                        $ret['message'] = '你的密码错误尝试超限，请明天再试';
                    }
                } else {
                    $ret['status'] = 27;
                    $ret['message'] = '该手机号尚未注册，是否前往注册';
                }
                //} else {
                //$ret['status'] = 31;
                //$ret['message'] = '手机号码或密码有误';
                //}
            } else {
                $ret['status'] = 8;
                $ret['message'] = '你输入的手机号码不存在';
            }
        }
        $this->apiCallback($ret);exit();
    }

    /**
     * 3.2修改会员昵称
     * @access public
     * @return true/false
     */
    public function editMemberNickname() {
        //验证相关密钥
        //$this->checkKey();
        $ret['success'] = true;
        $userId = I('post.userId');
        $name = I('post.name');
        $phone = I('post.phone');
        if (is_empty($userId) || is_empty($name) || is_empty($phone)) {//验证参数是否为空
            $ret['status'] = 29;
            $ret['message'] = '参数不完整';
        } else {
            //验证昵称长度
            $nameLength = utf8_strlen($name);
            if ($nameLength <= 16) {//判断昵称长度是否正确
                //验证昵称是否合法
                $flag = check_name_badwords($name, 3);
                //echo $flag;die;
                if ($flag == 1) {//验证昵称包含关键词
                    $model = D("Members");
                    $flag = $model->checkRepeat($name);
                    if ($flag) {
                        $ret['status'] = 42;
                        $ret['message'] = '昵称重复，请重新输入';
                    } else {
                        $resMember = $model->checkUserId($phone, $userId);
                        if ($resMember) {//判断唯一码是否有效
                            //修改昵称
                            $result = $model->updateNikename($userId, $phone, $name);
                            if ($result) {//判断修改是否成功
                                $ret['status'] = 1;
                                $ret['message'] = '修改成功';
                            } else {
                                $ret['status'] = 10;
                                $ret['message'] = '操作失败';
                            }
                        } else {
                            $ret['status'] = 10;
                            $ret['message'] = '操作失败';
                        }
                    }
                } else {
                    $ret['status'] = 28;
                    $ret['message'] = '昵称中包含关键词';
                }
            } else {
                $ret['status'] = 18;
                $ret['message'] = '昵称只能输入8位汉字或16位字母数字';
            }
        }
        $this->apiCallback($ret);exit();
    }

    /**
     * 3.3修改会员签名
     * @access public
     * @return true/false
     */
    public function editSignature() {
        //验证相关密钥
        //$this->checkKey();
        $ret['success'] = true;
        $userId = I('post.userId');
        $signature = I('post.signature');
        $phone = I('post.phone');
        if (is_empty($userId) || is_empty($signature) || is_empty($phone)) {//验证参数是否为空
            $ret['status'] = 29;
            $ret['message'] = '参数不完整';
        } else {
            $model = D("Members");
            $resMember = $model->checkUserId($phone, $userId);
            if ($resMember) {//判断唯一码是否有效
                //修改昵称
                $result = $model->updateSignture($userId, $phone, $signature);
                if ($result) {//判断修改是否成功
                    $ret['status'] = 1;
                    $ret['message'] = '修改成功';
                } else {
                    $ret['status'] = 10;
                    $ret['message'] = '操作失败';
                }
            } else {
                $ret['status'] = 10;
                $ret['message'] = '操作失败';
            }
        }
        $this->apiCallback($ret);exit();
    }
    
    /**
     * 3.2修改会员头像
     * @access public
     * @return true/false
     */
    public function editMemberUrl() {
        //验证相关密钥
        //$this->checkKey();
        $ret['success'] = true;
        $userId = I('post.userId');
        $imageUrl = I('post.imageUrl');
        $image = I('post.image');
        $phone = I('post.phone');
        if (is_empty($userId) || is_empty($imageUrl) || is_empty($image) || is_empty($phone)) {//验证参数是否为空
            $ret['status'] = 29;
            $ret['message'] = '参数不完整';
        } else {
            $model = D("Members");
            $resMember = $model->checkUserId($phone, $userId, 'image,imageUrl');
            if ($resMember) {//判断唯一码是否有效
                $id = $model->updateUserImage($userId, $phone, $image, $imageUrl);
                if ($id) {//判断修改是否成功
                    $ret['status'] = 1;
                    $ret['info'] = array('image' => $image, 'imageUrl' => $imageUrl);
                    $ret['message'] = '操作成功';
                } else {
                    $ret['status'] = 10;
                    $ret['message'] = '操作失败';
                }
            } else {
                $ret['status'] = 10;
                $ret['message'] = '操作失败';
            }
        }
        $this->apiCallback($ret);exit();
    }

    /**
     * 3.2修改密码-验证旧密码
     * @access public
     * @return true/false
     */
    public function editPasswordCheck() {
        //验证相关密钥
        //$this->checkKey();

        $ret['success'] = true;
        $userId = I('post.userId');
        $phone = I('post.phone');
        $pwd = I('post.password');
        $type = I('post.type');

        if (is_empty($userId) || is_empty($pwd) || is_empty($type) || is_empty($phone)) {//判断参数是否为空
            $ret['status'] = 29;
            $ret['message'] = '参数不完整';
        } else {
            //var_dump($type);die;
            if (($type == 1) || ($type == 2)) {//判断type值是否正确
                $pwdLength = strlen($pwd);
                if ($pwdLength != 32) {//判断密码长度
                    $ret['status'] = 22;
                    $ret['message'] = '密码不足6位';
                } else {
                    $model = D('Members');
                    //echo $this->userId;die;
                    if ($type == 1) {//修改登录密码
                        $times = $model->getUserIdErrorLogNum($this->userId, '5');

                        if ($times > 4) {
                            $ret['status'] = 21;
                            $ret['message'] = '您的旧密码错误尝试超限，请明天再试';
                            $this->apiCallback($ret);
                        }
                    } elseif ($type == 2) {
                        //$times = $model->getUserIdErrorLogNum($this->userId, '4');
                        $times = $model->getHandleErrorNum($this->userId, '4');
                        if ($times > 4) {
                            $ret['status'] = 21;
                            $ret['message'] = '您的旧密码错误尝试超限，请明天再试';
                            $this->apiCallback($ret);
                        }
                    }

                    $resMember = $model->checkUserId($phone, $userId, "id,encrypt,handlePassword,password");
                    //var_dump($resMember);die;
                    if ($resMember['id']) {//判断会员唯一码是否失效
                        //验证输入的密码是否正确
                        $oldPwd = md5(md5($pwd . $resMember['encrypt']));
                        $flag = false;
                        if (($type == 1 && $resMember['password'] == $oldPwd) || ($type == 2 && $resMember['handlePassword'] == $oldPwd)) {
                            $flag = true;
                        }
                        if ($flag) {//判断密码是否正确
                            if ($type == 1) {
                                $model->clearLoginNum($this->userId, $phone, 5);
                            } else {
                                $model->clearLoginNum($this->userId, $phone, 4);
                            }

                            $ret['status'] = 1;
                            $ret['message'] = '验证成功';
                        } else {
                            if ($type == 1) {//验证修改登录密码
                                $model->userErrorLog($phone, $imei, $resMember['id'], '5');
                                $resError = $model->getUserIdErrorLogNum($this->userId, '5');
                                if ($resError >= 5) {
                                    $ret['status'] = 21;
                                    $ret['message'] = '您的密码错误尝试超限，请明天再试';
                                    $ret['getTimes'] = 0;
                                } else {
                                    $ret['status'] = 23;
                                    //$ret['message'] = '密码输入错误!'.(3 - $resError);
                                    $ret['message'] = '密码错误，请重新输入(' . (5 - $resError) . ')';
                                    $ret['getTimes'] = 5 - $resError;
                                }
                            } elseif ($type == 2) {
                                //$model->userErrorLog($phone, $imei, $resMember['id'], '4');
                                //$resError = $model->getUserIdErrorLogNum($this->userId, '4');
                                $data['userId'] = $this->userId;
                                //$data['integral'] = I('post.integral');
                                //$data['money'] = I('post.money');
                                //$data['phone'] = I('post.phone');
                                $data['status'] = '4';
                                $data['addTime'] = time();

                                $model->addHandleErrorLog($data);
                                $resError = $model->getHandleErrorNum($this->userId, '4');
                                
                                if ($resError >= 5) {
                                    $ret['status'] = 21;
                                    $ret['message'] = '您的兑换密码错误尝试超限，请明天再试';
                                    $ret['getTimes'] = 0;
                                } else {
                                    $ret['status'] = 23;
                                    //$ret['message'] = '密码输入错误!'.(3 - $resError);
                                    $ret['message'] = '兑换密码错误，请重新输入(' . (5 - $resError) . ')';
                                    $ret['getTimes'] = 5 - $resError;
                                }
                            }
                        }
                    } else {
                        $ret['status'] = 10;
                        $ret['message'] = '操作失败';
                    }
                }
            } else {
                $ret['status'] = 10;
                $ret['message'] = '操作失败';
            }
        }
        $this->apiCallback($ret);exit();
    }

    /**
     * 3.2修改密码-进行修改密码
     * @access public
     * @return true/false
     */
    public function editPassword() {
        //验证相关密钥
        //$this->checkKey();

        $ret['success'] = true;
        $userId = I('post.userId');
        $phone = I('post.phone');
        $pwd = I('post.password');
        $newPwd = I('post.newPassword');
        $type = I('post.type');
        $imei = I('post.imei');
        if (is_empty($userId) || is_empty($pwd) || is_empty($type) || is_empty($newPwd) || is_empty($phone)) {//判断参数是否为空
            $ret['status'] = 29;
            $ret['message'] = '参数不完整';
        } else {
            if (($type == 1) || ($type == 2)) {//判断type值是否正确
                $pwdLength = strlen($pwd);
                $newPwdLength = strlen($newPwd);
                if (($pwdLength != 32) || ($newPwdLength != 32)) {//判断密码长度
                    $ret['status'] = 22;
                    $ret['message'] = '密码不足6位';
                } else {
                    $model = D('Members');
                    $resMember = $model->checkUserId($phone, $userId, "id,encrypt,handlePassword,password");
                    if ($resMember['id']) {//判断会员唯一码是否失效
                        //验证输入的密码是否正确
                        $oldPwd = md5(md5($pwd . $resMember['encrypt']));
                        $newPwd = $newPwd . $resMember['encrypt'];
                        if ($type == 1 && $resMember['password'] == $oldPwd) {//判断密码类型
                            $resultStatus = $model->updateUserPassword($resMember['id'], $newPwd);
                        } else if ($type == 2 && $resMember['handlePassword'] == $oldPwd) {
                            $resultStatus = $model->updateUserHandlePwd($resMember['id'], $newPwd);
                            $model->setHandleErrorStatus($resMember['id'], '4');
                        }
                        if ($resultStatus) {//判断密码是否正确
                            //获取用户IP
                            $ip = $_SERVER['HTTP_X_REAL_IP'];
                            //如果获取不到说明没有走代理,通过普通方式获取IP
                            $ip = $ip ? $ip : $_SERVER['REMOTE_ADDR'];
                            //生成修改密码记录
                            $model->membersPasswordLog($resMember['id'], $imei, $ip, $type);
                            $ret['status'] = 1;
                            $ret['message'] = '修改成功';
                        } else {
                            $ret['status'] = 23;
                            $ret['message'] = '密码错误，请重新输入';
                        }
                    } else {
                        $ret['status'] = 10;
                        $ret['message'] = '操作失败';
                    }
                }
            } else {
                $ret['status'] = 10;
                $ret['message'] = '操作失败';
            }
        }

        $this->apiCallback($ret);exit();
    }

    /**
     * 3.2忘记密码-验证短信验证码
     * @access public
     * @return true/false
     */
    public function checkForgetPasswordCode() {

        //验证相关密钥
        //$this->checkKey();
        $ret['success'] = true;
        $phone = I('post.phone');
        $code = I('post.code');
        //$imei = I('post.imei');
        if (is_empty($phone) || is_empty($code)) {//判断参数是否为空
            $ret['status'] = 29;
            $ret['message'] = '参数不完整';
        } else {
            //判断手机号码格式
            preg_match('/1[0-9]{10}/', $phone, $matches);
            if ((strlen($phone) != '11') || empty($matches[0])) {//验证手机号码规则
                $ret['status'] = 8;
                $ret['message'] = '你输入的手机号码不存在';
            } else {
                $model = D('Members');
                //查询手机号码是否未注册
                $resMember = $model->getUserDataByPhone($phone, 'id');
                if ($resMember['id']) {//判断会员是否未注册
                    //判断手机号码和验证码是否正确
                    $resCode = $model->checkCodePhone($phone, $code, '4');
                    if ($resCode) {//判断验证码是否符合要求
                        $ret['status'] = 1;
                        $ret['message'] = '修改成功';
                    } else {
                        $ret['status'] = 14;
                        //$ret['message'] = '验证码错误，请重新输入';
                        //3.3.1忘记密码 更新提示语版本
                        $ret['message'] = '亲，验证码不对哦！';
                        
                    }
                } else {
                    $ret['status'] = 27;
                    //$ret['message'] = '该手机号尚未注册，是否前往注册';
                    $ret['message'] = '手机号尚未注册，请先注册';
                }
            }
        }
        $this->apiCallback($ret);exit();
    }

    /**
     * 3.2忘记密码-验证修改密码
     * @access public
     * @return true/false
     */
    public function checkEditForgetPassword() {
        //验证相关密钥
        //$this->checkKey();

        $ret['success'] = true;
        $phone = I('post.phone');
        $code = I('post.code');
        $newpwd = I('post.password');
        //$imei = I('post.imei');

        if (is_empty($phone) || is_empty($code) || is_empty($newpwd)) {//判断参数是否为空
            $ret['status'] = 29;
            $ret['message'] = '参数不完整';
        } else {
            //判断手机号码格式
            preg_match('/1[0-9]{10}/', $phone, $matches);
            if ((strlen($phone) != '11') || empty($matches[0])) {//验证手机号码规则
                $ret['status'] = 8;
                $ret['message'] = '你输入的手机号码不存在';
            } else {
                $model = D('Members');
                //查询手机号码是否未注册
                $resMember = $model->getUserDataByPhone($phone, 'id,encrypt');
                if ($resMember['id']) {//判断会员是否未注册
                    //判断手机号码和验证码是否正确
                    $resCode = $model->checkCodePhone($phone, $code, '4');
                    if ($resCode) {//判断验证码是否符合要求
                        $pwdLength = strlen($newpwd);
                        if ($pwdLength != 32) {
                            $ret['status'] = 22;
                            $ret['message'] = '密码不足6位';
                        } else {
                            //修改密码
                            $result = $model->updateUserPassword($resMember['id'], $newpwd . $resMember['encrypt']);
                            if ($result) {
                                $ret['message'] = '操作成功';
                                $ret['status'] = 1;
                            } else {
                                $ret['message'] = '操作失败';
                                $ret['status'] = 10;
                            }
                        }
                    } else {
                        $ret['status'] = 14;
                        $ret['message'] = '验证码错误，请重新输入';
                    }
                } else {
                    $ret['status'] = 27;
                    $ret['message'] = '该手机号尚未注册，是否前往注册';
                }
            }
        }
        $this->apiCallback($ret);exit();
    }

    /**
     * 3.2绑定手机-验证会员兑换密码
     * @access public
     * @return true/false
     */
    public function checkHandlePassword() {
        //验证相关密钥
        //$this->checkKey();

        $ret['success'] = true;
        $userId = I('post.userId');
        $phone = I('post.phone');
        $handlePassword = I('post.handlePwd');
        $imei = I('post.imei');
        $type = I('post.type');

        if (is_empty($userId) || is_empty($handlePassword) || is_empty($phone)) {//判断参数是否为空
            $ret['status'] = 29;
            $ret['message'] = '参数不完整';
        } else {
            //验证唯一码是否正确
            $model = D('Members');
            $res = $model->checkUserId($phone, $userId, 'id,handlePassword,encrypt');
            if ($res) {

                if ($type == 2) {//判断是否是身份认证
                    $resError = $model->getHandleErrorNum($res['id'], '2');
                    //echo $resError;die;
                    if ($resError < 5) {//验证输入错误的次数
                        //验证操作密码是否正确
                        if ($res['handlePassword'] == md5(md5($handlePassword . $res['encrypt']))) {
                            //验证通过后，重置错误次数状态
                            $model->setHandleErrorStatus($res['id'], '2');
                            $ret['status'] = 1;
                            $ret['message'] = '验证成功';
                        } else {
                            //验证不通过时，记录失败次数.添加错误日志
                            $data['userId'] = $res['id'];
                            $data['status'] = '2';
                            $data['addTime'] = time();

                            $model->addHandleErrorLog($data);
                            $resError = $model->getHandleErrorNum($res['id'], '2');
                            if ($resError >= 5) {
                                $ret['status'] = 21;
                                $ret['message'] = '您的兑换密码错误尝试超限，请明天再试';
                                $ret['getTimes'] = 0;
                            } else {
                                $ret['status'] = 14;
                                //$ret['message'] = '密码输入错误!'.(3 - $resError);
                                $ret['message'] = '兑换密码错误，请重新输入(' . (5 - $resError) . ')';
                                $ret['getTimes'] = 5 - $resError;
                            }
                        }
                    } else {
                        $ret['status'] = 21;
                        $ret['message'] = '您的兑换密码错误尝试超限，请明天再试';
                        $ret['getTimes'] = 0;
                    }
                } else {
                    $resError = $model->getHandleErrorNum($res['id'], '1');
                    //echo $resError;die;
                    if ($resError < 5) {//验证输入错误的次数
                        //验证操作密码是否正确
                        if ($res['handlePassword'] == md5(md5($handlePassword . $res['encrypt']))) {
                            //验证通过后，重置错误次数状态
                            $model->setHandleErrorStatus($res['id'], '1');
                            $ret['status'] = 1;
                            $ret['message'] = '验证成功';
                        } else {
                            //验证不通过时，记录失败次数.添加错误日志
                            $data['userId'] = $res['id'];
                            $data['status'] = '1';
                            $data['addTime'] = time();

                            $model->addHandleErrorLog($data);
                            $resError = $model->getHandleErrorNum($res['id'], '1');
                            if ($resError >= 5) {
                                $ret['status'] = 21;
                                $ret['message'] = '您的兑换密码错误尝试超限，请明天再试';
                                $ret['getTimes'] = 0;
                            } else {
                                $ret['status'] = 14;
                                //$ret['message'] = '密码输入错误!'.(3 - $resError);
                                $ret['message'] = '兑换密码错误，请重新输入(' . (5 - $resError) . ')';
                                $ret['getTimes'] = 5 - $resError;
                            }
                        }
                    } else {
                        $ret['status'] = 21;
                        $ret['message'] = '您的兑换密码错误尝试超限，请明天再试';
                        $ret['getTimes'] = 0;
                    }
                }
            } else {
                $ret['status'] = 10;
                $ret['message'] = '操作失败';
            }
        }
        $this->apiCallback($ret);exit();
    }

    /**
     * 3.2获取会员详情
     * @access public
     * @return array/false
     */
    public function detail() {

        //验证相关密钥
        //$this->checkKey();

        $ret['success'] = true;
        $userId = I('post.userId');
        $phone = I('post.phone');
        if (is_empty($userId) || is_empty($phone)) {//判断参数是否为空
            $ret['status'] = 29;
            $ret['message'] = '参数不完整';
        } else {
            $model = D("Members");
            // 验证userId
            $field = 'id,uniqueId as userId,name,jpush,phone,image,imei,imageUrl,integral,invite,cityId,provinceId,freeze,handlePassword,type,groupType,memberCodeUrl,signture';
            $res = $model->checkUserId($phone, $userId, $field);
            //var_dump($res);
            if ($res['id']) {
                $res['code'] = base64_encode($res['id']); //加密userId
                $ret['status'] = 1;
                $ret['message'] = '成功';
                //loginCount：登录操作失败次数
                $res['loginCount'] = $model->getErrorPwdNum($phone);
                //isNewUser:是否是老用户，1：老用户，2：新用户
                (($res['type'] == '3.0') or ($res['type'] == '3.1')) ? ($res['isNewUser'] = 1) : ($res['isNewUser'] = 2);
                //switchOn：ios邀请开关,1:开启，2：关闭
                $system = D('system');
                $system_switch_status = $system->iosInviteStatus();
                ($system_switch_status == 1) ? ($res['switchOn'] = 1) : ($res['switchOn'] = 2);
                //操作密码状态
                empty($res['handlePassword']) ? ($res['handlePwdStatus'] = 2) : ($res['handlePwdStatus'] = 1);
                //会员状态
                $res['userStatus'] = intval($res['freeze']) + 1;
                if ($res['groupType'] == 2) {//1表示未认证 2 表示认证
                    $res['isAuthentication'] = 2;
                } else {
                    $flag = $model->getMemberAuthentication($res['id']);
                    $res['isAuthentication'] = $flag ? 3 : 1;
                }

                $res['memberCodeUrl'] = WEBURL .'/'. $res['memberCodeUrl'];
                //$res['isAuthentication'] = $res['groupType'] < 2 ? 1 : 2; //1表示未认证 2 表示认证
                $ret['info'] = $res;
            } else {
                $ret['status'] = 10;
                $ret['message'] = '操作失败';
            }
        }
        $this->apiCallback($ret);exit();
    }

    /**
     * 3.1快速体验接口
     * @access public
     * @param string $imei
     * @param string $mobileType
     * @param string $cityId
     * @param string $provinceId
     * @param string $jpush
     * @author FrankKung <kongfanjian@andlisoft.com>
     */
    public function fastExperience() {

        // 检测参数
        /* 上线时检测
          if ( empty($this->_map['imei'])) {
          $this->ret['success'] = false;
          $this->ret['status'] = -888;
          $this->ret['message'] = '参数不完整';
          $this->apiCallback($this->ret);
          }
         */

        //验证相关密钥
        $this->checkKey();
        $jpush = I('post.jpush');


        // 查询
        $where['imei'] = array('eq', $this->_map['imei']);
        $field = 'id,imei,name,code,jpush,image,imageUrl,integral,cityId,freeze,provinceId';
        $res = $this->_model->selData($where, 1, $field);
        //var_dump($res);die;

        if (!empty($res[0]['id'])) { // 授权
            if (!empty($jpush)) {//登陆时,若有JPush号,则同步更新
                $data['jpush'] = $jpush;
                $dataOb = $this->_model->create($data);
                $this->_model->where($where)->data($dataOb)->save();
            }
            $id = $res[0]['id'];
            if ($res) {
                $this->_check->checkUserFlag($id);
            }
            //修改 会员 更新时间
            $PublicOb = A('Public');
            $PublicOb->index($res[0]['id']);


            // 构建数据
            $this->ret['info'] = $res[0];
            $this->ret['message'] = '查询成功';
        } else {
            // 注册		
            //终端类型加密字段
            $mobileflag = (string) trim(I("post.mobileflag"));
            //密钥
            $secret_key = trim($_GET['secret_key']);
            //通过运算确定终端类型
            if (!is_numeric($mobileflag) && $mobileflag) {
                if ($mobileflag == md5("{$secret_key}2")) {
                    $mobileflag = '2';
                } else {
                    $mobileflag = '1';
                }
            } else {
                $mobileflag = '1';
            }

            if (empty($mobileflag) && $mobileflag != "1" && $mobileflag != "2") {
                $this->ret['success'] = false;
                $this->ret['status'] = -888;
                $this->ret['message'] = '参数不完整' . $mobileflag;
                $this->ret['info'] = (object) array();
                $this->apiCallback($this->ret);
                exit(0);
            }

            if ($mobileflag == "2") {
                $this->ret['success'] = false;
                $this->ret['status'] = -888;
                $this->ret['message'] = '参数不完整' . $mobileflag;
                $this->ret['info'] = (object) array();
                $this->apiCallback($this->ret);
                exit(0);
            }

            $data = array(
                'imei' => I('post.imei'),
                'name' => randNickname(),
                'image' => randPhoto(),
                'mobileType' => I('post.mobileType', '1'),
                'provinceId' => I('post.provinceId', '1', 'int'),
                'integral' => '1000',
                'jpush' => I('post.jpush', '0'),
                'mobileflag' => $mobileflag,
                'addTime' => time()
            );

            if (I('post.cityId', '', 'int'))
                $data['cityId'] = I('post.cityId', '', 'int');

            //手机端经常上传 字符串 过来
            switch (strtolower($data['mobileType'])) {
                case 'android':
                    $data['mobileType'] = '1';
                    break;
                case 'Android':
                    $data['mobileType'] = '1';
                    break;
                case 'ios':
                    $data['mobileType'] = '2';
                    break;
                case '2':
                    $data['mobileType'] = '2';
                    break;
                default:
                    $data['mobileType'] = '1';
            }

            //var_dump($data);die;
            $id = $this->_model->data($data)->add();
            //echo $this->_model->getLastSql();die;

            if (!empty($id)) {
                // 飞报号
                $where['id'] = array('eq', $id);
                $data['code'] = 'FB' . $id;
                $this->_model->where($where)->save($data);

                // 查询注册结果
                $res = $this->_model->selData($where, 1, $field);
                $this->ret['info'] = $res[0];
                $this->ret['message'] = '查询成功';
                if ($id) {
                    $this->_check->checkUserFlag($id);
                }

                //对新注册的用户、自动推送有效期内的公告
                $this->pushNoticeOfActite($id);
            } else {
                // $this->ret['status'] = -1;
                // $this->ret['message'] = '查询失败2';
                $this->ret['status'] = 0;
                $this->ret['message'] = '没有数据了';
                $this->ret['info'] = (object) array();
            }
        }

        // 反馈
        $this->apiCallback($this->ret);exit();
    }

    /*
     * 3.1对新注册的用户、自动推送有效期内的公告
     * @access public
     * @param string $userId 用户ID
     * @param string $image
     * @author Jine <luxikun@andlisoft.com>
     */

    public function pushNoticeOfActite($userId) {
        //查询有效期内的公告
        // $userId = 42883;
        $time = time();
        $ob = D('Message');

        $condition[0]['type'] = '1';
        $condition[0]['startTime'] = '0';
        $condition[0]['endTime'] = '0';

        $map[0]['type'] = '1';
        $map[0]['startTime'] = array('elt', $time);
        $map[0]['endTime'] = array('egt', $time);
        $map['_logic'] = 'or';
        $map['_complex'] = $condition;

        $re = $ob->selData($map, '', 'id');
        // echo $ob->getLastSql();
        // var_dump($re);die;
        if (!empty($re)) {
            $push = A('Admin/JPush');
            foreach ($re as $v) {
                $push->pushNotice($v['id'], $userId);
            }
        }
    }

    /**
     * 3.2添加意见反馈
     * @access public
     * @author FrankKung <kongfanjian@andlisoft.com>
     */
    public function addFeedback() {

        $userId = $this->userId;
        $versoin = I('post.versoin');
        $content = I('post.content');
        $this->ret['success'] = true;
        unset($this->ret['info']);

        if (is_empty($content)) {//判断参数
            $this->ret['status'] = 10;
            $this->ret['message'] = '操作失败';
        } else {
            $model = D('Feedback');
            $res = $model->addContent($userId, $content);

            if ($res) {//判断添加是否成功
                if ($res == 2) {
                    $this->ret['status'] = 37;
                    $this->ret['message'] = '已经反馈过了，请不要重复提交！';
                } else {
                    $this->ret['status'] = 1;
                    $this->ret['message'] = '添加成功';
                }
            } else {
                $this->ret['status'] = 10;
                $this->ret['message'] = '操作失败';
            }
        }
        $this->apiCallback($this->ret);exit();
        //echo jsonStr($return);
    }

    /**
     * 3.2添加会员分享
     * @access public
     * @author FrankKung <kongfanjian@andlisoft.com>
     */
    public function addMemberShare() {

        $userId = $this->userId;
        $versoin = I('post.versoin');
        $imei = I('post.imei');
        $type = I('post.type');

        $this->ret['success'] = true;
        unset($this->ret['info']);

        if (is_empty($type) || is_empty($versoin)) {//判断参数
            $this->ret['status'] = 10;
            $this->ret['message'] = '操作失败';
        } else {
            $model = D('Members');
            $res = $model->addShare($userId, $type, $imei);

            if ($res) {//判断添加是否成功
                $this->ret['status'] = 1;
                $this->ret['message'] = '添加成功';
            } else {
                $this->ret['status'] = 10;
                $this->ret['message'] = '操作失败';
            }
        }
        $this->apiCallback($this->ret);exit();
    }

    /**
     * 3.1修改个人资料
     * @access public
     * @param string $id
     * @param string $name
     * @param string $userId token验证用
     * @param string $image
     * @author FrankKung <kongfanjian@andlisoft.com>
     */
    public function modifyPersonalInformation() {
        // 检测参数
        if (empty($_POST['name']) && empty($_POST['image'])) {
            $this->ret['success'] = false;
            $this->ret['status'] = -888;
            $this->ret['message'] = '参数不完整1';
            $this->ret['info'] = (object) array();
            $this->apiCallback($this->ret);
            exit(0);
        }

        if (empty($_POST['userId'])) {
            $this->ret['success'] = false;
            $this->ret['status'] = -888;
            $this->ret['message'] = '参数不完整';
            $this->ret['info'] = (object) array();
            $this->apiCallback($this->ret);
            exit(0);
        }

        // 更新数据
        $where['id'] = I('post.userId');
        // 修改数据
        $data['id'] = I('post.userId');
        $name = I('post.name');
        $image = I('post.image');
        $imageUrl = I('post.imageUrl');

        if (!empty($name)) {

            //echo $name;die;
            /*
              $array=array(
              1=>'飞报',
              2=>'官方',
              3=>'通知',
              4=>'公告',
              5=>'客服',
              );

              $flag=0;
              for($i=1;$i<=count($array);$i++){
              //echo $array[$i];die;
              if(strpos($name,$array[$i]) !== false){
              $flag=1;
              }
              }
             */
            $flag = check_badwords($name);
            if ($flag == 2) {
                $this->ret['status'] = -2234;
                $this->ret['info'] = (object) array();
                $this->ret['message'] = '修改个人资料失败';
                $this->apiCallback($this->ret);
                exit(0);
            }

            $data['name'] = $name;
        }

        if (!empty($image)) {
            $data['image'] = $image;
            $data['imageUrl'] = $imageUrl;
        }


        $res = $this->_model->where($where)->data($data)->save();


        if ($res) {
            $field = 'id,imei,name,code,image,imageUrl,integral,cityId,provinceId';
            $res = $this->_model->selData($where, 1, $field);
            $this->ret['info'] = $res[0];
            $this->ret['message'] = '修改个人资料成功';
        } else {
            $id = $this->_model->where($where)->find();
            if ($id) {
                $field = 'id,imei,name,code,image,imageUrl,integral,cityId,provinceId';
                $res = $this->_model->selData($where, 1, $field);
                $this->ret['info'] = $res[0];
                $this->ret['message'] = '修改个人资料成功';
            } else {
                $this->ret['status'] = -1;
                $this->ret['info'] = (object) array();
                $this->ret['message'] = '修改个人资料失败';
            }
        }

        // 反馈
        $this->apiCallback($this->ret);exit();
    }

    /**
     * 3.1客服信息
     * @access public
     * @param string $cityId 
     * @author FrankKung <kongfanjian@andlisoft.com>
     */
    public function customerService() {

        //die;
        // 获取参数
        $cityId = I('post.cityId');

        // 添加参数检测
        if (is_empty($cityId)) {
            $this->ret['status'] = -888;
            $this->ret['message'] = '参数不完整';
            $this->ret['info']['branchCustomer'] = array();

            $this->apiCallback($this->ret);
        }

        // 查询
        $customerModel = D('Customer');
        // 地区客服 查询条件
        $map['cityId'] = $cityId;
        $map['type'] = '2'; // 1 总部 2 区域
        $field = 'phone';
        $limit = '';
        $branchCustomer = $customerModel->selData($map, $limit, $field);
        $headquartersCustomer = $customerModel->selData(array('type' => 1), $limit, $field);

        // 构建数据
        if (empty($branchCustomer) && empty($headquartersCustomer)) {
            //$this->ret['info'] = (object) array();
            $this->ret['info']['headquarters'] = array();
            $this->ret['info']['branchCustomer'] = array();
            // $this->ret['status'] = -1;
            // $this->ret['message'] = '查询失败';
            $this->ret['status'] = 0;
            $this->ret['message'] = '没有数据了';
        } elseif (empty($branchCustomer)) {
            $this->ret['status'] = 1;
            $this->ret['message'] = '查询成功';
            $this->ret['info']['headquarters'] = $headquartersCustomer;
            $this->ret['info']['branchCustomer'] = array();
        } else {
            $this->ret['status'] = 1;
            $this->ret['message'] = '查询成功';
            $this->ret['info']['headquarters'] = $headquartersCustomer;
            $this->ret['info']['branchCustomer'] = $branchCustomer;
        }

        // 反馈
        $this->apiCallback($this->ret);exit();
    }

    /**
     * 3.2个人消息中心列表
     * @access public
     * @param string $userId 
     * @param string $phone
     * @param string $type 
     * @param string $page
     * @param string $pageSize 
     * @param string $selectTime 
     */
    public function messageList() {
        //echo 'messagelist';die;
        
        // 获取参数
        $version = I('post.version');
        $userId = I('post.userId');
        $phone = I('post.phone');
        $this->ret['success'] = true;

        $type = I('post.type');
        $page = I('post.page');
        $pageSize = I('post.pageSize');
        $selectTime = I('post.selectTime');

        if (is_empty($userId) || is_empty($type) || is_empty($page) || is_empty($pageSize)) {// 参数检测
            $this->ret['status'] = -888;
            $this->ret['message'] = '参数不完整';
            $this->apiCallback($this->ret);exit();
        }

        if (empty($page) || $page == 0) {
            $page = 1;
        }

        $selectTime = $selectTime <= 0 ? time() : $selectTime;
        $limit = ($page - 1) * $pageSize . "," . $pageSize;
        $where = ' addTime <=' . $selectTime;
        $this->ret['selectTime'] = time();
        //echo $type;

        if ($type == 1 || $type == 2) {//判断参数
            if ($type == 1) {//消息列表
                $model = D("Members");
                // 验证userId
                $field = 'id,uniqueId as userId,new_message,name,jpush,phone,image,imageUrl,integral,cityId,provinceId,freeze,handlePassword,type';
                $res = $model->checkUserId($phone, $userId, $field);
                //var_dump($res);die;

                if ($res['id']) {
                    $field = 'id,content,isRead,integral,integralType,addTime'; // 查询字段
                    $where .= ' and userId =' . $res['id'];

                    $message = M('MembersDope')->field($field)->where($where)->order(' addTime desc')->limit($limit)->select();

                    //对id进行加密处理
                    if ($message) {
                        foreach ($message as $k => $v) {
                            $message[$k]['id'] = encodePass($v['id']);
                        }
                    }
                    
                    if($res['new_message']==2){//未阅 改 已阅
                        $dataStatus=array();
                        $dataStatus['new_message'] = '1' ;
                        //var_dump($dataStatus);
                        M('Members')->where('id =' . $res['id'])->save($dataStatus);
                        //echo M('Members')->getLastSql();die;
                    }
                } else {
                    $this->ret['status'] = 10;
                    $this->ret['message'] = '操作失败';
                }
            } else {//公告列表
                $where = 'status ="1" and addTime<=' . $selectTime;
                $field = 'id,title as content,txtUrl,addTime'; // 查询字段
                $message = M('Notice')->field($field)->where($where)->order(' addTime desc')->limit($limit)->select();
                if (!empty($message)) {
                    foreach ($message as $key => $val) {
                        $message[$key]['id'] = encodePass($val['id']);
                    }
                }
            }

            // 构建数据
            if (is_bool($message) && empty($message)) {
                $this->ret['status'] = -1;
                $this->ret['message'] = '查询成功，暂无数据';
            } else if ((is_array($message) || is_null($message)) && empty($message)) {
                $this->ret['status'] = 36;
                $this->ret['message'] = '查询成功，暂无数据';
            } else {
                $this->ret['status'] = 1;
                $this->ret['message'] = '查询成功';
                $this->ret['info'] = $message;
            }
        } else {
            $this->ret['status'] = 10;
            $this->ret['message'] = '操作失败';
        }

        // 反馈
        $this->apiCallback($this->ret);exit();
    }
    
        /**
     * 3.2个人消息中心列表
     * @access public
     * @param string $userId 
     * @param string $phone
     * @param string $type 
     * @param string $page
     * @param string $pageSize 
     * @param string $selectTime 
     */
    public function messageNewList() {

        // 获取参数
        $version = I('post.version');
        $userId = I('post.userId');
        $phone = I('post.phone');
        $this->ret['success'] = true;

        $type = I('post.type');
        $page = I('post.page');
        $pageSize = I('post.pageSize');
        $selectTime = I('post.selectTime');

        if (is_empty($userId) || is_empty($type) || is_empty($page) || is_empty($pageSize)) {// 参数检测
            $this->ret['status'] = -888;
            $this->ret['message'] = '参数不完整';
            $this->apiCallback($this->ret);
        }

        if (empty($page) || $page == 0) {
            $page = 1;
        }

        $selectTime = $selectTime <= 0 ? time() : $selectTime;
        $limit = ($page - 1) * $pageSize . "," . $pageSize;
        $where = ' addTime <=' . $selectTime;
        $this->ret['selectTime'] = time();
        //echo $type;die;

        if ($type == 1 || $type == 2) {//判断参数
            if ($type == 1) {//消息列表
                $model = D("Members");
                // 验证userId
                $field = 'id,uniqueId as userId,name,jpush,phone,image,imageUrl,integral,cityId,provinceId,freeze,handlePassword,type';
                $res = $model->checkUserId($phone, $userId, $field);
                //var_dump($res);die;

                if ($res['id']) {
                    $field = 'id,content,isRead,integral,integralType,addTime'; // 查询字段
                    //$where .= ' and status = "1"';
                    $where .= ' and userId =' . $res['id'];

                    $message = M('MembersDope')->field($field)->where($where)->order(' addTime desc')->limit($limit)->select();
                    //echo M('MembersDope')->getLastSql();
                    //未阅 改 已阅
                    if (!empty($message)) {

                        if ($message) {
                            foreach ($message as $k => $v) {
                                $message[$k]['id'] = encodePass($v['id']);
                            }
                        }

                        $dataS['isRead'] = '2';
                        M('MembersDope')->where('userId =' . $res['id'] . ' and isRead ="1"')->data($dataS)->save();
                    }
                } else {
                    $this->ret['status'] = 10;
                    $this->ret['message'] = '操作失败';
                }
            } else {//公告列表
                $where = 'status ="1" and addTime<=' . $selectTime;
                $field = 'id,title as content,txtUrl,addTime'; // 查询字段
                $message = M('Notice')->field($field)->where($where)->order(' addTime desc')->limit($limit)->select();
                if (!empty($message)) {
                    foreach ($message as $key => $val) {
                        $message[$key]['id'] = encodePass($val['id']);
                    }
                }
                //var_dump($message);die;
            }

            // 构建数据
            if (is_bool($message) && empty($message)) {
                $this->ret['status'] = -1;
                $this->ret['message'] = '查询失败';
            } else if ((is_array($message) || is_null($message)) && empty($message)) {
                $this->ret['status'] = 36;
                $this->ret['message'] = '查询成功，暂无数据';
            } else {
                $this->ret['status'] = 1;
                $this->ret['message'] = '查询成功';
                $this->ret['info'] = $message;
            }
        } else {
            $this->ret['status'] = 10;
            $this->ret['message'] = '操作失败';
        }

        // 反馈
        $this->apiCallback($this->ret);exit();
    }

    public function getMessage() {
        // 获取参数
        $version = I('post.version');
        $id = I('post.dataId');
        $id = decodePass($id);

        $res = M('Notice')->where('id = ' . $id)->field('id,title,content')->find();

        $this->ret['status'] = 1;
        $this->ret['message'] = '查询成功';

        if ($res['id']) {
            $this->ret['title'] = $res['title'];
            $this->ret['content'] = $res['content'];
        } else {
            $this->ret['title'] = '暂无通知';
            $this->ret['content'] = '此广告已经删除';
        }
        $this->apiCallback($this->ret);exit();
    }

    /**
     * categoryId2ModelName
     * @access private
     * @param string $type
     * @return string Model Name
     * @author FrankKung <kongfanjian@andlisoft.com>
     */
    private function categoryId2ModelName($category) {
        switch ($category) { //旧 1头条，2发现，3本地，4店铺  //2014-9-4 1 头条 2 店铺 4 发现 6 本地新闻
            case '1': //1 ->1
                $modelName = 'CollectNewsLog';
                $this->field = 'id,cid,tid,type,addTime,image,title,summary,time,detail,share';
                break;
            case '2': //4 ->2
                $modelName = 'CollectShopLog';
                $this->field = 'id,tid,name,image,address,lng,lat,addTime';
                break;
            case '4': //2 ->4
                $modelName = 'CollectFoundLog';
                $this->field = 'id,tid,publisher,addTime,image,title,time,hot,detail,lng,lat,share';
                break;
            case '6': //3 ->6
                $modelName = 'CollectLocalnewsLog';
                $this->field = 'id,tid,image,title,summary,time,detail,share,addTime';
                break;
        }
        return $modelName;
    }

    /**
     * 我的收藏列表
     * @access public
     * @param string $userId 会员ID
     * @param string $cityId 城市ID（非必填）
     * @param string $lng 经度 
     * @param string $lat 纬度
     * @param string $id
     * @param string $pageSize
     * @param string $category
     * @author FrankKung <kongfanjian@andlisoft.com>
     */
    public function myCollect() {

        // 获取参数
        $userId = I('post.userId');
        $type = I('post.type');
        // $cityId = I('post.cityId',0); // 没有使用
        $lng = I('post.lng', 0);
        $lat = I('post.lat', 0);
        $id = I('post.id');
        $pageSize = I('post.pageSize', 5);
        $category = I('post.category', 1);

        // 参数检测
        if (is_empty($userId) || is_empty($id) || !in_array($category, array('1', '2', '4', '6'))) {
            $this->ret['status'] = -888;
            $this->ret['message'] = '参数不完整';
            $this->apiCallback($this->ret);
        }

        // 查询条件
        $where['userId'] = $userId;
        $where['status'] = '1';
        if (!empty($type))
            $where['id'] = array('lt', $id);
        $order['id'] = 'desc';

        // 设置模型数据表
        $modelName = $this->categoryId2ModelName($category);

        // 查询
        $model = D($modelName . 'View');
        $list = $model->selData($where, $pageSize, $this->field, $order);
        // echo $model->getLastSql();die;
        // 头条特殊处理
        if ($category == 1) {
            foreach ($list as $k => $v) {
                if ($list[$k]['cid'] == 5) {//轮播图写死成 type=2
                    $list[$k]['type'] = 2;
                }
            }
        }

        // 发现特殊处理
        if ($category == 4) {
            if (!empty($list)) {
                foreach ($list as $k => $v) {
                    // 赞 的数量
                    $praise = D('PraiseFoundLog')->getNum(array('dataId' => $v['tid']));
                    $list[$k]['praiseNum'] = $praise;

                    //热图状态(是热图,且赞数大于5,则为真)
                    $hotNum = D('PraiseFoundLog')->selData(array('dataId' => $v['tid']), '', 'id');
                    $list[$k]['hot'] = (count($hotNum) > 5 && $v['hot'] == '1') ? 1 : 0;
                }
            }
        }

        // 店铺特殊处理
        if ($category == 2) {
            foreach ($list as $k => $v) {
                // 计算距离
                $list[$k]['distance'] = '0';
                if (!empty($lng) && !empty($lat) && !empty($v['lng']) && !empty($v['lat'])) {
                    $list[$k]['distance'] = GetDistance($lng, $lat, $v['lng'], $v['lat']);
                }

                // 标签
                $tagsWhere['id'] = $v['tid'];
                $tagsField = 'id,tag1,tag2,tag3,tag4';
                $tags = D('Shop')->where($tagsWhere)->getField($tagsField, '|');
                $list[$k]['tag'] = explode('|', $tags[$v['tid']]);
                // 星级&状态
                $shopInfo = D('Shop')->where($tagsWhere)->field('star,status')->find();
                $list[$k]['star'] = $shopInfo['star'];
                $list[$k]['status'] = $shopInfo['status'];
                // 赞b(￣▽￣)d
                $praise = D('PraiseShopLog')->getNum(array('dataId' => $v['tid']));
                $list[$k]['praiseNum'] = $praise;
            }
        }

        // 构建数据
        if (is_bool($list) && empty($list)) {
            $this->ret['status'] = -1;
            $this->ret['message'] = '查询失败';
            $this->ret['info'] = (object) array();
        } else if ((is_array($list) || is_null($list)) && empty($list)) {
            $this->ret['status'] = 0;
            $this->ret['message'] = '没有数据了';
            $this->ret['info'] = (object) array();
        } else {
            $this->ret['status'] = 1;
            $this->ret['message'] = '查询成功';
            $this->ret['info']['data'] = $list;
        }

        $this->apiCallback($this->ret);exit();
    }

    /**
     * 3.2设置 push 的ID号码
     * @access public
     * @param string $userId 会员ID
     * @param string $jpush 设备ID号码
     * @author Jine <luxikun@andlisoft.com>
     */
    public function setRegisterId() {
        $return['success'] = true;
        $userId = $this->userId;
        $jpush = I('post.jpush');
        $cityId = I('post.cityId');
        $provinceId = I('post.provinceId');
        if (empty($_POST['cityId']) && empty($jpush)) {
            $return['status'] = -888;
            $return['message'] = '传参不完整';
        } else {
            $map['id'] = $userId;
            $userInfo = D('Members')->where($map)->field('jpush,cityId,provinceId')->find();

            if (empty($userInfo)) {//判断会员id是否存在
                $return['status'] = -100;
                $return['message'] = '会员不存在';
            } else {
                $flag = 0;
                if (!empty($jpush)) {

                    $data['jpush'] = $jpush;
                    if ($jpush != $userInfo['jpush']) {
                        $flag = 1;
                    }
                }
                if (!empty($_POST['cityId'])) {
                    $data['cityId'] = $cityId;
                    $data['provinceId'] = $provinceId;

                    if ($cityId != $userInfo['cityId'] || $provinceId != $userInfo['provinceId']) {
                        $flag = 1;
                    }
                }
                if ($flag == 0) {//判断是否执行修改
                    $return['status'] = 1;
                    $return['message'] = '修改成功!';
                } else {

                    $re = D('Members')->upData($map, $data);
                    if (empty($re)) {
                        $return['status'] = -1;
                        $return['message'] = '修改失败';
                    } else {
                        $return['status'] = 1;
                        $return['message'] = '修改成功';
                    }
                }
            }
        }
        echo jsonStr($return);
        die;
    }

    /**
     * 3.2未读的消息数量
     * @access public
     * @param string $userId 会员ID
     * @author FrankKung <kongfanjian@andlisoft.com>
     */
    public function messageListReadNum() {
        $userId = I("post.userId");
        $phone = I("post.phone");
        $selectTime = I("post.selectTime");


        $model = D("Members");
        // 验证userId
        $field = 'id,uniqueId as userId,new_message,name,jpush,phone,image,imageUrl,integral,cityId,provinceId,freeze,handlePassword,type';
        $res = $model->checkUserId($phone, $userId, $field);
        $nowTime = time();

        if ($res['id']) {
            $this->ret['status'] = 1;
            $this->ret['message'] = '查询成功';
            if( $res['new_message']==2){
                $this->ret['info'] = 1;
            }else{
                $this->ret['info'] = 0;
            }
            
            $this->ret['selectTime'] = $nowTime;
            
            /*
            if ($selectTime) {//判断查询条件
                $where = 'userId =' . $res['id'] . ' and isRead = "1" and addTime >' . $selectTime;
            } else {
                $where = 'userId =' . $res['id'] . ' and isRead = "1" and addTime <=' . $nowTime;
            }
            $list = M('MembersDope')->where($where)->select();
            //echo M('MembersDope')->getLastSql();die;

            if ($list) {

                $total = count($list);
                // 构建数据
                if ($total == 0) {
                    $this->ret['status'] = 1;
                    $this->ret['message'] = '查询成功';
                    $this->ret['info'] = '0';
                    $this->ret['selectTime'] = $nowTime;
                } else {
                    $this->ret['status'] = 1;
                    $this->ret['message'] = '查询成功';
                    $this->ret['info'] = $total;
                    $this->ret['selectTime'] = $nowTime;
                }
            } else {
                $this->ret['status'] = 10;
                $this->ret['message'] = '操作失败';
                $this->ret['info'] = '0';
                $this->ret['selectTime'] = $nowTime;
            }
             */
        } else {
            $this->ret['status'] = 10;
            $this->ret['message'] = '操作失败';
            $this->ret['info'] = '0';
            $this->ret['selectTime'] = $nowTime;
        }
        $this->apiCallback($this->ret);exit();
    }

    /**
     * 3.1第一次安装时填邀请码送飞币处理
     * @param string $userId 邀请人ID
     * @param string $newuserid 新会员ID
     * @author xiaofeng <yuanmingwei@feibaokeji.com>
     */
    public function invite() {

        //echo '123';echo '<br />';
        //echo $this->getIP();die;
        //验证相关密钥
        $this->checkKey();

        //判断系统状态
        $system = D('system');
        $invite_switch_status = $system->inviteSwitchStatus();
        //当系统状态为不正常的时候关闭整个系统
        if ($invite_switch_status != 1) {
            $ret['status'] = 10002;
            $ret['message'] = '邀请功能暂时关闭';
            echo jsonStr($ret);
            exit(0);
        }

        $userId = I("post.userid");
        $newUserId = I("post.newuserid");
        $mobileflag = I("post.mobileflag");

        $flag = D('Members')->getUserStatus($userId);
        if ($flag != 2) {//判断会员是否正常
            $return['status'] = -100;
            $return['message'] = '抱歉，您的飞报号权限受限，暂时无法完成此操作！';
            echo jsonStr($return);
            die;
        }
        /*
          $umobileflag = I("post.umobileflag");
          //$numobileflag = I("post.numobileflag");

          $map1['id'] = $userId;
          $reMembers = M('Members')->field('id,integral,mobileflag')->where($map1)->select();
          //判断会员当前是否是虚拟机
          if(($umobileflag=='2') &&　($reMembers[0]['mobileflag']=='1')){
          $datas['mobileflag']='2';
          $reIntegral = M('Members')->where($map1)->save($datas);
          }
         */


        if ($userId == $newUserId) {
            //如果自己邀请自己,返回操作失败
            $this->ret['status'] = -14;
            $this->ret['message'] = '操作失败';
        } elseif ($userId > $newUserId) {
            //如果邀请人注册时间晚于被邀请人,则返回失败
            $this->ret['status'] = -19;
            $this->ret['message'] = '操作失败';
        } else {
            if ($userId && $newUserId) {

                $this->ret['info'] = '0';
                $nuser_flag = D('Members')->getUserFlag($newUserId);
                $user_flag = D('Members')->getUserFlag($userId);
                //var_dump($user_flag);die;
                //当用户邀请自己的时候直接返回错误
                if ($userId == $nuser_flag) {
                    $result = 5;
                } else {
                    $result = D('invite')->inviteSaveData($userId, $user_flag, $newUserId, $nuser_flag);
                }
            }
            if ($result == 1) {
                $this->ret['status'] = -888;
                $this->ret['message'] = '传参不完整';
            }
            if ($result == 2) {
                //如果当前用户的终端类型为模拟器
                if ($user_flag == "2" && $nuser_flag == '1') {
                    $this->ret['status'] = -13000;
                } else {
                    $this->ret['status'] = 1;
                }
                $this->ret['message'] = '操作成功';
            }
            if ($result == 3) {
                $this->ret['status'] = -1;
                $this->ret['message'] = '操作失败';
            }
            if ($result == 4) {
                $this->ret['status'] = -13;
                $this->ret['message'] = '数据已存在';
            }
            if ($result == 5) {
                $this->ret['status'] = -10;
                $this->ret['message'] = '非法传参';
            }
            if ($result == 6) {
                $this->ret['status'] = -15;
                $this->ret['message'] = '操作频繁';
            }
            if ($result == 7) {
                $this->ret['status'] = -16;
                $this->ret['message'] = '邀请用户非正常状态';
            }
            if ($result == 8) {
                $this->ret['status'] = -17;
                $this->ret['message'] = '新用户非正常状态';
            }
            if ($result == 9) {
                $this->ret['status'] = -18;
                $this->ret['message'] = '已被对方邀请,无法互相邀请!';
            }
        }

        //记录操作
        D('invite')->recordOperatingLog(array("uid" => $userId, 'nuid' => $newUserId, "remark" => $this->ret['message'] . ",result:" . $result . ",status:" . $this->ret['status']));
        $this->apiCallback($this->ret);exit();
    }

    /**
     * 查询用户当前状态
     * @param string $userId 用户ID
     */
    function membersStatus() {
        //验证相关密钥
        //$this->checkKey();
        $this->ret['info'] = 0;
        $this->ret['status'] = 1;
        $userId = I("post.userId");
        // 参数检测
        if (is_empty($userId)) {
            $this->ret['status'] = -888;
            $this->ret['message'] = '参数不完整';
            $this->apiCallback($this->ret);
        } else {
            $flag = D("Members")->getUserStatus($userId);
            if ($flag == 1) {
                $this->ret['message'] = '被冻结';
                $this->ret['info'] = 1; //被冻结
            }
            if ($flag == 2) {
                $this->ret['message'] = '正常';
                $this->ret['info'] = 2; //正常
            }
            if ($flag == 3) {
                $this->ret['message'] = '非法';
                $this->ret['info'] = 3; //非法
            }
            if ($flag == 4) {
                $this->ret['message'] = '空用户';
                $this->ret['info'] = 4; //空用户
            }
            $this->apiCallback($this->ret);exit();
        }
    }

    function getiosstatus() {

        $this->ret['success'] = 1;
        $system = D('system');
        $system_switch_status = $system->iosInviteStatus();

        if ($system_switch_status == 1) {
            $this->ret['message'] = '已开启';
            $this->ret['status'] = 1;
        } else {
            $this->ret['message'] = '未开启';
            $this->ret['status'] = 2;
        }
        $this->apiCallback($this->ret);exit();
    }

    /**
     * 3.2验证旧手机号码
     */
    function checkOldPhone() {
        $userId = I("post.userId");
        $phone = I("post.phone");
        $code = I("post.code");
        $handlepwd = I("post.handlePwd");
        $version = I("post.version");
        unset($this->ret['info']);
        $this->ret['status'] = 14;
        $this->ret['message'] = '验证码错误，请重新输入';
        if (is_empty($userId) || is_empty($phone) || is_empty($code) || is_empty($handlepwd)) {
            $this->ret['status'] = -888;
            $this->ret['message'] = '验证码错误，请重新输入';
        } else {
            $model = D("Members");
            $userIdStatus = $model->checkUserId($phone, $userId, 'id'); //验证用户唯一标识状态
            //$startTime=mktime(0,0,0,date('m'),1,date('Y'));
            //$endTime=mktime(23,59,59,date('m'),date('t'),date('Y'));
            $resTimes = $model->getPhoneMembersBindLog($userIdStatus['id']);
            //$resTimes=M('EditPhoneLog')->field('count(id) as times')->where('userId ='.$userIdStatus['id'].' and addTime>='.$startTime.' and addTime <='.$endTime)->find();
            if ($resTimes['total'] >= 2) {
                $this->ret['message'] = '每个自然月内只能修改2次';
                $this->ret['status'] = 41;
                $this->apiCallback($this->ret);
            }

            $codeStatus = $model->checkCodePhone($phone, $code, 5); //验证码是否在有效果时间内

            if ($userIdStatus && $codeStatus) {//当以上两个验证都成功
                $handlepwdStatus = $model->checkHandlePwd($userId, $phone, $handlepwd); //验证兑换密码是否正确
                if ($handlepwdStatus) {
                    $this->ret['status'] = 1;
                    $this->ret['message'] = '操作成功';
                }
            }
        }
        $this->apiCallback($this->ret);exit();
    }

    /**
     * 3.2验证新手机号码
     */
    function checkNewPhone() {
        $userId = I("post.userId");
        $phone = I("post.phone");
        $code = I("post.code");
        $newPhone = I("post.newPhone");
        $newCode = I("post.newCode");
        $handlepwd = I("post.handlePwd");
        $version = I("post.version");
        unset($this->ret['info']);
        $this->ret['status'] = 10;
        //$this->ret['message'] = '操作失败';
        $this->ret['message'] = '验证码错误，请重新输入';

        if (is_empty($userId) || is_empty($phone) || is_empty($code) || is_empty($newPhone) || is_empty($newCode) || is_empty($handlepwd)) {
            $this->ret['status'] = -888;
            $this->ret['message'] = '参数不完整';
        } else {
            $model = D("Members");
            if ($newPhone != $phone) {
                $userData = $model->getUserDataByPhone($newPhone); //验证新手机号是否存在
                if (empty($userData)) {
                    $userIdStatus = $model->checkUserId($phone, $userId, 'id'); //验证用户唯一标识状态
                    //$startTime=mktime(0,0,0,date('m'),1,date('Y'));
                    //$endTime=mktime(23,59,59,date('m'),date('t'),date('Y'));

                    $resTimes = $model->getPhoneMembersBindLog($userIdStatus['id']);
                    if ($resTimes['total'] >= 2) {
                        $this->ret['message'] = '每个自然月内只能修改2次';
                        $this->ret['status'] = 41;
                        $this->apiCallback($this->ret);
                    }
                    $codeStatus = $model->checkCodePhone($phone, $code, 5); //验证码是否在有效果时间内

                    if (empty($codeStatus)) {
                        $this->ret['message'] = '验证码错误，请重新输入';
                        $this->ret['status'] = 14;
                        $this->apiCallback($this->ret);
                    }

                    $codeNewStatus = $model->checkCodePhone($newPhone, $newCode, 6); //验证码是否在有效果时间内
                    if ($userIdStatus && $codeStatus && $codeNewStatus) {
                        $handlepwdStatus = $model->checkHandlePwd($userId, $phone, $handlepwd); //验证兑换密码是否正确
                        if ($handlepwdStatus) {
                            $result = $model->updateUserPhone($userIdStatus['id'], $newPhone);
                            if ($result) {
                                $data['userId'] = $userIdStatus['id'];
                                $data['addTime'] = time();
                                $data['phone'] = $newPhone;

                                M('MembersBindLog')->data($data)->add();
                                $this->ret['status'] = 1;
                                $this->ret['message'] = '操作成功';
                            }
                        }
                    }
                } else {
                    $this->ret['status'] = 19;
                    $this->ret['message'] = '亲，该手机号码已绑定其他用户';
                }
            } else {
                $this->ret['status'] = 19;
                $this->ret['message'] = '亲，该手机号码已绑定其他用户';
            }
        }
        $this->apiCallback($this->ret);exit();
    }

    /**
     * 3.3会员主页接口
     */
    function peopleHome() {
        $userId = I("post.userId");
        $phone = I("post.phone");
        $friendId = I("post.friendId");
        $version = I("post.version");
        $this->ret['status'] = 10;
        $this->ret['message'] = '操作失败';
        if (is_empty($userId) || is_empty($phone) || is_empty($friendId)) {
            $this->ret['status'] = -888;
            $this->ret['message'] = '参数不完整';
        } else {
            $userIdStatus = D("Members")->checkUserId($phone, $userId); //验证用户唯一标识状态
//            var_dump($userIdStatus);die;
            if ($userIdStatus) {
                //echo $friendId.'-'.decodePass($friendId);die;
                $this->ret['info'] = D("Members")->getUserHome($userIdStatus['id'], $friendId);
                $this->ret['status'] = 1;
                $this->ret['message'] = '操作成功';
            }
        }
        $this->apiCallback($this->ret);exit();
    }
    
    
     /**
     * 3.2.4会员登录或者注册第一步
     * @access public
     * @return array/false
     */
    public function registerOrLogin() {
        //验证相关密钥
        //$this->checkKey();

        $ret['success'] = true;
        $phone = I('post.phone');
        $pwd = I('post.password');
        $imei = I('post.imei');

        if (is_empty($phone) || is_empty($pwd)) {//验证参数是否为空
            $ret['status'] = 29;
            $ret['message'] = '参数不完整';
        } else {
            //验证手机号码格式、长度
            preg_match('/1[0-9]{10}/', $phone, $matches);
            if ((strlen($phone) == '11') && $matches[0]) {//验证手机号码规则
                $model = D("Members");
                $field = 'id,name,jpush,phone,image,imageUrl,integral,cityId,provinceId,freeze,handlePassword,invite,type,password,encrypt,groupType,memberCodeUrl,signture';
                $res = $model->getUserDataByPhone($phone, $field);
                
                //获取邀请开关是否开启
                $system = D('system');
                $system_switch_status = $system->iosInviteStatus();
                ($system_switch_status == 1) ? ($ret['switchOn'] = 1) : ($ret['switchOn'] = 2);
                
                if ($res) {//判断手机号码是否注册
                    $userId = $res['id'];
                    $ret['isRegister'] = 2;

                    if ($res['freeze'] != 0) {//判断账号是否非法
                        $return['status'] = 33;
                        $return['message'] = '账号非法，暂时无法完成此操作';

                        echo jsonStr($return);
                        exit(0);
                    }
                    //手机号码当天是否受限
                    $resTimes = $model->getErrorPwdNum($phone);
                    //var_dump($resTimes);die;
                    if ($resTimes < 5) {
                        //验证密码是否正确,正确时返回会员信息，错误时入错误日志
                        if ($res['password'] == md5(md5($pwd . $res['encrypt']))) {

                            //更新用户唯一标识
                            $res['userId'] = $model->updateUniqueId($userId);
                            //更新登陆次数
                            $model->updateLoginNum($userId);
                            //获取返回参数
                            //loginCount：登录操作失败次数
                            $res['loginCount'] = $model->getErrorPwdNum($phone);
                            //isNewUser:是否是老用户，1：老用户，2：新用户
                            (($res['type'] == '3.0') or ($res['type'] == '3.1')) ? ($res['isNewUser'] = 1) : ($res['isNewUser'] = 2);
                            //switchOn：ios邀请开关,1:开启，2：关闭
                            $system = D('system');
                            $system_switch_status = $system->iosInviteStatus();
                            ($system_switch_status == 1) ? ($res['switchOn'] = 1) : ($res['switchOn'] = 2);
                            //操作密码状态
                            empty($res['handlePassword']) ? ($res['handlePwdStatus'] = 2) : ($res['handlePwdStatus'] = 1);
                            //会员状态
                            $res['userStatus'] = intval($res['freeze']) + 1;
                            //$res['id'] = encodePass($res['id']);//加密userId
                            $res['code'] = base64_encode($res['id']); //加密userId
                            //获取用户IP
                            $ip = $_SERVER['HTTP_X_REAL_IP'];
                            //如果获取不到说明没有走代理,通过普通方式获取IP
                            $ip = $ip ? $ip : $_SERVER['REMOTE_ADDR'];
                            unset($res['password']);
                            unset($res['handlePassword']);
                            unset($res['encrypt']);
                            //添加登陆日志表
                            $model->userLoginLog($phone, $imei, $ip);
                            if ($res['groupType'] == 2) {//1表示未认证 2 表示认证
                                $res['isAuthentication'] = 2;
                            } else {
                                $flag = $model->getMemberAuthentication($res['id']);
                                $res['isAuthentication'] = $flag ? 3 : 1;
                            }

                            $model->clearLoginNum($res['id'], $phone, 1);

                            $tmpJson['fb_type'] = 1;
                            $tmpJson['userId'] = encodePass($res['id']);

                            $val = urlencode(base64_encode(jsonStr($tmpJson)));
                            
                            if (empty($res['memberCodeUrl'])) {
                                $text = C('DOWNLOAD_ADDRESS');

                                $text .= '?' . $val; //二维码内信息
                                $nowDay=date("Y-m-d");
                                $file = 'Uploads/memberCode/'.$nowDay.'/';
                                
                                if(!is_dir($file)){//判断目录是否存在
                                    mkdir($file);
                                }
                                
                                $url = '/Uploads/memberCode/'.$nowDay.'/'; //存储地址
                                $urlLast = encodePass($res['id']) . time() . '.jpg';
                                $model->qrcode($text, ROOT . $url . $urlLast, 'H', '5');

                                $str = $url . $urlLast;
                                $model->updateMemberCodeUrl($res['id'], $str);
                                $res['memberCodeUrl'] = WEBURL . $str;
                            } else {
                                $res['memberCodeUrl'] = WEBURL . $res['memberCodeUrl'];
                            }

                            //$res['isAuthentication'] = $res['groupType'] < 2 ? 1 : 2; 
                            $ret['info'] = $res;
                            $ret['status'] = 1;
                            $ret['message'] = '成功';
                        } else {
                            //验证失败时，入日志库
                            $model->loginErrorLog($userId, $phone, $imei);
                            $resTimes = $model->getErrorPwdNum($phone);
                            //echo $resTimes;
                            if ($resTimes >= 5) {
                                $ret['status'] = 24;
                                //$ret['message'] = '你的密码错误尝试超限，请明天再试';
                                //$ret['message'] = '亲，您的操作太频繁了哦！请明天再试~！';
                                $ret['message'] = '额偶~ 密码错误已超限啦！';
                            } else {
                                $ret['status'] = 31;
                                //$ret['message'] = '密码输入错误，还剩' . (5 - $resTimes) . '次机会';
                                $ret['message'] = '亲，密码不对呦~！（' . (5 - $resTimes) . '）';
                            }
                        }
                    } else {
                        $ret['status'] = 24;
                        //$ret['message'] = '你的密码错误尝试超限，请明天再试';
                        //$ret['message'] = '亲，您的操作太频繁了哦！请明天再试~！';
                        $ret['message'] = '额偶~ 密码错误已超限啦！';
                    }
                } else {
                    $ret['status'] = 1;
                    $ret['message'] = '操作成功';
                    $ret['isRegister'] = 1;
                }
            } else {
                $ret['status'] = 8;
                $ret['message'] = '手机号未在地球上出现';
            }
        }
        $this->apiCallback($ret);exit();
    }
    

    
    /**
     * 3.2.4注册2-验证密码和短信验证码
     * @access public
     * @return true/false
     */
    public function register() {
        //验证相关密钥
        //$this->checkKey();
        
        $ret['success'] = true;
        $imei = I('post.imei');
        $phone = I('post.phone');
        $code = I('post.code');
        $mobileType = I('post.mobileType');
        $pwd = I('post.password');
        $inviteCode = trim(I('post.inviteCode'),' ');
        $cityId = I('post.cityId');
        $provinceId = I('post.provinceId');
        $jpush = I('post.jpush');
        
        //echo $phone.'-'.$code.'-'.$pwd.'-'.$mobileType.'-'.$imei;
        
        if (is_empty($phone) || is_empty($code) || is_empty($pwd) || is_empty($mobileType) ) {//判断参数是否为空
            $ret['status'] = 29;
            $ret['message'] = '参数不完整';
        } else {
            //验证手机号码格式、长度
            preg_match('/1[0-9]{10}/', $phone, $matches);
            if ((strlen($phone) == '11') && $matches[0]) {//验证手机号码规则
                //获取会员信息
                $model = D('Members');
                //查询手机号码是否已注册
                $res = $model->getUserDataByPhone($phone, 'id');
                if (empty($res)) {//判断会员是否已注册
                    $resCode = $model->checkCodePhone($phone, $code);
                    if ($resCode) {//判断验证码信息是否存在                                       
                        if($imei){//判断imei是否为空
                            $resImei = $model->getUserDataByImei($imei, 'id,addTime,password,freeze');

                            if ($resImei['id'] && empty($resImei['password']) && $resImei['freeze']=='0') {//进行编辑
                                $resultSave = $model->updateRegisterUserInfo($resImei['id'], $phone, $pwd, '',$cityId,$provinceId,$jpush);
                            } else {
                                //只存用户信息
                                $resultSave = $model->saveUserInfo($phone, $pwd, $imei, $mobileType, '',$cityId,$provinceId,$jpush);
                            }
                        }else{
                            $resultSave = $model->saveUserInfo($phone, $pwd, $imei, $mobileType, '',$cityId,$provinceId,$jpush);
                        }

                        if ($resultSave) {//判断操作是否成功
                            $field = 'id,phone,imageUrl,image,freeze,type,name,integral,invite,uniqueId as userId,cityId,provinceId,handlePassword,groupType,memberCodeUrl';
                            $result = $model->getUserDataByPhone($phone, $field);

                            // 邀请标识 1-未邀请，2-邀请失败，3-邀请成功
                            $inviteFlag=1;

                            //邀请处理
                            if(strlen($inviteCode)>0){
                                $inviteFlag=2;
                                //验证会员邀请次数是否受限
                                $resTimes = $model->getErrorByInviteNum($result['id']);
                                if ($resTimes > 5) {
                                    $ret['status'] = 16;
                                    $ret['message'] = '对不起，邀请码错误尝试已超限';
                                }else{
                                    //验证邀请码的唯一码是否有效
                                    $resImei = $model->checkInviteCode($inviteCode);
                                    //print_r($resImei);

                                    if (empty($resImei)) {//判断邀请码不存在
                                        //验证失败时，入日志库
                                        $model->userErrorLog($phone, $imei, $result['id'], '3');
                                        $resError = $model->getErrorByInviteNum($result['id']);
                                        if ($resError >= 5) {
                                            $ret['status'] = 16;
                                            $ret['message'] = '对不起，邀请码错误尝试已超限';
                                            $ret['getTimes'] = 0;
                                        } else {
                                            $ret['status'] = 15;
                                            $ret['message'] = '邀请码有误，请重新填写（' . (5 - $resError) . '）';
                                            $ret['getTimes'] = 5 - $resError;
                                        }
                                    }else{
                                        $oldUserId = $resImei['id'];
                                        if ($oldUserId == $result['id']) {
                                            //如果自己邀请自己,返回操作失败
                                            $this->ret['status'] = -14;
                                            $this->ret['message'] = '操作失败';
                                        } elseif ($oldUserId > $result['id']) {
                                            //如果邀请人注册时间晚于被邀请人,则返回失败
                                            $this->ret['status'] = -19;
                                            $this->ret['message'] = '操作失败';
                                        } else {
                                            if ($oldUserId && $result['id']) {

                                                $this->ret['info'] = '0';
                                                $nuser_flag = D('Members')->getUserFlag($result['id']);
                                                $user_flag = D('Members')->getUserFlag($oldUserId);
                                                //var_dump($user_flag);die;
                                                //当用户邀请自己的时候直接返回错误
                                                if ($oldUserId == $nuser_flag) {
                                                    $resultInvite = 5;
                                                } else {
                                                    $resultInvite = D('invite')->inviteSaveData($oldUserId, $user_flag, $result['id'], $nuser_flag);
                                                }
                                            }
                                            if ($resultInvite == 1) {
                                                $this->ret['status'] = -888;
                                                $this->ret['message'] = '传参不完整';
                                            }elseif($resultInvite == 2){
                                                //如果当前用户的终端类型为模拟器
                                                if ($user_flag == "2" && $nuser_flag == '1') {
                                                    $this->ret['status'] = -13000;
                                                } else {
                                                    $this->ret['status'] = 1;
                                                }
                                                $inviteFlag=3;
                                                $this->ret['message'] = '操作成功';
                                            }elseif($resultInvite == 3){
                                                $this->ret['status'] = -1;
                                                $this->ret['message'] = '操作失败';
                                            }elseif($resultInvite == 4){
                                                $this->ret['status'] = -13;
                                                $this->ret['message'] = '数据已存在';
                                            }elseif($resultInvite == 5){
                                                $this->ret['status'] = -10;
                                                $this->ret['message'] = '非法传参';
                                            }elseif ($resultInvite == 6) {
                                                $this->ret['status'] = -15;
                                                $this->ret['message'] = '操作频繁';
                                            }elseif($resultInvite == 7){
                                                $this->ret['status'] = -16;
                                                $this->ret['message'] = '邀请用户非正常状态';
                                            }elseif($resultInvite == 8){
                                                $this->ret['status'] = -17;
                                                $this->ret['message'] = '新用户非正常状态';
                                            }elseif($resultInvite == 9){
                                                $this->ret['status'] = -18;
                                                $this->ret['message'] = '已被对方邀请,无法互相邀请!';
                                            }
                                        }

                                        //生成记录
                                        D('invite')->recordOperatingLog(array("uid" => $oldUserId, 'nuid' => $result['id'], "remark" => $ret['message'] . ",result:" . $result . ",status:" . $ret['status'])); 
                                    }
                                }
                            }
                            
                            $result = $model->getUserDataByPhone($phone, $field);
                            $result['code'] = base64_encode($result['id']); //加密userId

                            $text = C('DOWNLOAD_ADDRESS');
                            $tmpJson['fb_type'] = 1;
                            $tmpJson['userId'] = encodePass($result['id']);

                            $val = urlencode(base64_encode(jsonStr($tmpJson)));
                            $text .= '?' . $val; //二维码内信息

                            //获取年月日
                            //$nowDay=date("Y-m-d");
                            $nowDay=date("Y-m-d");
                            $file = 'Uploads/memberCode/'.$nowDay.'/';

                            if(!is_dir($file)){//判断目录是否存在
                                mkdir($file);
                            }
                            $url = 'Uploads/memberCode/'.$nowDay.'/'; //存储地址

                            $urlLast = encodePass($result['id']) . time() . '.jpg';
                            $model->qrcode($text, ROOT .'/'. $url . $urlLast, 'H', '5');

                            $str = $url . $urlLast;
                            $model->updateMemberCodeUrl($result['id'], $str);
                            $result['memberCodeUrl'] = WEBURL .'/'. $str;


                            //判断邀请开关是否开启 iosInviteStatus：ios邀请开关,1:开启，2：关闭
                            $system = D('system');
                            $system_switch_status = $system->iosInviteStatus();
                            ($system_switch_status == 1) ? ($result['switchOn'] = 1) : ($result['switchOn'] = 2);
                            //判断是否是老用户
                            $result['isNewUser'] = 2;
                            if (in_array($result['type'], array('3.0', '3.1'))) {
                                $result['isNewUser'] = 1;
                            }
                            //判断用户状态
                            if ($result['freeze'] == '0') {
                                $result['userStatus'] = 1;
                            } elseif ($result['freeze'] == '1') {
                                $result['userStatus'] = 2;
                            } elseif ($result['freeze'] == '2') {
                                $result['userStatus'] = 3;
                            }
                            //判断操作密码状态
                            $result['handlePwdStatus'] = 2;
                            if ($result['handlePassword']) {
                                $result['handlePwdStatus'] = 1;
                            }


                            unset($result['handlePassword']);
                            unset($result['type']);
                            unset($result['freeze']);


                            if ($result['groupType'] == 2) {//1表示未认证 2 表示认证
                                $result['isAuthentication'] = 2;
                            } else {
                                $flag = $model->getMemberAuthentication($res['id']);
                                $result['isAuthentication'] = $flag ? 3 : 1;
                            }

                            $ret['info'] = $result;
                            $ret['status'] = 1;
                            if($inviteFlag==3){
                                $ret['message'] = '成功邀请并注册成功！';
                            }elseif($inviteFlag==2){
                                $ret['message'] = '注册成功, 邀请码'.$inviteCode.'无效！';
                            }else{
                                $ret['message'] = '恭喜您，注册成功！';
                            }

                        } else {
                            $ret['status'] = 10;
                            $ret['message'] = '操作失败';
                        }
                    } else {
                        //验证失败时，入日志库
                        $model->userErrorLog($phone, $imei, '', '2');
                        $ret['status'] = 14;
                        $ret['message'] = '验证码错误，请重新输入';
                    }
                } else {
                    $ret['status'] = 9;
                    $ret['message'] = '亲，你的手机号已注册，请直接登录';
                }
            }else{
                $ret['status'] = 8;
                $ret['message'] = '手机号未在地球上出现';
            }
            

        }
        $this->apiCallback($ret);exit();
    }
    
    
    /*
     * 3.3.1合并注册
     * 
     */
    public function registerMergeVersion(){
        //验证相关密钥
        //$this->checkKey();
        
        $ret['success'] = true;
        $imei = I('post.imei');
        $phone = I('post.phone');
        $code = I('post.code');
        $mobileType = I('post.mobileType');
        $pwd = I('post.password');
        $inviteCode = trim(I('post.inviteCode'),' ');
        $cityId = I('post.cityId');
        $provinceId = I('post.provinceId');
        $jpush = I('post.jpush');
        $type = I('post.registerType');
        
        //备注：注册时 分两种验证，1-邀请验证、不进行注册，2-注册验证
        if ( is_empty($phone)) {//判断手机号码是否为空
            $ret['status'] = 29;
            $ret['message'] = '参数不完整';
            $this->apiCallback($ret);exit();
        }
        
        if ( is_empty($code)) {//判断短信验证码是否为空
            $ret['status'] = 29;
            $ret['message'] = '参数不完整';
            $this->apiCallback($ret);exit();
        }
        
        if ( is_empty($pwd)  ) {//判断判断密码是否为空
            $ret['status'] = 29;
            $ret['message'] = '参数不完整';
            $this->apiCallback($ret);exit();
        }
        
        if ( is_empty($mobileType) ) {//判断注册手机类型是否为空
            $ret['status'] = 29;
            $ret['message'] = '参数不完整';
            $this->apiCallback($ret);exit();
        }
        
        if (is_empty($type) ) {//判断注册类型是否为空
            $ret['status'] = 29;
            $ret['message'] = '参数不完整';
            $this->apiCallback($ret);exit();
        }
        
        if($type==1 or $type==2){
            
        }else{
            $ret['status'] = 29;
            $ret['message'] = '参数不完整';
            $this->apiCallback($ret);exit();
        }
        
        //验证手机号码格式、长度
        preg_match('/1[0-9]{10}/', $phone, $matches);
        
        if ((strlen($phone) == '11') && $matches[0]) {//验证手机号码规则
            
        }else{
            $ret['status'] = 8;
            $ret['message'] = '手机格式有些逆天啊';
            $this->apiCallback($ret);exit();
        }
        
        $model = D('Members');
        //查询手机号码是否已注册
        $res = $model->getUserDataByPhone($phone, 'id');
        if ($res) {//判断会员是否已注册
            $ret['status'] = 9;
            $ret['message'] = '手机号已注册了哦';
            $this->apiCallback($ret);exit();
        }
        
        //判断验证码信息是否存在  
        $resCode = $model->checkCodePhone($phone, $code);
        //$resCode =1;
        if(empty($resCode)){
            //验证失败时，入日志库
            $model->userErrorLog($phone, $imei, '', '2');
            $ret['status'] = 9;
            $ret['message'] = '亲，验证码不对哦';
            $this->apiCallback($ret);exit();
        }
        
        
        
        
        if($type==1){
            
            if($inviteCode){//验证邀请码是否存在
                
                $resImei = $model->checkInviteCode($inviteCode);
                if(empty($resImei)){//验证邀请码错误
                    $ret['message'] = '额，邀请码无效~';
                    //验证到此结束
                    $ret['status'] = 11;
                    $this->apiCallback($ret);exit();
                }
                
            }else{
                //验证到此结束
                $ret['status'] = 11;
                $ret['message'] = '没有邀请码没有奖励哦';
                $this->apiCallback($ret);exit();
            }
        }
        
        if($imei){//判断imei是否为空
            $resImei = $model->getUserDataByImei($imei, 'id,addTime,password,freeze');

            if ($resImei['id'] && empty($resImei['password']) && $resImei['freeze']=='0') {//进行编辑
                $resultSave = $model->updateRegisterUserInfo($resImei['id'], $phone, $pwd, '',$cityId,$provinceId,$jpush);
            } else {
                //只存用户信息
                $resultSave = $model->saveUserInfo($phone, $pwd, $imei, $mobileType, '',$cityId,$provinceId,$jpush);
            }
        }else{
            $resultSave = $model->saveUserInfo($phone, $pwd, $imei, $mobileType, '',$cityId,$provinceId,$jpush);
        }
        
        
        //判断会员是否注册成功
        // 邀请标识 1-未邀请，2-邀请失败，3-邀请成功
        $inviteFlag=1;
        if ($resultSave) {
            
            //注册-验证邀请码
            if($inviteCode){
                $inviteFlag=2;
                
                $resImei = $model->checkInviteCode($inviteCode);
                if(empty($resImei)){
                    //验证失败时，入日志库
                    $model->userErrorLog($phone, $imei, $result['id'], '3');
                    
                }else{
                    $oldUserId = $resImei['id'];
                    if ($oldUserId == $result['id']) {
                        //$inviteFlag=2;
                    }elseif ($oldUserId > $result['id']) {
                        //$inviteFlag=2;
                    }else{
                        if ($oldUserId && $result['id']) {

                            $this->ret['info'] = '0';
                            $nuser_flag = D('Members')->getUserFlag($result['id']);
                            $user_flag = D('Members')->getUserFlag($oldUserId);
                            //var_dump($user_flag);die;
                            //当用户邀请自己的时候直接返回错误
                            if ($oldUserId == $nuser_flag) {
                                $resultInvite = 5;
                            } else {
                                $resultInvite = D('invite')->inviteSaveData($oldUserId, $user_flag, $result['id'], $nuser_flag);
                            }
                        }
                        if ($resultInvite == 1) {
                            $this->ret['status'] = -888;
                            $this->ret['message'] = '传参不完整';
                        }elseif($resultInvite == 2){
                            //如果当前用户的终端类型为模拟器
                            if ($user_flag == "2" && $nuser_flag == '1') {
                                $this->ret['status'] = -13000;
                            } else {
                                $this->ret['status'] = 1;
                            }
                            $inviteFlag=3;
                            $this->ret['message'] = '操作成功';
                        }elseif($resultInvite == 3){
                            $this->ret['status'] = -1;
                            $this->ret['message'] = '操作失败';
                        }elseif($resultInvite == 4){
                            $this->ret['status'] = -13;
                            $this->ret['message'] = '数据已存在';
                        }elseif($resultInvite == 5){
                            $this->ret['status'] = -10;
                            $this->ret['message'] = '非法传参';
                        }elseif ($resultInvite == 6) {
                            $this->ret['status'] = -15;
                            $this->ret['message'] = '操作频繁';
                        }elseif($resultInvite == 7){
                            $this->ret['status'] = -16;
                            $this->ret['message'] = '邀请用户非正常状态';
                        }elseif($resultInvite == 8){
                            $this->ret['status'] = -17;
                            $this->ret['message'] = '新用户非正常状态';
                        }elseif($resultInvite == 9){
                            $this->ret['status'] = -18;
                            $this->ret['message'] = '已被对方邀请,无法互相邀请!';
                        }
                    }

                    //生成记录
                    if($inviteFlag==3){
                        //echo 'yaoqing';
                        D('invite')->recordOperatingLog(array("uid" => $oldUserId, 'nuid' => $result['id'], "remark" => $ret['message'] . ",result:" . $result . ",status:" . $ret['status'])); 
                    }
                }
            }else{
                //无邀请注册
                $inviteFlag=1;
            }
            
            $field = 'id,phone,imageUrl,image,freeze,type,name,integral,invite,uniqueId as userId,cityId,provinceId,handlePassword,groupType,memberCodeUrl';
            $result = $model->getUserDataByPhone($phone, $field);
            $result['code'] = base64_encode($result['id']); //加密userId

            $text = C('DOWNLOAD_ADDRESS');
            $tmpJson['fb_type'] = 1;
            $tmpJson['userId'] = encodePass($result['id']);

            $val = urlencode(base64_encode(jsonStr($tmpJson)));
            $text .= '?' . $val; //二维码内信息

            //获取年月日
            //$nowDay=date("Y-m-d");
            $nowDay=date("Y-m-d");
            $file = 'Uploads/memberCode/'.$nowDay.'/';

            if(!is_dir($file)){//判断目录是否存在
                mkdir($file);
            }
            $url = 'Uploads/memberCode/'.$nowDay.'/'; //存储地址

            $urlLast = encodePass($result['id']) . time() . '.jpg';
            $model->qrcode($text, ROOT .'/'. $url . $urlLast, 'H', '5');

            $str = $url . $urlLast;
            $model->updateMemberCodeUrl($result['id'], $str);
            $result['memberCodeUrl'] = WEBURL .'/'. $str;


            //判断邀请开关是否开启 iosInviteStatus：ios邀请开关,1:开启，2：关闭
            $system = D('system');
            $system_switch_status = $system->iosInviteStatus();
            ($system_switch_status == 1) ? ($result['switchOn'] = 1) : ($result['switchOn'] = 2);
            //判断是否是老用户
            $result['isNewUser'] = 2;
            if (in_array($result['type'], array('3.0', '3.1'))) {
                $result['isNewUser'] = 1;
            }
            //判断用户状态
            if ($result['freeze'] == '0') {
                $result['userStatus'] = 1;
            } elseif ($result['freeze'] == '1') {
                $result['userStatus'] = 2;
            } elseif ($result['freeze'] == '2') {
                $result['userStatus'] = 3;
            }
            
            //判断操作密码状态
            $result['handlePwdStatus'] = 2;
            if ($result['handlePassword']) {
                $result['handlePwdStatus'] = 1;
            }

            unset($result['handlePassword']);
            unset($result['type']);
            unset($result['freeze']);

            if ($result['groupType'] == 2) {//1表示未认证 2 表示认证
                $result['isAuthentication'] = 2;
            } else {
                $flag = $model->getMemberAuthentication($res['id']);
                $result['isAuthentication'] = $flag ? 3 : 1;
            }

            $ret['info'] = $result;
            $ret['status'] = 1;
            $ret['message'] = '恭喜您，注册成功！';
            
//            if($inviteFlag==3){
//                $ret['message'] = '成功邀请并注册成功！';
//            }elseif($inviteFlag==2){
//                $ret['message'] = '注册成功, 邀请码'.$inviteCode.'无效！';
//            }else{
//                $ret['message'] = '恭喜您，注册成功！';
//            }
            
        }else{
            $ret['status'] = 10;
            $ret['message'] = '操作失败';
        }
        
        $this->apiCallback($ret);exit();
    }
    
    
    
    /*
     * 3.3.1登录
     */
    public function memberLoginVersion(){
        //验证相关密钥
        //$this->checkKey();

        $ret['success'] = true;
        $phone = I('post.phone');
        $pwd = I('post.password');
        $imei = I('post.imei');
        
        if(is_empty($phone)){
            $ret['status'] = 29;
            $ret['message'] = '手机号码不可为空';
            $this->apiCallback($ret);exit();
        }
        //var_dump(I('post.password'));
        if(is_empty($pwd)){
            $ret['status'] = 29;
            $ret['message'] = '亲，密码不能为空哦！';
            $this->apiCallback($ret);exit();
        }
        
        //验证手机号码格式、长度
        preg_match('/1[0-9]{10}/', $phone, $matches);
        if ((strlen($phone) == '11') && $matches[0]) {//验证手机号码规则
            //不在此进行判断
        }else{
            $ret['status'] = 29;
            $ret['message'] = '手机号格式有些逆天啊';
            $this->apiCallback($ret);exit();
        }
        
        //获取会员信息
        $model = D("Members");
        $field = 'id,name,jpush,phone,image,imageUrl,integral,cityId,provinceId,freeze,handlePassword,invite,type,password,encrypt,groupType,memberCodeUrl,signture';
        $res = $model->getUserDataByPhone($phone, $field);
        //var_dump($res);die;
        if(empty($res)){
            $ret['status'] = 29;
            $ret['message'] = '亲，手机号尚未注册呢！';
            $this->apiCallback($ret);exit(); 
        }
        
        $userId = $res['id'];
        $ret['isRegister'] = 2;
        
        //此处的判断建议保留
        if ($res['freeze'] != 0) {//判断账号是否非法
            $return['status'] = 33;
            $return['message'] = '账号非法，暂时无法完成此操作';

            echo jsonStr($return);exit(0);
        }
        
        //手机号码当天是否受限
        $resTimes = $model->getErrorPwdNum($phone);
        if ($resTimes < 5) {
            
        } else {
            $ret['status'] = 24;
            $ret['message'] = '您的密码错误尝试次数超限，请明天再试！';
            $this->apiCallback($ret);exit();
        }
        
        //验证密码是否正确,正确时返回会员信息，错误时入错误日志
        if ($res['password'] == md5(md5($pwd . $res['encrypt']))) {

            //更新用户唯一标识
            $res['userId'] = $model->updateUniqueId($userId);
            //更新登陆次数
            $model->updateLoginNum($userId);
            //获取返回参数
            //loginCount：登录操作失败次数
            $res['loginCount'] = $model->getErrorPwdNum($phone);
            //isNewUser:是否是老用户，1：老用户，2：新用户
            (($res['type'] == '3.0') or ($res['type'] == '3.1')) ? ($res['isNewUser'] = 1) : ($res['isNewUser'] = 2);
            //switchOn：ios邀请开关,1:开启，2：关闭
            $system = D('system');
            $system_switch_status = $system->iosInviteStatus();
            ($system_switch_status == 1) ? ($res['switchOn'] = 1) : ($res['switchOn'] = 2);
            //操作密码状态
            empty($res['handlePassword']) ? ($res['handlePwdStatus'] = 2) : ($res['handlePwdStatus'] = 1);
            //会员状态
            $res['userStatus'] = intval($res['freeze']) + 1;
            //$res['id'] = encodePass($res['id']);//加密userId
            $res['code'] = base64_encode($res['id']); //加密userId
            //获取用户IP
            $ip = $_SERVER['HTTP_X_REAL_IP'];
            //如果获取不到说明没有走代理,通过普通方式获取IP
            $ip = $ip ? $ip : $_SERVER['REMOTE_ADDR'];
            unset($res['password']);
            unset($res['handlePassword']);
            unset($res['encrypt']);

            //添加登陆日志表
            $model->userLoginLog($phone, $imei, $ip);
            if ($res['groupType'] == 2) {//1表示未认证 2 表示认证
                $res['isAuthentication'] = 2;
            } else {
                $flag = $model->getMemberAuthentication($res['id']);
                $res['isAuthentication'] = $flag ? 3 : 1;
            }

            $model->clearLoginNum($res['id'], $phone, 1);

            $tmpJson['fb_type'] = 1;
            $tmpJson['userId'] = encodePass($res['id']);

            $val = urlencode(base64_encode(jsonStr($tmpJson)));

            if (empty($res['memberCodeUrl'])) {
                $text = C('DOWNLOAD_ADDRESS');

                $text .= '?' . $val; //二维码内信息
                $nowDay=date("Y-m-d");
                $file = 'Uploads/memberCode/'.$nowDay.'/';

                if(!is_dir($file)){//判断目录是否存在
                    mkdir($file);
                }

                $url = '/Uploads/memberCode/'.$nowDay.'/'; //存储地址
                $urlLast = encodePass($res['id']) . time() . '.jpg';
                $model->qrcode($text, ROOT . $url . $urlLast, 'H', '5');

                $str = $url . $urlLast;
                $model->updateMemberCodeUrl($res['id'], $str);
                $res['memberCodeUrl'] = WEBURL . $str;
            } else {
                $res['memberCodeUrl'] = WEBURL . $res['memberCodeUrl'];
            }

            $ret['info'] = $res;
            $ret['status'] = 1;
            $ret['message'] = '登录成功';
        } else {
            //验证失败时，入日志库
            $model->loginErrorLog($userId, $phone, $imei);
            $resTimes = $model->getErrorPwdNum($phone);

            if ($resTimes >= 5) {
                $ret['status'] = 24;
                $ret['message'] = '您的密码错误尝试次数超限，请明天再试！';
            } else {
                $ret['status'] = 31;
                $ret['message'] = '亲，密码不对呦~！（' . (5 - $resTimes) . '）';
            }
        }
        
        $this->apiCallback($ret);exit();
    }
    

    
     /**
     * 3.2.4注册4-设置操作密码
     * @access public
     * @return true/false
     */
    public function setHandlePassword() {
        //验证相关密钥
        //$this->checkKey();

        $ret['success'] = true;
        $imei = I('post.imei');
        $phone = I('post.phone');
        $userId = I('post.userId'); //唯一码
        $handlePassword = I('post.handlePwd');

        if (is_empty($phone) || is_empty($userId) || is_empty($handlePassword)) {//判断参数是否为空
            $ret['status'] = 29;
            $ret['message'] = '参数不完整';
        } else {
            $ret['handlePwdStatus'] = 2; //操作密码状态
            $model = D("Members");
            
            //判断帐号是否存在
            $resmember = $model->checkUserId($phone, $userId, 'id,addTime,encrypt,handlePassword');
            if ($resmember) {//判断帐号是否存在
                if($resmember['handlePassword']){
                    $ret['status'] = 10;
                    $ret['message'] = '兑换码已设置，不可重复提交';
                }else{
                    $userId = $this->userId;
                    //$id = $resmember['id'];

                    //修改帐号信息，重置操作密码状态
                    $id = $model->updateUserHandlePwd($userId, $handlePassword . $resmember['encrypt']);
                    if ($id) {//判断添加是否成功
                        $ret['status'] = 1;
                        $ret['message'] = '设置成功';
                        $ret['handlePwdStatus'] = 1; //操作密码状态
                    } else {
                        $ret['status'] = 10;
                        $ret['message'] = '操作失败';
                    }
                }
            } else {
                $ret['status'] = 10;
                $ret['message'] = '操作失败';
            }
        }
        $this->apiCallback($ret);exit();
    }
    
    
    
}
