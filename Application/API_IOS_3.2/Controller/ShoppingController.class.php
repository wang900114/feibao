<?php

use Think\Controller;

class ShoppingController extends BaseController {

    /**
     * 初始化
     */
    public function _initialize() {
        parent::_initialize();
        $check_m = D('Check');

        $ACTION_NAME = strtolower(ACTION_NAME);
        if (in_array($ACTION_NAME, array('addstore', 'importstore', 'addstar'))) {
            //A('API_3.2/Public')->testPublicToken();//验证 公共 token
        }

        // 记录接口调用日志
        if (in_array($ACTION_NAME, array('addstore', 'importstore', 'setstatus'))) {
            $userId = base64_decode(I('post.userId'));//解密
            $type = array(
                'addStore' => '3',
                'importStore' => '4',
                'setStatus' => '5',
            );
            logAPI($type[$ACTION_NAME], $userId);
        }
    }

    /*
     * 验证店铺token，起到 踢人 的作用
     * @param string $storeId 店铺id
     * @param string $storetoken 店铺token
     */

    public function testStoreToken($storeId, $storetoken) {
        if ($_SERVER['SERVER_ADDR'] == '127.0.0.1')
            return true;
        $map['id'] = $storeId;
        $re = D('Shop')->selData($map, 1, 'storetoken');
        if (empty($re) || empty($storetoken) || $storetoken != $re[0]['storetoken']) {
            unset($this->ret['info']);
            $this->ret['status'] = -999;
            $this->ret['message'] = '店铺token错误';
            $this->apiCallback($this->ret);
        }
    }

    /**
     * 店铺商品列表
     * @access public
     * @param string $storeId 店铺id
     * @param string $type 1加载(翻页)；0刷新（默认值）
     * @param string $id
     * @param string $pageSize
     * @author FrankKung <kongfanjian@andlisoft.com>
     */
    public function goodsList() {

        // 获取参数
        $storeId = I('post.storeId');
        $type = I('post.type');
        $id = I('post.id');
        $pageSize = I('post.pageSize');

        // 参数检测
        //上线时检测
        if (is_empty($storeId) || is_empty($type) || is_empty($id) || is_empty($pageSize)) {
            $this->ret['status'] = -888;
            $this->ret['message'] = '参数不完整';
            $this->apiCallback($this->ret);
        }

        $id = base64_decode($id);//解密
        $storeId = base64_decode($storeId);//解密

        // 查询条件
        $where['storeId'] = $storeId;
        if ($type) {
            $where['id'] = array('lt', $id);
        } else {
            $where['id'] = array('egt', $id);
        }
        $field = 'id,image,price,name,content'; // 查询字段
        $order['id'] = 'desc';

        // 查询商品
        $goodsModel = D('Goods');
        $total = $goodsModel->getNum($where);
        $goods = $goodsModel->selData($where, $pageSize, $field, $order);

        // 尾页判断
        $isLastPage = ($total - $pageSize) > 0 ? '0' : '1';

        // 构建数据
        if ($total == 0) {
            $this->ret['status'] = 0;
            $this->ret['message'] = '没有数据了';
            $this->apiCallback($this->ret);
        } elseif (empty($goods)) {
            $this->ret['status'] = -1;
            $this->ret['message'] = '查询失败';
            $this->apiCallback($this->ret);
        } else {
            $this->ret['status'] = 1;
            $this->ret['message'] = '查询成功';
            $this->ret['info'] = $goods;
        }

        $this->apiCallback($this->ret);exit();
    }

    /**
     * 店铺详情
     * @access public
     * @param string $userId 用户id
     * @param string $storeId 店铺id
     * @param string $token token
     * @author FrankKung <kongfanjian@andlisoft.com>
     */
    public function storeDetail() {

        // 获取参数
        $userId = I('post.userId');
        $storeId = I('post.storeId');
        $token = I('post.token');
        $storetoken = I('post.storetoken');
        $type = I('post.type');

        // 参数检测
        //上线时检测
        if (is_empty($userId) || is_empty($storeId)) {
            $this->ret['status'] = -888;
            $this->ret['message'] = '参数不完整';
            $this->apiCallback($this->ret);
        }

        $userId = base64_decode($userId);//解密
        $storeId = base64_decode($storeId);//解密

        if ($type == 2)
            $this->testStoreToken($storeId, $storetoken); //验证店铺token

            
// 查询条件
        $where['id'] = $storeId;
        // $where['userId'] = $userId;//会员ID只查询“是否收藏”，不能拿来搜店铺
        $field = 'id,image,name,phone,lng,lat,content,address,address_baidu,star,status'; // 查询字段
        $tagsField = 'id,tag1,tag2,tag3,tag4'; // 标签字段
        // 获取详情
        $shopModel = D('Shop');
        $shop = $shopModel->selData($where, 1, $field);

        // 构建数据
        if (is_bool($shop) && empty($shop)) {
            $this->ret['status'] = -1;
            $this->ret['message'] = '查询失败';
            $this->ret['info'] = (object) array();
        } else if ((is_array($shop) || is_null($shop)) && empty($shop)) {
            $this->ret['status'] = 0;
            $this->ret['message'] = '没有数据了';
            $this->ret['info'] = (object) array();
        } else {
            // 查询标签
            $tags = $shopModel->where($where)->getField($tagsField, '|');
            $tagsArray = explode('|', $tags[$shop[0][id]]);
            // 查询是否收藏
            $isCollectMap['dataId'] = $storeId;
            $isCollectMap['userId'] = $userId;
            $isCollect = D('CollectShopLog')->isCollect($isCollectMap);

            // 查询是否评星
            $isStar = D('Star')->hasStar($isCollectMap);

            //访问量+1
            $PublicOb = A('Public');
            $PublicOb->setAccessCount($storeId, $userId, 2);

            $this->ret['status'] = 1;
            $this->ret['message'] = '查询成功';
            $shop[0]['id'] = base64_encode($shop[0]['id']);//加密
            $this->ret['info'] = $shop[0];
            $this->ret['info']['isStar'] = $isStar;
            $this->ret['info']['isCollect'] = $isCollect;
            $this->ret['info']['tag'] = $tagsArray;
        }

        $this->apiCallback($this->ret);exit();
    }

