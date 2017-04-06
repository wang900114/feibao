<?php

use Think\Controller;

class FriendController extends BaseController {

    /**
     * 控制器初始化
     * @access public
     * @author FrankKung <kongfanjian@andlisoft.com>
     */
    public function _initialize() {
        parent::_initialize();
        //自动处理IP相关的限制
        $check_m = D('Check');
        $ACTION_NAME = strtolower(ACTION_NAME);
        $userId = I('post.userId');
        $phone = I('post.phone');
        $return['success'] = true;
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
     * 我的朋友列表
     */
    function getList() {
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
        $this->apiCallback($return);exit(0);
    }

    /**
     * 搜索我的朋友列表接口
     */
    function getSearchList() {
        $return['success'] = true;
        $return['status'] = 10;
        $pageSize = I('post.pageSize', 10);
        $content = I('post.content', '');
        $page = I('post.page', 1);
        $selectTime = I("post.selectTime", time());
        $userId = $this->userId;
        $selectTime = empty($selectTime) ? time() : $selectTime;
        $reList = array();
        if (is_empty($userId) || is_empty($pageSize) || is_empty($page) || is_empty($content) || is_empty($selectTime)) {
            $return['status'] = 10;
            $return['message'] = '操作失败';
        } else {
            //数据查询
            $reList = D('Friend')->getSearchListData($userId, $content, $selectTime, $page, $pageSize);
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
        $this->apiCallback($return);exit(0);
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
            $reList = D('Friend')->getShopInfo($friendId);
            if (empty($reList)) {
                $return['status'] = 36;
                $return['message'] = '查询成功，暂无数据';
            } else {
                $reList['content'] = base64_encode($reList['content']);
                $return['info'] = base64_encode(jsonStr($reList));
                $return['status'] = 1;
                $return['message'] = '查询成功';
            }
        }
        $this->apiCallback($return);exit(0);
    }

    /**
     * 搜索朋友列表
     */
    function searchFriend() {
        $return['success'] = true;
        $return['status'] = 10;
        $pageSize = I('post.pageSize', 10);
        $page = I('post.page', 1);
        $selectTime = I("post.selectTime", time());
        $userId = $this->userId;
        $content = I("post.content");
        $selectTime = empty($selectTime) ? time() : $selectTime;
        $reList = array();
        if (is_empty($userId) || is_empty($pageSize) || is_empty($page) || is_empty($selectTime) || is_empty($content)) {
            $return['status'] = 10;
            $return['message'] = '操作失败';
        } else {
            //数据查询
            $reList = D('Friend')->searchData($userId, $content, $page, $pageSize, $selectTime);
            //print_r($reList);
            if (empty($reList)) {
                $return['status'] = 36;
                $return['message'] = '查询成功，暂无数据';
            } else {
                $return['info'] = $reList;
                $return['status'] = 1;
                $return['selectTime'] = $selectTime;
                $return['message'] = '查询成功';
            }
        }
        $this->apiCallback($return);exit(0);
    }

    /**
     * 关注朋友
     */
    function attention() {
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
            $flag = D('Friend')->attention($userId, $friendId, $type);
            switch ($flag) {
                case 5:
                    $return['status'] = 1;
                    $return['message'] = $type == 1 ? '关注成功' : '取消成功';
                    break;
                case 8:
                    $return['status'] = 37;
                    $return['message'] = '操作太频繁';
                    break;
                case 4:
                    $return['status'] = 1;
                    $return['message'] = '已经关注';
                    break;
                default:
                    $return['status'] = 10;
                    $return['message'] = '操作失败';
                    break;
            }
        }
        $this->apiCallback($return);exit(0);
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
            $flag = D('Friend')->shield($userId, $friendId, $type);
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
        $this->apiCallback($return);exit(0);
    }

    /**
     * 他的发现列表
     */
    function found() {
        $return['success'] = true;
        $pageSize = I('post.pageSize', 10);
        $page = I('post.page', 1);
        $selectTime = I("post.selectTime", time());
        $userId = $this->userId;
        $friendId = I("post.friendId");
        $selectTime = empty($selectTime) ? time() : $selectTime;
        $reList = array();
        if (is_empty($userId) || is_empty($pageSize) || is_empty($page) || is_empty($selectTime) || is_empty($friendId)) {
            $return['status'] = 10;
            $return['message'] = '操作失败';
        } else {
            //数据查询
            $reList = D("Friend")->getFoundData($userId, $friendId, $page, $pageSize, $selectTime);
            if (empty($reList)) {
                $return['status'] = 36;
                $return['message'] = '查询成功，暂无数据';
                $return['info'] = array();
            } else {
                foreach ($reList as $k => $v) {
                    $reList[$k]['content'] = base64_encode(jsonStrWithOutBadWordsNew($v['content']));
                }
                $return['info'] = $reList;
                $return['status'] = 1;
                $return['selectTime'] = $selectTime;
                $return['message'] = '查询成功';
            }
        }
        $this->apiCallback($return);exit(0);
    }

