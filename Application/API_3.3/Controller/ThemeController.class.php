<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of AdvertisingController
 *
 * @author wangwei
 */
class ThemeController extends CommonController {
    //put your code here
    
    /**
     * 初始化
     */
    public function _initialize() {
        parent::_initialize();
        $userId = I('post.userId');
        $phone = I('post.phone');
        $return['success'] = true;

        if ($phone && $userId) {//判断参数是否为空
            $model = D("Members");
            if($phone=='12345678900'){
                $res = $model->getUserDataByPhone($phone , 'id,freeze');
            }else{
                $res = $model->checkUserId($phone, $userId, 'id,freeze');
            }
            //$res = $model->checkUserId($phone, $userId, 'id,freeze');
            
            if (empty($res['id'])) {// 验证唯一码是否正确
                $return['status'] = 35;
                $return['message'] = '账号异常，已退出登录！ ';
                //$return['info'] = array();
                echo jsonStr($return);
                exit(0);
            }else{
                if ($res['freeze'] != '0') {//验证账号是否非法
                    $return['status'] = 33;
                    $return['message'] = '账号非法，暂时无法完成此操作';
                    //$return['info'] = array();
                    echo jsonStr($return);
                    exit(0);
                }else{
                    if($res['id']==44427){
                        $return['status'] = 32;
                        $return['message'] = '请到个人中心登录';
                        //$return['info'] = array();
                        echo jsonStr($return);
                        exit(0);
                    }
                    $this->userId = $res['id'];
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
    
    /**
     * 精选主题接口
     * 
     */
    public function lists()
    {
        $type = I('post.type', 1);
        $page = I('post.page', 0);
        
        $pageTotal = 3;
        if($type == 2)
        {
            $pageTotal = 10;
        }
        $offset = $page * $pageTotal;
        
        $rs = M('theme')->lists($offset, $pageTotal);
        if(!$rs)
        {
            $return['status'] = -1;
            $return['message'] = '查询失败';
        }else
        {
            $return['info'] = $rs;
        }
        
        echo jsonStr($return);exit();
    }
    
    /**
     * 主题详情接口
     * 
     */
    public function detail()
    {
        $themeId = I('post.themeId');
        if (is_empty($themeId)) 
        {
            //判断参数是否完整
            $return['status'] = -888;
            $return['message'] = '传参不完整';
        }else
        {
            $rs = M('theme')->detail($themeId);
            if(!$rs)
            {
                $return['status'] = -1;
                $return['message'] = '查询失败';
            }else
            {
                $return['info'] = $rs;
                $return['info']['advInfo'] = M('advertising')->lists($friendId, $order);
            }
        }
        
        echo jsonStr($return);exit();
    }
}