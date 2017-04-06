<?php

/**
 * 踩 - 数据接口
 * @author Miko <yuanmingwei@feibaokeji.com>
 */
class TreadController extends BaseController {

    /**
     * 初始化
     */
    public function _initialize() {
        //A('API_3.2/Public')->testPersonalToken(); //验证 个人 token
        parent::_initialize();
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

            if (empty($res['id'])) {//先判断账号是否存在
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

                    if ($res['id'] == 44427) {//判断是否是游客
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
     * 发现踩接口 - 添加踩信息
     * @param  string $token 令牌
     * @param  string $version 版本号(如"1.2")
     * @param  string $dataId 数据ID
     * @param  string $userId 会员ID
     * @return JSON 	
     */
    public function found() {
        //获取参数
        $token = I('post.token', '1.2', 'trim');
        $userId = I('post.userId', '', 'trim', 'intval');
        $dataId = I('post.id', '', 'trim', 'intval');
        $version = I('post.version', '1.2', 'trim');
        $ret['success'] = true;
        //echo $dateId;die;

        header('Content-Type:application/json; charset=UTF-8');

        if (is_empty($dataId) || is_empty($userId) || $userId < 0) {
            $ret['status'] = -888;
            $ret['message'] = '传参不完整';
        } else {
            $result = D("Members")->getUserInfo($userId);
            if (empty($result)) {
                $ret['status'] = -1;
                $ret['message'] = '传参不完整';
            } else {
                $data = D("TreadFoundLog")->getFoundById($dataId);
                if (empty($data)) {
                    $ret['status'] = -10;
                    $ret['message'] = '数据不存在、或非法传参';
                    $ret['flag'] = 0;
                } else {
                    //查询是否已踩过
                    $exist = D('TreadFoundLog')->treadFoundByDidAndUid($userId, $dataId);
                    if (is_bool($exist) || !empty($exist)) {
                        $ret['status'] = -621;
                        $ret['message'] = '您已经踩过了';
                        $ret['flag'] = '1';
                    } else {
                        //添加到数据库
                        $res = D('TreadFoundLog')->treadFoundAdd($userId, $dataId);
                        if ($res == true) {
                            $ret['status'] = 1;
                            $ret['message'] = '添加成功';
                            $ret['flag'] = 1;
                        } else {
                            $ret['status'] = -1;
                            $ret['message'] = '添加失败';
                            $ret['flag'] = 0;
                        }
                    }
                }
            }
        }
        $this->apiCallback($ret);exit(0);
    }

}