    /**
     * 朋友转发给我的广告列表
     */
    function forward() {
        $return['success'] = true;
        $pageSize = I('post.pageSize', 10);
        $page = I('post.page', 1);
        $selectTime = I("post.selectTime", time());
        $lastTime = I("post.lastTime", time());
        $userId = $this->userId;
        $selectTime = empty($selectTime) ? time() : $selectTime;
        $reList = array();
        if (is_empty($userId) || is_empty($pageSize) || is_empty($page) || is_empty($selectTime) || is_empty($lastTime)) {
            $return['status'] = 10;
            $return['message'] = '操作失败';
        } else {
            $reList = D("Friend")->forward($userId, $lastTime, $page, $pageSize, $selectTime);
            if (empty($reList)) {
                $return['status'] = 36;
                $return['message'] = '查询成功，暂无数据';
                $return['info'] = array();
            } else {
                $return['info'] = $reList;
                $return['status'] = 1;
                $return['selectTime'] = $selectTime;
                $return['lastTime'] = $lastTime;
                $return['message'] = '操作成功';
            }
        }
        $this->apiCallback($return);exit(0);
    }

    /**
     * 朋友转发给我的广告列表
     */
    function searchForward() {
        $return['success'] = true;
        $pageSize = I('post.pageSize', 10);
        $page = I('post.page', 1);
        $selectTime = I("post.selectTime", time());
        $lastTime = I("post.lastTime", time());
        $content = I("post.content");
        $userId = $this->userId;
        $selectTime = empty($selectTime) ? time() : $selectTime;
        $reList = array();
        if (is_empty($userId) || is_empty($pageSize) || is_empty($page) || is_empty($selectTime) || is_empty($lastTime) || is_empty($content)) {
            $return['status'] = 10;
            $return['message'] = '操作失败';
        } else {
            $reList = D("Friend")->searchForward($userId, $content, $lastTime, $page, $pageSize, $selectTime);
            if (empty($reList)) {
                $return['status'] = 36;
                $return['message'] = '查询成功，暂无数据';
                $return['info'] = array();
            } else {
                $return['info'] = $reList;
                $return['status'] = 1;
                $return['selectTime'] = $selectTime;
                $return['lastTime'] = $lastTime;
                $return['message'] = '操作成功';
            }
        }
        $this->apiCallback($return);exit(0);
    }

    /**
     * 个人转发广告列表
     */
    function personalForward() {
        //echo 1;die;
        $return['success'] = true;
        $pageSize = I('post.pageSize', 10);
        $page = I('post.page', 1);
        $selectTime = I("post.selectTime", time());
        $userId = $this->userId;
        $friendId = I('post.friendId');
        $selectTime = empty($selectTime) ? time() : $selectTime;
        $reList = array();
        if (is_empty($userId) || is_empty($pageSize) || is_empty($page) || is_empty($selectTime) || is_empty($friendId)) {
            $return['status'] = 10;
            $return['message'] = '操作失败';
        } else {
            $reList = D("Friend")->personalForward($friendId, $userId, $page, $pageSize, $selectTime);
            if (empty($reList)) {
                $return['status'] = 36;
                $return['message'] = '查询成功，暂无数据';
                $return['info'] = array();
            } else {
                $friendId = decodePass($friendId);
                $lastFriendId =  encodePass($friendId);

                foreach ($reList as $k => $v) {
                    $mapP['dataId'] = $v['id'];
                    $mapP['userId'] = $userId;

                    $reB = D('ExposePosterLog')->selData($mapP, 1); //查询揭海报状态
                    $reList[$k]['isExpose'] = empty($reB[0]) ? '2' : '1';

                    //获取打包路径
                    $mapPP['dataId'] = $v['id'];
                    $dataP = D('PicturePoster')->selData($mapPP, '', 'field');
                    //$resPoster[$k]['field'] = empty($dataP) ? '':$dataP['field'];
                    $reList[$k]['field'] = 'http://dev.feibaokeji.com/Application/Home/View/Adinfo/index.html?id=3&userId=1&phone=12345678910';

                    //海报收藏状态
                    $rec = D('Members')->getUserCollectStatus($v['id'], $userId);
                    $reList[$k]['collectflag'] = $rec ? 1 : 2;

                    $resUser = D('Members')->getUserInfo($v['userId']);

                    //设置会员信息
                    $reList[$k]['userId'] = '';
                    $reList[$k]['nickname'] = '';
                    $reList[$k]['userImage'] = '';
                    $reList[$k]['forwardUserId'] = '';

                    $reList[$k]['id'] = encodePass($v['id']);
                    $reList[$k]['noteId'] = encodePass($v['noteId']);
                    //$friendId = decodePass($friendId);
                    $reList[$k]['forwardUserId'] = $lastFriendId;
                    if ($resUser['id']) {
                        $reList[$k]['userId'] = encodePass($v['userId']);
                        //$reList[$k]['forwardUserId'] = encodePass($v['forwardUserId']);
                        $reList[$k]['nickname'] = $resUser['name'];
                        $reList[$k]['userImage'] = $resUser['imageUrl'];
                    }
                }

                $return['info'] = $reList;
                $return['status'] = 1;
                $return['selectTime'] = $selectTime;
                $return['lastTime'] = $lastTime;
                $return['message'] = '操作成功';
            }
        }
        $this->apiCallback($return);exit(0);
    }

}