    /**
     * 店铺-新建
     * @access public
     * @param string $userId ：会员ID
     * @param string $image ：店铺图片（是联想云存储服务器的地址）
     * @param string $name ：店铺名称
     * @param string $phone ：店铺电话（唯一存在）
     * @param int $lng ：店铺经度
     * @param int $lat ：店铺纬度
     * @param string $address ：店铺位置描述 
     * @param string $address_baidu ：百度地图根据经纬度反编译后的地址名称
     * @param string $password ：店铺登录密码
     * @param string $imei ：设备号
     * @param int $mobileType ：设备类型：1 Android ；2 IOS
     * @param int $provinceId ：省份ID
     * @author FrankKung <kongfanjian@andlisoft.com>
     */
    public function addStore() {
        // 参数
        $userId = I('post.userId');
        $image = I('post.image');
        $name = I('post.name');
        $phone = I('post.phone');
        $lng = I('post.lng', '0');
        $lat = I('post.lat', '0');
        $address = I('post.address', '');
        $address_baidu = I('post.address_baidu', '');
        $password = I('post.password');
        $imei = I('post.imei', '');
        $mobileType = I('post.mobileType', '1');
        $cityId = I('post.cityId');
        $provinceId = getProvinceIdById($cityId);

        // 参数检测
        if (is_empty($userId) || is_empty($image) || is_empty($name) || is_empty($phone) || is_empty($password) || is_empty($provinceId)) {
            $this->ret['status'] = -888;
            $this->ret['message'] = '参数不完整';
            $this->ret['info'] = (object) array();
            $this->apiCallback($this->ret);
        }
        $userId = base64_decode($userId);//解密
        //echo $userId;
        // 构建店铺数据
        $model = D('Shop');
        $data['userId'] = $userId;
        $data['image'] = $image;
        $data['name'] = $name;
        $data['phone'] = $phone;
        $data['lng'] = $lng;
        $data['lat'] = $lat;
        $data['address_baidu'] = $address_baidu;
        $data['address'] = $address;
        $data['password'] = $password;
        $data['imei'] = $imei;
        $data['mobileType'] = $mobileType;
        //$res = $model->add($data);
        $data = $model->create($data);

        //var_dump($res);die;
        $data['addTime'] = time();
        $data['cityId'] = $cityId;
        $data['provinceId'] = $provinceId;
        $err = $model->getError();
        //var_dump($err);die;

        if (!empty($err)) {
            switch ($err) {
                case '手机号码不合法':
                    $this->ret['status'] = -461;
                    $this->ret['message'] = '电话号码格式有误';
                    $this->ret['info'] = (object) array();
                    break;
                case '手机号码不唯一':
                    $this->ret['status'] = -460;
                    $this->ret['message'] = '电话已占用';
                    $this->ret['info'] = (object) array();
                    break;
                default:
                    # code...
                    break;
            }
            $this->apiCallback($this->ret);
        }

        // 保存
        $id = $model->add();
        //var_dump($model->getLastSql());die;
        // 构建反馈数据
        if ($id) {
            //修改店铺token
            $mapU['id'] = $userId;
            $feiBaoCode = D('Members')->selData($mapU, 1, 'id'); //飞报号
            //$storetoken = md5(md5($id).C('TOKEN_ALL').md5($feiBaoCode[0]['code']));//店铺token
            $storetoken = md5(md5(base64_encode($id)) . C('TOKEN_ALL') . md5(base64_encode($userId))); //店铺token //加密
            $mapS['id'] = $id;
            $dataS['storetoken'] = $storetoken;
            $model->upData($mapS, $dataS);
            // $shop[0]['storetoken'] = $storetoken;//返回店铺token
            // 构建店铺信息
            // 查询条件
            $where['id'] = $id;
            $where['userId'] = $userId;
            $field = 'id,image,name,storetoken,phone,content,lng,lat,address,address_baidu,star,status'; // 查询字段
            $tagsField = 'id,tag1,tag2,tag3,tag4'; // 标签字段
            // 获取详情
            $shopModel = D('Shop');
            $shop = $shopModel->selData($where, 1, $field);
            $tags = $shopModel->where($where)->getField($tagsField, '|');
            $tagsArray = explode('|', $tags[$shop[0]['id']]);

            // 查询是否收藏
            $isCollectMap['dataId'] = $storeId;
            $isCollectMap['userId'] = $userId;
            $isCollect = D('CollectShopLog')->isCollect($isCollectMap);

            // 构建数据
            if (empty($shop)) {
                $this->ret['status'] = -1;
                $this->ret['message'] = '查询店铺信息失败';
                $this->ret['info'] = (object) array();
            } else {
                $this->ret['status'] = 1;
                $this->ret['message'] = '新建成功';
                $shop[0]['id'] = base64_encode($shop[0]['id']);//加密
                $this->ret['info'] = $shop[0];
                $this->ret['info']['isCollect'] = $isCollect;
                $this->ret['info']['tag'] = $tagsArray;
                $this->ret['info']['goods'] = array();
            }
        } else {
            $this->ret['status'] = -1;
            $this->ret['message'] = '新建失败';
            $this->ret['info'] = (object) array();
        }

        $this->apiCallback($this->ret);exit();
    }

