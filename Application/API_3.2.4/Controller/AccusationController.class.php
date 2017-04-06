<?php

/**
 * 举报 - 数据接口
 * @author Miko <wangmeihui@andlisoft.com>
 */
class AccusationController extends CommonController {

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
     * 举报接口 - 增加举报信息
     * @param  string $token 令牌
     * @param  string $dataId 数据ID
     * @param  string $userId 会员ID(举报人)
     * @param  string $category 举报内容的类别
     * @return JSON 是否成功 status -1 失败  1 成功
     */
    public function addAccusation() {
        $return['success'] = true;
        //获取参数
        $dataId = I('post.dataId');
        $userId = $this->userId;
        $category = I('post.category');
        $content = I('post.content', '', 'trim');
        $options = I('post.options', '6');
        $return['status'] = 10;
        $return['message'] = '操作失败';
        $return['success'] = true;
        $dataId = decodePass($dataId);
        if (is_empty($dataId) || is_empty($category) || is_empty($options)) {
            $return['status'] = 10;
        } else {
            if (in_array($category, array(1, 2, 3))) {
                switch ($category) {
                    case 1:
                        $map['id'] = $dataId;
                        $self = M('poster_advert')->where($map)->find();
                        if ($self['userId'] == $userId) {
                            $return['status'] = -631;
                            $return['message'] = '不能举报自己';
                            echo jsonStr($return);
                            exit(0);
                        }
                        break;
                    case 2:
                        $map['id'] = $dataId;
                        $self = M('Found')->where($map)->find();
                        if ($self['userId'] == $userId) {
                            $return['status'] = -631;
                            $return['message'] = '不能举报自己';
                            echo jsonStr($return);
                            exit(0);
                        }
                        break;
                    case 3:

                        if ($dataId == $userId) {
                            $return['status'] = -631;
                            $return['message'] = '不能举报自己';
                            echo jsonStr($return);
                            exit(0);
                        }
                        break;
                }
                

                //查询数据是否正常显示（删除等信息不做以下操作）
                if ($category == 2) {
                    $data = D("Found")->where(array('id' => $dataId, "del" => "1"))->field("id")->find();
                }
                if ($category == 1) {
                    //$data = M("poster_advert")->where(array('id' => $dataId, "status" => 1))->field("id")->find();
                    $data = M("poster_advert")->where(array('id' => $dataId))->field("id,status,integral,endTime,exposeTotalIntegral,extendTotalIntegral")->find();
                    //echo M("poster_advert")->getLastSql();die;
                    
                    if($data){//验证广告是否正常
                        if($data['status']!=1){
                            $return['status'] = 10;
                            $return['message'] = '广告已下架';
                            echo jsonStr($return);exit();
                        }else{
                            //echo $data['integral'].'-'.$data['exposeTotalIntegral'].'-'.$data['extendTotalIntegral'];die;
                            if($data['integral']-$data['exposeTotalIntegral']-$data['extendTotalIntegral']<=0 || time()>$data['endTime']){
                                $return['status'] = 10;
                                $return['message'] = '广告已下架';
                                echo jsonStr($return);exit();
                            }
                        }
                    }
                }
                if ($category == 3) {
                    $data = D("Members")->where(array('id' => $dataId, "freeze" => "0"))->field("id")->find();
                }
                if (empty($data)) {
                    $return['status'] = 10;
                    $return['message'] = '操作失败';
                    //$return['success'] = true;
                    $return['flag'] = '0';
                } else {
                    $data = array(
                        'userId' => $userId,
                        'dataId' => $dataId,
                        'content' => $content,
                        'moduleType' => $category,
                        'type' => $options,
                        'addTime' => time()
                    );
                    $res = M('accusation')->add($data);
                    if ($res == true) {
                        $return['status'] = 1;
                        $return['message'] = '举报成功';
                    } else {
                        $return['status'] = 10;
                        $return['message'] = '操作失败';
                    }
                }
            }
        }
        header("Content-Type: application/json; charset=utf-8");
        //$this->apiCallback($return);
        echo jsonStr($return);
        exit(0);
    }

}
