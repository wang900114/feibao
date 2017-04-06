<?php

/**
 * ip处理类
 * 
 */
use Think\Model;

class CheckModel extends CommonModel {

    //当前用户的ID
    public $user_id;
    //当前用户的IP
    public $ip;
    //当前用户访问的控制器
    public $controller_name;
    //当前用户访问的操作
    public $action_name;

    public function _initialize() {
        parent::_initialize();
        //初始化用户ID
        //$this->user_id = (int) I('post.userId');
        //$this->user_id = $this->user_id ? $this->user_id : 43146;
        //初始化用户IP
        $this->ip = $this->getIP();
        //初始化用户访问的控制器
        $this->controller_name = CONTROLLER_NAME;
        //初始化用户访问的操作
        $this->action_name = ACTION_NAME;

        $action_name = $this->action_name;
        $controller_name = $this->controller_name;

        //控制的入口 --更新控制方法
        if(
             ($controller_name == "Bill" and $action_name == "exchange" )//充值兑换接口
             or ($controller_name == "NewBill" and $action_name == "exchange" )//新充值接口  
                 or ( $controller_name == "Posters" and $action_name == "expose" )//揭海报
                 or ( $controller_name == "Posters" and $action_name == "share")//分享海报接口
                or ( $controller_name == "Personal" and $action_name == "registerInvite" )//注册邀请接口
                or ( $controller_name == "Found" and  $action_name == "addFounds")//发布发现接口
                or ( $controller_name == "Comments" and $action_name == "addComments")//评论接口
                ){
            
            $tmpUserId = dataDecode($_POST['userId']);
            //var_dump($tmpUserId);die;
            
            $res=D('Members')->checkUserId(I('post.phone'),$tmpUserId,'id');
            if($res['id']){
                $this->user_id=$res['id'];
            }else{
                jsonMassageReturn(-11003, "操作异常，请重新登录");
            }
            $this->checkIpAndUser();
            
        }
        
        //自动写入IP访问日志
        $this->writeIpAccessLog();
        
        //自动写入server 日志
        $this->allLog();

        /*
        //控制的入口
        if (
                ($controller_name == "Bill" and $action_name == "exchange" )//充值兑换接口
                or ($controller_name == "NewBill" and $action_name == "exchange" )//新充值接口
                or ( $controller_name == "Posters" and ( $action_name == "expose" or $action_name == "share"))//揭海报\分享海报接口
                or ( $controller_name == "Personal" and ( $action_name == "invite" or $action_name = "fastExperience"))//邀请接口\快速体验接口
                or ( $controller_name == "Found" and ( $action_name == "addFound" or $action_name == "addFounds"))//发布发现接口
                or ( $controller_name == "Comments" and $action_name == "addComments")//评论接口
        ) {
            $this->checkIpAndUser();
        }
        */
    }

    public function checkIpAndUser() {

        //判断IP是否在禁止访问列表
        $this->ipLimit();
        //判断是否违法访问
        $this->checkIpLimit();
        //当发现用户ID的时候进行IP判断
        if ($this->user_id) {
            //判断用户和IP是否在禁止访问列表
            $this->ipAndUserLimit();
            //判断用户和IP是否违法访问
            $this->checkIpAndUserLimit();
            //判断用户是否正常
            $this->checkUserStatus();
        }
    }

    /**
     * 获取客户端IP并转换为整形
     * 
     * @return int 返回整形的IP数据
     */
    public function getIP() {
        //获取用户IP
        $ip = $_SERVER['HTTP_X_REAL_IP'];
        //如果获取不到说明没有走代理,通过普通方式获取IP
        $ip = $ip ? $ip : $_SERVER['REMOTE_ADDR'];
        //将IP转化为整形提高效率
        $ip_int = sprintf("%u", ip2long($ip));
        return $ip_int;
    }

    /**
     * 将访问的IP放入访问日志
     * 
     * @param int $ip 写入
     */
    public function writeIpAccessLog() {
        $m = M('ip_access_log');
        //组合日志数据
        $data['ip'] = $this->ip;
        $data['user_id'] = $this->user_id;
        $data['controller_name'] = $this->controller_name;
        $data['action_name'] = $this->action_name;
//        $data['server'] = I('post.mobileflag') . "_" . D("System")->readConfig('old_user_switch') . "_" . I('post.userId');
        $data['access_time'] = time();
//        showTestInfo($data);
        $m->add($data);
        $member_m = M('ip_access_log_member');
        //读取最小的时间
        $min_time = $member_m->min("access_time");
        //最小时间的小时数
        $min_h = date("H", $min_time);
        //如果到下一个小时则清空缓存表
        if ($min_h != date("H")) {
            $member_m->where("1")->delete();
        }
        //如果超过10000条记录则清空
        if ($member_m->count() > 30000) {
            $member_m->where("1")->delete();
        }
        //写入缓存表
        unset($data['server']);
        $member_m->add($data);
    }
    
    
    /**
     * 将访问的IP放入访问日志
     * 
     * @param int $ip 写入
     */
    public function allLog() {
        $m = M('allLog');
        //组合日志数据
        $data['content'] = $_SERVER;
        $data['time'] = time();
//        showTestInfo($data);
        $m->add($data);
    }

