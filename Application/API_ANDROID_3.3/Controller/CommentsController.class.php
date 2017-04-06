<?php

/**
 * 评论接口
 * @author FrankKung <kongfanjian@andlisoft.com>
 */
class CommentsController extends BaseController {

    protected $userId;

    /**
     * 控制器初始化
     * @access public
     * @author FrankKung <kongfanjian@andlisoft.com>
     */
    public function _initialize() {
        parent::_initialize();

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
                    if (in_array($ACTION_NAME, array('commentslist'))) {
                        $this->userId = $res['id'];
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
     * @param string $type
     * @return string Model Name
     * @author FrankKung <kongfanjian@andlisoft.com>
     */
    private function categoryId2ModelName($category) {
        switch ($category) {//1头条（非轮播图）；2店铺；3海报；4发现；5头条（轮播图）；6发现模块-本地新闻
            case '6':
                $modelName = 'CommentsLocalnews';
                break;
            case '5':
                $modelName = 'CommentsNewsCarousel';
                break;
            case '4':
                $modelName = 'CommentsFound';
                break;
            case '3':
                $modelName = 'CommentsPoster';
                break;
            case '2':
                $modelName = 'CommentsShop';
                break;
            case '1':
                $modelName = 'CommentsNewsNormal';
                break;
            // default:
            // $modelName = 'CommentsNewsNormal';
            // break;
        }
        return $modelName;
    }

    /**
     * 评论列表
     * @access public
     * @param string $dataId 相关板块下的数据的ID值(比如头条ID)
     * @param string $type 
     * @param string $id
     * @param string $pageSize
     * @param string $category
     * @author FrankKung <kongfanjian@andlisoft.com>
     */
    public function commentsList() {

        header('Content-Type:application/json; charset=UTF-8');

        // 获取参数
        $dataId = I('post.dataId');
        $type = I('post.type', 0);
        $id = I('post.id', 0);
        $pageSize = I('post.pageSize', 5);
        $category = I('post.category');

        $dataId = decodePass($dataId);
        $id = decodePass($id);

        if (is_empty($dataId) || is_empty($category)) {// 参数检测
            $this->ret['status'] = -888;
            $this->ret['message'] = '参数不完整';
            $this->apiCallback($this->ret);
        }

        // 查询条件

        if($category==4){//获取正常的发现评论信息
             $map['status']='1';
        }

        if ($type) {//判断评论类型
            $map['id'] = array('lt', $id);
        } else {
            $map['id'] = array('gt', $id);
        }
        
        $map['dataId'] = $dataId;

        $field = 'id,userId,name,head,content,time'; // 查询字段
        $order['id'] = 'desc';

        // 设置模型数据表
        $modelName = $this->categoryId2ModelName($category);

        //查询数据是否正常显示（删除等信息不做以下操作）
        if ($category == 4) {
            $data = D("Found")->where(array('id' => $dataId, "del" => "1"))->field("*")->find();
        }
        if ($category == 3) {
            $data = D("Poster")->where(array('id' => $dataId, "del" => "1"))->field("*")->find();
        }
        if ($category == 2) {
            $data = D("Shop")->where(array('id' => $dataId, "status" => "1"))->field("*")->find();
        }
        if (empty($data)) {
            $this->ret['status'] = 10;
            //$this->ret['message'] = '数据不存在、或非法传参';
            $this->ret['message'] = '操作失败';
            $this->ret['success'] = true;
            //$this->ret['info'] = (Object) array();
            $this->ret['flag'] = '0';
        } else {
            unset($this->ret['info']);
            // 查询
            $model = D($modelName . 'View');
            $commentList = $model->selData($map, $pageSize, $field, $order);
            if($category==4){//获取正常的发现评论信息
                //$map['status'] = array('eq', '1');
                $total = $model->getNum(array('dataId' => $dataId,'status'=>'1'));
            }else{
                $total = $model->getNum(array('dataId' => $dataId));
            }
//            if ($category == '4') { // Found额外字段
//                // 查询Found信息
//                $foundMap['id'] = $dataId;
//                $foundField = 'userId,time,lng,lat';
//                $found = D('Found')->selData($foundMap, 1, $foundField);
//
//                $memberMap['id'] = $found[0]['userId'];
//                $memberField = 'id,name,image,code';
//                $member = D('Members')->selData($memberMap, 1, $memberField);
//
//                // 计算距离
//                $lng = I('post.lng');
//                $lat = I('post.lat');
//                $distance = '0';
//                if (!empty($lng) || !empty($lat) || !empty($found[0]['lng']) || !empty($found[0]['lat'])) {
//                    $distance = GetDistance($lng, $lat, $found[0]['lng'], $found[0]['lat']);
//                }
//
//                $publisher = array(
//                    'praiseNum' => D('PraiseFoundLog')->getNum(array('dataId' => $dataId)), // 查询赞的数量
//                    'publisherName' => $member[0]['name'],
//                    'publisherId' => $member[0]['id'],
//                    'publishImage' => $member[0]['image'],
//                    'publisherCode' => $member[0]['code'],
//                    'distance' => $distance,
//                    'addTime' => $found[0]['time'],
//                );
//                $this->ret['info']['publisher'] = $publisher;
//            }
            // 构建数据
            if ($total == 0) {
                $this->ret['status'] = 0;
                $this->ret['message'] = '没有数据了';
                //$this->ret['info']['data'] = array();
                $this->ret['info']['total'] = $total;
                $this->ret['info']['isIncrement'] = "1";
            } elseif (empty($commentList)) {
                $this->ret['status'] = 0;
                $this->ret['message'] = '没有数据了';
            } else {
                $count = count($commentList);
                for ($i = 0; $i < $count; $i++) {
                    $commentList[$i]['id'] = encodePass($commentList[$i]['id']);
                    $commentList[$i]['content'] = base64_encode(jsonStrWithOutBadWordsNew($commentList[$i]['content'], 4));
                }
                $this->ret['status'] = 1;
                $this->ret['message'] = '查询成功';
                $this->ret['info']['total'] = $total;
                $this->ret['info']['isIncrement'] = '0';
                $this->ret['info']['data'] = $commentList;
            }
        }
        $this->apiCallback($this->ret);exit();
    }

    /**
     * 添加评论
     * @access public
     * @param string $dataId
     * @param string $userId
     * @param string $category
     * @param string $content
     * @author FrankKung <kongfanjian@andlisoft.com>
     */
    public function addComments() {
        // 获取参数
        $dataId = I('post.dataId');
        $category = I('post.category');
        $content = I('post.content');
        $dataId = decodePass($dataId);
        // 参数检测
        if (is_empty($dataId) || is_empty(I('post.userId')) || is_empty($category) || is_empty($content)) {
            $this->ret['status'] = 10;
            //$this->ret['message'] = '参数不完整';
            $this->ret['message'] = '操作失败';
            $this->apiCallback($this->ret);exit();
        }

        $content = base64_decode($content); //base64解码
        // 设置模型数据表
        $modelName = $this->categoryId2ModelName($category);
        //查询数据是否正常显示（删除等信息不做以下操作）
        if ($category == 4) {
            //检测是否能通过检测
            $this->checkKey();
            
            $data = D("Found")->where(array('id' => $dataId, "del" => "1"))->field("*")->find();
        }
        
        $userId = $this->userId;
        
        if ($category == 3) {
            $data = D("Poster")->where(array('id' => $dataId, "del" => "1"))->field("*")->find();
        }
        if ($category == 2) {
            $data = D("Shop")->where(array('id' => $dataId, "status" => "1"))->field("*")->find();
        }
        if (empty($data)) {
            $this->ret['status'] = 10;
            $this->ret['message'] = '操作失败';
        } else {
            // 构建数据
            $data = array(
                'userId' => $userId,
                'dataId' => $dataId,
                'content' => $content,
                'time' => time(),
            );
            // 保存数据
            $id = D($modelName)->add($data);
            if ($id) {
                if ($category == 4) {//20141202 xiaofeng 更新发现表中评论数量字段
                    //更新发现表 评论数量
                    D('found')->setColunm(array('id' => $dataId), 'commentNum', 1);
                }
                if ($category == 2) {
                    $m = D('Shop');
                    $uid = $m->where('id=' . $dataId)->getField('userId');
                    $jpush = A('Admin_3.2/JPush');
                    $content = '亲，您的店铺有新评论了';
                    $res = $jpush->pushNoticeWithOutDB($uid, $content);
                }
                $this->ret['message'] = '评论成功';
            } else {
                $this->ret['status'] = -1;
                $this->ret['message'] = '评论失败';
            }
        }
        header('Content-Type:application/json; charset=UTF-8');
        $this->apiCallback($this->ret);exit();
    }

}
