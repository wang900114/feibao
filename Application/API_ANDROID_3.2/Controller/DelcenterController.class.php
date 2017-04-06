<?php

/**
 * 删除 - 数据接口
 * @author Miko <wangmeihui@andlisoft.com>
 */
class DelcenterController extends CommonController {

    /**
     * 初始化
     */
    public function _initialize() {
        parent::_initialize();
        //A('API_3.2/Public')->testPersonalToken();//验证 个人 token
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

            if (empty($res['id'])) {// 验证唯一码是否正确
                $return['status'] = 35;
                $return['message'] = '账号异常，已退出登录！ ';
                //$return['info'] = array();
                echo jsonStr($return);
                exit(0);
            } else {
                if ($res['freeze'] != '0') {//验证账号是否非法
                    $return['status'] = 33;
                    $return['message'] = '账号非法，暂时无法完成此操作';
                    //$return['info'] = array();
                    echo jsonStr($return);
                    exit(0);
                } else {
                    if ($res['id'] == 44427) {
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
     * categoryId2ModelName
     * @access private
     * @return string Model Name
     */
    private function categoryId2ModelName($category) {
        switch ($category) {  // 3海报; 7消息
            case '7':
                $modelName = 'MessageUser';
                break;
            case '3':
                $modelName = 'ExposePosterLog';
                break;
        }
        return $modelName;
    }

    /**
     * 删除 - 删除接口
     * @param  string $token 令牌
     * @param  string $dataId 数据ID
     * @param  string $userId 会员ID
     * @param  string $category 操作的表
     * @return JSON 是否成功 status -1 失败  1 成功
     */
    public function delData() {
        $return['success'] = true;

        //获取参数
        $token = I('post.token', '', 'trim');
        $dataId = I('post.dataId', '', 'trim', 'intval');
        $userId = I('post.userId', '', 'trim', 'intval');
        $phone = I('post.phone');
        $category = I('post.category', '', 'trim', 'intval');

        $modelName = $this->categoryId2ModelName($category);

        if (is_empty($userId) || is_empty($category) || is_empty($dataId)) {
            $return['status'] = -888;
            $return['message'] = '参数不完整';
            echo jsonStr($return);exit();
        } else {
            //判断会员唯一码是否有效，若无效直接返回未操作状态、会员唯一码状态
            $model = D("Members");
            $field = 'id,uniqueId as userId,name,jpush,phone,image,imageUrl,integral,cityId,provinceId,freeze,handlePassword,type';
            $res = $model->checkUserId($phone, $userId, $field);
            if ($res['id']) {//首先判断会员id是否正确
                $return['userStatus'] = 1;
                if ($res['freeze'] != '0') {//验证账号是否非法
                    //$return['userStatus'] = 34;
                    $return['status'] = -1;
                    $return['message'] = '删除失败';
                    echo jsonStr($return);
                    exit();
                }
            } else {
                //$return['userStatus'] = 35;
                $return['status'] = -1;
                $return['message'] = '删除失败';
                echo jsonStr($return);
                exit();
            }

            $userId = $res['id'];
            $dataId = decodePass($dataId);

            //echo $userId;die;
            if ($category == 3) {// 3海报; 7消息
                $map = array(
                    'userId' => $userId,
                    'dataId' => $dataId
                );
            } else {
                $map = array(
                    'id' => $dataId,
                    'userId' => $userId
                );
            }

            if ($category == '3') {//海报，是逻辑删除
                $data['status'] = '0';
                $res = D($modelName)->upData($map, $data);
            } else {
                $res = M('MembersDope')->where(' id =' . $dataId . ' and userId=' . $userId)->delete(); //D($modelName)->where($map)->delete();
            }

            header('Content-Type:application/json; charset=UTF-8');

            if ($res == true) {
                $return['status'] = 1;
                $return['message'] = '删除成功';
                echo jsonStr($return);exit();
            } else {
                $return['status'] = -1;
                $return['message'] = '删除失败';
                echo jsonStr($return);exit();
            }
        }
    }

}
