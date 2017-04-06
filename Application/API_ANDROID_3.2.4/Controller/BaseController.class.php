<?php

use Think\Controller;
use Think\Controller\RestController; // 使用其中的response方法
class BaseController extends RestController {

    /**
     * API反馈信息
     */
    public $ret = array(
        'success' => true, // boolean 通讯成功
        'status' => 1, // init 接口状态
        'message' => '', // string 接口反馈消息
        'info' => array(), // mixed 反馈数据
    );

    /**
     * 初始化 Model 相关变量
     */
    public $_modelName = '';
    public $_model = '';
    public $_map = '';
    public $_check;

    /**
     * 初始化
     * @access public
     * @author FrankKung <kongfanjian@andlisoft.com>
     */
    public function _initialize() {
        //有用户uid的情况中判断用户状态
        //$this->userStatus();
        //header('Content-Type:text/html; charset=UTF-8');
        header('Content-Type:application/json; charset=UTF-8');
        
//        showTestInfo('断点');
        //判断系统状态
        $system = D('system');
        $system_switch_status = $system->systemSwitchStatus();

        //当系统状态为不正常的时候关闭整个系统
        if ($system_switch_status != 1) {
            $ret['status'] = 10001;
            $ret['message'] = '系统关闭';
            echo jsonStr($ret);
            exit(0);
        }
        //$ACTION_NAME = strtolower(ACTION_NAME);
        //echo $ACTION_NAME;die;
        
        //自动处理IP相关的限制
        //$this->_check = A('API_3.1/Check');
        $this->_check = A('API_ANDROID_3.2.4/Check');
        
//        $result = D('Ip')->filtersel($_SERVER['SERVER_ADDR']);
//        if ($result) {
//            $ret['status'] = 10003;
//            $ret['message'] = '非法ip';
//            echo jsonStr($ret);
//            exit(0);
//        }
        writeGetUrlInfo();

        //修改 会员 更新时间
        $PublicOb = A('API_ANDROID_3.2.4/Public');
        $PublicOb->index();

        // 定义接口状态
        define('success', '200'); // 通讯成功
        define('failed', '300'); // token验证失败
        define('none', '404'); // 没有数据
        // 获取参数
        $this->token = $_POST ['token'];
        $this->userId = $_POST ['userId'];
        $this->version = $_POST ['version'];
        $this->lastUpdateTime = $_POST ['lastUpdateTime'];
        $this->type = $_POST ['type'];


        /* if(CONTROLLER_NAME != 'Shopping' && CONTROLLER_NAME != 'Personal' && CONTROLLER_NAME != 'City'){
          //个人中心，城市、店铺模块的是 单独验证
          A('API_3.2/Public')->testPublicToken();//验证 公共 token
          } */

        // 验证API版本
        if ($version !== C('APIVer')) {
            # code...
        }

        C('SHOW_PAGE_TRACE', true);
        C('SHOW_RUN_TIME', true);
    }

    /**
     * 魔术方法 有不存在的操作的时候执行
     * @access public
     * @param string $method 方法名
     * @param array $args 参数
     */
    public function __call($method, $args) {
        $this->none();
    }

    /**
     * API数据输出
     * @access public
     * @param string|array $data 要输出的数据
     * @param string $type 输出数据的类型：1.json 2.xml 3.php序列化
     * @author FrankKung <kongfanjian@andlisoft.com>
     */
    public function apiCallback($data, $outType = 'json') {
        // $outType = ( in_array($_GET['outType'], array('json','xml','php')) ) ? $_GET['outType'] : 'json' ;
        // echo $this->response($data, $outType);
        // 禁用调试,防止出错
        C('SHOW_PAGE_TRACE', false);
        C('SHOW_RUN_TIME', false);

        // 输出
        echo jsonStr($data);
        exit(0);
    }

    public function apiCallbackWithOutBadWords($data, $outType = 'json') {
        // $outType = ( in_array($_GET['outType'], array('json','xml','php')) ) ? $_GET['outType'] : 'json' ;
        // echo $this->response($data, $outType);
        // 禁用调试,防止出错
        C('SHOW_PAGE_TRACE', false);
        C('SHOW_RUN_TIME', false);

        // 输出
        echo jsonStrWithOutBadWords($data);
        exit(0);
    }

    /**
     * 默认index页面
     * @access public
     * @author FrankKung <kongfanjian@andlisoft.com>
     */
    public function index() {
        $this->apiCallback(array('version:' => C('APIVer')));
    }

    /**
     * 调用失败
     * @access private
     * @author FrankKung <kongfanjian@andlisoft.com>
     */
    private function none() {
        $this->ret['success'] = true;
        $this->ret['status'] = -777;
        $this->ret['message'] = '接口调用失败';
        $this->apiCallback($this->ret);
    }

    /**
     * 根据表单生成查询条件
     * 进行列表过滤
     * @access protected
     * @param string $dwz_db_name 数据对象名称
     * @return HashMap
     * @throws ThinkExecption
     */
    protected function _search($dwz_db_name = '') {
        $dwz_db_name = $dwz_db_name ? $dwz_db_name : $this->getActionName();
        //生成查询条件
        $model = M($dwz_db_name);
        $map = array();
        foreach ($model->getDbFields() as $key => $val) {
            if (isset($_POST [$val]) && $_POST [$val] != '') {
                $map [$val] = I('post.' . $val);
            }
        }
        return $map;
    }

    /**
     * 验证Token
     * @access public
     * @param string $token
     * @return boolean
     */
    public function checkToken($token) {
        // 默认token
        $publicToken = md5(md5('feibao') . C('TOKEN_ALL') . md5('andlisoft'));

        // 验证用户token
        // if ( true ) {
        // return true;
        // exit(0);
        // } else
        if ($token === $publicToken) { // 验证默认token
            return true;
            exit(0);
        }
        return false;
    }

    /**
     * 校验IMEI字符串合法性
     * @access public
     * @param string $imei 
     * @return boolean 
     */
    public function checkImei($imei) {
        #code...
        return ture;
    }

    /**
     * 有用户uid的情况中判断用户状态
     */
    public function userStatus() {
        $userId = I("post.userId") ? I("post.userId") : I("post.userid");
        if ($userId) {
            userStatus($userId);
        }
    }

    /**
     * 客户端密钥验证
     */
    public function checkKey() {
        $this->_check->checkCode();
    }

}

?>