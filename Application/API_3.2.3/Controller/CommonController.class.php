<?php

use Think\Controller;

/**
 * 海报 数据接口
 * @author Jine <luxikun@andlisoft.com>
 */
class CommonController extends Controller {

    public $_check;
    /**
     * 初始化
     */
    public function _initialize() {
        //有用户uid的情况中判断用户状态
        //$this->userStatus();
        // header('Content-Type:text/html; charset=UTF-8');
        header('Content-Type:application/json; charset=UTF-8');

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

        //自动处理IP相关的限制
        $this->_check = A('API_3.2.3/Check');
        writeGetUrlInfo();

        // A('API_3.2/Public')->testPublicToken();//验证 公共 token
        //修改 会员 更新时间
        $PublicOb = A('API_3.2.3/Public');
        $PublicOb->index();
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
