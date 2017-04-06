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
//                    if ($res['id'] == 44427) {
//                        $return['status'] = 32;
//                        $return['message'] = '请到个人中心登录';
//                        $return['info'] = array();
//                        echo jsonStr($return);
//                        exit(0);
//                    }
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
     * 我的朋友列表
     */
    function getList()
    {
        $return['success'] = true;
        $return['status'] = 10;
        $userId = $this->userId;
        $reList = array();
        if (is_empty($userId)) {
            $return['status'] = 10;
            $return['message'] = '操作失败';
        } else {
            //数据查询
            $reList = D('Friends')->getListData($userId);
            if (empty($reList)) {
                $return['status'] = 36;
                $return['message'] = '查询成功，暂无数据';
            } else {
                foreach ($reList as $key => $value) {
                    $reList[$key]['userId'] = encodePass($value['userId']);
                }
                $return['info'] = $reList;
                $return['status'] = 1;
                $return['selectTime'] = $selectTime;
                $return['message'] = '查询成功';
            }
        }
        echo jsonStr($return);exit;
//        $this->apiCallback($return);
        
    }
    
  
    /**
     * 添加朋友列表接口
     */
    function searchFriend() {
        $return['success'] = true;
        $return['status'] = 10;
        $content = I('post.searchContent', '');
        $page = I('post.page', 0);
        $pageSize = 10;
        $page = $page * $pageSize;
        $userId = $this->userId;
        $reList = array();
        if (is_empty($userId) || is_empty($content)) {
            $return['status'] = 10;
            $return['message'] = '操作失败';
        } else {
            //数据查询
            if(strpos($content, "飞报官方") || $content == "飞报")
            {
                $return['status'] = 36;
                $return['message'] = '查询成功，暂无数据';
                $return['message'] = '没有找到相关结果';
            }else{
                $reList = D('Friends')->getSearchListData($userId, $content, $page, $pageSize);

                if (empty($reList)) {
                    $return['status'] = 36;
//                    $return['message'] = '查询成功，暂无数据';
                    $return['message'] = '没有找到相关结果';
                } else {
                    foreach ($reList as $key => $value) {
                        $reList[$key]['userId'] = encodePass($value['userId']);
                    }
                    $return['info'] = $reList;
                    $return['status'] = 1;
                    $return['message'] = '查询成功';
                }
            }
        }
        echo jsonStr($return);exit;
//        $this->apiCallback($return);
    }
    
    
   /**
    * 搜索通讯录中朋友列表接口
    */
    public function searchForward()
    {
        $userId = $this->userId;
        $content = I('post.searchContent', '');
//        $content = ' {  "content" : [    "13681260087", "18500540715"  ]}';
//        $content = '{"content":["18201415541","13681260087","15069005222"]}';
//        var_dump($userId, $content);exit;
        
        $return['success'] = 1;
        if ( is_empty($userId) || is_empty($content) ) {
            $return['status'] = 10;
            $return['message'] = '操作失败';
        } else {
            //数据查询
            $contentArr = json_decode(str_replace('&quot;', '"', $content), true);
            foreach($contentArr as $k => $v)
            {
                $contentArr[$k]=preg_replace("#[^0-9]#",'',$v);
            }
//                $contentArr = json_decode($content, true);
            $contentStr = implode(',', $contentArr);
            
            
            $reList = D('Friends')->getSearchListByPhone($userId, $contentStr);
            if (empty($reList)) {
                $return['status'] = 36;
                $return['info'] = array();
                $return['message'] = '查询成功，暂无数据';
            } else {
                foreach ($reList as $key => $value) {
                    $reList[$key]['userId'] = encodePass($value['userId']);
//                    $reList[$key]['isAuthentication'] = $value['groupType'] < 2 ? 1 : 2;
//                    unset($reList[$key]['groupType']);
                }
                $return['info'] = $reList;
                $return['status'] = 1;
                $return['message'] = '查询成功';
            }
        }
        $this->apiCallback($return);
    }
    
    
    /**
     * 会员店铺详情
     */
    function getShopInfo() {

        $return['success'] = true;
        $return['status'] = 10;
        $userId = $this->userId;
        $friendId = I('post.friendId');
        $reList = array();
        if (is_empty($userId) || is_empty($friendId)) {
            $return['status'] = 10;
            $return['message'] = '操作失败';
        } else {
            //数据查询
            $reList = D('Friends')->getShopInfo($friendId);
            if (empty($reList)) {
                $return['status'] = 36;
                $return['message'] = '查询成功，暂无数据';
            } else {
                if($reList['image1'])
                {
                    $reList['image'] = $reList['image1'];
                }else if($reList['image2'])
                {
                    $reList['image'] = $reList['image5'];
                }else if($reList['image3'])
                {
                    $reList['image'] = $reList['image5'];
                }else if($reList['image4'])
                {
                    $reList['image'] = $reList['image5'];
                }else if($reList['image5'])
                {
                    $reList['image'] = $reList['image5'];
                }
                unset($reList['image1']);
                unset($reList['image2']);
                unset($reList['image3']);
                unset($reList['image4']);
                unset($reList['image5']);
                $reList['content'] = base64_encode($reList['content']);
                $return['info'] = $reList;
                $return['status'] = 1;
                $return['message'] = '查询成功';
            }
        }
        $this->apiCallback($return);exit();
    }
    
    /**
     * 屏蔽朋友
     */
    function shield() {
        $return['success'] = true;
        $return['status'] = 10;
        $userId = $this->userId;
        $friendId = I("post.friendId");
        $type = I("post.type", '1');
        $reList = array();
        if (is_empty($friendId) || is_empty($type)) {
            $return['status'] = 10;
            $return['message'] = '操作失败';
        } else {
            //数据查询
            $flag = D('Friends')->shield($userId, $friendId, $type);
            switch ($flag) {
                case 5:
                    $return['status'] = 1;
                    $return['message'] = '操作成功';
                    break;
                case 8:
                    $return['status'] = 37;
                    $return['message'] = '操作太频繁';
                    break;
                case 4:
                    $return['status'] = 1;
                    $return['message'] = '已经屏蔽';
                    break;
                default:
                    $return['status'] = 10;
                    $return['message'] = '操作失败';
                    break;
            }
        }
        $this->apiCallback($return);exit();
    }
    
    /**
     * 消息列表接口
     */
    public function messageUserList()
    {
        $return['success'] = true;
        $return['lastAddTime'] = 0;
        $pageSize = I('post.pageSize', 20);
        $lastAddTime = I('post.lastAddTime', 0);
//        $page = I('post.page', 0);
//        $page = $page * $pageSize;
        $userId = $this->userId;
        $phone = I('post.phone');
        //游客没有消息
        if ($phone == 12345678900) {
            $return['status'] = 32;
            $return['message'] = '请到个人中心登录';
            $return['info'] = array();
            echo jsonStr($return);
            exit();
        }
        
        $reList = array();
        if (is_empty($userId)) {
            $return['status'] = 10;
            $return['message'] = '操作失败';
        } else {
            $reLists = D("Friends")->forwardUserList($userId, $lastAddTime, $pageSize);
//            var_dump($reLists);die;
            if($lastAddTime==0){
                //获取官方转发的信息
                $reOfficialList = D("Friends")->officialForwardUser();
                if($reOfficialList){
                    foreach($reOfficialList as $k => $v)
                    {
                        $reOfficialList[$k]['userId'] = encodePass($v['userId']);
                        $reOfficialList[$k]['typeGuan'] = 1;
                        $reOfficialList[$k]['isNew'] = 2;
                    }
                    if($reLists){
                        $arr_num = count($reLists);
                        foreach($reLists as $k => $v)
                        {
                            $reLists[$k]['userId'] = encodePass($v['userId']);
                            $reLists[$k]['typeGuan'] = 2;
                            if($arr_num == $k + 1)
                            {
                                $return['lastAddTime'] = $v['addTime'];
                            }
                        }
                        $reList = array_merge($reOfficialList, $reLists);
                    }else{
                        $reList = $reOfficialList;
                    }
                }else{
                    if($reLists){
                        $arr_num = count($reLists);
                        foreach($reLists as $k => $v)
                        {
                            $reLists[$k]['userId'] = encodePass($v['userId']);
                            $reLists[$k]['typeGuan'] = 2;
                            if($arr_num == $k + 1)
                            {
                                $return['lastAddTime'] = $v['addTime'];
                            }
                        }
                        $reList = array_merge($reOfficialList, $reLists);
                    }else{
                        $reList = array();
                    }
                }
            }
            
            if (empty($reList)) {
                $return['status'] = 36;
                $return['message'] = '查询成功，暂无数据';
                $return['info'] = array();
            } else {
                $return['info'] = $reList;
                $return['status'] = 1;
                $return['message'] = '操作成功';
            }
        }
//        print_r($return);exit;
//        echo json_encode(array('message' => '这是个测试'));exit;
        $this->apiCallback($return);exit();
    }
    
    /**
     * 消息详情接口
     */
    public function userMessageList()
    {
        $return['success'] = true;
        $return['lastAddTime'] = 0;
        $friendId = I('post.friendId');
        $friendId = decodePass($friendId);
        $pageSize = I('post.pageSize', 20);
        $lastAddTime = I('post.lastAddTime', 0);
        
        $userId = $this->userId;
        $reList = array();
        if (is_empty($userId) || is_empty($friendId)) {
            $return['status'] = 10;
            $return['message'] = '操作失败';
        } else {
            $sql3 = "update lu_new_message set `isNew` = '2' where `userId` = $userId and `friendId` = $friendId ";
            M()->execute($sql3);
            
            $reLists = D("Friends")->messageDetail($userId, $friendId, $lastAddTime, $pageSize);
            if($reLists && is_array($reLists)){
                $arr_num = count($reLists);
                foreach($reLists as $k => $v)
                {
                    if($userId == $v['friendId'])
                    {
                        $reLists[$k]['isForward'] = 1;
                    }else
                    {
                        $reLists[$k]['isForward'] = 2;
                    }
                    $reLists[$k]['friendId'] = encodePass($v['friendId']);
                    
                    if($arr_num == $k + 1)
                    {
                        $return['lastAddTime'] = $v['addTime'];
                    }
                }
            }else
            {
                $reLists = array();
            }
            
            if (empty($reLists)) {
                $return['status'] = 36;
                $return['message'] = '查询成功，暂无数据';
                $return['info'] = array();
            } else {
                $return['info'] = $reLists;
                $return['status'] = 1;
                $return['message'] = '操作成功';
            }
        }
        $this->apiCallback($return);exit();
    }
    
    /**
     * 官方消息详情接口
     */
    public function officialMessageDetailList()
    {
        $return['success'] = true;
        $return['lastAddTime'] = 0;
        $pageSize = I('post.pageSize', 20);
        $lastAddTime = I('post.lastAddTime', 0);
        $return['lastAddTime'] = $lastAddTime;
        
        $where = " where `status` = '2' and `is_recommend` = '1' ";
        if($lastAddTime > 0)
        {
            $where .= " and recommend_time < {$lastAddTime} ";
        }
        $sql = "select id as dataId, image, title, recommend_time from lu_advertising_base $where order by recommend_time desc limit $pageSize";
        $reLists = M()->query($sql);
//        var_dump($sql);

        if (empty($reLists)) {
            $return['status'] = 36;
            $return['message'] = '查询成功，暂无数据';
            $return['info'] = array();
        } else {
            $field = " id, image ";
            $where = " where `name` = '飞报官方推荐' ";
    //        $where = "";
            $order = " order by id desc ";
            $limit = " limit 1 ";
            $sql = "select $field from lu_members $where $order $limit ";
            $userInfo = M()->query($sql);
            $arr_num = count($reLists);
            foreach($reLists as $k => $v)
            {
                $reLists[$k]['isForward'] = 2;
                $reLists[$k]['type'] = 1;
                $reLists[$k]['friendImage'] = $userInfo[0]['image'];
                $reLists[$k]['friendId'] = $userInfo[0]['id'];
                $reLists[$k]['addTime'] = $v['recommend_time'];
                if($arr_num == $k + 1)
                {
                    $return['lastAddTime'] = $v['recommend_time'];
                }
            }
            $return['info'] = $reLists;
            $return['status'] = 1;
            $return['message'] = '操作成功';
        }
       $this->apiCallback($return);exit();
    }
    
    
}