    /**
     * 店铺-新建-店铺详情
     * @access public
     * @param string $storetoken ：店铺token
     * @param string $userId ：会员ID
     * @param string $storeId ：店铺ID
     * @param string $password ：店铺登陆密码
     * @param string $content ：店铺介绍（75字以内）
     * @param string $tag ：店铺标签（最多4个），为JSON串
     * @param string $introduction ：商品介绍JSON串；
     * 包含商品图片url地址image（是联想云存储服务器的地址），
     * 商品价格price，商品名称name，商品介绍content；数组结构,最多20个
     * @author FrankKung <kongfanjian@andlisoft.com>
     */
    public function addStoreDetail() {
        // 参数
        $storetoken = I('post.storetoken');
        $userId = I('post.userId');
        $storeId = I('post.storeId');
        $password = I('post.password');
        $content = I('post.content', '');
        $tag = I('post.tag', '', 'strip_tags');
        $introduction = I('post.introduction', '', 'strip_tags');

        // 参数检测
        if (is_empty($userId) || is_empty($storeId) || is_empty($password)) {
            $this->ret['status'] = -888;
            $this->ret['message'] = '参数不完整';
            $this->ret['info'] = (object) array();
            $this->apiCallback($this->ret);
        }

        $userId = base64_decode($userId);//解密
        $storeId = base64_decode($storeId);//解密

        $this->testStoreToken($storeId, $storetoken); //验证店铺token
        //模型
        $shopModel = D('Shop');
        $goodsModel = D('Goods');

        // 检测token
        // 检测密码
        if (!$shopModel->passwordIsRight($password, $storeId)) {
            $this->ret['status'] = -1;
            $this->ret['message'] = '密码不正确';
            $this->ret['info'] = (object) array();
            $this->apiCallback($this->ret);
        }

        // 保存店铺信息
        $tagsArray = json_decode($tag, ture);
        unset($shopData['storetoken']);
        unset($shopData['password']);
        $shopData = array(
            'content' => $content,
            'updateTime' => time(),
            'tag1' => empty($tagsArray['tag']['0']) ? '' : $tagsArray['tag']['0'],
            'tag2' => empty($tagsArray['tag']['1']) ? '' : $tagsArray['tag']['1'],
            'tag3' => empty($tagsArray['tag']['2']) ? '' : $tagsArray['tag']['2'],
            'tag4' => empty($tagsArray['tag']['3']) ? '' : $tagsArray['tag']['3']
        );

        $shopRet = $shopModel->upData(array('id' => $storeId), $shopData);

        /*         * * 保存商品信息 & 查询店铺 Start ** */
        $goodsArray = json_decode($introduction, true);
        if (sizeof($goodsArray) > 20) { // 检测数量
            $this->ret['status'] = '-1';
            $this->ret['message'] = '编辑失败,商品数量大于20了。';
            $this->ret['info'] = (object) array();
            $this->apiCallback($this->ret);
        }
        // 启用回滚
        $goodsModel->startTrans();

        // 删除旧
        $where['storeId'] = $storeId;
        $hasOld = $goodsModel->selData($where); //查询是否有旧数据
        if (empty($hasOld)) {
            $oldStatus = true;
        } else {
            $oldStatus = $goodsModel->where($where)->delete();
        }

        if (empty($goodsArray["introduction"])) {//商品 可以为空
            $newStatus = true;
        } else {
            // 写入新
            foreach ($goodsArray["introduction"] as $goods) {
                $goods['storeId'] = $storeId;
                $goods['addTime'] = time();
                $goodsData[] = $goods;
            }
            $newStatus = $goodsModel->addAll($goodsData);
        }

        if ($oldStatus && $newStatus) { //提交事务
            $goodsModel->commit();
            $this->ret['status'] = '1';
            $this->ret['message'] = '新建成功';
            // 查询店铺数据
            $whereShop['id'] = $storeId;
            $whereShop['userId'] = $userId;
            $field = 'id,image,name,storetoken,phone,content,lng,lat,address,address_baidu,star,status'; // 查询字段
            $tagsField = 'id,tag1,tag2,tag3,tag4'; // 标签字段
            // 获取详情
            $shop = $shopModel->selData($whereShop, 1, $field);
            $tags = $shopModel->where($whereShop)->getField($tagsField, '|');
            $tagsArray = explode('|', $tags[$shop[0][id]]);

            // 查询是否收藏
            $isCollectMap['dataId'] = $storeId;
            $isCollectMap['userId'] = $userId;
            $isCollect = D('CollectShopLog')->isCollect($isCollectMap);

            // 构建数据
            if (empty($shop)) {
                $this->ret['status'] = -1;
                $this->ret['message'] = '查询店铺信息失败';
                $this->ret['info'] = (object) array();
            } else {
                // 查询商品
                $goodsFields = 'id,image,price,name,content';
                $goodsList = $goodsModel->selData($where, 20, $goodsFields);
                $goodsList = empty($goodsList) ? array() : $goodsList;
                $shop[0]['id'] = base64_encode($shop[0]['id']);//加密
                $this->ret['info'] = $shop[0];
                $this->ret['info']['isCollect'] = $isCollect;
                $this->ret['info']['tag'] = $tagsArray;
                $this->ret['info']['goods'] = $goodsList;
            }
        } else { // 回滚
            $goodsModel->rollback();
            $this->ret['status'] = '-1';
            $this->ret['message'] = '新建失败，回滚到旧数据';
            $this->ret['info'] = (object) array();
        }
        /*         * * 保存商品信息 & 查询店铺 End ** */

        $this->apiCallback($this->ret);exit();
    }

