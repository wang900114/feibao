<?php

use Think\Controller;

class AppPackageController extends BaseController {
    
    /**
     * 控制器初始化
     * @access public
     * @author wangping <wangping@feibaokeji.com>
     */
    public function _initialize() {
        parent::_initialize();
//        $_POST['userId'] = 593425;
//        $_POST['phone'] = 18910149105;
        //自动处理IP相关的限制
        $check_m = D('Check');
        $ACTION_NAME = strtolower(ACTION_NAME);
        if (in_array($ACTION_NAME, array('getswitch'))){
            
        }else{
            
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
                //$res = $model->checkUserId($phone, $userId, 'id,freeze');

                if (empty($res['id'])) {
                    $return['status'] = 35;
                    $return['message'] = '账号异常，已退出登录！ ';
                    $return['info'] = array();
                    echo jsonStr($return);
                    exit(0);
                } else {
                    if ($res['freeze'] != '0') {//验证账号是否非法
                        $return['status'] = 33;
                        $return['message'] = '账号非法，暂时无法完成此操作';
                        $return['info'] = array();
                        echo jsonStr($return);
                        exit(0);
                    } else {
                        if ($res['id'] == 44427) {
                            $return['status'] = 32;
                            $return['message'] = '请到个人中心登录';
                            $return['info'] = array();
                            echo jsonStr($return);
                            exit(0);
                        }
                        $this->userId = $res['id'];
                    }
                }
            } else {

                $return['message'] = '操作失败';
                $return['status'] = 10;
                $return['info'] = array();
                echo jsonStr($return);
                exit(0);
            }
        }

    }
    
    /*
     * 3.2.5通用-获取会员钱包余额
     */
    public function taskStart()
    {
        $userId = I('post.userId');
        $appId = I('post.appId');
        $type = I('post.type');
        D('AppPackage')->addAppUseLog($userId,$appId,$type);
        
    }
    
    
    /*
     * 3.2.5通用-获取会员钱包余额
     */
    function getUserMoney(){
        $return['success'] = true;
        $return['status'] = 10;
        $return['message'] = '操作失败';
        $userId = $this->userId;
        if($userId){
            $res = D('Members')->getUserInfo($userId);
            if($res){
                $return['money'] = $res['money'];
                if($res['ali_number']){//判断阿里账号状态
                    $return['ali_number'] = str_replace(substr($res['ali_number'], 3, -4), '****', $res['ali_number']);
                    $return['ali_user_name'] = $res['ali_user_name'];
                    $return['aliIsSet'] = '1';
                    
                }else{
                    $return['aliIsSet'] = '2';
                    $return['ali_number'] = '';
                    $return['ali_user_name'] = '';
                }
                $return['app_withdrawals_10_on'] = D("System")->readConfig('switch_app_withdrawals_10_on');
                $return['app_withdrawals_30_on'] = D("System")->readConfig('switch_app_withdrawals_30_on');
                $return['app_withdrawals_50_on'] = D("System")->readConfig('switch_app_withdrawals_50_on');
                $todayTime_s = strtotime(date('Ymd', time()));
                $todayTime_d = $todayTime_s + 86399;
                $sql = "select count(*) as count from lu_app_withdrawals_order where userId = {$userId} and addTime >= $todayTime_s and addTime <= $todayTime_d";
                $is_withdrawals = M()->query($sql);
                $return['is_withdrawals'] = $is_withdrawals[0]['count'];
                $return['integral'] = $res['integral'];
                
                $return['message'] = '获取成功';
                $return['status'] = 1;
            }else
            {
                $return['message'] = '操作失败';
                $return['status'] = 10;
            }
        }
        
        $this->apiCallback($return);exit(0);
    }
    
    /*
     * 3.2.5通用-添加提现订单
     */
    function addWithdrawalsOrder(){
        $return['success'] = true;
        $return['status'] = 10;
        $return['message'] = '操作失败';
        $userId = $this->userId;
        $money = I('post.money');
//        $data = array();
//        $data['addTime'] = $userId;
//        $data['content'] = '金额：' . $money;
//        M('WeixinTest')->data($data)->add();
        if($userId && $money){//判断参数是否为空
            $getUserRole = D('AppPackage')->getUserRole($userId);
            //var_dump($getUserRole);die;
            //判断金额是否有效
            $app_withdrawals_10_on = D("System")->readConfig('switch_app_withdrawals_10_on');
            $app_withdrawals_30_on = D("System")->readConfig('switch_app_withdrawals_30_on');
            $app_withdrawals_50_on = D("System")->readConfig('switch_app_withdrawals_50_on');
            //echo $app_withdrawals_10_on.'-'.$app_withdrawals_30_on.'-'.$app_withdrawals_50_on;die;
            if($app_withdrawals_10_on ==$money || $app_withdrawals_30_on==$money || $app_withdrawals_50_on== $money){
                //var_dump($getUserRole);die;
                if($getUserRole){//判断当天是否已经体现
                    $return['message'] = '您当天已经提现，请明天再试';
                }else{
                    $res = D('Members')->getUserInfo($userId);
                    if($res['money'] >= $money){//判断金额是否充足
                        if($res['ali_number']){//判断账号是否已经绑定
                            //echo 1;die;
                            $result = D('AppPackage')->addOrder($userId,$money,$res['ali_number'],$res['ali_user_name']);
                            if($result){
                                //对会员积分进行较少金额处理
                                $moneyN = $res['money'] - $money;
                                $save_sql = "update lu_members set `money` = {$moneyN} where `id` = {$userId}";
                                $reuslts = M()->execute($save_sql);
//                                $reuslts = M('Members')->where("Id={$userId}")->setDec('money', $money);
                                if($reuslts){
                                    $return['status'] = 1;
                                    $return['message'] = '恭喜您，提现成功';
                                }
                            }
                        }else{
                            $return['message'] = '亲，请先绑定账号';
                        }
                    }else{
                        $return['message'] = '亲，您的余额不足';
                    }
                    
                }
            }
        }
        $this->apiCallback($return);exit(0);
    }
    
    
     /**
     * 3.2.5通用-获取我的提现列表
     */
    function getOrderList() {
        $return['success'] = true;
        $return['status'] = 10;

        //$type = I('post.type');
        $userId = $this->userId;
        
        $pageSize = I('post.pageSize', 10);
        $page = I('post.page', 1);
        $selectTime = I("post.selectTime", time());
        $selectTime = empty($selectTime) ? time() : $selectTime;
        $reList = array();
        if (is_empty($userId) || is_empty($pageSize) || is_empty($page) || is_empty($selectTime)) {
            $return['status'] = 10;
            $return['message'] = '操作失败';
        } else {
            
            
            //获取app下载包-正常数据
            $reList = D('AppPackage')->getOrderList($userId,$selectTime, $page, $pageSize);
            
            if (empty($reList)) {
                $return['info'] = array();
                $return['status'] = 36;
                $return['message'] = '查询成功，暂无数据';
            } else {
                foreach ($reList as $key => $value) {
                    $reList[$key]['id'] = encodePass($value['id']);
                    $reList[$key]['status'] = $value['status'];
                    $reList[$key]['money'] = $value['money'];
                    $reList[$key]['addTime'] = $value['addTime'];
                    
                }
                $return['info'] = $reList;
                $return['status'] = 1;
                $return['selectTime'] = $selectTime;
                $return['message'] = '查询成功';
            }
        }
        $this->apiCallback($return);exit(0);
    }
    
    /*
     * 3.2.5通用-会员绑定支付宝
     */
    function bingAlipay(){
        $return['success'] = true;
        $return['status'] = 10;
        $return['message'] = '操作失败';
        $userId = $this->userId;
        $AlipayNumber = trim (I('post.AlipayNumber'));
        $AlipayUserName = trim (I('post.AlipayUserName'));
        //echo $AlipayNumber.'-'.$userId;die;
        
        if($AlipayNumber && $userId && $AlipayUserName){
            $nowTime = time();
            $save_sql = "INSERT INTO `lu_members_ali_number_log` (`user_id`,`ali_number`,`add_time`) VALUES({$userId},'"
            . "{$AlipayNumber}',{$nowTime})";
            M('members_ali_number_log')->execute($save_sql);
            //先查询是否绑定
            $res = D('Members')->getUserInfo($userId);
            
            if($res['ali_number']){
                $return['status'] = 10;
                $return['message'] = '亲，账号已绑定！';
            }else{
//                $data['ali_number'] = $AlipayNumber;
//                $data['ali_user_name'] = $AlipayUserName;
//                
//                $where = 'id ='.$userId;
//                $result = M('Members')->where($where)->data($data)->save();
                //echo M('Members')->getLastSql();die;
                $re = M('Members')->field('id')->where("ali_number='{$AlipayNumber}'")->find();
                if($re){
                    $return['status'] = 10;
                    $return['message'] = '该支付宝帐号已被绑定！';
                }else{
                    $save_sql = "update lu_members set `ali_number` = '{$AlipayNumber}', `ali_user_name` = '{$AlipayUserName}' where `id` = {$userId}";
                    $result = M()->execute($save_sql);
                    //修改绑定返回状态        
                    if($result){
                        $return['status'] = 1;
                        $return['message'] = '绑定成功';
                    }
                }
            }
        }
        
        $this->apiCallback($return);exit(0);
    }
    
    /*
     * 3.2.5通用-获取下载页面开关
     */
    function getSwitch(){
        
        $return['success'] = true;
        $return['status'] = 10;
        //$userId = $this->userId;
        $type = I('post.type');
//        $type =1;
        //if (is_empty($type) || is_empty($userId)) {
        if (is_empty($type)) {
            //$return['status'] = 10;
            $return['message'] = '操作失败';
        } else {
            if($type==1){
                $user_app_switch = M('Config')->field('value')->where("`key`='switch_app_android_download_on'")->find();
//                $user_app_switch = D("System")->readConfig('switch_app_android_download_on');
                $return['status'] = 1;
                $return['message'] = '查询成功';
                $return['user_app_switch']= $user_app_switch['value'];
            }elseif($type==2){
                $user_app_switch = D("System")->readConfig('switch_app_ios_download_on');
                $return['status'] = 1;
                $return['message'] = '查询成功';
                $return['user_app_switch']= $user_app_switch['value'];
            }else{
                //$return['status'] = 10;
                $return['message'] = '操作失败';
            }
        }
        
        $this->apiCallback($return);exit(0);
    }

    /**
     * 3.2.5获取下载包列表
     */
    function getList() {
        $return['success'] = true;
        $return['status'] = 10;

        $type = I('post.type');
        $userId = $this->userId;
//        $userId = 95328;
        //$pageSize = I('post.pageSize', 10);
        //$page = I('post.page', 1);
        //$selectTime = I("post.selectTime", time());
        //$selectTime = empty($selectTime) ? time() : $selectTime;
        $reList = array();
        //if (is_empty($userId) || is_empty($pageSize) || is_empty($page) || is_empty($selectTime)) {
        if (is_empty($userId)) {
            $return['status'] = 10;
            $return['message'] = '操作失败';
        } else {
            
            //获取会员钱包金额
            $res = D('Members')->getUserInfo($userId);
            if($res){
                $return['money'] = $res['money'];
            }
            
            //获取会员获取今日的金额
            $return['dayMoney'] = D('AppPackage')->getUserTodayMoney($userId);
            if(!$return['dayMoney']){
                $return['dayMoney'] = '0';
            }
            //包类型 2-ios，1-android
            if($type==2){
                
            }else{
                $type =1;
            }
            /*
            //获取app下载包-已结束的数据
            $reslists = D('AppPackage')->getListEnd($type);
            //var_dump($reslists);die;
            if($reslists){
                //var_dump($reslists);die;
                foreach ($reslists as $k => $v) {
                    $reslists[$k]['id'] = encodePass($v['id']);
                    $reslists[$k]['title'] = base64_encode(jsonStrWithOutBadWordsNew($v['title'], 2));
                    $reslists[$k]['subtitle'] = base64_encode(jsonStrWithOutBadWordsNew($v['subtitle'], 2));
                    $reslists[$k]['introduce'] = base64_encode(jsonStrWithOutBadWordsNew($v['introduce'], 2));
                    $reslists[$k]['package_file_size'] = sprintf('%.2f',(($v['package_file_size'] / 1024 / 1024 ) * 100 + 1 ) / 100);
                }
            }
            if(empty($reslists)){
                $return['data'] = array();
            }  else {
                $return['data'] = $reslists;
            }
            */
            //获取app下载包-正常数据
            //$reList = D('AppPackage')->getListData($type,$selectTime, $page, $pageSize);
            $reList = D('AppPackage')->getListData($type);
            
            if (empty($reList)) {
                $return['status'] = 36;
                $return['message'] = '查询成功，暂无数据';
            } else {
                $timeName = $this->getTimeName();
                $nowTime = time();
                $reLists = array();
                $i = 0;
                foreach ($reList as $key => $value) {
                          
                    $use_num = $this->getRemainingNum($value['id'], $timeName, $value['time_numbers'], $value['package_tasks_numbers']);
                    if($use_num == 0){  //当剩余数量为0时，根据留存率判断是否显示
                        if(!empty($value['endTime'])){
                            if($nowTime > $value['endTime']){
//                                unset($reList[$key]);
                                continue;
                            }
                        }
                    }
//                    $reList[$i]['package_tasks_numbers'] =  $value['time_numbers'];
                    $reLists[$i] = $value;
                    $reLists[$i]['package_tasks_numbers'] =  $use_num;
                    $reLists[$i]['id'] = encodePass($value['id']);
                    $reLists[$i]['title'] = base64_encode(jsonStrWithOutBadWordsNew($value['title'], 2));
                    $reLists[$i]['subtitle'] = base64_encode(jsonStrWithOutBadWordsNew($value['subtitle'], 2));
                    $reLists[$i]['introduce'] = base64_encode(jsonStrWithOutBadWordsNew($value['introduce'], 2));
                    $reLists[$i]['true_size'] = $value['package_file_size'];
                    $reLists[$i]['package_file_size'] = sprintf('%.2f',(($value['package_file_size'] / 1024 / 1024 ) * 100 + 1 ) / 100);
                    $reLists[$i]['package_unit_price'] = $value['user_get_money'];
                    $i++;
                      
                }
                $return['info'] = $reLists;
                $return['status'] = 1;
                $return['selectTime'] = $selectTime;
                $return['message'] = '查询成功';
            }
        }
//        var_export($return);
        $this->apiCallback($return);exit(0);
    }
    
    /*
     * 获取app下载包详情
     */
    function getDetail(){
        $return['success'] = true;
        $return['status'] = 10;
        $return['message'] = '操作失败';
        $id = I('post.appId');
        $type = I('post.type');
        $userId = $this->userId;
        $id = decodePass($id);
        if (is_numeric($id) && in_array($type,array(1,2)) ) {
            if($type == 1){
                $res = D('AppPackage')->getAppAndroidDetail($userId, $id);
                if($res){
                    $return['appId'] =  encodePass($res['id']);
                    $return['title'] =  $res['title'];
                    $return['subtitle'] =  $res['subtitle'];
                    $return['introduce'] = base64_encode($res['introduce']);
//                    $return['introduce'] = "5Y+M55y855qu77yM5L2g55qE576O5Li95b+F6ZyA77yM5aWz56We5b+F5aSH77yBCuWPjOecvOearmFwcOaYr+eUsee+juWuueWkp+W4iOenkeaKgOW8gOWPkeeahOS4gOasvuaXqOWcqOW4ruWKqeaDs+imgeWBmuWPjOecvOearuWPiuaDs+S6huino+WPjOecvOearueahOaci+WPi+eahOenkeaZruS6pOa1geexu2FwcOOAggrpgJrov4flj4znnLznmq5BUFDvvIzkvaDlj6/ku6XvvJoK5LqG6Kej5Y+M55y855qu55+l6K+G5Y+K5Y+M55y855qu5YWo5pa55L2N5LuL57uN77yM5YyF5ous5Y+M55y855qu55m+56eR77yM5Y+M55y855qu6Zeu562U77yM5Y+M55y855qu5Yy755Sf5Zyo57q/5ZKo6K+i77yM5Y+M55y855qu5qGI5L6L5bGV56S6562J562J5Y+M55y855qu55qE5YaF5a6544CC";
                    $return['app_icon_url'] =  $res['app_icon_url'];
                    $return['app_download_url'] =  $res['app_download_url'];
                    $return['addTime'] =  $res['addTime'];
                    $return['package_name'] =  $res['package_name'];
                    $return['package_unit_price'] =  $res['user_get_money'];
                    $timeName = $this->getTimeName();
                    $use_num = $this->getRemainingNum($id, $timeName, $res['time_numbers'],$res['package_tasks_numbers']);
                    $return['package_tasks_numbers'] =  $use_num;
                    $return['package_file_size'] =  $res['package_file_size'];
                    $return['picture1'] =  $res['picture1'];
                    $return['picture2'] =  $res['picture2'];
                    $return['task_time'] =  $res['task_time'];

                    $return['status'] = 1;
                    $return['message'] = '操作成功';
                }
            }elseif($type==2){
                
            }
        }
        
        $this->apiCallback($return);exit(0);
    }
    
    
    /**
     * 
     * @param type $appID   APPID
     * @param type $timeName    时段名
     * @param type $time_numbers
     * @param type $package_tasks_numbers
     */
    private function getRemainingNum($appId,$timeName,$time_numbers,$package_tasks_numbers){
        $get_use_num_by_time = $this->getUseNumByTime($appId, $timeName, $time_numbers);        //获取该时段剩余数量
        $get_all_num = $this->getAllNum($package_tasks_numbers, $appId);            //获取总剩余数量
        if(($get_all_num - $get_use_num_by_time) > 0){    //总剩余数量大于该时段剩余数量
            return $get_use_num_by_time;
        }elseif(($get_all_num - $get_use_num_by_time) < 0){
            return $get_all_num;
        }else{
            if($get_all_num == $get_use_num_by_time && $get_all_num == 0){
                return 0;
            }else{
                return $get_all_num;
            }
        }
    }
    
    /**
     * 获取该时段剩余数量
     * @param type $appId   APPID
     * @param type $timeName    时段名
     * @param type $name 该时段总量
     */
    private function getUseNumByTime($appId, $timeName,$time_numbers){
        $map = array();
        $map['periodName'] = $timeName;
        $map['appId'] = $appId;
        $use_num = M('AppTaskTime')->where($map)->find();   //该时段剩余 
        return $time_numbers - $use_num['timeUseNum'];
    }
    
    
    /**
     * 获取总剩余数量
     * @param type $package_tasks_numbers   总推广量
     * @param type $appId       APPID
     */
    private function getAllNum($package_tasks_numbers,$appId){
        $map = array();
        $map['appId'] = $appId;
        $get_all_num = M('AppTaskTime')->where("appId={$appId}")->Sum('timeUseNum');   //该APP所有时段的下载量
        return $package_tasks_numbers - $get_all_num;
    }


    /*
     * 3.2.5添加使用记录
     */
    function addNote(){
        $return['success'] = true;
        $return['status'] = 10;
        $id = I('post.appId');
        $type = I('post.type');
        $userId = $this->userId;
        
        $id = decodePass($id);
        if (is_empty($id) || is_empty($type) ) {
            $return['status'] = 10;
            $return['message'] = '操作失败';
        }else{
            if($type==1 || $type=2){//type ：1-下载，2-使用
                
                $res = D('AppPackage')->addNote($userId, $type, $id);
                if($res){
                    $return['status'] = 1;
                    $return['message'] = '添加成功';
                }else{
                    $return['status'] = 10;
                    $return['message'] = '操作失败';
                }
            }else{
                $return['status'] = 10;
                $return['message'] = '操作失败';
            }
        }
        $this->apiCallback($return);exit(0);
    }
    
    /**
     * 任务是否下载
     */
    function isTask(){
        $return['success'] = true;
        $return['status'] = 10;
        $id = I('post.appId');
        $userId = $this->userId;
        $id = decodePass($id);
        
        if (is_empty($id)) {
            $return['status'] = 10;
            $return['message'] = '操作失败';
        }else{
            $where = array(
                'appId' => $id,
                'userId' => $userId,
                'type'  => '1',             //类型下载
            );
            $res = M('AppUseLog')->where($where)->find();
            if($res){
                $return['status'] = 1;
                $return['isTask'] = 1;
                $return['message'] = '查询成功';
            }else{
                $return['status'] = 1;
                $return['isTask'] = 0;
                $return['message'] = '查询成功';
            }
        }
        $this->apiCallback($return);exit(0);
    }
    
    /*
     * 3.2.5APP任务详情接口
     * 
     */
    function taskDetail(){
        $return['success'] = true;
        $return['status'] = 10;
        $type = I('post.type');
        $id = I('post.appId');
        $userId = $this->userId;    //用户ID
        $id = decodePass($id);      //appId
        
        if (is_empty($id) || is_empty($type) ) {
            $return['status'] = 10;
            $return['message'] = '操作失败';
        }else{
            if($type==1){
                $res = M('AppTask')->where("appId={$id}")->order('id asc')->select();        //任务详情表
                if($res){
                    //查询任务完成到哪一步
                    $resTask = M('AppTaskLog')->where(array('userId' => $userId, 'appId' => $id))->order('id desc')->find();        //用户完成任务日志表最后一条数据
                    if($resTask){   //已经开始做任务
                        //判断今天任务有没有完成 
                        $todayStatus = $this->checkAppTaskToday($userId, $id);
                        $return['todayStatus'] = $todayStatus;
                        if($resTask['status'] == 1){    //说明该任务已完成，要做下个任务
                            $submitInfo = M('AppTask')->field('id')->where("appId={$id} AND id>{$resTask['taskId']}")->order('id asc')->find();
                            if(!$submitInfo){       //没有数据表示没有下一步任务了
                                $submitInfo['id'] = 0;
                            }
                        }elseif($resTask['status'] == 4 || $resTask['status'] == 2){   //任务已做但还没完成或做了但失败
                            $submitInfo = M('AppTask')->field('id')->where("id={$resTask['taskId']}")->find();
                        }
                    }else{          //还没做过任务
                        $submitInfo = M('AppTask')->field('id')->where(array('appId' => $id))->order('id asc')->find();
                        $return['todayStatus'] = 0;
                    }
                    $return['info'] = $res;
                    $return['submitTaskId'] = $submitInfo['id'];    
                    $return['status'] = 1;
                    $return['message'] = '查询成功';
                }else{
                    $return['status'] = -1;
                    $return['message'] = '查询失败';
                }
            }elseif($type==2){
                
            }
        }
        $this->apiCallback($return);exit(0);
    }
    
    /**
     * 判断当天任务有没有完成
     * @param int $userId 用户ID
     * @param int $appId APPid
     * @return int 
     *      1 当天任务完成
     *      2 当天任务未完成
     *      3 当天没任务
     *      4 未做过该任务
     *      5 任务集第一个任务没完成
     *      6 任务时间结束
     */
    private function checkAppTaskToday($userId,$appId){
        //所有任务
        $info = M('AppTask')->where("appId={$appId}")->order('id asc')->select();
        $task = M('AppPackage')->where("id={$appId}")->find();
        //检测第一个任务完成日志
        $todayTime = strtotime(date('Y-m-d'));      //今天0时时间戳
        $res = M('AppTaskLog')->where("userId={$userId} and appId={$appId} and taskId={$info[0]['id']} and status='1' ")->order('id desc')->find();
        if($res){           //任务集有第一个任务完成，检测后续任务是今天是否有任务
            //今天离完成第一个任务的天数
            $day = ($todayTime - strtotime(date('Y-m-d',$res['openTime']))) / 3600 / 24;
            if($task['task_retained'] == 0) $arr = array(0);
            if($task['task_retained'] == 1) $arr = array(0,1);
            if($task['task_retained'] == 7) $arr = array(0,1,2,7);
            if(in_array($day, $arr)){       //说明今日有任务
                foreach ($info as $key => $value) {
                    $info_[$value['day']] = $value;
                }
//                $nowTime = $todayTime;
                $re = M('AppTaskLog')->where("userId={$userId} and appId={$appId} and taskId={$info_[$day + 1]['id']} and status='1' and addTime>{$todayTime} ")->order('id desc')->find();
                if($re){        
                    return 1;           //当天任务完成
                }else{
                    if($task['task_retained'] == 7 && $day == 2){  //如果是第三天任务，判断第二天任务是否完成
                         $re = M('AppTaskLog')->where("userId={$userId} and appId={$appId} and taskId={$info_[2]['id']} and status='1'")->order('id desc')->find();
                         if(!$re){
                             return 6; 
                         }
                    }
                    if($task['task_retained'] == 7 && $day == 3){  //如果是第七天任务，判断第三天任务是否完成
                         $re = M('AppTaskLog')->where("userId={$userId} and appId={$appId} and taskId={$info_[3]['id']} and status='1'")->order('id desc')->find();
                         if(!$re){
                             return 6; 
                         }
                    }
                    return 2;           //当天任务没完成
                }
            }else{
                $maxDay = end($arr);
//                dump($maxDay);dump($day);exit;
                if($day > $maxDay){
                    return 6;                    //'任务结束！';
                }else{
                    return 3;                   //当天没任务
                }
            }
        }else{
            //检测第一个任务没完成
            $re = M('AppTaskLog')->where("userId={$userId} and appId={$appId} and taskId={$info[0]['id']}")->order('id desc')->find();  
            if($re){
                return 5;           //第一个任务没完成
            }else{
                return 4;           //没做过任务
            }
        }
    }
    
    /**
     * 获取当前时间点的APP下载时段名
     */
    private function getTimeName(){
        $time1 = M('Config')->where("`key`='app_time1'")->getField('value');
        $time2 = M('Config')->where("`key`='app_time2'")->getField('value');
        $nowTime = time();
        $time = strtotime(date('Y-m-d'));      //今天0时时间戳
        $time1 = $time + $time1 * 3600;    //今天第一时段开始时间
        $time2 = $time + $time2 * 3600;    //今天第二时段开始时间
        if($nowTime > $time1 && $nowTime < $time2){
            return date('Ymd') . '_1';
        }else{
            if($nowTime < $time1){
                return date('Ymd',time() - 24 * 3600) . '_2';
                
            }else{
                return date('Ymd') . '_2';
                
            }
        }
    }


    /**
     * 任务打开接口
     */
    public function openTask(){
        $return['success'] = true;
        $return['status'] = 10;
        $type = I('post.type');
        $id = I('post.appId');
        $id= decodePass($id);      //appId
        $taskId = I('post.submitTaskId');       //任务ID
        $userId = $this->userId;    //用户ID
        if(is_empty($id) || is_empty($type)){
            $return['status'] = 8;
            $return['message'] = '操作失败';
        }else{
            if($taskId != 0){
                //判断该任务今天有无任务
                 $res = M('AppPackage')->where("id={$id}")->find();
                 if($res['status'] == 2){
                    $taskStatus = $this->checkAppTaskToday($userId, $id);
                    if(in_array($taskStatus, array(1,3,6))){            
                        $return['status'] = 20;
                        $return['message'] = '任务完成、任务过期、当天没任务';
                    }else{
                        //检测当日任务数量是否可用
                        $re_ = M('AppTask')->field('min(id) mid')->where("appId={$id}")->find();
                       $mtaskId = $re_['mid'];
                       if($mtaskId == $taskId){
                            $num = $this->checkTaskNum($id);
                       }else{
                           $num = true;
                       }
                        if($num){
                            //任务数量可用，数据入库
                            $timeName = $this->getTimeName();
                            $re = D('AppPackage')->addTask($id,$taskId,$userId,$timeName);
                            if($re){
                                $return['status'] = 1;
                                $return['message'] = '记录成功';
                            }else{
                                $return['status'] = $re;
                                $return['message'] = '记录失败';
                            }
                        }else{
                            $return['status'] = 30;
                            $return['message'] = '该时段任务数量为0';
                        }
                    }
                 }else{
                    $return['status'] = 10;
                    $return['message'] = '操作失败';
                 }
            }else{
                $return['status'] = 10;
                $return['message'] = '操作失败';
            }
        }
        $this->apiCallback($return);exit(0);
    }
    /**
     * 添加使用记录
     * @param type $appId   APPID
     * @param type $taskId  任务ID
     * @param type $userId  用户ID
     * @return int 添加成后返回的ID
     */
    private function addTask($appId, $taskId, $userId){
        $nowTime = time();
        $periodName = $this->getTimeName();
        $sql = "INSERT INTO `lu_app_task_log` (`appId`,`taskId`,`userId`,`periodName`,`type`,`status`,`openTime`,`submitTime`,`addTime`) "
                . "VALUES ({$appId},{$taskId},$userId,'{$periodName}','2','4',$nowTime,0,$nowTime)";
        $re = M('AppTaskLog')->query($sql);
        return TRUE;

        
//        ++++++++++++++++++++++++++++分割线
//        $nowTime = time();
//        $data['appId'] = $appId;
//        $data['taskId'] = $taskId;
//        $data['userId'] = $userId;
//        $timeName = $this->getTimeName();
//        $data['periodName'] = (string) $timeName;
//        $data['type'] = '2';
//        $data['status'] = '4';
//        $data['openTime'] = $nowTime;
//        $data['submitTime'] = 0;
//        $data['addTime'] = $nowTime;
//        $re = M('AppTaskLog')->add($data);
//        return $re;
    }
    
    /**
     * 检测当日该时段任务数量是否可用
     * @param type $id APPID
     * @return bool 当日该时段任务是否可用 
     */
    private function checkTaskNum($appId){
        $timeName = $this->getTimeName();
        $timeUseNum = M('AppTaskTime')->field('timeUseNum')->where("periodName='{$timeName}'")->find();
        $timeNumbers = M('AppPackage')->field('time_numbers,package_tasks_numbers,use_numbers')->where("id={$appId}")->find();
        $num1 = $timeNumbers['time_numbers'] - $timeUseNum['timeusenum'];
        $num2 = $timeNumbers['package_tasks_numbers'] - $timeNumbers['use_numbers'];
        if($num1 > 0 && $num2 > 0){
            return true;
        }else{
            return false;
        }
    }
    
    /**
     * 任务失败接口
     */
    public function failureTask(){
        $return['success'] = true;
        $return['status'] = 10;
        $type = I('post.type');
        $id = I('post.appId');
        $id= decodePass($id);      //appId
        $taskId = I('post.submitTaskId');       //任务ID
        $userId = $this->userId;    //用户ID
        $arr = array(1,2);
        if(is_numeric($id) && in_array($type,$arr) && is_numeric($taskId)){
            //查找出要失败的数据
            $where = "taskId={$taskId} and userId={$userId} and status='4'";
            $order = "id desc";
            $limit = 1;
            $res = M('AppTaskLog')->where($where)->order($order)->limit($limit)->find();
            if($res){
                $re = M('AppTaskLog')->where("id={$res['id']}")->data(array("status"=>'2'))->save();
                if($re){
                    $return['status'] = 1;
                    $return['message'] = '提交成功';
                }else{
                    $return['status'] = -1;
                    $return['message'] = '提交失败';
                }
            }else{
                $return['status'] = 20;
                $return['message'] = '参数错误';
            }
            
        }else{
            $return['status'] = 10;
            $return['message'] = '操作失败';
        }
        $this->apiCallback($return);exit(0);
    }


    /**
     * 任务提交接口
     */
    public function submitTask(){
        $return['success'] = true;
        $return['status'] = 10;
        $type = I('post.type');
        $id = I('post.appId');
        $id= decodePass($id);      //appId
        $taskId = I('post.submitTaskId');       //任务ID
        $taskId = authcodeAndroid($taskId, 'DECODE');
        $userId = $this->userId;    //用户ID
        if(is_empty($id) || is_empty($type)){
            $return['status'] = authcodeAndroid(10,'ENCODE');
            $return['message'] = '操作失败';
        }else{
            if($taskId != 0){
                $res = M('AppPackage')->where("id={$id}")->find();
                if($res['status'] == 2){
                    //检测该用户有无该任务的合理时间段的记录
                    $taskInfo = $this->checkReasonableTask($id, $taskId, $userId);
                    if($taskInfo){      //有合理的任务
                        if($taskInfo['experienceTime']){
                            //判断该用户是不是做该任务集第一个任务
                            $re_ = M('AppTask')->field('min(id) mid')->where("appId={$id}")->find();
                            $mtaskId = $re_['mid'];
                            if($mtaskId == $taskId){    //做后续任务不需要验证任务数量
                                //验证该任务有无足够数量
                                $num = $this->checkTaskNum($id);
                            }else{
                                $num = true;
                            }
                            if($num == true){   //任务数量足够
                                $taskIn = M('AppTask')->where("id={$taskId}")->find();
                                //做入库操作，增加使用数量
                                $res = $this->updateTask($id, $taskId, $userId, $taskInfo,$mtaskId);
                                if($res){
                                    //查询该任务金额、为用户加钱
                                    $resu = D('AppPackage')->addMoneyLog($id, $taskId, $userId,$taskIn['reward_money'],$taskInfo['periodName']);
                                    if($resu){
                                        $return['status'] = authcodeAndroid(1,'ENCODE');
                                        $return['message'] = $taskIn['reward_money'];
                                    }else{
                                        $return['status'] = authcodeAndroid(50,'ENCODE');
                                        $return['message'] = '用户获取金额失败';
                                    }
                                }else{
                                    $return['status'] = authcodeAndroid(30,'ENCODE');
                                    $return['message'] = '添加使用数量失败';
                                }
                            }else{      //任务数量不足
                                $return['status'] = authcodeAndroid(40,'ENCODE');
                                $return['message'] = '该时段任务被抡光';
                            }

                        }else{
                            $return['status'] = authcodeAndroid(60,'ENCODE');
                            $return['message'] = '体验时间不足';
                        }
                    }else{          //没有合理的任务
                        $return['status'] = authcodeAndroid(20,'ENCODE');
                        $return['message'] = '没有合理的任务';  //即数据库中没有该用户对应该任务的记录
                    }
                }else{
                    $return['status'] = authcodeAndroid(70,'ENCODE');
                    $return['message'] = '该任务已下架';
                }
            }else{
                $return['status'] = authcodeAndroid(14,'ENCODE');
                $return['message'] = '缺少参数';
            }
        }
        $this->apiCallback($return);exit(0);
//        $this->apiEncodeCallback($return);exit(0);
    }
  




    /**
     * 修改任务完成情况日志表状态等操作
     * @param int $appId    APPID
     * @param int $taskId   任务ID
     * @param int $userId   用户ID
     */
    private function updateTask($appId, $taskId, $userId, $taskInfo,$mtaskId){
        //修改任务完成情况日志表状态
        $nowTime = time();
        $data['submitTime'] = $nowTime;
        $data['updateTime'] = $nowTime;
        $data['status'] = '1';
        
        $re = M('AppTaskLog')->where("id={$taskInfo['id']}")->data($data)->save($data);
        if($re){
            if($mtaskId == $taskId){     //如果相等则减任务数量
                //修改任务时段表数据，
                $res = M('AppTaskTime')->where("appId={$appId} and periodName='{$taskInfo['periodName']}'")->find();
                if($res){   //有该时段的记录，则修改
                    $res = M('AppTaskTime')->where("id={$res['id']}")->setInc('timeUseNum');   //APP下载在该时段已用数量+1
                }else{      //没有该时段的记录，则新增
                    $dataT = array();
                    $dataT['appId'] = $appId;
                    $dataT['periodName'] = $this->getTimeName();
                    $dataT['timeUseNum'] = 1;
                    $dataT['addTime'] = $nowTime;
                    $dataT['updateTime'] = 0;
                    $res = M('AppTaskTime')->data($dataT)->add();
                }
                M('AppPackage')->where("id={$appId}")->setInc('use_numbers',1);
                $resu = M('AppPackage')->field('package_tasks_numbers,use_numbers,task_retained')->where(array('id'=>$appId))->find();
                if(($resu['package_tasks_numbers'] - $resu['use_numbers']) == 0){
                    //修改APP表结束时间
                    $this->updateAppPackage($appId,$resu['task_retained'],$nowTime);
                }
            }
            return true;
        }else{
            return false;
        }
    }
    
    /**
     * 修改APP表结束时间
     */
    private function updateAppPackage($appId,$task_retained,$nowTime){
        if($task_retained == 0){
            $endTime = $nowTime;
        }elseif ($task_retained == 1) {
            $endTime = strtotime(date('Y-m-d',time() + 3600 * 24 * 2));
        }elseif ($task_retained == 7) {
            $endTime = strtotime(date('Y-m-d',time() + 3600 * 24 * 8));
        }
//        M('AppPackage')->where(array('id'=>$appId))->data(array('endTime'=>$endTime))->save();         //修改结束时间
//        $s = M('AppPackage')->where(array('id'=>$appId))->setField('endTime',$nowTime);         //修改结束时间
        $s = M('AppPackage')->execute("UPDATE `lu_app_package` SET `endTime`={$endTime} WHERE  id={$appId} ");
    }


    /**
     * 检测该用户有无该任务的合理时间段的记录
     * @param type $appId
     * @param type $taskId
     * @param type $userId
     * @param type $openTime
     */
    private function checkReasonableTask($appId, $taskId, $userId){
        $appInfo = M('AppPackage')->field('task_time')->where("id={$appId}")->find();
        //检测该用户有无该任务的合理时间段的记录
        $submitTime = time();
        $openTime = $submitTime - $appInfo['task_time'] * 60;   //体验时间达到时间戳
        $ttime = $submitTime - 2*3600;  //2小时前时间戳
        //记录的时间未到二小时，大于体验时间
//        $where = "taskId={$taskId} and userId={$userId} and openTime<{$openTime} and openTime>{$ttime} and status='4'";     //试玩时间不足
        $where = "taskId={$taskId} and userId={$userId} and status='4'";
        $order = "id desc";
        $limit = 1;
        $res = M('AppTaskLog')->where($where)->order($order)->limit($limit)->find();
        if($res['openTime'] < $openTime && $res['openTime'] > $ttime){  //判断体验时间是否足够
            $res['experienceTime'] = true;
        }
        return $res;
    }


    
    /**
     * 验证任务金额该如何分配(未用)
     * @param type $task
     * @param type $time
     * @param type $retained
     */
    private function method($task,$time,$retained,$price){
        if(in_array($task,array(1,2))){
            if(in_array($time, array(0,3,5))){
                switch ($retained) {
                    case 0:
                        $return = array();
                        $return[0] = '120分钟完成任务。';
                        $return[1] = '下载并打开奖励' . $price . '元。';
                        break;
                    
                    case 1:
                        $price_1 = substr($price, 0, strpos($price, '.') + 2);  //下载奖励金额
                        $price_2 = $price - $price_1;           //第二日奖励金额
                        $return = array();
                        $return[0] = '120分钟完成任务。';
                        $return[1] = '下载并打开试玩' . $time . '分钟奖励' . $price . '元。';
                        $return[2] = '第二天打开试用，奖励' . $price . '元。';
                        break;
                    
                    case 7:
                        $price_1 = substr($price / 2 , 0, strpos($price / 2, '.') + 2);          //下载奖励金额
                        $price_2 = substr($price_1 / 3 , 0, strpos($price_1 / 3, '.') + 2);        //第2、3日奖励金额
                        $price_3 = $price - $price_1 - $price_2 - $price_2;      //第7日奖励金额
                        $return = array();
                        $return[0] = '120分钟完成任务。';
                        $return[1] = '下载并打开试玩' . $time . '分钟奖励' . $price_1 . '元。';
                        $return[2] = '第二天打开试用，奖励' . $price_2 . '元。';
                        $return[3] = '第三天打开试用，奖励' . $price_2 . '元。';
                        $return[4] = '第七天打开试用，奖励' . $price_3 . '元。';
                        break;

                    default:
                        $return = '参数错误';
                        break;
                }
            }
        }else{
            $return = '参数错误';
        }
        return $return;
    }
    

}
