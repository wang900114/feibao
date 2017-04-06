<?php

/**
 * 收藏 - 数据接口
 * @author Miko <wangmeihui@andlisoft.com>
 */
class CollectController extends CommonController {

    protected $userId;

    /**
     * 初始化
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
            if($phone=='12345678900') {
                $res = $model->getUserDataByPhone($phone, 'id,freeze');
            } else {
                $res = $model->checkUserId($phone, $userId, 'id,freeze');
            }
            $res = $model->checkUserId($phone, $userId, 'id,freeze');

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
    private function categoryId2ModelName($type) {
        switch ($type) {  // 1头条（轮播、非轮播都有）；2店铺；3海报；4发现；6本地新闻
            case '6':
                $modelName = 'CollectLocalnewsLog';
                break;
            case '4':
                $modelName = 'CollectFoundLog';
                break;
            case '3':
                $modelName = 'CollectPosterLog';
                break;
            case '2':
                $modelName = 'CollectShopLog';
                break;
            case '1':
                $modelName = 'CollectNewsLog';
                break;
        }
        return $modelName;
    }

    /**
     * 收藏接口 - 添加收藏信息
     * @param  string $token 令牌
     * @param  string $version 版本号(如"1.2")
     * @param  string $dataId 数据ID
     * @param  string $userId 会员ID
     * @param  string $type 收藏内容的类别
     * @return JSON 	
     */
    public function add() {
        //获取参数
        $type = I('post.type', '', 'trim');
        //$token = I('post.token', '1.2', 'trim');
        //$userId = I('post.userId');
        $userId = $this->userId;
        $dataId = I('post.id');
        $version = I('post.version', '1.2', 'trim');
        $modelName = $this->categoryId2ModelName($type);
        $dataId = decodePass($dataId);

        if (is_empty($dataId) || is_empty($userId) || is_empty($type)) {
            $return['status'] = 10;
            //$return['message'] = '传参不完整';
            $return['message'] = '操作失败';
            $return['success'] = true;
        } else {
            //查询数据是否正常显示（删除等信息不做以下操作）
            if ($type == 4) {
                $data = D("Found")->where(array('id' => $dataId, "del" => "1"))->field("*")->find();
            }
            if ($type == 3) {
                //$data = M('PosterAdvert')->where(array('id' => $dataId, "status" => "1"))->field("*")->find();
                $data = M('PosterAdvert')->where(array('id' => $dataId))->field("*")->find();
            }
            if ($type == 2) {
                $data = D("Shop")->where(array('id' => $dataId, "status" => "1"))->field("*")->find();
            }

            if (empty($data)) {
                $return['status'] = 10;
                $return['success'] = true;
                //$return['message'] = '数据不存在、或非法传参';
                $return['message'] = '操作失败';
                $return['flag'] = 0;
            } else {
                $map['userId'] = $userId;
                $map['dataId'] = $dataId;
                if ($type == 3) {
                    $exist = M('CollectPosterLog')->where($map)->find();
                    M('PosterAdvert')->where(array('id' => $dataId))->setInc('collectTotal', 1);
                } else {
                    $exist = D($modelName)->where($map)->find();
                }
                //echo M('CollectPosterLog')->getLastSql();die;

                if (is_bool($exist) || !empty($exist)) {
                    $return['status'] = -611;
                    $return['message'] = '请勿重复收藏';
                    $return['success'] = true;
                    $return['flag'] = 1;
                } else {
                    $data = array(
                        'userId' => intval($userId),
                        'dataId' => intval($dataId),
                        'addTime' => time()
                    );

                    $res = D($modelName)->add($data);
                    //echo D($modelName)->getLastSql();die;

                    if ($res == true) {
                        $return['status'] = 1;
                        $return['success'] = true;
                        $return['message'] = '收藏成功';
                        $return['flag'] = 1;
                    } else {
                        $return['status'] = -1;
                        $return['success'] = true;
                        $return['message'] = '操作失败';
                        $return['flag'] = 0;
                    }
                }
            }
        }
        header('Content-Type:application/json; charset=UTF-8');
        echo jsonStr($return);exit(0);
    }

    /**
     * 收藏接口 - 取消收藏信息
     * @param  string $token 令牌
     * @param  string $version 版本号(如"1.2")
     * @param  string $d 数据ID
     * @param  string $userId 会员ID
     * @param  string $type 收藏内容的类别
     * @return JSON 	
     */
    public function cancel() {
        $type = I('post.type', '', 'trim');
        //$token = I('post.token', '', 'trim');
        $dataId = I('post.id', '', 'trim');
        //$userId = I('post.userId', '', 'trim');
        $userId = $this->userId;
        $version = I('post.version', '1.2', 'trim');
        $modelName = $this->categoryId2ModelName($type);
        header('Content-Type:application/json; charset=UTF-8');
        $dataId = decodePass($dataId);
        if (is_empty($dataId) || is_empty($userId) || is_empty($type)) {
            $return['status'] = 10;
            //$return['message'] = '传参不完整';
            $return['message'] = '操作失败';
            $return['success'] = true;
            echo jsonStr($ret);exit(0);
        } else {


            $map = array(
                'userId' => intval($userId),
                'dataId' => intval($dataId)
            );

            $res = D($modelName)->where($map)->delete();

            $map['userId'] = $userId;
            $map['dataId'] = $dataId;
            $exist = D($modelName)->where($map)->find();
            if (is_bool($exist) || !empty($exist)) {
                $flag = 1;
            } else {
                $flag = 0;
            }

            if ($res == true) {
                if ($type == 3) {
                    M('PosterAdvert')->where(array('id' => $dataId))->setDec('collectTotal', 1);
                }

                $return['status'] = 1;
                $return['success'] = true;
                $return['message'] = '取消成功';
                $return['flag'] = $flag;
                echo jsonStr($return);exit(0);
            } else {
                $return['status'] = -1;
                $return['success'] = true;
                $return['message'] = '取消失败';
                $return['flag'] = $flag;
                echo jsonStr($return);exit(0);
            }
        }
    }

    /**
     * 查询收藏关系
     * @author xiaofeng <yuanmingwei@feibaokeji.com>
     */
    function getFoundCollectStatus() {
        header('Content-Type:application/json; charset=UTF-8');

        $token = I('post.token', '', 'trim');
        $dataId = I('post.id', '', 'trim');
        //$userId = I('post.userId', '', 'trim');
        $userId = $this->userId;
        $version = I('post.version', '1.2', 'trim');
        $dataId = decodePass($dataId);
        $result = D('CollectFoundLog')->getFoundCollectStatus($userId, $dataId);

        if ($result) {

            $return['status'] = 1;
            $return['success'] = true;
            $return['message'] = '操作成功';
            $return['info'] = $result['status'];
        } else {

            $return['status'] = 1;
            $return['success'] = true;
            $return['message'] = '操作成功';
            $return['info'] = $result['status'] ? $result['status'] : 0;
        }

        echo jsonStr($return);exit(0);
    }

}
