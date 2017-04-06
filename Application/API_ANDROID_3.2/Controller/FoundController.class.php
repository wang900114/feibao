<?php

/**
 * 发现 数据接口
 * @author Jine <luxikun@andlisoft.com>
 */
class FoundController extends CommonController {

    protected $userId;

    /**
     * 初始化
     * @access public
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

                if (in_array($ACTION_NAME, array('detail'))) {
                    
                } else {
                    $return['info'] = array();
                }
                echo jsonStr($return);
                exit(0);
            } else {
                if ($res['freeze'] != '0') {//验证账号是否非法
                    $return['status'] = 33;
                    $return['message'] = '账号非法，暂时无法完成此操作';
                    if (in_array($ACTION_NAME, array('detail'))) {
                        
                    } else {
                        $return['info'] = array();
                    }
                    echo jsonStr($return);
                    exit(0);
                } else {
                    if (in_array($ACTION_NAME, array('listnearby', 'listtime', 'topoflist', 'detail'))) {
                        $this->userId = $res['id'];
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
            }
        } else {
            $return['message'] = '操作失败';
            $return['status'] = 10;
            if (in_array($ACTION_NAME, array('detail'))) {
                
            } else {
                $return['info'] = array();
            }
            $return['info'] = array();
            echo jsonStr($return);
            exit(0);
        }

//        if (in_array($ACTION_NAME, array('myfoundlist', 'delmyfound'))) {
//            A('API_3.2/Public')->testPersonalToken(); //验证 个人 token
//        } else if (in_array($ACTION_NAME, array('aaa', 'aaa2', 'bbb', 'ccc', 'ccc2', 'ddd'))) {
//            //不验证
//        } else {
//            A('API_3.2/Public')->testPublicToken(); //验证 公共 token
//        }
    }

    /**
     * 发布发现
     * @param  string $lng 经度
     * @param  string $lat 纬度
     * @param  string $userId 会员ID
     * @param  string $content 内容
     * @param  string $provinceId 省份ID
     * @param  string $images 图片列表JSON串
     * @param  string $address 地址
     * @param  string $address_baidu 百度地址
     * @param  string $title 标题
     * @param  string $cityId 城市ID
     * @return json 海报数据的JSON字符串
     */
    public function addFounds() {
        header('Content-Type:application/json; charset=UTF-8');
        $return['success'] = true;
        $content = I('post.content', '', 'trim');
        $images = json_decode(str_replace('&quot;', '"', I("post.images")), true);
        $unique = I('post.unique');
        $lng = I('post.lng');
        $lat = I('post.lat');
        $userId = $this->userId;
        $phone = I('post.phone');
        $provinceId = I('post.provinceId');
        $address_baidu = I('post.address_baidu');
        $cityId = I('post.cityId');
        $return['status'] = 10;
        $return['message'] = '操作失败';

        $model = D("Found");
        //判断唯一标识
        if (empty($unique)) {
            $return['status'] = -888;
            $return['message'] = '传参不完整';
            echo jsonStr($return);
            die;
        } else {
            $uniqueMarkData = $model->getFoundByUniqueMar($unique);
            if ($uniqueMarkData) {
                $return['status'] = 1;
                $return['message'] = '发布成功';
                echo jsonStr($return);
                exit(0);
            } else {
                $data['uniqueMark'] = $unique;
            }
        }
        if (empty($content) && empty($images)) {//判断两个参数不能同时为空
            $return['status'] = 10;
            $return['message'] = '操作失败';
        } else {
            if ($content) {//对内容进行加密
                $content = base64_decode($content);
            }

            if (!empty($content) && empty($images)) {//判断只上传内容
                if (is_empty($lng) || is_empty($lat) || is_empty($phone) || is_empty($userId) || is_empty($provinceId) || is_empty($address_baidu) || is_empty($cityId)) {
                    $return['status'] = 10;
                    $return['message'] = '操作失败';
                } else {
                    $data['userId'] = $this->userId;
                    $data['content'] = $content;
                    $data['address_baidu'] = trim($address_baidu);
                    $data['cityId'] = $cityId;
                    $data['lng'] = $lng;
                    $data['lat'] = $lat;
                    $data['isImage'] = '1'; //是否代图片 1表示没有图片
                    $data['provinceId'] = getProvinceIdById($cityId);
                    $data['time'] = time();
                    $re = $model->addData($data);
                    $mapShare['id'] = $re;
                    $dataShare['sharePath'] = getShortUrl(WEBURL . "/index.php/Home/index/foundShare/id/" . base64_encode($re));
                    $model->upData($mapShare, $dataShare);
                    if ($re) {//判断添加是否成功
                        $return['status'] = 1;
                        $return['message'] = '发布成功';
                    } else {
                        $return['status'] = -1;
                        $return['message'] = '发布失败';
                    }
                }
            } elseif (empty($content) && !empty($images)) {//判断只上传图片
                if (count($images['images']) > 9 || count($images['images']) == 0) {
                    $return['status'] = -361;
                    $return['message'] = '图片上传数量有误';
                    echo jsonStr($return);
                    die;
                }

                if (is_empty($lng) || is_empty($lat) || is_empty($phone) || is_empty($userId) || is_empty($provinceId) || is_empty($address_baidu) || is_empty($cityId)) {
                    $return['status'] = 10;
                    $return['message'] = '操作失败';
                } else {
                    $data['userId'] = $this->userId;
                    $data['image'] = $images['images'][0]['url']; //默认取第一张图片作为封面
                    $data['address_baidu'] = trim($address_baidu);
                    $data['cityId'] = $cityId;
                    $data['lng'] = $lng;
                    $data['lat'] = $lat;
                    $data['isImage'] = '2'; //是否代图片 2表示有图片
                    $data['provinceId'] = getProvinceIdById($data['cityId']);
                    $data['time'] = time();
                    $model->startTrans();
                    $re = $model->addData($data);

                    if ($re) {
                        $reImgNum = 0;
                        foreach ($images['images'] as $k => $v) {
                            $imagesData = array();
                            $imagesData['dataId'] = $re;
                            $imagesData['image'] = $v['url'];
                            $imagesData['thumbUrl'] = $v['thumbUrl'];
                            $imagesData['addTime'] = $data['time'];

                            $reImg = D('PictureFound')->addData($imagesData);
                            if ($reImg)
                                $reImgNum+=1;
                        }

                        if ($reImgNum == count($images['images'])) {
                            $model->commit();

                            //修改 分享 页面
                            $mapShare['id'] = $re;
                            $dataShare['sharePath'] = getShortUrl(WEBURL . "/index.php/Home/index/foundShare/id/" . base64_encode($re));
                            $model->upData($mapShare, $dataShare);
                            $return['status'] = 1;
                            $return['message'] = '发布成功';
                        } else {
                            $model->rollback();
                            $return['status'] = -360;
                            $return['message'] = '图片上传失败';
                        }
                    } else {
                        $model->rollback();
                        $return['status'] = -1;
                        $return['message'] = '发布失败';
                    }
                }
            } else {
                if (count($images['images']) > 9 || count($images['images']) == 0) {
                    $return['status'] = -361;
                    $return['message'] = '图片上传数量有误';
                    echo jsonStr($return);
                    die;
                }

                if (is_empty($lng) || is_empty($lat) || is_empty($phone) || is_empty($userId) || is_empty($provinceId) || is_empty($address_baidu) || is_empty($cityId)) {
                    $return['status'] = 10;
                    $return['message'] = '操作失败';
                } else {
                    //$data['userId'] = I('post.userId');
                    $data['userId'] = $this->userId;
                    $data['image'] = $images['images'][0]['url']; //默认取第一张图片作为封面
                    $data['content'] = $content;
                    $data['address_baidu'] = trim($address_baidu);
                    $data['cityId'] = $cityId;
                    $data['lng'] = $lng;
                    $data['lat'] = $lat;
                    $data['isImage'] = '2'; //是否代图片 2表示有图片
                    $data['provinceId'] = getProvinceIdById($data['cityId']);
                    $data['time'] = time();
                    $model->startTrans();
                    $re = $model->addData($data);

                    if ($re) {//判断添加是否成功
                        $reImgNum = 0;
                        foreach ($images['images'] as $k => $v) {
                            $imagesData = array();
                            $imagesData['dataId'] = $re;
                            $imagesData['image'] = $v['url'];
                            $imagesData['thumbUrl'] = $v['thumbUrl'];
                            $imagesData['addTime'] = $data['time'];

                            $reImg = D('PictureFound')->addData($imagesData);
                            if ($reImg) {
                                $reImgNum+=1;
                            }
                        }
                        if ($reImgNum == count($images['images'])) {
                            $model->commit();
                            //修改 分享 页面
                            $mapShare['id'] = $re;
                            $dataShare['sharePath'] = getShortUrl(WEBURL . "/index.php/Home/index/foundShare/id/" . base64_encode($re));
                            $model->upData($mapShare, $dataShare);
                            $return['status'] = 1;
                            $return['message'] = '发布成功';
                        }
                    } else {
                        $model->rollback();
                        $return['status'] = -1;
                        $return['message'] = '发布失败';
                    }
                }
            }
        }
        echo jsonStr($return);exit();
    }

