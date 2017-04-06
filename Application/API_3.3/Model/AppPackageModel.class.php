<?php

use Think\Model;

// 下载包模型
class AppPackageModel extends CommonModel {

    /**
     * 获取下载包列表
     * @param int $selectTime
     * @param int $page
     * @param int $pageSize
     * @return list
     */
    //function getListData($type,$selectTime, $page, $pageSize) {
    function getListData($type) {
        $field = 'id,title,package_name,subtitle,introduce,app_icon_url,app_download_url,addTime,package_file_size,package_tasks_numbers,package_unit_price,key,value,time_numbers,user_get_money,endTime';
        //$limit = ($page - 1) * $pageSize . "," . $pageSize;
        $limit = 50;
        $where = array(
            //'addTime' => array('elt', $selectTime),
            'status' => '2',
            'type' => (string)$type,
//            'endTime' => array(array('gt',0),array('lt',time())),
        );
        $order = 'id desc';
        //$result = M('AppPackage')->field($field)->where($where)->order($order)->limit($limit)->select();
        $result = M('AppPackage')->field($field)->where($where)->order($order)->limit($limit)->select();
//        $nowTime = time();
//        foreach ($result as $key => $value) {
//            $use_num = $this->getRemainingNum($value['id'], $timeName, $value['time_numbers'], $value['package_tasks_numbers']);
//            if($use_num == 0 && $nowTime > $value['endtime']){  //当剩余数量为0时，根据留存率判断是否显示
//                unset($result[$key]);
//            }
//        }
        //echo $this->getLastSql();
        return $result;
    }
    
    /*
     * 获取我的订单列表
     */
    function getOrderList($userId,$selectTime, $page, $pageSize){
        $field = 'id,money,addTime,status';
        $limit = ($page - 1) * $pageSize . "," . $pageSize;
        $where = array(
            //'addTime' => array('elt', $selectTime),
            'userId' => $userId,
        );
        
        $order = 'id desc';
        $result = M('AppWithdrawalsOrder')->field($field)->where($where)->order($order)->limit($limit)->select();
        //echo $this->getLastSql();
        return $result;
    }
    
    /*
     * 获取已结束的下载任务
     */
    function getListEnd($type){
        $field = 'id,title,package_name,subtitle,introduce,app_icon_url,app_download_url,addTime,package_file_size,package_tasks_numbers,package_unit_price';
        $where = array(
            'status' => '3',
            'type' => (string)$type,
        );
        
        $limit = 10;
        $order = 'id desc';
        //$result = M('AppPackage')->field($field)->where($where)->order($order)->limit($limit)->select();
        $result= array();
        $result = M('AppPackage')->field($field)->where($where)->order($order)->limit($limit)->select();
        //echo $this->getLastSql();die;
        //var_dump($result);die;
        return $result;
    }
    
    /*
     * 判断会员获取提现资格
     */
    function getUserRole($userId){
        $start_time = $this->getTodayStart();
        $end_time = $start_time + 86400;
        
        $where = array(
            'addTime' => array('BETWEEN', "{$start_time}, {$end_time}"),
            'userId' => $userId
        );
            
        $res = M('AppWithdrawalsOrder')->field('id')->where($where)->find();
        //echo M('AppWithdrawalsOrder')->getLastSql();die;
        return $res['id'];
    }
    
    /*
     * 获取安卓下载包详情
     */
    function getAppAndroidDetail($userId, $id){
        //判断会员是否已经玩过此任务
        
        //获取app包资料
        $field = 'id,title,subtitle,introduce,app_icon_url,app_download_url,addTime,package_name,package_unit_price,package_tasks_numbers,package_file_size,picture1,picture2,task_time,time_numbers,user_get_money';
        $where ='id ='.$id;
        
        $result =array();
        $result = M('AppPackage')->field($field)->where($where)->find();
        $result['true_size'] = $result['package_file_size'];
        $result['package_file_size'] = sprintf('%.2f',(($result['package_file_size'] / 1024 / 1024 ) * 100 + 1 ) / 100);
        return $result;
    }
    
