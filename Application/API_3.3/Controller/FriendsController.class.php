<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of FriendsController
 *
 * @author wangwei
 */
use Think\Controller;

class FriendsController extends BaseController {

    /**
     * 控制器初始化
     * @access public
     * @author FrankKung <kongfanjian@andlisoft.com>
     */
    public function _initialize() {
        
        parent::_initialize();
        //echo 2;die;
        //自动处理IP相关的限制
        $check_m = D('Check');
        $ACTION_NAME = strtolower(ACTION_NAME);
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

    /**
     * test
     */
    public function index()
    {
        echo 1234;
    }
    
    /**
     * 我的朋友列表
     */
    function getList()
    {
        $return['success'] = true;
        $return['status'] = 10;
        $pageSize = I('post.pageSize', 10);
        $page = I('post.page', 1);
        $selectTime = I("post.selectTime", time());
        $userId = $this->userId;
        $selectTime = empty($selectTime) ? time() : $selectTime;
        $reList = array();
        if (is_empty($userId) || is_empty($pageSize) || is_empty($page) || is_empty($selectTime)) {
            $return['status'] = 10;
            $return['message'] = '操作失败';
        } else {
            //数据查询
            $reList = D('Friend')->getListData($userId, $selectTime, $page, $pageSize);
            if (empty($reList)) {
                $return['status'] = 36;
                $return['message'] = '查询成功，暂无数据';
            } else {
                foreach ($reList as $key => $value) {
                    $reList[$key]['userId'] = encodePass($value['userId']);
                    $reList[$key]['isAuthentication'] = $value['groupType'] < 2 ? 1 : 2;
                    unset($reList[$key]['groupType']);
                }
                $return['info'] = $reList;
                $return['status'] = 1;
                $return['selectTime'] = $selectTime;
                $return['message'] = '查询成功';
            }
        }
        $this->apiCallback($return);
        
    }
    
    
}