    /**
     * 我的收藏列表
     * @access public
     * @param string token:令牌（个人token）
     * @param string version:版本号(如“1.2”)
     * @param string userId：会员ID
     * @param string pageSize：每页显示数量
     * @param string myLng: 物理地址经度(我的位置) 【必填项】
     * @param string myLat: 物理地址纬度(我的位置) 【必填项】
     * @param int page:当前页【必填项】
     * @author xiaofeng <yuanmingwei@feibaokeji.com>
     */
    public function myFoundCollect() {
        $return['success'] = true;
        $return['status'] = 0;
        $page = I('post.page', 1);
        $myLng = I('post.myLng');
        $myLat = I('post.myLat');
        $pageSize = I('post.pageSize', 10);
        $userId = $this->userId;
        if (is_empty($myLng) || is_empty($myLat) || is_empty($pageSize) || is_empty($userId) || is_empty($page)) {
            $return['status'] = 10;
            $return['message'] = '操作失败';
        } else {
            //数据查询
            $reList = D('Found')->getMyCollectList($userId, $myLng, $myLat, $page, $pageSize);
            if (empty($reList)) {
                $return['status'] = 0;
                $return['message'] = '没有数据了';
                $return['info'] = array();
            } else {
                foreach ($reList as $k => $v) {
                    $reList[$k]['content'] = base64_encode(jsonStrWithOutBadWordsNew($v['content'], 1));
                }
                $return['info'] = $reList;
                $return['status'] = 1;
                $return['message'] = '查询成功';
            }
        }

        echo jsonStr($return);exit();
    }