    /**
     * 店铺-编辑
     * @access public
     * @param string $storetoken ：店铺token
     * @param string $userId ：会员ID
     * @param string $storeId ：店铺ID
     * @param string $password ：店铺登陆密码
     * @param string $image 店铺图片
     * @param string $name 店铺名称
     * @param string $phone 店铺电话（唯一存在）
     * @param string $content ：店铺介绍（75字以内）
     * @param string $tag ：店铺标签（最多4个），为JSON串
     * @param int $lng ：店铺经度
     * @param int $lat ：店铺纬度
     * @param address ：店铺位置描述 
     * @param address_baidu ：百度地图根据经纬度反编译后的地址名称
     * @author FrankKung <kongfanjian@andlisoft.com>
     */
    public function updateStore() {
        /** 返回状态
         * 1：编辑成功
         * -1：编辑失败
         * -480：店铺token有误
         * -481： 电话已占用等
         * -482：店铺token已过期
         * -483：电话号码格式有误
         * -484：店铺图片上传失败
         * -485：店铺不存在
         * -486：原密码有误
         * -888：传参不完整
         */
        // 参数
        $storetoken = I("post.storetoken");
        $userId = I("post.userId");
        $storeId = I("post.storeId");
        $password = I("post.password");
        $image = I("post.image");
        $name = I("post.name");
        $phone = I("post.phone");
        $content = I("post.content", '');
        $tag = I("post.tag", '', 'strip_tags');
        $lng = I("post.lng");
        $lat = I("post.lat");
        $address = I("post.address");
        $address_baidu = I("post.address_baidu");



        // 参数检测
        if (is_empty($userId) || is_empty($storeId) || is_empty($password)) {
            $this->ret['status'] = -888;
            $this->ret['message'] = '参数不完整';
            $this->ret['info'] = (object) array();
            $this->apiCallback($this->ret);
        }

        $userId = base64_decode($userId);//解密
        $storeId = base64_decode($storeId);//解密
        //echo $userId.'-'.$storeId;die;
        $this->testStoreToken($storeId, $storetoken); //验证店铺token
        // 模型
        $model = D("Shop");
        $goodsModel = D("Goods");

        // 检测店铺
        if ($model->isExisted($storeId)) {
            $this->ret['status'] = -485;
            $this->ret['message'] = '店铺不存在';
            $this->ret['info'] = (object) array();
            $this->apiCallback($this->ret);
        }
        // 检测token
        // 检测密码
        // if ( !$model->passwordIsRight($password, $storeId) ) {
        // $this->ret['status'] = -486;
        // $this->ret['message'] = '密码不正确';
        // $this->ret['info'] = (object)array();
        // $this->apiCallback($this->ret);
        // }
        $datas['userId'] = $userId;
        $datas['storeId'] = $storeId;
        $datas['storetoken'] = $storetoken;
        $datas['image'] = $image;
        $datas['name'] = $name;
        $datas['phone'] = $phone;
        $datas['lng'] = $lng;
        $datas['lat'] = $lat;
        $datas['address_baidu'] = $address_baidu;
        $datas['address'] = $address;
        $datas['password'] = $password;
        $datas['content'] = $content;
        $datas['tag '] = $tag;

        // 创建数据
        $data = $model->create($datas);
        $err = $model->getError();
        //var_dump($model);die;
        if (!empty($err)) {//var_dump($model);
            switch ($err) {
                case '手机号码不唯一':
                    $this->ret['status'] = -481;
                    $this->ret['message'] = '电话已占用';
                    $this->ret['info'] = (object) array();
                    break;
                case '手机号码不合法':
                    $this->ret['status'] = -483;
                    $this->ret['message'] = '电话号码格式有误';
                    $this->ret['info'] = (object) array();
                    break;
                default:
                    $this->ret['status'] = -1;
                    $this->ret['message'] = '编辑失败:' . $err;
                    $this->ret['info'] = (object) array();
                    break;
            }
            $this->apiCallback($this->ret);exit();
        }

        // 构建&保存店铺数据
        $data['id'] = $storeId;
        $data['updateTime'] = time();
        unset($data['storetoken']);
        unset($data['password']);
        $tagsArray = json_decode($tag, true);
        if (!empty($tagsArray)) {
            $data['tag1'] = $tagsArray['tag']['0'];
            $data['tag2'] = $tagsArray['tag']['1'];
            $data['tag3'] = $tagsArray['tag']['2'];
            $data['tag4'] = $tagsArray['tag']['3'];
        } else {//标签未传时， $tagsArray['tag'] 还是有默认值，故而去掉
            unset($tag);
            unset($data['tag2']);
            unset($data['tag3']);
            unset($data['tag4']);
            unset($data['tag4']);
        }
        $shopRet = $model->save($data);

        // 反馈数据
        if (!$shopRet) {
            $this->ret['status'] = -1;
            $this->ret['message'] = '编辑失败,保存店铺数据失败';
            $this->ret['info'] = (object) array();
        } else {
            // 查询店铺数据
            $whereShop['id'] = $storeId;
            $whereShop['userId'] = $userId;
            $field = 'id,image,name,storetoken,phone,content,star,lng,lat,address,address_baidu,status'; // 查询字段
            $tagsField = 'id,tag1,tag2,tag3,tag4'; // 标签字段
            // 获取详情
            $shop = $model->selData($whereShop, 1, $field);
            $tags = $model->where($whereShop)->getField($tagsField, '|');
            $tagsArray = explode('|', $tags[$shop[0][id]]);

            // 查询是否收藏
            $isCollectMap['dataId'] = $storeId;
            $isCollectMap['userId'] = $userId;
            $isCollect = D('CollectShopLog')->isCollect($isCollectMap);

            // 构建数据
            if (empty($shop)) {
                $this->ret['status'] = -1;
                $this->ret['message'] = '查询店铺信息失败';
                $this->ret['info'] = (object) array();
            } else {
                // 查询商品
                $goodsFields = 'id,image,price,name,content';
                $where['storeId'] = $storeId;
                $goodsList = $goodsModel->selData($where, 20, $goodsFields);
                $goodsList = empty($goodsList) ? array() : $goodsList;
                $this->ret['status'] = 1;
                $this->ret['message'] = '编辑成功';
                $shop[0]['id'] = base64_encode($shop[0]['id']);//加密
                $this->ret['info'] = $shop[0];
                $this->ret['info']['isCollect'] = $isCollect;
                $this->ret['info']['tag'] = $tagsArray;
                $this->ret['info']['goods'] = $goodsList;
            }
        }

        $this->apiCallback($this->ret);exit();
    }

