<?php
use Think\Controller;
/**
 * 获取缓存名称对应的值
 * @param type $name
 */
function getMemcacheValue($name) {
    $memcache = new Memcache;
    $memcache->connect(MEMCACHE_HOST, 11211);
    return $memcache->get($name);
}

/**
 * 设置缓存名称对应的值
 * @param string $name 设置的变量名
 * @param type $value 缓存的值
 */
function setMemcacheValue($name, $value) {
    $memcache = new Memcache;
    $memcache->connect(MEMCACHE_HOST, 11211);
    $memcache->set($name, $value);
}

/**
 * 获取队列名称
 * @return boolean
 */
function getMqName() {
    if (!$mq_name = getMemcacheValue('mq_name')) {
        return FALSE;
    }
    return $mq_name;
}

/**
 * 话费 接口
 * @author Jine <luxikun@andlisoft.com>
 */
class NewBillController extends Controller {

    private $mq_name;

    public function __construct() {
        parent::__construct();
        $process_data[] = 'password';
        $process_data[] = 'newPassword';
        $process_data[] = 'newHandlePwd';
        $process_data[] = 'handlePwd';
        $process_data[] = 'userId';
        foreach ($process_data as $key) {
            if (isset($_POST[$key])) {
                $_POST[$key] = dataDecode($_POST[$key]);
            }
        }
        //设定当前程序对应的队列名称
        $this->mq_name = getMqName();
    }

    /**
     * 兑换状态
     */
    public function switchStatus() {
        $memcache = new Memcache;
        $memcache->connect(MEMCACHE_HOST, 11211);
        echo $memcache->get('mq_switch');
        exit();
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
    public function inputQueue() {
        //设定当前程序对应的队列名称
        $this->mq_name = getMqName();
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
        $data['money'] = $_POST['money']; //兑换的话费
        $res = M('PosterBillQueue')->add($data);
//        $res = $this->db->add('poster_bill_queue', $data);
        if ($res) {//判断是否添加成功
            $ret['status'] = 1;
            //$ret['message'] = ':-D 排队成功，已进入队列请稍后查看';
            $ret['message'] = '前方人数众多，正在为您排队缴话费...';
        } else {
            $ret['status'] = 10;
            $ret['message'] = '%>_<% 排队失败';
        }

        echo json_encode($ret);
        exit();
    }

}