    /**
     * 发现踩接口 - 添加踩信息
     * @param  string $token 令牌
     * @param  string $version 版本号(如"1.2")
     * @param  string $dataId 数据ID
     * @param  string $userId 会员ID
     * @return JSON 	
     */
    public function tread() {
        //获取参数
        $userId = $this->userId;
        $dataId = I('post.id');
        $version = I('post.version');
        $ret['success'] = true;
        if (is_empty($dataId) || is_empty($userId)) {//判断参数是否完整
            $ret['status'] = 10;
            $ret['message'] = '操作失败';
        } else {
            $result = D("Members")->getUserInfo($userId);
            if (empty($result)) {
                $ret['status'] = 10;
                $ret['message'] = '操作失败';
            } else {
                $dataId = decodePass($dataId);
                $data = D("TreadFoundLog")->getFoundById($dataId);
                if (empty($data)) {
                    $ret['status'] = 10;
                    //$ret['message'] = '数据不存在、或非法传参';
                    $ret['message'] = '操作失败';
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
                            $ret['status'] = 10;
                            $ret['message'] = '操作失败';
                            //$ret['message'] = '添加失败';
                            $ret['flag'] = 0;
                        }
                    }
                }
            }
        }
        header('Content-Type:application/json; charset=UTF-8');
        echo jsonStr($ret);exit();
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
        $content = I('post.content', '', 'trim');
        $dataId = decodePass($dataId);
        if (is_empty($dataId) || is_empty($userId) || is_empty($content)) {//判断参数是否存在
            $return['status'] = 10;
            $return['message'] = '操作失败';
        } else {
            $model = D("Found");
            $result = $model->getFoundById($dataId, "userId,del");
            if ($result['userId'] == $userId) {//判断是否是自己发布的发现
                $return['status'] = -631;
                $return['message'] = '不能举报自己';
            } else {
                //查询数据是否正常显示（删除等信息不做以下操作）
                if ($result['del'] == '0') {//判断发现是否存在
                    $return['status'] = 10;
                    $return['message'] = '操作失败';
                } else {
                    $arrayData = explode('|', $content);
                    foreach ($arrayData as $key => $value) {
                        $res = $model->addAccusation($userId, $dataId, $value);
                    }
                    if ($res == true) {
                        $return['status'] = 1;
                        $return['message'] = '举报成功';
                    } else {
                        $return['status'] = -1;
                        $return['message'] = '举报失败';
                    }
                }
            }
        }

        header("Content-Type: application/json; charset=utf-8");
        echo jsonStr($return);exit();
    }

    /**
     * 返回 发现 详情页
     * @param  string $id 发现ID
     * @param  string $userId 会员ID
     * @return json 海报数据的JSON字符串
     * @author xiaofeng <yuanmingwei@feibaokeji.com>
     */
    public function detail() {
        $return = array(
            'success' => true,
            'status' => 10,
            'message' => '操作失败'
        );
        $id = I('post.id');
        $userId = $this->userId;
        if ($id && $userId) {
            //查询发现数据
            $result = D('found')->detailByid($id, $userId);
            if ($result) {
                $result['content'] = base64_encode(jsonStrWithOutBadWordsNew($result['content'], 1));
                $return['message'] = '查询成功';
                $return['status'] = 1;
                $return['info'] = $result;
            } else {
                $return['message'] = '查询失败';
                $return['status'] = -10;
            }
        }

        echo jsonStr($return);exit();
    }

    /**
     * 发现热图
     * token:令牌（公共token）
     * version:版本号(如“1.2”)
     * cityId:城市id
     * userId：会员ID
     * 
     */
    public function topOfList() {
        $return['success'] = true;
        $cityId = I("post.cityId", 1);
        $userId = $this->userId;
        $list = D("Found")->getTopListData($cityId, $userId);
        if (empty($list)) {//判断数据是否为空
            $return['status'] = 0;
            $return['message'] = '没有数据了';
            $return['info'] = array();
        } else {
            $return['status'] = 1;
            $return['message'] = '查询成功';
            $return['info'] = $list;
        }
        echo jsonStr($return);exit();
    }

    /**
     * 删除 我的 发现
     * 是假删除(图集信息是真删除)
     * @param  string $id 发现ID
     * @param  string $userId 发布人ID
     * @return json 海报数据的JSON字符串
     */
    public function delMyFound() {
        $return['success'] = true;

        //$userId = I('post.userId');
        $userId = $this->userId;
        $id = I('post.id');
        $id = decodePass($id);
        if (is_empty($userId) || is_empty($id)) {
            $return['status'] = 10;
            $return['message'] = '操作失败';
        } else {
            $map['id'] = $id;
            $re = D('Found')->selData($map, 1, 'userId,del,cityId');
            $cityId = $re[0]['cityId'];
            if (empty($re)) {//先校验发现id 是否存在
                $return['status'] = -383;
                $return['message'] = '查无此记录';
            } else {
                if ($re[0]['del'] == '0') {//已删除
                    $return['status'] = -381;
                    $return['message'] = '不能重复删除';
                } else if ($re[0]['userId'] != $userId) {
                    $return['status'] = -380;
                    $return['message'] = '不能删除别人的 发现 信息';
                } else {
                    D('Found')->startTrans();
                    //修改发现id的状态为删除（==0），
                    $data['del'] = '0';
                    $data['isImage'] = '1';
                    $data['topflag'] = '0';
                    $data['hotshow'] = '0';
                    $data['hotflag'] = '0';
                    $data['delTime'] = time(); //删除时间
                    $re = D('Found')->upData($map, $data);
                    if (empty($re)) {//修改状态 失败
                        D('Found')->rollback();
                        $return['status'] = -1;
                        $return['message'] = '删除失败';
                    } else {
                        //删除该发现下的图集和图片
                        $mapSon['dataId'] = $id;
                        $re21 = D('PictureFound')->selData($mapSon, 1);
                        if (empty($re21)) {//不存在该图集信息
                            D('Found')->commit();
                            $return['status'] = 1;
                            $return['message'] = '删除成功';
                        } else {
                            //删除该发现下的图集和图片
                            $re2 = D('PictureFound')->delData($mapSon);
                            if (empty($re2)) {
                                D('Found')->rollback();
                                $return['status'] = -382;
                                $return['message'] = '删除图集失败';
                            } else {
                                D('Found')->commit();
                                $return['status'] = 1;
                                $return['message'] = '删除成功';
                            }
                        }
                        adminSetHotMap();
                        getHotMapList($cityId);
                        createCityFoundHotMap($cityId);
                    }
                }
            }
        }

        // echo jsonStrWithOutBadWords($return);
        echo jsonStr($return);exit();
    }

    /**
     * 返回 发现 列表 时间-列表模式
     * @param  string $myLng GPS定位的物理经度
     * @param  string $myLat GPS定位的物理纬度
     * @param  string $cityId 城市ID
     * @param int $userId 当前人ID
     * @param int $page 页数
     * @param int $pageSize 分页大小
     * @param int $selectTime 取数据时间
     * @return json 数据的JSON字符串
     * @author xiaofeng <yuanmingwei@feibaokeji.com>
     */
    public function listTime() {
        $return['success'] = true;
        $myLng = I('post.myLng');
        $myLat = I('post.myLat');
        $pageSize = I('post.pageSize', 10);
        $page = I('post.page', 1);
        $userId = $this->userId;
        $cityId = I('post.cityId', 10000);
        $selectTime = I("post.selectTime");
        $selectTime = empty($selectTime) ? time() : $selectTime;
        if (is_empty($myLng) || is_empty($myLat) || is_empty($pageSize) || is_empty($selectTime) || is_empty($page) || is_empty($cityId)) {
            $return['status'] = 10;
            $return['message'] = '操作失败';
        } else {
            //数据查询
            $reList = D('Found')->getListNewData($userId, $cityId, $myLng, $myLat, $selectTime, $page, $pageSize);
            if (empty($reList)) {
                $return['status'] = 0;
                $return['message'] = '没有数据了';
            } else {
                $reList = createApiArray($reList);
                if ($reList) {
                    foreach ($reList as $k => $v) {
                        $reList[$k]['content'] = base64_encode(jsonStrWithOutBadWordsNew($v['content'], 1));
                    }
                }
                $return['info'] = $reList;
                $return['selectTime'] = $selectTime;
                $return['status'] = 1;
                $return['message'] = '查询成功';
            }
        }
        echo jsonStr($return);exit();
    }

    /**
     * 返回 发现 列表 时间-列表模式
     * @param  int $page 类型
     * @param int $userId 当前人ID
     * @param  string $pageSize 分页大小
     * @author 李丰瀚<lifenhgan@feibaokeji.com>
     */
    public function myFoundList() {
        $return['success'] = true;
        $pageSize = I('post.pageSize', 10);
        $page = I('post.page', 1);
        $userId = $this->userId;
        if (is_empty($pageSize) || is_empty($page) || is_empty($userId)) {
            $return['status'] = 10;
            $return['message'] = '操作失败';
        } else {
            //数据查询
            $reList = D('Found')->getMyListNewData($userId, $page, $pageSize);
            if (empty($reList)) {
                $return['status'] = 0;
                $return['message'] = '没有数据了';
                $return['info'] = array();
            } else {
                foreach ($reList as $k => $v) {
                    $reList[$k]['content'] = base64_encode(jsonStrWithOutBadWordsNew($v['content'], 1));
                }
                $return['info'] = $reList;
                $return['status'] = 1;
                $return['message'] = '查询成功';
            }
        }
        echo jsonStr($return);exit();
    }

    /**
     * 返回 发现 列表 附近-列表模式
     * @param  string $myLng GPS定位的物理经度
     * @param  string $myLat GPS定位的物理纬度
     * @param  string $cityId 城市ID
     * @param  string $pageSize 分页大小
     * @param  int $page 当前页
     * @param  int     $selectTime 取数据时间
     * @return json 数据的JSON字符串
     * @author xiaofeng <yuanmingwei@feibaokeji.com>
     */
    public function listNearby() {
        $return['success'] = true;
        $return['status'] = 0;
        $myLng = I('post.myLng');
        $myLat = I('post.myLat');
        $pageSize = I('post.pageSize', 10);
        $userId = $this->userId;
        $cityId = I('post.cityId');
        $page = I('post.page', 1);
        $selectTime = I("post.selectTime", time());
        $selectTime = empty($selectTime) ? time() : $selectTime;
        $reList = array();
        if ($cityId) {
            if (is_empty($myLng) || is_empty($myLat) || is_empty($userId) || is_empty($pageSize) || is_empty($page) || is_empty($selectTime)) {
                $return['status'] = 10;
                $return['message'] = '操作失败';
            }
        } else {
            if (is_empty($userId) || is_empty($pageSize) || is_empty($page) || is_empty($selectTime)) {
                $return['status'] = 10;
                $return['message'] = '操作失败';
            }
        }

        if ($return['status'] >= 0) {
            //数据查询
            $reList = D('Found')->getNearListData($userId, $cityId, $myLng, $myLat, $selectTime, $page, $pageSize);
            if (empty($reList)) {
                $return['status'] = 0;
                $return['message'] = '没有数据了';
            } else {
                $reList = createApiArray($reList);
                if ($reList) {
                    foreach ($reList as $k => $v) {
                        $reList[$k]['content'] = base64_encode(jsonStrWithOutBadWordsNew($v['content'], 1));
                    }
                }
                $return['info'] = $reList;
                $return['status'] = 1;
                $return['selectTime'] = $selectTime;
                $return['message'] = '查询成功';
            }
        }
        echo jsonStr($return);exit();
    }

}