    /**
     * 店铺-编辑-商品
     * @access public
     * @param string $storetoken ：店铺token
     * @param string $userId ：会员ID
     * @param string $storeId ：店铺ID
     * @param string $password ：店铺登陆密码
     * @param string $introduction ：商品介绍JSON串；
     * 包含商品图片url地址image（是联想云存储服务器的地址），
     * 商品价格price，商品名称name，商品介绍content；数组结构,最多20个
     * @author FrankKung <kongfanjian@andlisoft.om>
     */
    public function updateGoods() {
        // 参数
        $storetoken = I("post.storetoken");
        $userId = I("post.userId");
        $storeId = I("post.storeId");
        $password = I("post.password");
        $introduction = I("post.introduction", '', 'strip_tags');

        // 参数检测
        if (is_empty($userId) || is_empty($storeId) || is_empty($password) || is_empty($introduction)) {
            $this->ret['status'] = -888;
            $this->ret['message'] = '参数不完整';
            $this->ret['info'] = (object) array();
            $this->apiCallback($this->ret);
        }
        $userId = base64_decode($userId);//解密
        $storeId = base64_decode($storeId);//解密
        $this->testStoreToken($storeId, $storetoken); //验证店铺token
        // 模型
        $shopModel = D("Shop");
        $goodsModel = D("Goods");

        // 检测token
        // 检测密码
        // if ( !$shopModel->passwordIsRight($password, $storeId) ) {
        // $this->ret['status'] = -490;
        // $this->ret['message'] = '密码不正确';
        // $this->ret['info'] = (object)array();
        // $this->apiCallback($this->ret);
        // }

        /*         * * 保存商品信息 & 查询店铺 Start ** */
        $goodsArray = json_decode($introduction, true);
        // var_dump($goodsArray);die;
        if (sizeof($goodsArray) > 20) { // 检测数量
            $this->ret['status'] = '-1';
            $this->ret['message'] = '编辑失败,商品数量大于20了。';
            $this->ret['info'] = (object) array();
            $this->apiCallback($this->ret);
        }
        // 启用回滚
        $goodsModel->startTrans();

        // 删除旧
        $where['storeId'] = $storeId;
        $hasOld = $goodsModel->selData($where, 1, 'id');
        if (empty($hasOld)) {//没有旧数据、则返回true
            $oldStatus = true;
        } else {
            $oldStatus = $goodsModel->where($where)->delete();
        }

        // 写入新
        if (!empty($goodsArray["introduction"])) {
            foreach ($goodsArray["introduction"] as $goods) {
                $goods['storeId'] = $storeId;
                $goods['addTime'] = time();
                //$goodsData[] = $goods;
                // $goodsData[] = $goods[0];
                $newStatus = $goodsModel->data($goods)->add();
            }
            //$newStatus = $goodsModel->addAll($goodsData);//非法数据对象
        } else {
            $newStatus = true; //商品为空，就直接为真
        }

        if (!empty($oldStatus) && !empty($newStatus)) { //提交事务
            $goodsModel->commit();
            $this->ret['status'] = '1';
            $this->ret['message'] = '编辑成功';
            // 查询店铺数据
            $whereShop['id'] = $storeId;
            $field = 'id,image,name,storetoken,phone,content,lng,lat,address,address_baidu,star,status'; // 查询字段
            $tagsField = 'id,tag1,tag2,tag3,tag4'; // 标签字段
            // 获取详情
            $shop = $shopModel->selData($whereShop, 1, $field);
            $tags = $shopModel->where($whereShop)->getField($tagsField, '|');
            $tagsArray = explode('|', $tags[$shop[0]['id']]);

            // 查询是否收藏
            $isCollectMap['dataId'] = $storeId;
            $isCollectMap['userId'] = $userId;
            $isCollect = D('CollectShopLog')->isCollect($isCollectMap);

            // 构建数据
            if (is_bool($shop) && empty($shop)) {
                $this->ret['status'] = -1;
                $this->ret['message'] = '查询失败';
                $this->ret['info'] = (object) array();
            } elseif ((is_array($shop) || is_null($shop)) && empty($shop)) {
                $this->ret['status'] = 0;
                $this->ret['message'] = '没有数据了';
                $this->ret['info'] = (object) array();
            } else {
                // 查询商品
                $goodsFields = 'id,image,price,name,content';
                $goodsList = $goodsModel->selData($where, 20, $goodsFields);
                $goodsList = empty($goodsList) ? array() : $goodsList;
                $shop[0]['id'] = base64_encode($shop[0]['id']);//加密
                $this->ret['info'] = $shop[0];
                $this->ret['info']['isCollect'] = $isCollect;
                $this->ret['info']['tag'] = $tagsArray;
                $this->ret['info']['goods'] = $goodsList;
            }
        } else { // 回滚
            $goodsModel->rollback();
            $this->ret['status'] = '-1';
            $this->ret['message'] = '编辑失败，回滚到旧数据';
            $this->ret['info'] = (object) array();
        }
        /*         * * 保存商品信息 & 查询店铺 End ** */

        $this->apiCallback($this->ret);exit();
    }

