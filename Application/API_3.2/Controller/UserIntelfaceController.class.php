<?php

/**
 * 话费 接口
 * @author Jine <luxikun@andlisoft.com>
 */
class UserIntelface extends CommonController 
{
    /**
     * 初始化
     */
    public function _initialize() 
    {
        //获取当前方法名
        $ACTION_NAME = strtolower(ACTION_NAME);
        //设定当前程序对应的队列名称
        $this->mq_name = getMqName();
        //第一步解密
        $process_data[] = 'password';
        $process_data[] = 'newPassword';
        $process_data[] = 'newHandlePwd';
        $process_data[] = 'handlePwd';
        $process_data[] = 'userId';
        foreach ($process_data as $key) {
            if ($_POST[$key]) {
                $_POST[$key] = dataDecode($_POST[$key]);
            }
        }
    }
    
    
    /**
     * Ajax 检测充值兑换功能是否开启
     * ajax用
     */
    public function ajaxSwitchStatus() {
        $exchange_switch_status = $this->getMemcacheValue('mq_switch');

        //返回当前充值订单开关是否开启
        if ($exchange_switch_status != 2) {
            $ret['status'] = 10000;
            $ret['message'] = '兑换功能开启';
        } else {
            $ret['status'] = 10002;
            $ret['message'] = '兑换功能暂时关闭';
        }
        exit(json_encode($ret));
    }

    /**
     * 兑换功能状态
     * 内部程序用
     */
    public function checkSwitchStatus() {
        $exchange_switch_status = $this->getMemcacheValue('mq_switch');
        if ($exchange_switch_status == 1) {
            return TRUE;
        } else {
            return FALSE;
        }
    }

    /**
     * 添加队列
     */
    public function inputQueue() {
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
     * 获取缓存名称对应的值
     * @param type $name
     */
    function getMemcacheValue($name) {
        $memcache = new Memcache;
        $memcache->connect(MEMCACHE_HOST, 11211);
        return $memcache->get($name);
    }
}