    /*addNote
     * 添加提现订单
     */
    function addOrder($userId,$money,$ali_number,$ali_user_name){
        $data['userId'] = $userId;
        $data['ali_number'] = $ali_number;
        $data['ali_user_name'] = $ali_user_name;
        $data['orderNumber'] = time().$userId;
        $data['money'] = $money;
        $data['addTime'] = time();
        
        $res = M('AppWithdrawalsOrder')->add($data);
        //echo M('AppWithdrawalsOrder')->getLastSql();die;
        return $res;
    }
    
     /**
     * 获取今天开始时间
     */
    public function getTodayStart() {
        list($y, $m, $d) = explode(',', date('Y,m,d'));

        return mktime(0, 0, 0, $m, $d, $y);
    }
    
    /**
     * 添加使用记录
     * @param int $userId
     * @param int $type
     * @param int $id
     * @return true/false
     */
    function addNote($userId, $type, $id){
        $data['addTime']=time();
        $data['userId']=$userId;
        $data['type']=(string)$type;
        $data['appId']= $id;
        
        $result = M('AppUseLog')->add($data);
        return $result;
    }

    function getUserTodayMoney($userId){
        $nowTimeS = strtotime(date('Y-m-d'));
        $getMoney = M('AppUserGetMoneyLog')->where("userId={$userId} and addTime>{$nowTimeS}")->Sum('money');
        return $getMoney;
    }
        
    /**
     * 
     * @param type $appId
     * @param type $taskId
     * @param type $userId
     * @param type $money   相应任务的金额
     * @param type $periodName  时段名称
     */
    function addMoneyLog($appId,$taskId,$userId,$money,$periodName){
        //为用户加钱
        $userInfo = M('Members')->where("id={$userId}")->find();
        $da_['total_money'] = $userInfo['total_money'] + $money;
        $da_['money'] = $userInfo['money'] + $money;
//        M('Members')->where("id={$userId}")->data($da_)->save();
        
        $save_sql = "update lu_members set `total_money` = {$da_['total_money']}, `money` = {$da_['money']} where `id` = {$userId}";
        M()->execute($save_sql);
        
//        M('Members')->where("id={$userId}")->setInc('total_money',$money);  //
//        M('Members')->where("id={$userId}")->setInc('money',$money);        
        M('AppPackage')->where("id={$appId}")->setInc('use_money',$money);  //APP详情表使用金额增加
        //APP详情表使用金额增加
        $data_ = array();
        $data_['appId'] = $appId;
        $data_['userId'] = $userId;
        $data_['taskId'] = $taskId;
        $data_['periodName'] = $periodName;
        $data_['money'] = $money;
        $data_['addTime'] = time();
        $result = M('AppUserGetMoneyLog')->add($data_);
        return $result;
    }
    
    /**
     * 添加使用记录
     * @param type $appId   APPID
     * @param type $taskId  任务ID
     * @param type $userId  用户ID
     * @return int 添加成后返回的ID
     */
    function addTask($appId, $taskId, $userId,$timeName){
        $nowTime = time();
        $data['appId'] = $appId;
        $data['taskId'] = $taskId;
        $data['userId'] = $userId;
        $data['periodName'] = $timeName;
        $data['type'] = '2';
        $data['status'] = '4';
        $data['openTime'] = $nowTime;
        $data['submitTime'] = 0;
        $data['addTime'] = $nowTime;
        $re = M('AppTaskLog')->add($data);
        return $re;
    }
    
    /*
     * 添加app包使用日志
     */
    function addAppUseLog($userId,$appId,$type){
        $data['userId'] = $userId;
        $data['appId'] = $appId;
        $data['type'] = $type;
        $data['addTime'] = time();
        
        $res = M('appUseLog')->add($data);
        return $res;
    }
}