    /**
     * 统计某个IP最近一分钟的的访问数量
     * 
     * @param int $minute 读取多少分钟的数据;
     */
    public function countIpAndUserAccessLog() {
        $minute = D("System")->ipAccessMinute();
        //获得当前访问的IP
        $ip = $this->ip;
        //获得访问的当前用户的ID
        $user_id = $this->user_id;
        //设定测算的时间为最近1分钟
        $time = time() - $minute * 60;
        /**
         *                 ($controller_name == "Bill" and $action_name == "exchange" )//充值兑换接口
          or ( $controller_name == "Posters" and ( $action_name == "expose" or $action_name == "share"))//揭海报\分享海报接口
          or ( $controller_name == "Personal" and ( $action_name == "invite" or $action_name = "fastExperience"))//邀请接口\快速体验接口
          or ( $controller_name == "Found" and ( $action_name == "addFound" or $action_name == "addFounds"))//发布发现接口
          or ( $controller_name == "Comments" and $action_name == "addComments")//评论接口
         */
        $where = "ip={$ip} and user_id={$user_id} and access_time>{$time} AND controller_name in ('Bill','Posters','Personal','Found','Comments') and action_name in ('exchange','expose','share','invite','fastExperience','addFound','addFounds','addComments')";

        $m = M('ip_access_log_member');
        $count = $m->where($where)->count();
        return $count;
    }

    /**
     * 验证IP访问限制并处理
     */
    public function checkIpAndUserLimit() {
        //单位时间最大访问次数限制
        $max = D("System")->ipAccessMax();
        //单位时间访问次数统计
        $count = $this->countIpAndUserAccessLog();
        //判断IP用户访问限制,并处理
        if ($count >= $max) {
            //添加用户和IP的访问限制
            $this->changeIpAndUserLimit();
            //添加用户访问限制
            $this->changeUserStatus();
            //终止程序执行并返回相关提示
            jsonMassageReturn(-11003, "用户和IP受限");
        }
    }

    /**
     * 改变IP访问限制
     */
    public function changeIpAndUserLimit() {
        $m = M("ip_limit");
        $where['ip'] = $this->ip;
        $where['user_id'] = $this->user_id;
        $result = $m->where($where)->find();
        $data = array();
        $data['ip'] = $this->ip;
        $data['user_id'] = $this->user_id;
        $data['status'] = 2;
        $data['utime'] = time();
        //如果找到,更新,如果没找到,添加
        if ($result) {
            $m->where($where)->save($data);
        } else {
            $data['ctime'] = $data['utime'];
            $m->add($data);
        }
    }

    /**
     * IP受限检测
     * 
     * 如果IP在受限列表中,则终止程序执行
     */
    public function ipAndUserLimit() {
        $where['ip'] = $this->ip;
        $where['user_id'] = $this->user_id;
        $where['status'] = 2;
        $m = M("ip_limit");
        $result = $m->where($where)->find();
        if ($result) {
            //终止程序执行并返回相关提示
            jsonMassageReturn(-11003, "用户和IP受限");
        }
    }

    /**
     * 统计某个IP最近一分钟的的访问数量
     * 
     * @param int $minute 读取多少分钟的数据;
     */
    public function countIpAccessLog() {
        $minute = D("System")->ipAccessMinute();
        //获得当前访问的IP
        $ip = $this->ip;
        //获得访问的当前用户的ID
        $user_id = 0;
        //设定测算的时间为最近1分钟
        $time = time() - $minute * 60;
        $where = "ip={$ip} and access_time>{$time}  "
                . "AND controller_name in ('Bill','Posters','Personal','Found','Comments') "
                . "and action_name in ('exchange','expose','share','invite','fastExperience','addFound','addFounds','addComments')";
        $m = M('ip_access_log_member');
        $count = $m->where($where)->count();
        return $count;
    }

    /**
     * 验证IP访问限制并处理
     */
    public function checkIpLimit() {
        //单位时间最大访问次数限制
        $max = D("System")->ipAccessMax();
        //单位时间访问次数统计
        $count = $this->countIpAccessLog();
        //判断IP用户访问限制,并处理
        if ($count >= $max * 1.5) {
            $this->changeIpLimit();
            //终止程序执行并返回相关提示
            jsonMassageReturn(-110012, "IP受限");
        }
    }

    /**
     * 改变IP访问限制
     */
    public function changeIpLimit() {
        $m = M("ip_limit");
        $where['ip'] = $this->ip;
        $where['user_id'] = 0;
        $result = $m->where($where)->find();
        $data = array();
        $data['ip'] = $this->ip;
        $data['user_id'] = 0;
        $data['status'] = 2;
        $data['utime'] = time();
        //如果找到,更新,如果没找到,添加
        if ($result) {
            $m->where($where)->save($data);
        } else {
            $data['ctime'] = $data['utime'];
            $m->add($data);
        }
    }

    /**
     * IP受限检测
     * 
     * 如果IP在受限列表中,则终止程序执行
     */
    public function ipLimit() {
        $where['ip'] = $this->ip;
        $where['user_id'] = 0;
        $where['status'] = 2;
        $m = M("ip_limit");
        $result = $m->where($where)->find();
        if ($result) {
            //终止程序执行并返回相关提示
            jsonMassageReturn(-110011, "IP受限");
        }
    }

    /**
     * 设定此用户状态为非法状态
     */
    public function changeUserStatus() {
        $user_id = $this->user_id;
        $m = M("members");
        $where['id'] = $user_id;
        $data['freeze'] = '2';
        $data['updateTime'] = time();
        $result = $m->where($where)->save($data);
        if ($result) {
            $massage = "抱歉，您的账号存在异常，暂时无法完成此操作！";
            pushMassageAndWriteMassage($massage, $user_id);
        }
    }

    /**
     * 检测用户是否受限
     * 
     */
    public function checkUserStatus() {
        $user_id = $this->user_id;
        $m = M("members");
        $where = "id = {$user_id}";
        $data = $m->where($where)->find();
        $freeze = $data['freeze'];
        if ($freeze) {
            //终止程序执行并返回相关提示
            jsonMassageReturn(-11002, "用户受限");
        }
    }

}