    /**
     * 店铺-修改-密码
     * @access public
     * @param string $storetoken  店铺token
     * @param string $userId  会员ID
     * @param string $storeId  店铺ID
     * @param string $newPassword  新密码
     * @param string $oldPassword  旧密码
     * @author FrankKung <kongfanjian@andlisoft.om>
     */
    public function updatePassword() {
        // 参数
        $storetoken = I("post.storetoken");
        $userId = I("post.userId");
        $storeId = I("post.storeId");
        $newPassword = I("post.newPassword");
        $oldPassword = I("post.oldPassword");



        // 参数检测
        if (is_empty($userId) || is_empty($storeId) || is_empty($newPassword) || is_empty($oldPassword)) {
            $this->ret['status'] = -888;
            $this->ret['message'] = '参数不完整';
            // $this->ret['info'] = (object)array();
            $this->apiCallback($this->ret);
        }

        $userId = base64_decode($userId);//解密
        $storeId = base64_decode($storeId);//解密
        $this->testStoreToken($storeId, $storetoken); //验证店铺token
        // 模型
        $shopModel = D("Shop");

        //查询店铺状态
        $status = $shopModel->selData(array('id' => $storeId), 1, 'status');
        $this->ret['info'] = $status[0]['status'];

        // 检测token
        // 检测密码
        if (!$shopModel->passwordIsRight($oldPassword, $storeId)) {
            $this->ret['status'] = -1;
            $this->ret['message'] = '密码不正确';
            // $this->ret['info'] = (object)array();
            $this->apiCallback($this->ret);
        }

        if ($newPassword == $oldPassword) {
            $this->ret['status'] = "1";
            $this->ret['message'] = '密码修改成功';
            // $this->ret['info'] = (object)array();
            $this->apiCallback($this->ret);
        }

        // 保存密码
        $data = array(
            "id" => $storeId,
            "password" => $newPassword,
        );
        $ret = $shopModel->save($data);

        if ($ret) {
            $this->ret['status'] = "1";
            $this->ret['message'] = '密码修改成功';
            // $this->ret['info'] = (object)array();
        } else {
            $this->ret['status'] = "-1";
            $this->ret['message'] = '密码修改失败';
            // $this->ret['info'] = (object)array();
        }
        $this->apiCallback($this->ret);exit();
    }

