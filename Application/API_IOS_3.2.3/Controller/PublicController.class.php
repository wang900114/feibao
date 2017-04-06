<?php

use Think\Controller;

/**
 * 公共模块
 * @author Jine <luxikun@andlisoft.com>
 */
class PublicController extends Controller {

    /**
     * 修改 会员 更新时间
     * @access public
     * @param int $userId 会员ID（如果为空，就去$_POST找）
     * @param null 
     */
    public function index($userId = '') {
        $mapUpUser['id'] = empty($userId) ? $_POST['userId'] : $userId;
        $dataUpUser['updateTime'] = time();
        $reUpUser = D('Members')->upData($mapUpUser, $dataUpUser);
        // var_dump($reUpUser);
    }

    /**
     * 店铺详情、发现详情 点击量+1
     * 去除会员自己进入的情况
     * @access public
     * @param int $dataId 主键ID
     * @param int $userId 会员ID
     * @param int $type 1发现;2店铺
     * @param null 
     */
    public function setAccessCount($dataId, $userId, $type) {
        if (!empty($dataId) && !empty($userId) && !empty($type)) {
            if ($type == 1) {//发现
                $model = D('Found');
            } else {//店铺
                $model = D('Shop');
            }

            $mapUpUser['id'] = $dataId;
            $re = $model->selData($mapUpUser, 1, 'id,userId');
            if (!empty($re)) {
                if ($re[0]['userId'] != $userId) {//非自己
                    $re = $model->setColunm($mapUpUser, 'click', 1);
                    // return $re;
                }
            }
        }
    }

    /**
     * 判断归属地
     * @param  string $phone 代充值手机号
     * @param  string $money 面额
     * @return 
      -2534;//'运营商充值失败';
      oufei欧飞；gaoyang高阳
     */
    public function roleOfRecharge() {
        $re = D('PosterBillLog')->roleOfRecharge(I('post.phone'), I('post.money'));
        // var_dump($re);
    }

    //验证 公共 token
    public function testPublicToken() {
        // var_dump('testPublicToken');
//        if ($_SERVER['SERVER_ADDR'] == '127.0.0.1') return true;
        if ($_POST['token'] != md5(md5('feibao') . C('TOKEN_ALL') . md5('andlisoft'))) {
            $return['status'] = -999;
            $return['message'] = '公共token错误';
            echo jsonStr($return);
            die();
        }
    }

    //验证 个人 token
    public function testPersonalToken() {
//        if ($_SERVER['SERVER_ADDR'] == '127.0.0.1') return true;
        // var_dump('testPersonalToken');die;
        $re = M('Members')->field('code')->where(array('id' => $_POST['userId']))->find(); //查询飞报号
        //var_dump(md5(md5($_POST['userId']).C('TOKEN_ALL').md5($re['code'])));die;

        if ($_POST['token'] != md5(md5($_POST['userId']) . C('TOKEN_ALL') . md5($re['code']))) {
            $return['status'] = -999;
            $return['message'] = '个人token错误';
            echo jsonStr($return);
            die();
        }
    }

    //必须https访问
    public function testHTTPS() {
        if ($_SERVER['SERVER_ADDR'] == '127.0.0.1')
            return true;

        if ($_SERVER["HTTPS"] != 'on') {
            $return['status'] = -12;
            //$return['message'] = '必须使用https访问';
            $return['message'] = $_SERVER["HTTPS"];
            echo jsonStr($return);
            die();
        }
    }
}