    /**
     * 店铺-导入
     * @access public
     * @param string $password  密码
     * @param string $phone  电话
     * @author FrankKung <kongfanjian@andlisoft.om>
     */
    public function importStore() {
        // 参数
        $password = I("post.password");
        $phone = I("post.phone");
        $code = I("post.code");
        //var_dump($code);die;

        // 参数检测
        /*
          if (is_empty($password) || is_empty($phone) || is_empty($code) ) {
          $this->ret['status'] = -888;
          $this->ret['message'] = '参数不完整';
          $this->ret['info'] = (object)array();
          $this->apiCallback($this->ret);
          } */

        // 模型
        $shopModel = D("Shop");
        $goodsModel = D("Goods");

        // 查询店铺数据
        $whereShop['phone'] = $phone;
        $whereShop['password'] = $password;
        
        $field = 'id,userId,image,password,name,storetoken,phone,content,lng,lat,address,address_baidu,star,status'; // 查询字段
        $tagsField = 'id,tag1,tag2,tag3,tag4'; // 标签字段
        // 获取详情
        $shop = $shopModel->selData($whereShop, 1, $field);

        if (empty($shop[0])) {
            $this->ret['status'] = -4111;
            $this->ret['message'] = '手机号不存在';
            $this->ret['info'] = (object) array();
        } else if ($shop[0]['password'] != $password) {
            $this->ret['status'] = -4110;
            $this->ret['message'] = '密码错误';
            $this->ret['info'] = (object) array();
        }
        // 构建数据
        // if ( empty($shop) ) {
        // $this->ret['status'] = -1;
        // $this->ret['message'] = '查询店铺信息失败';
        // $this->ret['info'] = (object)array();
        // }
        else {
            // 查询标签数据
            $tags = $shopModel->where($whereShop)->getField($tagsField, '|');
            $tagsArray = explode('|', $tags[$shop[0]['id']]);

            // 查询是否收藏
            $isCollectMap['dataId'] = $shop[0]['id'];
            $isCollectMap['userId'] = $shop[0]['userId'];
            $isCollect = D('CollectShopLog')->isCollect($isCollectMap);
            unset($shop[0]['userId']);

            //根据访问人的飞报号,去更新 店铺token
            // $mapU['id'] = $userId;
            // $feiBaoCode = D('Members')->selData($mapU,1,'code');//飞报号
            // $code = $feiBaoCode[0]['code'];
            //$storetoken = md5(md5($shop[0]['id']).C('TOKEN_ALL').md5($code));//店铺token
            $storetoken = md5(md5(base64_encode($shop[0]['id'])) . C('TOKEN_ALL') . md5($code)); //店铺token //加密

            $mapS['id'] = $shop[0]['id'];
            $dataS['storetoken'] = $storetoken;
            $shopModel->upData($mapS, $dataS);
            // $shop[0]['storetoken'] = $storetoken;//返回店铺token
            $shop[0]['storetoken'] = $storetoken; //返回店铺token
            // 查询商品
            $whereGoods['storeId'] = $shop[0]['id'];
            $goodsFields = 'id,image,price,name,content';
            $goodsList = $goodsModel->selData($whereGoods, 20, $goodsFields);
            $goodsList = empty($goodsList) ? array() : $goodsList;

            $this->ret['status'] = '1';
            $this->ret['message'] = '导入成功';
            $shop[0]['id'] = base64_encode($shop[0]['id']);//加密
            $this->ret['info'] = $shop[0];
            $this->ret['info']['isCollect'] = $isCollect;
            $this->ret['info']['tag'] = $tagsArray;
            $this->ret['info']['goods'] = $goodsList;
        }

        $this->apiCallback($this->ret);exit();
    }

    /**
     * 店铺-评星
     * @access public
     * @param string $id  店铺id
     * @param string $userId  用户id
     * @param int $content  星级
     * @author FrankKung <kongfanjian@andlisoft.om>
     */
    public function addStar() {
        // 参数
        $id = I("post.id");
        $userId = I("post.userId");
        $content = (int) I("post.content");
        unset($this->ret['info']);

        // 参数检测
        if (is_empty($id) || is_empty($userId) || is_empty($content)) {
            $this->ret['status'] = -888;
            $this->ret['message'] = '参数不完整';
            $this->apiCallback($this->ret);
        }

        $userId = base64_decode($userId);//解密
        $id = base64_decode($id);//解密


        // 检测星值合法性
        if ($content > 5 || $content < 0) {
            $this->ret['status'] = -1;
            $this->ret['message'] = '评星失败';
            $this->apiCallback($this->ret);
        }

        // 模型
        $starModel = D("Star");
        $shopModel = D("Shop");

        // 判断是否评过了
        $starWhere = array(
            'userId' => $userId,
            'dataId' => $id,
        );
        $star = $starModel->where($starWhere)->find();
        if (!is_null($star)) {
            $this->ret['status'] = -4121;
            $this->ret['message'] = '请勿重复评星';
            $this->apiCallback($this->ret);
        }

        // 保存评星数据
        $starData = array(
            'userId' => $userId,
            'dataId' => $id,
            'star' => $content,
            'time' => time(),
        );
        $addStatus = $starModel->add($starData);

        // 更新shop表
        $whereStar['dataId'] = $id;
        $totalStar = $starModel->getNum($whereStar);
        $sumStar = $starModel->where($whereStar)->sum('star');
        $shopStar['id'] = $id;
        $shopStar['star'] = number_format($sumStar / $totalStar, 1);
        $updateStatus = $shopModel->save($shopStar);

        if ($addStatus && $updateStatus) {
            $this->ret['status'] = 1;
            $this->ret['message'] = '评星成功';
        } else {
            $this->ret['status'] = -1;
            $this->ret['message'] = '评星失败';
        }

        $this->apiCallback($this->ret);exit();
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
        $dataId = I('post.dataId', '', 'trim', 'intval');
        $token = I('post.token', '', 'trim');
        $userId = I('post.userId', '', 'trim', 'intval');
        $category = I('post.category', '', 'trim', 'intval');
        $content = I('post.content', '', 'trim');
        
        $dataId = base64_decode($dataId);//解密
        $userId = base64_decode($userId);//解密
        
        $modelName = $this->categoryId2ModelName($category);
        if ($category == 2) {
            $map['id'] = $dataId;
            $self = D('Shop')->where($map)->find();
            if ($self['userId'] == $userId) {
                $return['status'] = -631;
                $return['message'] = '不能举报自己';
                echo jsonStr($return);
                exit;
            }
        }else{
            $return['status'] = -888;
            $return['message'] = '参数不完整';
        }

        if (is_empty($dataId) || is_empty($category) || is_empty($content)) {
            $return['status'] = -888;
            $return['message'] = '参数不完整';
        } else {
            //查询数据是否正常显示（删除等信息不做以下操作）
            $data = D("Shop")->where(array('id' => $dataId, "status" => "1"))->field("*")->find();
            
            if (empty($data)) {
                $return['status'] = -10;
                $return['message'] = '数据不存在、或非法传参';
                $return['success'] = true;
                $return['flag'] = '0';
            } else {
                $data = array(
                    'userId' => $userId,
                    'dataId' => $dataId,
                    'content' => $content,
                    'time' => time()
                );

                $res =  D($modelName)->add($data);
                if ($res == true) {
                    $return['status'] = 1;
                    $return['message'] = '举报成功';
                } else {
                    $return['status'] = -1;
                    $return['message'] = '举报失败';
                }
            }
        }
        
        header("Content-Type: application/json; charset=utf-8");
        echo jsonStr($return);
        die;
    }
    
        /**
     * categoryId2ModelName
     * @access private
     * @return string Model Name
     */
    private function categoryId2ModelName($category) {
        switch ($category) {  // 1头条；2店铺；3海报；4发现；5头条轮播图；6本地新闻
            case '6':
                $modelName = 'AccusationLocalnews';
                break;
            case '5':
                $modelName = 'AccusationNewsCarousel';
                break;
            case '4':
                $modelName = 'AccusationFound';
                break;
            case '3':
                $modelName = 'AccusationPoster';
                break;
            case '2':
                $modelName = 'AccusationShop';
                break;
            case '1':
                $modelName = 'AccusationNewsNormal';
                break;
        }
        return $modelName;
    }

    /**
     * 店铺-状态
     * @access public
     * @param string $id  店铺id
     * @param string $userId  用户id
     * @param int $content  1我要开业，2我要休业，3提交审核
     * @param int $storetoken 
     * @author FrankKung <kongfanjian@andlisoft.om>
     */
    public function setStatus() {
        // 参数
        $id = I("post.id");
        $userId = I("post.userId");
        $content = I("post.content");
        $storetoken = I("post.storetoken");
        unset($this->ret['info']);

        $userId = base64_decode($userId);//解密
        $id = base64_decode($id);//解密

        $this->testStoreToken($id, $storetoken); //验证店铺token
        // 参数检测
        if (is_empty($id) || is_empty($userId) || is_empty($content)) {
            $this->ret['status'] = -888;
            $this->ret['message'] = '参数不完整';
            $this->apiCallback($this->ret);
        }

        // 检测店铺状态合法性
        $shopStatus = array(1, 2, 3);
        if (!in_array($content, $shopStatus)) {
            $this->ret['status'] = -1;
            $this->ret['message'] = '设置失败,状态值不正确';
            $this->apiCallback($this->ret);
        }

        // 模型
        $shopModel = D("Shop");

        //查询店铺状态(审核状态时、返回已冻结)
        $status = $shopModel->selData(array('id' => $id), 1, 'status');
        if (($content == 1 || $content == 2) && ($status[0]['status'] == '3' || $status[0]['status'] == '4')) {
            $this->ret['status'] = -4130;
            $this->ret['message'] = '状态已冻结';
            $this->apiCallback($this->ret);
        }

        if ($content == 3) {
            //查询店铺状态(审核状态时、返回已冻结)
            $status = $shopModel->selData(array('id' => $id), 1, 'status');
            $this->ret['info'] = $status[0]['status'];

            //提交审核，需要同步添加“提交审核记录”
            $ExamineData['addTime'] = time();
            $ExamineData['userId'] = $userId;
            $ExamineData['shopId'] = $id;
            $shopStatus2 = D('ShopExamine')->addData($ExamineData);
            // var_dump($shopStatus2);
        }

        //状态已经为“提交审核”，就提示 不要重复 操作
        if ($content == 3 && ($status[0]['status'] == '3')) {
            $this->ret['status'] = 1;
            $this->ret['message'] = '设置成功';
            $this->apiCallback($this->ret);
        }

        //为4，才是“冻结”状态，方能提交审核
        if ($content == 3 && ($status[0]['status'] != '4')) {
            $this->ret['status'] = -4131;
            $this->ret['message'] = '状态未冻结';
            $this->apiCallback($this->ret);
        }

        // $storetoken 身份验证
        // 更新数据
        $statusData = array(
            'status' => (string) $content,
        );
        $setStatus = $shopModel->upData(array('id' => $id), $statusData);
        // echo $shopModel->getLastSql();die;
        if ($setStatus) {
            $this->ret['status'] = 1;
            $this->ret['message'] = '设置成功';
        } else {
            $this->ret['status'] = -1;
            $this->ret['message'] = '设置失败';
        }

        $this->apiCallback($this->ret);exit();
    }

}
