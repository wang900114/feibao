<?php

/**
 * 广告 数据接口
 * @author Jine <luxikun@andlisoft.com>
 */
class PostersController extends CommonController {

    protected $userId;

    /**
     * 初始化
     */
    public function _initialize() {
        parent::_initialize();
        $check_m = D('Check');
        $ACTION_NAME = strtolower(ACTION_NAME);

        $userId = I('post.userId');
        $phone = I('post.phone');
        $return['success'] = true;
        //echo $userId;die;

        if ($phone && $userId) {//判断参数是否为空
            $model = D("Members");
            if ($phone == '12345678900') {
                $res = $model->getUserDataByPhone($phone, 'id,freeze');
            } else {
                $res = $model->checkUserId($phone, $userId, 'id,freeze');
                //var_dump($res);die;
            }

            if (empty($res['id'])) {// 验证唯一码是否正确
                $return['status'] = 35;
                $return['message'] = '账号异常，已退出登录！ ';

                if (in_array($ACTION_NAME, array('homepage', 'searchfront', 'goodsdetail'))) {
                    $return['selectTime'] = '';
                    $return['userStatus'] = '';
                } else {
                    //$return['info'] = array();
                    $return['selectTime'] = '';
                    $return['range'] = '';
                    $return['userStatus'] = '';
                }

                echo jsonStr($return);
                exit(0);
            } else {
                if ($res['freeze'] != '0') {//验证账号是否非法
                    $return['status'] = 33;
                    $return['message'] = '账号非法，暂时无法完成此操作';

                    if (in_array($ACTION_NAME, array('homepage', 'searchfront', 'goodsdetail'))) {
                        $return['selectTime'] = '';
                        $return['userStatus'] = '';
                    } else {
                        //$return['info'] = array();
                        $return['selectTime'] = '';
                        $return['range'] = '';
                        $return['userStatus'] = '';
                    }
                    echo jsonStr($return);
                    exit(0);
                } else {
                    if (in_array($ACTION_NAME, array('expose', 'addforward', 'getmyfriends', 'addaccusation', 'share', 'myattenitionlist', 'undercarriage'))) {
                        if ($res['id'] == 44427) {
                            $return['status'] = 32;
                            $return['message'] = '请到个人中心登录';
                            //$return['info'] = array();
                            $return['selectTime'] = '';
                            $return['range'] = '';
                            $return['userStatus'] = '';
                            echo jsonStr($return);
                            exit(0);
                        }
                        $this->userId = $res['id'];
                    }
                    $this->userId = $res['id'];
                }
            }
        } else {
            if (in_array($ACTION_NAME, array('changedataid'))) {
                
            } else {
                $return['message'] = '操作失败';
                $return['status'] = 10;
                if (in_array($ACTION_NAME, array('homepage', 'searchfront', 'goodsdetail'))) {
                    $return['selectTime'] = '';
                    $return['userStatus'] = '';
                } else {
                    //$return['info'] = array();
                    $return['selectTime'] = '';
                    $return['range'] = '';
                    $return['userStatus'] = '';
                }
                echo jsonStr($return);
                exit(0);
            }
        }
    }

    /*
     * 转换广告id
     */

    public function changeDataId() {
        $dataId = I('post.dataId');

        if ($dataId) {
            $dataId = decodePass($dataId);

            if ($dataId) {
                $field = 'id,title,address,status,integral,warnPhone,userId,collectTotal,lngMax,latMax,lngMin,latMin,pushCityId,pushType,exposeTotalIntegral,extendTotalIntegral,weburl,addTime,proRedStart,proRedEnd,type,startTime,endTime';
                $res = D('Poster')->getPosterAdvert($dataId, $field); //查询广告基本信息
                if (is_bool($res) && empty($res)) {//判断广告状态
                    $return['status'] = -1;
                    $return['message'] = base64_encode('查询失败');
                    $return['title'] =base64_encode($res['title']);
                } else if ((is_array($res) || is_null($res)) && empty($res)) {
                    $return['status'] = 36;
                    $return['message'] = base64_encode('查询成功，暂无数据');
                    $return['title'] =base64_encode($res['title']);
                } else if ($res['status'] != '1') {
                    //1 正常;2 下架暂停;3 举报下架;4 未支付; 5 已到期 ; 6 飞币耗完 ;7 举报关闭; 8 待上架; 9 草稿箱
                    $return['status'] = -230;
                    $return['message'] = base64_encode('广告已关闭');
                    $return['title'] =base64_encode($res['title']);
                } else {

                    //判断当前时间
                    $nowTime = time();
                    if ($nowTime < $res['startTime'] || $nowTime > $res['endTime']) {
                        $return['status'] = 39;
                        $return['message'] = base64_encode('广告已过期');
                        $return['title'] =base64_encode($res['title']);
                        echo jsonStr($return);
                        exit(0);
                    }

                    //判断飞币
                    if ($res['integral'] <= ($res['exposeTotalIntegral'] + $res['extendTotalIntegral'])) {
                        $return['status'] = 40;
                        $return['message'] = base64_encode('广告飞币已经消耗完');
                        $return['title'] =base64_encode($res['title']);
                        echo jsonStr($return);
                        exit(0);
                    }

                    /*
                      //判断会员是否在广告范围内
                      if ($res['pushType'] == '4') {//判断全国投放
                      } elseif (($res['pushType'] == '3') || ($res['pushType'] == '2')) {
                      if ($cityId) {//判断会员id是否为空
                      if ($res['pushType'] == '2') {
                      if ($res['pushCityId'] != ','.$cityId.',') {//判断广告详情是否相同
                      $return['status'] = 38;
                      $return['message'] = '当前不在广告范围内';
                      echo jsonStr($return);
                      exit(0);
                      }
                      } else {//判断是否在区域范围内
                      $cityIdList = explode(',', $res['pushCityId']);
                      $flag = 1;
                      foreach ($cityIdList as $key => $val) {
                      if ($cityId == $val) {
                      $flag = 2;
                      }
                      }

                      if ($flag == 1) {
                      $return['status'] = 38;
                      $return['message'] = '当前不在广告范围内';
                      echo jsonStr($return);
                      exit(0);
                      }
                      }
                      } else {
                      $return['status'] = 38;
                      $return['message'] = '当前不在广告范围内';
                      echo jsonStr($return);
                      exit(0);
                      }
                      } else {//判断精准投放
                      if ($cityId && $myLng && $myLat) {
                      if ((','.$cityId.',' == $res['pushCityId']) && ($myLng > $res['lngMin']) && ($myLng > $res['lngMin']) && ($myLat > $res['latMin']) && ($myLat < $res['latMax'])) {

                      } else {
                      $return['status'] = 38;
                      $return['message'] = '当前不在广告范围内';
                      echo jsonStr($return);
                      exit(0);
                      }
                      }
                      }
                     */
                    $return['message'] = base64_encode('查询成功');
                    $return['status'] = 1;

                    $res['id'] = encodePass($res['id']);
                    //$res['title'] = strip_name_badwords($res['title'],2);
                    $return['info'] = $res;
                }
            }

            $return['dataId'] = encodePass($dataId);
        } else {
            $return['dataId'] = '';
        }
        echo jsonStr($return);exit();
    }

    /**
     * 3.2广告首页列表
     * @param  string $version 版本号
     * @param  string $page 当前页
     * @param  string $pageSize 每页显示数量
     * @param  string $userId 唯一码
     * @param  string $phone 会员注册手机号
     * @param  string $cityId 上一次请求的城市id
     * @param  string $selectTime 每次刷新请求返回的时间
     * @return json 广告数据的JSON字符串
     */
    public function homePage() {
        $return['success'] = true;

        $version = I('post.version');
        $userId = I('post.userId');
        $phone = I('post.phone');
        //$cityId = I('post.cityId');
        $selectTime = I('post.selectTime');
        $cityId = 10000;

        if (is_empty($version) || is_empty($userId) || is_empty($phone)) {//判断参数是否有缺失
            $return['status'] = -888;
            $return['message'] = '传参不完整';
        } else {
            $userId = $this->userId;

            //获取广告轮播图数据
            $modelPoster = D('Poster');
            $resPoster = $modelPoster->getHomePage($cityId, 'id,title,address,is_above_display,shareUrl,imageUrl,warnPhone,collectTotal,weburl,userId,addTime');
            //var_dump($resPoster);die;
            //判断广告获取飞币状态
            if ($resPoster) {
                foreach ($resPoster as $k => $v) {

                    $resPoster[$k]['title'] = base64_encode(jsonStrWithOutBadWordsNew($v['title'], 2));

                    $mapP['dataId'] = $v['id'];
                    $mapP['userId'] = $res['id'];

                    //获取打包路径
                    //$mapPP['dataId'] = $v['id'];
                    //$dataP = D('PicturePoster')->selData($mapPP, '', 'field');
                    //$resPoster[$k]['field'] = empty($dataP) ? '':$dataP['field'];
                    $resPoster[$k]['field'] = 'http://dev.feibaokeji.com/Application/Home/View/Adinfo/index.html?userId=' . $userId . '&phone =' . $phone . '&version=' . $version . '&dataId=' . $v['id'] . '&myLng=&MyLat=&cityId=' . $cityId;

                    if ($userId == 44427) {//广告收藏状态、获取飞币状态
                        $resPoster[$k]['collectflag'] = 2;
                        $resPoster[$k]['isExpose'] = '2';
                    } else {
                        $reB = D('ExposePosterLog')->selData($mapP, 1); //查询揭广告状态
                        $resPoster[$k]['isExpose'] = empty($reB[0]) ? '2' : '1';

                        $rec = D("Members")->getUserCollectStatus($v['id'], $userId);
                        $resPoster[$k]['collectflag'] = $rec ? 1 : 2;
                    }

                    //$field = 'id,uniqueId as userId,name,jpush,phone,image,imageUrl,integral,cityId,provinceId,freeze,handlePassword,type';
                    $resUser = D("Members")->getUserInfo($v['userId']);

                    //设置会员信息
                    $resPoster[$k]['userId'] = '';
                    $resPoster[$k]['nickname'] = '';
                    $resPoster[$k]['userImage'] = '';

                    if ($resUser['id']) {
                        $resPoster[$k]['userId'] = encodePass($v['userId']);
                        $resPoster[$k]['nickname'] = $resUser['name'];
                        $resPoster[$k]['userImage'] = $resUser['imageUrl'];
                    }
                    $resPoster[$k]['id'] = encodePass($v['id']);
                }
            }

            //获取首页模块数据
            $resCategory = $modelPoster->getHomeCategory($selectTime, 'id,nickName,name,imageUrl,status,addTime');
            if ($resCategory) {
                foreach ($resCategory as $k => $v) {//返回最新状态 1：是，2：否
                    $resCategory[$k]['isNew'] = 2;
                    $resCategory[$k]['type'] = 1;

                    if ($v['nickName']) {
                        $resCategory[$k]['name'] = $v['nickName'];
                    }
                    $resCategory[$k]['id'] = encodePass($v['id']);
                    if ($v['addTime'] > $selectTime) {
                        $resCategory[$k]['isNew'] = 1;
                    }
                }
            }

            if (empty($resPoster) && empty($resCategory)) {//判断返回数据
                $return['info']['modulePosters'] = array();
                $return['info']['publicPosters'] = array();
                $return['selectTime'] = time();

                $return['status'] = 36;
                $return['message'] = '查询成功，暂无数据';
            } elseif (empty($resPoster) && !empty($resCategory)) {

                $return['info']['modulePosters'] = $resCategory;
                $return['info']['publicPosters'] = array();
                $return['selectTime'] = time();

                $return['status'] = 36;
                $return['message'] = '查询成功，暂无数据';
            } elseif (!empty($resPoster) && empty($resCategory)) {

                $return['info']['modulePosters'] = array();
                $return['info']['publicPosters'] = $resPoster;
                $return['selectTime'] = time();

                $return['status'] = 36;
                $return['message'] = '查询成功，暂无数据';
            } else {
                $return['info']['modulePosters'] = $resCategory;
                $return['info']['publicPosters'] = $resPoster;
                $return['selectTime'] = time();

                $return['status'] = 1;
                $return['message'] = '查询成功';
            }
        }
        echo jsonStr($return);exit();
    }

    /**
     * 3.2广告搜索前置列表
     * @param  string $version 版本号
     * @param  string $userId 唯一码
     * @param  string $phone 会员注册手机号
     * @return json 广告数据的JSON字符串
     */
    public function searchFront() {
        $return['success'] = true;

        $version = I('post.version');
        $userId = I('post.userId');
        $phone = I('post.phone');

        if (is_empty($version) || is_empty($userId) || is_empty($phone)) {//判断参数是否有缺失
            $return['status'] = -888;
            $return['message'] = '传参不完整';
        } else {
            $userId = $this->userId;
            $modelPoster = D('Poster');
            //查询搜索词信息
            $searchList = $modelPoster->getSearchList('id,content,amount,addTime');
            //var_dump($searchList);die;
            if ($searchList) {
                foreach ($searchList as $k => $v) {//返回分类类型
                    $searchList[$k]['id'] = encodePass($v['id']);
                    $searchList[$k]['type'] = 4;
                }
                $return['info']['searchList'] = $searchList;
            } else {
                $return['info']['searchList'] = array();
            }
            //查询分类信息
            $categoryList = $modelPoster->getCategoryList('cid as id,name');
            if ($categoryList) {
                foreach ($categoryList as $k => $v) {//返回分类类型
                    $categoryList[$k]['id'] = encodePass($v['id']);
                    $categoryList[$k]['type'] = 2;
                }
                $return['info']['categoryList'] = $categoryList;
            } else {
                $return['info']['categoryList'] = array();
            }

            if ($searchList && $categoryList) {
                $return['status'] = 1;
                $return['message'] = '查询成功';
            } else {
                $return['status'] = 36;
                $return['message'] = '查询成功，暂无数据';
            }
            //20150521-xiaofeng 优化 注释以下
//            if (empty($searchList) && empty($categoryList)) {//判断返回数据
//                $return['info']['searchList'] = array();
//                $return['info']['categoryList'] = array();
//
//                $return['status'] = 36;
//                $return['message'] = '查询成功，暂无数据';
//            } elseif (empty($searchList) && !empty($categoryList)) {
//                $return['info']['searchList'] = array();
//                $return['info']['categoryList'] = $categoryList;
//
//                $return['status'] = 36;
//                $return['message'] = '查询成功，暂无数据';
//            } elseif (!empty($searchList) && empty($categoryList)) {
//                $return['info']['searchList'] = $searchList;
//                $return['info']['categoryList'] = array();
//
//                $return['status'] = 36;
//                $return['message'] = '查询成功，暂无数据';
//            } else {
//                $return['info']['searchList'] = $searchList;
//                $return['info']['categoryList'] = $categoryList;
//
//                $return['status'] = 1;
//                $return['message'] = '查询成功';
//            }
        }
        echo jsonStr($return);exit();
    }

    /**
     * 3.2广告信息列表
     * @param  string $version 版本号
     * @param  string $myLng 物理地址经度(即手机GPS定位的“我的位置”)
     * @param  string $myLat 物理地址纬度(即手机GPS定位的“我的位置”)
     * @param  string $page 当前页
     * @param  string $pageSize 每页显示数量
     * @param  string $userId 唯一码
     * @param  string $phone 会员注册手机号
     * @param  string $cityId 上一次请求的城市id
     * @param  string $typeId 分类id     
     * @param  string $type 类型：1-首页广告模块，2-分类，3-自定义标签，4-搜索词
     * @param  string $selectTime 每次刷新请求返回的时间【必填项】
     * @return json 广告数据的JSON字符串
     */
    public function dataList() {
        $return['success'] = true;

        $version = I('post.version');
        $userId = I('post.userId');
        $phone = I('post.phone');
        $myLng = I('post.myLng');
        $myLat = I('post.myLat');

        $page = I('post.page');
        $pageSize = I('post.pageSize');
        $cityId = I('post.cityId');
        $typeId = I('post.typeId');

        $type = I('post.type');
        $selectTime = I('post.selectTime');

        if (is_empty($version) || is_empty($userId) || is_empty($phone) || is_empty($page) || is_empty($typeId) || is_empty($pageSize)) {//判断参数是否有缺失
            $return['status'] = -888;
            $return['message'] = '传参不完整';
        } else {
            $userId = $this->userId;
            $modelPoster = D('Poster');

            //查询搜索词信息
            $field = 'id,name';
            $typeId = decodePass($typeId);
            //echo $typeId;die;
            $searchList = $modelPoster->dataList($type, $typeId, $selectTime, $page, $pageSize, $field, $cityId, $myLng, $myLat);

            $return['selectTime'] = time();
            if (empty($searchList)) {
                $return['status'] = 36;
                $return['message'] = '查询成功，暂无数据';

                $return['range'] = '';
                $return['info'] = array();
            } else {
                $return['status'] = 1;
                $return['message'] = '查询成功';

                $modelUser = D("Members");
                foreach ($searchList as $k => $v) {
                    //会员相关信息
                    $field = 'id,uniqueId as userId,name,jpush,phone,image,imageUrl,integral,cityId,provinceId,freeze,handlePassword,type';
                    $resUser = $modelUser->getUserInfo($v['userId']);

                    //设置会员信息
                    $searchList[$k]['userId'] = '';
                    $searchList[$k]['nickname'] = '';
                    $searchList[$k]['userImage'] = '';

                    if ($resUser['id']) {
                        $searchList[$k]['userId'] = encodePass($resUser['id']);
                        $searchList[$k]['nickname'] = $resUser['name'];
                        $searchList[$k]['userImage'] = $resUser['imageUrl'];
                    }

                    $searchList[$k]['title'] = base64_encode(jsonStrWithOutBadWordsNew($v['title'], 2));

                    //if ($res['id']) {//首先判断会员id是否正确
                    $mapP['dataId'] = $v['id'];
                    $mapP['userId'] = $userId;

                    //获取打包路径
                    $mapPP['dataId'] = $v['id'];
                    $dataP = D('PicturePoster')->selData($mapPP, '', 'field');
                    //$resPoster[$k]['field'] = empty($dataP) ? '':$dataP['field'];
                    $searchList[$k]['field'] = 'http://dev.feibaokeji.com/Application/Home/View/Adinfo/index.html?id=3&userId=1&phone=12345678910';


                    if ($userId == 44427) {
                        $searchList[$k]['collectflag'] = 2;
                        $searchList[$k]['isExpose'] = '2';
                    } else {
                        $reB = D('ExposePosterLog')->selData($mapP, 1); //查询揭广告状态
                        $searchList[$k]['isExpose'] = empty($reB[0]) ? '2' : '1';

                        $rec = $modelUser->getUserCollectStatus($v['id'], $userId); //广告收藏状态
                        $searchList[$k]['collectflag'] = $rec ? 1 : 2;
                    }
                    //$searchList[$k]['testid'] = $v['id'];
                    $searchList[$k]['id'] = encodePass($v['id']);

                    if ($k == (count($searchList) - 1)) {
                        if ($v['pushType'] != '1') {
                            $return['range'] = '1公里外';
                        } else {
                            $range = GetDistance($myLng, $myLat, $searchList[$k]['lng'], $searchList[$k]['lat']);
                            $return['range'] = judgeDistance($range);
                        }
                    }
                }

                //更新会员定位信息
                if ($page == 1 && $myLng && $myLat && $userId != 44427) {
                    $modelAddress = D('Members');
                    $modelAddress->updateUserAddress($userId, $myLng, $myLat, $cityId);
                }

                //$rangelist = array_slice($searchList, -1, 1);
                //$range = GetDistance($myLng, $myLat, $rangelist[0]['lng'], $rangelist[0]['lat']);
                //$return['range'] = judgeDistance($range);
                $return['info'] = $searchList;
            }
        }
        echo jsonStr($return);exit();
    }

    /**
     * 3.2广告搜索信息列表
     * @param  string $version 版本号
     * @param  string $myLng 物理地址经度(即手机GPS定位的“我的位置”)
     * @param  string $myLat 物理地址纬度(即手机GPS定位的“我的位置”)
     * @param  string $page 当前页
     * @param  string $pageSize 每页显示数量
     * @param  string $userId 唯一码
     * @param  string $phone 会员注册手机号
     * @param  string $cityId 上一次请求的城市id
     * @param  string $search:搜索的关键词   
     * @param  string $selectTime 每次刷新请求返回的时间【必填项】
     * @return json 广告数据的JSON字符串
     */
    public function searchList() {
        $return['success'] = true;

        $version = I('post.version');
        $userId = I('post.userId');
        $phone = I('post.phone');
        $myLng = I('post.myLng');
        $myLat = I('post.myLat');

        $page = I('post.page');
        $pageSize = I('post.pageSize');
        $cityId = I('post.cityId');
        $search = I('post.search');
        $selectTime = I('post.selectTime');
        //if (is_empty($version) || is_empty($page) || is_empty($myLat) || is_empty($myLng) || is_empty($search) || is_empty($pageSize)) {//判断参数是否有缺失
        if (is_empty($version) || is_empty($page) || is_empty($search) || is_empty($pageSize)) {//判断参数是否有缺失
            $return['status'] = -888;
            $return['message'] = '传参不完整';
        } else {
            $userId = $this->userId;
            $modelPoster = D('Poster');

            $flag = check_name_badwords($search, 2);
            if ($flag == 2) {
                $return['status'] = 43;
                $return['message'] = '你搜索的信息太敏感，请更换搜索内容';

                $return['range'] = '';
                $return['info'] = array();
                echo jsonStr($return);
                exit();
            }

            //查询搜索词信息
            $field = 'id,name';
            $searchList = $modelPoster->datasearchList($search, $selectTime, $page, $pageSize, $field, $cityId, $myLng, $myLat);

            $return['selectTime'] = time();
            if (empty($searchList)) {
                $modelPoster->addSearch($search);
                $return['status'] = 36;
                $return['message'] = '查询成功，暂无数据';

                $return['range'] = '';
                $return['info'] = array();
            } else {
                $modelPoster->addSearch($search);
                $return['status'] = 1;
                $return['message'] = '查询成功';

                $modelUser = D("Members");
                foreach ($searchList as $k => $v) {
                    //会员相关信息
                    $field = 'id,uniqueId as userId,name,jpush,phone,image,imageUrl,integral,cityId,provinceId,freeze,handlePassword,type';
                    $resUser = $modelUser->getUserInfo($v['userId']);

                    //设置会员信息
                    $searchList[$k]['userId'] = '';
                    $searchList[$k]['nickname'] = '';
                    $searchList[$k]['userImage'] = '';

                    if ($resUser['id']) {
                        $searchList[$k]['userId'] = encodePass($resUser['id']);
                        $searchList[$k]['nickname'] = $resUser['name'];
                        $searchList[$k]['userImage'] = $resUser['imageUrl'];
                    }

                    $searchList[$k]['title'] = base64_encode(jsonStrWithOutBadWordsNew($v['title'], 2));
                    $mapP['dataId'] = $v['id'];
                    $mapP['userId'] = $userId;

                    //获取打包路径
                    $mapPP['dataId'] = $v['id'];
                    $dataP = D('PicturePoster')->selData($mapPP, '', 'field');
                    //$resPoster[$k]['field'] = empty($dataP) ? '':$dataP['field'];
                    $searchList[$k]['field'] = 'http://dev.feibaokeji.com/Application/Home/View/Adinfo/index.html?id=3&userId=1&phone=12345678910';

                    if ($userId == 44427) {
                        $searchList[$k]['collectflag'] = 2;
                        $searchList[$k]['isExpose'] = '2';
                    } else {
                        $reB = D('ExposePosterLog')->selData($mapP, 1); //查询揭广告状态
                        $searchList[$k]['isExpose'] = empty($reB[0]) ? '2' : '1';

                        $rec = $modelUser->getUserCollectStatus($v['id'], $userId); //广告收藏状态
                        $searchList[$k]['collectflag'] = $rec ? 1 : 2;
                    }

                    if ($k == (count($searchList) - 1)) {
                        //'116.493690', '39.922940'  '116.495027', '39.923396' $myLng, $myLat $searchList[$k]['lng'], $searchList[$k]['lat']
                        $range = GetDistance($myLng, $myLat, $searchList[$k]['lng'], $searchList[$k]['lat']);
                        $return['range'] = judgeDistance($range);
                    }
                    $searchList[$k]['id'] = encodePass($v['id']);
                }

                //更新会员定位信息
                if ($page == 1 && $myLng && $myLat && $userId != 44427) {
                    $modelAddress = D('Members');
                    $modelAddress->updateUserAddress($userId, $myLng, $myLat, $cityId);
                }

                //$rangelist = array_slice($searchList, -1, 1);
                //$range = GetDistance($myLng, $myLat, $rangelist[0]['lng'], $rangelist[0]['lat']);
                //$return['range'] = judgeDistance($range);
                $return['info'] = $searchList;
            }
        }
        echo jsonStr($return);exit();
    }

    /*
     * 3.2广告下架
     * @param  string $version 版本号
     * @param  string $userId 唯一码
     * @param  string $phone 会员注册手机号
     * @param  string $dataId 广告id
     * @return json 广告数据的JSON字符串
     */

    public function undercarriage() {
        $return['success'] = true;

        $version = I('post.version');
        $userId = I('post.userId');
        $phone = I('post.phone');
        $id = I('post.dataId');

        if (is_empty($id) || is_empty($phone) || is_empty($userId) || is_empty($version)) {//判断参数是否有缺失
            $return['status'] = 10;
            $return['message'] = '操作失败';
        } else {
            $userId = $this->userId;
            $id = decodePass($id);

            $res = D('Poster')->undercarriage($userId, $id);

            if ($res == 1) {
                $return['status'] = 1;
                $return['message'] = '下架成功';
            } elseif ($res == 2) {
                $return['status'] = 39;
                $return['message'] = '广告已下架';
            } else {
                $return['status'] = 10;
                $return['message'] = '操作失败';
            }
        }
        echo jsonStr($return);exit();
    }

    /**
     * 3.2我发布广告信息列表
     * @param  string $version 版本号
     * @param  string $page 当前页
     * @param  string $pageSize 每页显示数量
     * @param  string $userId 唯一码
     * @param  string $phone 会员注册手机号
     * @param  string $selectTime 每次刷新请求返回的时间【必填项】
     * @return json 广告数据的JSON字符串
     */
    public function myDataList() {
        $return['success'] = true;

        $version = I('post.version');
        $userId = I('post.userId');
        $phone = I('post.phone');

        $page = I('post.page');
        $pageSize = I('post.pageSize');
        $minId = I('post.minId');
        $selectTime = I('post.selectTime', time());


        if (is_empty($version) || is_empty($page) || is_empty($userId) || is_empty($pageSize)) {//判断参数是否有缺失
            $return['status'] = -888;
            $return['message'] = '传参不完整';
        } else {

            $userId = $this->userId;
            if (empty($minId)) {
                $minId = 0;
            } else {
                $minId = decodePass($minId);
            }
            //echo $userId;die;

            $modelPoster = D('Poster');
            //查询搜索词信息
            $field = 'id,name';
            $searchList = $modelPoster->myDatasList($userId, $minId, $page, $pageSize, $field, $selectTime);

            $return['selectTime'] = time();
            if (empty($searchList)) {
                $return['status'] = 36;
                $return['message'] = '查询成功，暂无数据';

                $return['range'] = '';
                $return['info'] = array();
            } else {
                $return['status'] = 1;
                $return['message'] = '查询成功';

                $modelUser = D("Members");
                foreach ($searchList as $k => $v) {

                    if ($v['status'] == 4) {//受限判断是否支付
                        $searchList[$k]['status'] = 7;
                    } else {
                        if ($v['endTime'] < time()) {//判断是否已到期
                            $searchList[$k]['status'] = 8;
                        } else {
                            if ($v['integral'] <= ($v['exposeTotalIntegral'] + $v['extendTotalIntegral'])) {//判断是否飞币已经耗尽
                                $searchList[$k]['status'] = 2;
                            } else {
                                if ($v['status'] == 2) {//下架暂停
                                    $searchList[$k]['status'] = 5;
                                } elseif ($v['status'] == 3) {//判断举报下架
                                    $searchList[$k]['status'] = 6;
                                } elseif ($v['status'] == 7) {
                                    $searchList[$k]['status'] = 4;
                                } else {
                                    $endBefore = C('END_BEFORE_BASIC');
                                    if ($v['endTime'] - time() <= (3600 * 24 * $endBefore)) {
                                        $searchList[$k]['status'] = 3;
                                    } else {
                                        $searchList[$k]['status'] = 1;
                                    }
                                }
                            }
                        }
                    }
                    $field = 'id,uniqueId as userId,name,imageUrl';
                    $res = D('Members')->getUserInfo($userId);

                    $searchList[$k]['userId'] = encodePass($res['userId']);
                    $searchList[$k]['nickname'] = $res['name'];
                    $searchList[$k]['userImage'] = $res['imageUrl'];

                    $mapP['dataId'] = $v['id'];
                    $mapP['userId'] = $userId;

                    //获取打包路径
                    $mapPP['dataId'] = $v['id'];
                    $dataP = D('PicturePoster')->selData($mapPP, '', 'field');
                    //$resPoster[$k]['field'] = empty($dataP) ? '':$dataP['field'];
                    $searchList[$k]['field'] = 'http://dev.feibaokeji.com/Application/Home/View/Adinfo/index.html?id=3&userId=1&phone=12345678910';

                    $reB = D('ExposePosterLog')->selData($mapP, 1); //查询揭广告状态
                    $searchList[$k]['isExpose'] = empty($reB[0]) ? '2' : '1';

                    $rec = $modelUser->getUserCollectStatus($v['id'], $userId); //广告收藏状态
                    $searchList[$k]['collectflag'] = $rec ? 1 : 2;

                    $searchList[$k]['title'] = base64_encode(jsonStrWithOutBadWordsNew($v['title'], 2));

                    $searchList[$k]['id'] = encodePass($v['id']);
                }

                //$rangelist=array_slice($searchList,-1,1);
                //$range=GetDistance($myLng, $myLat, $rangelist[0]['lng'], $rangelist[0]['lat']);
                //$return['range']=judgeDistance($range);                 
                $return['info'] = $searchList;
                $return['selectTime'] = $selectTime;
            }
        }
        echo jsonStr($return);exit();
    }

    /**
     * 3.2我的收藏广告信息列表
     * @param  string $version 版本号
     * @param  string $page 当前页
     * @param  string $pageSize 每页显示数量
     * @param  string $userId 唯一码
     * @param  string $phone 会员注册手机号
     * @param  string $selectTime 每次刷新请求返回的时间【必填项】
     * @return json 广告数据的JSON字符串
     */
    public function myCollectList() {
        $return['success'] = true;

        $version = I('post.version');
        $userId = I('post.userId');
        $phone = I('post.phone');

        $page = I('post.page');
        $pageSize = I('post.pageSize');
        $selectTime = I('post.selectTime');

        if (is_empty($version) || is_empty($page) || is_empty($userId) || is_empty($pageSize)) {//判断参数是否有缺失
            $return['status'] = -888;
            $return['message'] = '传参不完整';
        } else {

            $userId = $this->userId;
            $modelPoster = D('Poster');
            //查询搜索词信息
            $field = 'id,name';
            $searchList = $modelPoster->myCollectList($userId, $selectTime, $page, $pageSize, $field);
            //var_dump($searchList);die;

            $return['selectTime'] = time();
            if (empty($searchList)) {
                $return['status'] = 36;
                $return['message'] = '查询成功，暂无数据';

                $return['range'] = '';
                $return['info'] = array();
            } else {
                $return['status'] = 1;
                $return['message'] = '查询成功';

                $modelUser = D("Members");
                foreach ($searchList as $k => $v) {

                    $field = 'id,title,userId,is_above_display,imageUrl,userId,address,addTime,status,warnPhone,shareUrl,pushCityId,pushType,collectTotal,weburl,addTime,proRedStart,proRedEnd,type,startTime,endTime';
                    $resPoster = D('Poster')->getPosterAdvert($v['dataId'], $field); //查询广告基本信息

                    $field = 'id,uniqueId as userId,name,jpush,phone,image,imageUrl,integral,cityId,provinceId,freeze,handlePassword,type';
                    $res = D('Members')->getUserInfo($resPoster['userId']);


                    $searchList[$k]['userId'] = encodePass($res['id']);
                    $searchList[$k]['nickname'] = $res['name'];
                    $searchList[$k]['userImage'] = $res['imageUrl'];

                    $mapP['dataId'] = $v['dataId'];
                    $mapP['userId'] = $userId;
                    if ($userId == 44427) {
                        $searchList[$k]['collectflag'] = 2;
                        $searchList[$k]['isExpose'] = '2';
                    } else {
                        $reB = D('ExposePosterLog')->selData($mapP, 1); //查询揭广告状态
                        $searchList[$k]['isExpose'] = empty($reB[0]) ? '2' : '1';

                        $rec = $modelUser->getUserCollectStatus($v['dataId'], $userId); //广告收藏状态
                        $searchList[$k]['collectflag'] = $rec ? 1 : 2;
                    }
                    $searchList[$k]['weburl'] = $resPoster['weburl'];
                    $searchList[$k]['imageUrl'] = $resPoster['imageUrl'];
                    $searchList[$k]['collectTotal'] = $resPoster['collectTotal'];
                    $searchList[$k]['warnPhone'] = $resPoster['warnPhone'];

                    $searchList[$k]['title'] = base64_encode(jsonStrWithOutBadWordsNew($resPoster['title'], 2));

                    $searchList[$k]['id'] = encodePass($v['dataId']);
                }

                //$rangelist=array_slice($searchList,-1,1);
                //$range=GetDistance($myLng, $myLat, $rangelist[0]['lng'], $rangelist[0]['lat']);
                //$return['range']=judgeDistance($range);                 
                $return['info'] = $searchList;
            }
        }
        echo jsonStr($return);exit();
    }

    /**
     * 3.2广告详情信息
     * @param  string $version 版本号
     * @param  string $userId 唯一码
     * @param  string $phone 会员注册手机号
     * @param  string $dataId 广告ID【必填项】
     * @param  string $myLng：物理地址经度(即手机GPS定位的“我的位置”)
     * @param  string $myLat: 物理地址纬度(即手机GPS定位的“我的位置”)
     * @param  string $cityId: 城市id
     * @return json 广告数据的JSON字符串
     */
    public function dataDetail() {
        $return['success'] = true;

        $version = I('post.version');
        $userId = I('post.userId');
        $phone = I('post.phone');
        $id = I('post.dataId');
        $myLng = I('post.myLng');
        $myLat = I('post.myLat');
        $cityId = I('post.cityId');

        if (is_empty($version) || is_empty($userId) || is_empty($phone) || is_empty($id)) {//判断参数
            $return['status'] = -888;
            $return['message'] = '传参不完整';
        } else {

            $userId = $this->userId;
            $id = decodePass($id);

            $res = D('Poster')->getPosterAdvert($id); //查询广告基本信息

            if (is_bool($res) && empty($res)) {//判断广告状态
                $return['status'] = -1;
                $return['message'] = '查询失败';
            } else if ((is_array($res) || is_null($re)) && empty($res)) {
                $return['status'] = 36;
                $return['message'] = '查询成功，暂无数据';
            } else if ($res['status'] != '1') {
                //1 正常;2 下架暂停;3 举报下架;4 未支付; 5 已到期 ; 6 飞币耗完 ;7 举报关闭; 8 待上架; 9 草稿箱
                $return['status'] = -230;
                $return['message'] = '广告已关闭';
            } else {

                //判断当前时间
                $nowTime = time();
                if ($nowTime < $res['startTime'] || $nowTime > $res['endTime']) {
                    $return['status'] = 39;
                    $return['message'] = '广告已过期';
                    echo jsonStr($return);
                    exit(0);
                }

                //判断飞币
                if ($res['integral'] <= ($res['exposeTotalIntegral'] + $res['extendTotalIntegral'])) {
                    $return['status'] = 40;
                    $return['message'] = '广告飞币已经消耗完';
                    echo jsonStr($return);
                    exit(0);
                }


                //判断会员是否在广告范围内
                if ($res['pushType'] == '4') {//判断全国投放
                } elseif (($res['pushType'] == '3') || ($res['pushType'] == '2')) {
                    if ($cityId) {//判断会员id是否为空
                        if ($res['pushType'] == '2') {
                            if ($res['pushCityId'] != ',' . $cityId . ',') {//判断广告详情是否相同
                                $return['status'] = 38;
                                $return['message'] = '当前不在广告范围内';
                                echo jsonStr($return);
                                exit(0);
                            }
                        } else {//判断是否在区域范围内
                            $cityIdList = explode(',', $res['pushCityId']);
                            $flag = 1;
                            foreach ($cityIdList as $key => $val) {
                                if ($cityId == $val) {
                                    $flag = 2;
                                }
                            }

                            if ($flag == 1) {
                                $return['status'] = 38;
                                $return['message'] = '当前不在广告范围内';
                                echo jsonStr($return);
                                exit(0);
                            }
                        }
                    } else {
                        $return['status'] = 38;
                        $return['message'] = '当前不在广告范围内';
                        echo jsonStr($return);
                        exit(0);
                    }
                } else {//判断精准投放
                    if ($cityId && $myLng && $myLat) {
                        if ((',' . $cityId . ',' == $res['pushCityId']) && ($myLng > $res['lngMin']) && ($myLng < $res['lngMax']) && ($myLat > $res['latMin']) && ($myLat < $res['latMax'])) {
                            
                        } else {
                            $return['status'] = 38;
                            $return['message'] = '当前不在广告范围内';
                            echo jsonStr($return);
                            exit(0);
                        }
                    }
                }
                $return['status'] = 1;
                $return['message'] = '查询成功';

                //D('Poster')->addClickTotal($id);
                if ($userId == 44427) {
                    $res['collectflag'] = 2;
                    $res['isExpose'] = '2';
                } else {
                    //查询揭广告状态
                    $mapP['dataId'] = $id;
                    $mapP['userId'] = $resUser['id'];
                    $reB = D('ExposePosterLog')->selData($mapP, 1);
                    $res['isExpose'] = empty($reB[0]) ? '0' : '1';


                    $rec = D("Members")->getUserCollectStatus($id, $userId); //广告收藏状态
                    //var_dump($rec);die;
                    $res['collectflag'] = $rec ? 1 : 2;
                }

                //获取打包路径
                //$mapP['dataId'] = $id;
                //$dataP = D('PicturePoster')->selData($mapP, '', 'field');
                //$res['field'] = empty($dataP) ? '':$dataP['field'];
                //$res['field'] = 'http://dev.feibaokeji.com/Application/Home/View/Adinfo/index.html?id=3&userId=1&phone=12345678910';

                $res['id'] = encodePass($res['id']);
                $return['info'] = $res;
            }
        }
        echo jsonStr($return);exit();
    }

    /**
     * 3.2广告商品详情信息
     * @param  string $version 版本号
     * @param  string $userId 唯一码
     * @param  string $phone 会员注册手机号
     * @param  string $dataId 广告ID【必填项】
     * @param  string $type：类型：1-商品，2-优惠【必填项】
     * @return json 广告商品数据的JSON字符串
     */
    public function goodsDetail() {
        $return['success'] = true;

        $version = I('post.version');
        $userId = I('post.userId');
        $phone = I('post.phone');
        $id = I('post.dataId');
        $type = I('post.type');

        if (is_empty($version) || is_empty($userId) || is_empty($phone) || is_empty($id)) {//判断参数
            $return['status'] = -888;
            $return['message'] = '传参不完整';
        } else {

            $userId = $this->userId;
            $id = decodePass($id);

            if ($type == 1) {//商品
                $res = D('Poster')->getGoods($id, 'id as goodsId,goodsTitle,goodsPrice,goodsImage,goodsLink'); //查询广告商品信息

                $return['info']['goodsId'] = '';
                $return['info']['goodsTitle'] = '';
                $return['info']['goodsPrice'] = '';
                $return['info']['goodsImage'] = '';
                $return['info']['goodsLink'] = '';
                $return['info']['collectflag'] = '';
                $return['info']['endTime'] = '';
                $return['info']['discountWcontent'] = '';

                if ($res['goodsId']) {
                    $return['info']['goodsId'] = $res['goodsId'];
                    $return['info']['goodsTitle'] = $res['goodsTitle'];
                    $return['info']['goodsPrice'] = $res['goodsPrice'];
                    $return['info']['goodsImage'] = $res['goodsImage'];
                    $return['info']['goodsLink'] = $res['goodsLink'];
                }

                $return['status'] = 1;
                $return['message'] = '操作成功';
            } elseif ($type == 2) {
                $res = D('Poster')->getPosterAdvert($id); //查询广告基本信息

                $rec = D('Members')->getUserCollectStatus($res['id'], $userId); //广告收藏状态
                $return['info']['collectflag'] = $rec ? 1 : 2;

                $return['info']['endTime'] = $res['endTime'];
                $htmlData = M("poster_discount")->where('dataId =' . $id)->field('wayContent')->find();
                $return['info']['discountWcontent'] = $htmlData['wayContent'];
                //var_dump($res);die;

                $return['info']['goodsId'] = '';
                $return['info']['goodsTitle'] = '';
                $return['info']['goodsPrice'] = '';
                $return['info']['goodsImage'] = '';
                $return['info']['goodsLink'] = '';

                $return['status'] = 1;
                $return['message'] = '操作成功';
            } else {
                $return['status'] = 10;
                $return['message'] = '操作失败';
            }
        }
        echo jsonStr($return);exit();
    }

    /**
     * 3.2获取飞币（揭广告）
     * @param  string version:版本号(如“1.2”)
     * @param  string id：广告ID
     * @param  string userId 会员ID
     * @param  string phone：会员注册手机号
     * @param  string address：揭广告的地址（文字）
     * @param  string myLng：物理地址经度(即手机GPS定位的“我的位置”)
     * @param  string myLat：物理地址纬度(即手机GPS定位的“我的位置”)
     * @return json
     */
    public function expose() {
        //检测是否能通过检测
        $this->checkKey();

        $return['success'] = true;

        $version = I('post.version');
        $userId = I('post.userId');
        $phone = I('post.phone');
        $id = I('post.dataId');

        $myLng = I('post.myLng');
        $myLat = I('post.myLat');
        $cityId = I('post.cityId');
        $noteId = I('post.noteId');
        $forwardUserId = I('post.forwardUserId');

        $address = I('post.address', '', 'trim');
        $mobileflag = I('post.mobileflag');

        if ($forwardUserId && $noteId) {//转发
            if (is_empty($userId) || is_empty($id)) {//判断参数是否完整
                $return['status'] = -888;
                $return['message'] = '传参不完整';
            } else {
                $userId = $this->userId;
                $id = decodePass($id);

                $field = 'id,title,address,status,integral,warnPhone,userId,advLowPrompt,advHighPrompt,exLowPrompt,exHighPrompt,collectTotal,lngMax,latMax,lngMin,latMin,pushCityId,pushType,advRedStart,advRedEnd,exposeTotalIntegral,extendTotalIntegral,weburl,addTime,proRedStart,proRedEnd,type,startTime,endTime';
                $res = D('Poster')->getPosterAdvert($id, $field); //查询广告基本信息
                //var_dump($res);die;
                if (is_bool($res) && empty($res)) {//判断广告状态
                    $return['status'] = -1;
                    $return['message'] = '查询失败';
                } else if ((is_array($res) || is_null($res)) && empty($res)) {
                    $return['status'] = 36;
                    $return['message'] = '查询成功，暂无数据';
                } else if ($res['status'] != '1') {
                    //1 正常;2 下架暂停;3 举报下架;4 未支付; 5 已到期 ; 6 飞币耗完 ;7 举报关闭; 8 待上架; 9 草稿箱
                    $return['status'] = -230;
                    if ($res['status'] == 2) {
                        $return['message'] = '广告已下架';
                    } elseif ($res['status'] == 3) {
                        $return['message'] = '广告已下架';
                    } elseif ($res['status'] == 4) {
                        $return['message'] = '广告已下架';
                    } elseif ($res['status'] == 5) {
                        $return['message'] = '广告已到期';
                    } elseif ($res['status'] == 6) {
                        $return['message'] = '广告飞币已耗完';
                    } elseif ($res['status'] == 7) {
                        $return['message'] = '广告已关闭';
                    } elseif ($res['status'] == 8) {
                        $return['message'] = '广告已下架';
                    } else {
                        $return['message'] = '广告已下架';
                    }
                } else {

                    //判断当前时间
                    $nowTime = time();
                    if ($nowTime < $res['startTime'] || $nowTime > $res['endTime']) {
                        $return['status'] = 39;
                        $return['message'] = '不在时间范围内';
                        echo jsonStr($return);
                        exit(0);
                    }

                    //判断飞币
                    if ($res['integral'] <= ($res['exposeTotalIntegral'] + $res['extendTotalIntegral'])) {
                        $return['status'] = 40;
                        $return['message'] = '广告飞币已经消耗完';
                        echo jsonStr($return);
                        exit(0);
                    }


                    //判断会员是否在广告范围内
                    if ($res['pushType'] == '4') {//判断全国投放
                    } elseif (($res['pushType'] == '3') || ($res['pushType'] == '2')) {
                        if ($cityId) {//判断会员id是否为空
                            if ($res['pushType'] == '2') {
                                if ($res['pushCityId'] != ',' . $cityId . ',') {//判断广告详情是否相同
                                    $return['status'] = 38;
                                    $return['message'] = '当前不在广告范围内';
                                    echo jsonStr($return);
                                    exit(0);
                                }
                            } else {//判断是否在区域范围内
                                $cityIdList = explode(',', substr($res['pushCityId'], 1, -1));
                                $flag = 1;
                                foreach ($cityIdList as $key => $val) {
                                    if ($cityId == $val) {
                                        $flag = 2;
                                    }
                                }

                                if ($flag == 1) {
                                    $return['status'] = 38;
                                    $return['message'] = '当前不在广告范围内';
                                    echo jsonStr($return);
                                    exit(0);
                                }
                            }
                        } else {
                            $return['status'] = 38;
                            $return['message'] = '当前不在广告范围内';
                            echo jsonStr($return);
                            exit(0);
                        }
                    } else {//判断精准投放
                        if ($cityId && $myLng && $myLat) {
                            if ((',' . $cityId . ',' == $res['pushCityId']) && ($myLng > $res['lngMin']) && ($myLng > $res['lngMin']) && ($myLat > $res['latMin']) && ($myLat < $res['latMax'])) {
                                
                            } else {
                                $return['status'] = 38;
                                $return['message'] = '当前不在广告范围内';
                                echo jsonStr($return);
                                exit(0);
                            }
                        }
                    }

                    if ($noteId && $forwardUserId) {//判断是否执行添加飞币操作
                        $noteId = decodePass($noteId);
                        $forwardUserId = decodePass($forwardUserId);
                        //echo $noteId;die;
                        //if($forwardUserId==$res['userId']){
                        //$return['message'] = '查询成功';
                        //}else{
                        //执行添加飞币
                        $result = D('Poster')->addIntegral($userId, $noteId, $id, $forwardUserId, $res['proRedStart'], $res['proRedEnd'], 1, $res['advRedStart'], $res['advRedEnd'], $res['address']);


                        //var_dump($result);die;
                        if ($result > 0) {

                            $return['message'] = '获取成功';
                            //根据获取不同的飞币值，提示不同的提示语
                            if ($result == $res['advRedEnd']) {//等于最大值时
                                if ($res['advHighPrompt']) {
                                    $return['message'] = $res['advHighPrompt'];
                                }
                            } elseif ($result > $res['advRedStart'] && $result < $res['advRedEnd']) {
                                //$return['message'] = '获取成功,你已经获取飞币';
                            } else {
                                if ($result == $res['advRedStart']) {
                                    if ($res['advLowPrompt']) {
                                        $return['message'] = $res['advLowPrompt'];
                                    }
                                } else {
                                    if ($result > 0) {
                                        //$return['message'] = '获取成功,你已经获取飞币';
                                    }
                                }
                            }

                            $return['integral'] = $result;
                            //$return['message'] = '飞币已经添加，请到消息中心查看';
                        } elseif ($result == 0) {
                            $return['integral'] = 0;
                            $return['message'] = '获取成功';
                        } else {

                            $return['status'] = 10;
                            $return['message'] = '操作失败';
                            
                            echo jsonStr($return);exit();
                        
                            //$return['integral'] = 0;
                            //$return['message'] = '飞币已获取';
                        }
                        //}
                    } else {
                        $return['message'] = '查询成功';
                    }

                    $return['status'] = 1;
                    D('Poster')->addClickTotal($id);
                    $mapPs['dataId'] = $id;
                    $mapPs['userId'] = $userId;

                    if ($userId == 44427) {
                        $res['collectflag'] = 2;
                        $res['isExpose'] = '2';
                    } else {
                        $reB = D('ExposePosterLog')->selData($mapPs, 1); //查询揭广告状态
                        $res['isExpose'] = empty($reB[0]) ? '2' : '1';

                        $rec = D('Members')->getUserCollectStatus($res['id'], $userId); //广告收藏状态
                        $res['collectflag'] = $rec ? 1 : 2;
                    }

                    $res['id'] = encodePass($res['id']);
                    //$res['forwardUserId'] = encodePass($res['forwardUserId']);
                    $return['info'] = $res;
                }
            }
        } else {//获取飞币
            if (is_empty($userId) || is_empty($id)) {
                $return['status'] = -888;
                $return['message'] = '传参不完整';
            } else {
                $id = decodePass($id);
                $userId = $this->userId;

                //判断是否是非法地址
                if (!empty($address)) {
                    $sql = "address='{$address}' and status=2";
                    $address_data = M("address_limit")->where($sql)->find();

                    //如果是非法地址则直接终止程序并将用户设置为非法用户
                    if ($address_data) {
                        D("Members")->changeUserFreeze($userId, '2', 1);
                        $return['status'] = 33;
                        $return['message'] = '账号非法，暂时无法完成此操作';
                        echo jsonStr($return);
                        exit();
                    }
                }

                $flag = M('ExposePosterLog')->field('id,integral')->where('dataId=' . $id . ' and userId =' . $userId . ' and type = "1" and status = "1"')->find();
                //var_dump(M('ExposePosterLog')->getLastSql());die;

                if ($flag) {
                    $return['status'] = -231;
                    $return['message'] = '红包已打开';
                    echo jsonStr($return);
                    exit();
                }

                //查询广告数据
                $field = 'id,title,address,integral,status,warnPhone,extendTotal,exposeTotal,advLowPrompt,advHighPrompt,advRedStart,advRedEnd,exposeTotalIntegral,extendTotalIntegral,collectTotal,weburl,endTime,startTime,addTime';
                $res = D('Poster')->getPosterAdvert($id, $field);
                //var_dump($res['status'] );die;

                if (is_bool($res) && empty($res)) {//判断广告状态
                    $return['status'] = -1;
                    $return['message'] = '查询失败';
                } else if ((is_array($res) || is_null($re)) && empty($res)) {
                    $return['status'] = 36;
                    $return['message'] = '查询成功，暂无数据';
                } else if ($res['status'] != '1') {
                    //1 正常;2 下架暂停;3 举报下架;4 未支付; 5 已到期 ; 6 飞币耗完 ;7 举报关闭; 8 待上架; 9 草稿箱
                    $return['status'] = -230;
                    if ($res['status'] == 2) {
                        $return['message'] = '广告已下架';
                    } elseif ($res['status'] == 3) {
                        $return['message'] = '广告已下架';
                    } elseif ($res['status'] == 4) {
                        $return['message'] = '广告已下架';
                    } elseif ($res['status'] == 5) {
                        $return['message'] = '广告已到期';
                    } elseif ($res['status'] == 6) {
                        $return['message'] = '广告飞币已耗完';
                    } elseif ($res['status'] == 7) {
                        $return['message'] = '广告已关闭';
                    } elseif ($res['status'] == 8) {
                        $return['message'] = '广告已下架';
                    } else {
                        $return['message'] = '广告已下架';
                    }
                } else {
                    
                    //判断当前时间
                    $nowTime = time();
                    if ($nowTime < $res['startTime'] || $nowTime > $res['endTime']) {
                        $return['status'] = 39;
                        $return['message'] = '不在时间范围内';
                        echo jsonStr($return);
                        exit(0);
                    }

                    //判断飞币
                    if ($res['integral'] <= ($res['exposeTotalIntegral'] + $res['extendTotalIntegral'])) {
                        $return['status'] = 40;
                        $return['message'] = '广告飞币已经消耗完';
                        echo jsonStr($return);
                        exit(0);
                    }
                    
                    $tmpIntegral = $res['integral'] - ( $res['exposeTotalIntegral'] + $res['extendTotalIntegral']);
                    //echo $tmpIntegral;die;
                    if ($tmpIntegral > 0) {
                        $integral = D('Poster')->getPosterIntegral($res['advRedStart'], $res['advRedEnd'], $tmpIntegral);
                        //echo $integral;die;
                        if($integral<0){
                            $return['status'] = 10;
                            $return['message'] = '操作失败';
                            
                            echo jsonStr($return);exit();
                        }

                        //开始揭广告
                        $mapP['addTime'] = time();
                        $mapP['integral'] = $integral;
                        $mapP['userId'] = $userId;
                        $mapP['dataId'] = $id;
                        $mapP['address'] = $address; //揭广告的地址
                        //更新地址信息
                        $aid = D("Poster")->updataPostLimitLog($id, $address, $userId);
                        $mapP['aid'] = $aid;

                        //添加揭广告记录
                        $reBillLog = D('ExposePosterLog')->addData($mapP);
                        //echo M('ExposePosterLog')->getLastSql();die;


                        //echo D('ExposePosterLog')->getLastSql();die;
                        //更新数据
                        M('PosterAdvert')->where('id =' . $id)->setInc("exposeTotal", 1);
                        M('PosterAdvert')->where('id =' . $id)->setInc("exposeTotalIntegral", $integral);

                        if ($integral) {//进入消息中心
                            //添加飞币
                            D("Members")->addUsersIntegral($userId, $integral);
                            
                            $content = '你通过点击“' . $res['title'] . '”的获取飞币，送飞币';
                            D("Members")->addMemberDope($userId, $content, '1', $integral, $id, '8');
                        }

                        $return['message'] = '获取成功';
                        //根据获取不同的飞币值，提示不同的提示语
                        if ($integral == $res['advRedEnd']) {//等于最大值时
                            if ($res['advHighPrompt']) {
                                $return['message'] = $res['advHighPrompt'];
                            }
                        } elseif ($integral > $res['advRedStart'] && $integral < $res['advRedEnd']) {
                            //$return['message'] = '获取成功,你已经获取飞币';
                        } else {
                            if ($integral == $res['advRedStart']) {
                                if ($res['advLowPrompt']) {
                                    $return['message'] = $res['advLowPrompt'];
                                }
                            } else {
                                if ($integral > 0) {
                                    //$return['message'] = '获取成功,你已经获取飞币';
                                }
                            }
                        }

                        $return['integral'] = $integral;
                        $return['status'] = 1;
                    } else {
                        $return['status'] = 10;
                        $return['message'] = '操作失败';
                    }
                }
            }
        }
        echo jsonStr($return);exit();
    }

    /**
     * 3.2统计进入商品次数接口
     * @param  string $version:版本号(如“1.2”)
     * @param  string $userId：会员唯一码
     * @param  string $phone：会员注册手机号
     * @param  string $dataId:广告的ID
     * @return json 广告数据的JSON字符串
     */
    public function countClickTimes() {
        $return['success'] = true;

        $version = I('post.version');
        $userId = I('post.userId');
        $phone = I('post.phone');
        $id = I('post.dataId');

        if (is_empty($userId) || is_empty($id)) {//判断参数是否完整
            $return['status'] = -888;
            $return['message'] = '传参不完整';
        } else {

            $id = decodePass($id);
            $userId = $this->userId;
            $res = D('Poster')->addGoodsClickTimes($userId, $id);
            if ($res) {//判断添加记录是否成功
                $return['status'] = 1;
                $return['message'] = '添加成功';
            } else {
                $return['status'] = 10;
                $return['message'] = '操作失败';
            }
        }
        echo jsonStr($return);exit();
    }

    /**
     * 3.2会员分享接口
     * @param  string $version:版本号(如“1.2”)
     * @param  string $userId：会员唯一码
     * @param  string $phone：会员注册手机号
     * @param  string $dataId:广告的ID
     * @param  string $type:广告分享类型 分享类型：1表示新浪微博，2表示微信好友，3表示微信朋友圈，4表示qq，5表示人人，6表示qq空间
     * @return json 广告数据的JSON字符串
     */
    public function share() {
        //检测是否能通过检测
        $this->checkKey();

        $return['success'] = true;

        $version = I('post.version');
        $userId = I('post.userId');
        $phone = I('post.phone');
        $type = I('post.type');
        $id = I('post.dataId');

        if (is_empty($userId) || is_empty($id) || is_empty($type)) {
            $return['status'] = -888;
            $return['message'] = '传参不完整';
        } else {
            $userId = $this->userId;
            $id = decodePass($id);
            //查询广告数据
            $field = 'id,integral,title,status,proRedEnd,proRedStart,exLowPrompt,exHighPrompt,exposeTotalIntegral,extendTotalIntegral,addTime';
            $res = D('Poster')->getPosterAdvert($id, $field);

            if (is_bool($res) && empty($res)) {//判断广告状态
                $return['status'] = -1;
                $return['message'] = '查询失败';
            } else if ((is_array($res) || is_null($re)) && empty($res)) {
                $return['status'] = 36;
                $return['message'] = '查询成功，暂无数据';
            } else if ($res['status'] != '1') {
                //1 正常;2 下架暂停;3 举报下架;4 未支付; 5 已到期 ; 6 飞币耗完 ;7 举报关闭; 8 待上架; 9 草稿箱
                $return['status'] = -230;
                $return['message'] = '广告已关闭';
            } else {
                $tmpIntegral = $res['integral'] - ( $res['exposeTotalIntegral'] + $res['extendTotalIntegral']);
                if ($tmpIntegral > 0) {

                    //添加飞币 
                    $integral = $this->calculateUserShareIntegral($userId, $id, $res['proRedStart'], $res['proRedEnd'], $tmpIntegral);

                    //写入广告分享日志表
                    $data = array();
                    $data['dataId'] = $id;
                    $data['userId'] = $userId;
                    $data['integral'] = $integral;
                    $data['status'] = $integral ? "1" : "0";
                    $data['addTime'] = time();
                    $data['type'] = $type;
                    //$data['mobileflag'] = $this->_check->user_flag;
                    $reShareLog = M("share_poster_log")->add($data);
                    //echo  M("share_poster_log")->getLastSql();die;

                    if ($reShareLog) {//判断添加分享记录是否成功
                        D('Poster')->addClickTotal($id, 2);

                        //根据获取不同的飞币值，提示不同的提示语
                        $return['message'] = '分享成功';
                        if ($integral) {
                            $result = D("Members")->addUsersIntegral($userId, $integral);

                            $content = '你通过分享“' . $res['title'] . '”的获取飞币，送飞币';
                            D("Members")->addMemberDope($userId, $content, '1', $integral, $id, '9');

                            M('PosterAdvert')->where('id =' . $id)->setInc("extendTotal", 1);
                            M('PosterAdvert')->where('id =' . $id)->setInc("extendTotalIntegral", $integral);
                        }

                        if ($integral == $res['proRedEnd']) {//等于最大值时
                            if ($res['exHighPrompt']) {
                                $return['message'] = $res['exHighPrompt'];
                            }
                        } elseif ($integral > $res['proRedStart'] && $integral < $res['proRedEnd']) {
                            $return['message'] = '分享成功,你已经获取飞币';
                        } else {
                            if ($integral == $res['proRedStart']) {
                                if ($res['exLowPrompt']) {
                                    $return['message'] = $res['exLowPrompt'];
                                }
                            } else {
                                if ($integral > 0) {
                                    $return['message'] = '分享成功,你已经获取飞币';
                                }
                            }
                        }

                        $return['status'] = 1;
                    } else {
                        $return['status'] = 10;
                        $return['message'] = '操作失败';
                    }
                } else {
                    $return['status'] = 1;
                    $return['message'] = '分享成功';
                }
            }
        }
        echo jsonStr($return);exit();
    }

    /**
     * 3.2获取我的朋友列表接口
     * @param  string $version:版本号(如“3.2”)
     * @param  string $userId：会员唯一码
     * @param  string $phone：会员注册手机号
     * @param  string $myLng：物理地址经度(即手机GPS定位的“我的位置”)
     * @param  string $myLat: 物理地址纬度(即手机GPS定位的“我的位置”)
     * @param  string $cityId: 上一次请求的城市id
     * @return json 广告数据的JSON字符串
     */
    public function getMyfriends() {
        $return['success'] = true;

        $version = I('post.version');
        $userId = I('post.userId');
        $phone = I('post.phone');

        $myLng = I('post.myLng');
        $myLat = I('post.myLat');
        $cityId = I('post.cityId');
        $id = I('post.dataId');

        if (is_empty($userId) || is_empty($myLng) || is_empty($myLat)) {//判断参数是否完整
            $return['status'] = -888;
            $return['message'] = '传参不完整';
        } else {
            $userId = $this->userId;

            $id = decodePass($id);
            //广告详情信息
            $modelPoster = D('Poster');
            $field = 'id,pushCityId,pushType,range,lngMax,latMax,lngMin,latMin';
            $res = $modelPoster->getPosterAdvert($id, $field); //查询广告基本信息

            if ($res) {//判断广告是否为空
                //设为默认
                //echo $resUser['id'].'-'.$res['pushCityId'].'-'.$res['range'].'-'.$myLat.'-'.$cityId;die;
                if ($res['pushType'] == '1') {//精准投放
                    $reList = $modelPoster->getListData($userId, $res['pushCityId'], $res['range'], $myLng, $myLat, $cityId, 1, $res['lngMax'], $res['latMax'], $res['lngMin'], $res['latMin']);
                } elseif ($res['pushType'] == '2') {//单个城市投放
                    $reList = $modelPoster->getListData($userId, $res['pushCityId'], $res['range'], $myLng, $myLat, $cityId, 2);
                } elseif ($res['pushType'] == '3') {//多个城市投放
                    $reList = $modelPoster->getListData($userId, $res['pushCityId'], $res['range'], $myLng, $myLat, $cityId, 3);
                } else {//全国投放
                    $reList = $modelPoster->getListData($userId, $res['pushCityId'], $res['range'], $myLng, $myLat, $cityId, 4);
                }

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
                    $return['message'] = '查询成功';
                }
            } else {
                $return['status'] = 10;
                $return['message'] = '操作失败';
            }
        }
        echo jsonStr($return);exit();
    }

    /**
     * 3.2获取我的朋友转发广告记录信息接口更改为朋友发布记录
     * @param  string $version:版本号(如“3.2”)
     * @param  string $userId：会员唯一码
     * @param  string $phone：会员注册手机号
     * @param  string $page：当前页【必填项】
     * @param  string $pageSize：每页显示数量【必填项】
     * @param  string $friendsId：朋友的会员id
     * @param  string $selectTime：每次刷新请求返回的时间【必填项】
     * @return json 广告数据的JSON字符串
     */
    public function friendDataList() {
        $return['success'] = true;

        $version = I('post.version');
        $userId = I('post.userId');
        $phone = I('post.phone');

        $page = I('post.page');
        $pageSize = I('post.pageSize');

        $myLng = I('post.myLng');
        $myLat = I('post.myLat');
        $cityId = I('post.cityId');

        $friendsId = I('post.friendId');
        $selectTime = I('post.selectTime') ? I('post.selectTime') : time();

        if (is_empty($userId) || is_empty($page) || is_empty($pageSize) || is_empty($friendsId) || is_empty($selectTime)) {//判断参数是否完整
            $return['status'] = -888;
            $return['message'] = '传参不完整';
        } else {
            $friendsId = decodePass($friendsId);
            $userId = $this->userId;
            //echo $friendsId.'-'.$userId;die;
            //获取朋友转发记录
            $posterModel = D('Poster');
            //$searchList=$posterModel->getFriendsPosterList($res['id'],$friendsId,$page,$pageSize,$selectTime);
            //echo $friendsId.'-'.$selectTime.'-'.$page.'-'.$pageSize.'-'.$field;die;
            //获取朋友发布记录
            $searchList = $posterModel->myDataList($friendsId, $selectTime, $page, $pageSize, $field, $myLat, $myLng, $cityId);


            $return['selectTime'] = time();
            if (empty($searchList)) {//判断记录是否为空
                $return['status'] = 36;
                $return['message'] = '查询成功，暂无数据';

                $return['range'] = '';
                $return['info'] = array();
            } else {
                $return['status'] = 1;
                $return['message'] = '查询成功';

                $modelUser = D("Members");
                foreach ($searchList as $k => $v) {
                    //会员相关信息
                    $field = 'id,uniqueId as userId,name,jpush,phone,image,imageUrl,integral,cityId,provinceId,freeze,handlePassword,type';
                    $resUser = $modelUser->getUserInfo($v['userId']);

                    //设置会员信息
                    $searchList[$k]['userId'] = '';
                    $searchList[$k]['nickname'] = '';
                    $searchList[$k]['userImage'] = '';

                    if ($resUser['id']) {
                        $searchList[$k]['userId'] = encodePass($resUser['userId']);
                        $searchList[$k]['nickname'] = $resUser['name'];
                        $searchList[$k]['userImage'] = $resUser['imageUrl'];
                    }

                    $searchList[$k]['title'] = base64_encode(jsonStrWithOutBadWordsNew($v['title'], 2));

                    $mapP['dataId'] = $v['id'];
                    $mapP['userId'] = $userId;

                    //获取打包路径
                    $mapPP['dataId'] = $v['id'];
                    $dataP = D('PicturePoster')->selData($mapPP, '', 'field');
                    //$resPoster[$k]['field'] = empty($dataP) ? '':$dataP['field'];
                    $searchList[$k]['field'] = 'http://dev.feibaokeji.com/Application/Home/View/Adinfo/index.html?id=3&userId=1&phone=12345678910';

                    $reB = D('ExposePosterLog')->selData($mapP, 1); //查询揭广告状态
                    $searchList[$k]['isExpose'] = empty($reB[0]) ? '2' : '1';

                    $rec = $modelUser->getUserCollectStatus($v['id'], $userId); //广告收藏状态
                    $searchList[$k]['collectflag'] = $rec ? 1 : 2;

                    if ($k == (count($searchList) - 1)) {
                        if ($v['pushType'] != '1') {
                            $return['range'] = '1公里外';
                        } else {
                            $range = GetDistance($myLng, $myLat, $searchList[$k]['lng'], $searchList[$k]['lat']);
                            $return['range'] = judgeDistance($range);
                        }
                    }
                    $searchList[$k]['id'] = encodePass($v['id']);
                    $searchList[$k]['noteId'] = encodePass($v['noteId']);
                }
                $return['info'] = $searchList;
            }
        }
        echo jsonStr($return);exit();
    }

    /**
     * 3.2他的广告详情接口
     * @param  string $version:版本号(如“3.2”)
     * @param  string $userId：会员唯一码
     * @param  string $phone：会员注册手机号
     * @param  string $dataId:广告的ID
     * @param  string $myLng：物理地址经度(即手机GPS定位的“我的位置”)
     * @param  string $myLat: 物理地址纬度(即手机GPS定位的“我的位置”)
     * @param  string $cityId: 城市id
     * @param  string $noteId：记录id
     * @param  string $forwardUserId：转发人id
     * @return json 广告数据的JSON字符串
     */
    public function friendPosterDetail() {
        $return['success'] = true;

        $version = I('post.version');
        $userId = I('post.userId');
        $phone = I('post.phone');
        $id = I('post.dataId');

        $myLng = I('post.myLng');
        $myLat = I('post.myLat');
        $cityId = I('post.cityId');
        $noteId = I('post.noteId');
        $forwardUserId = I('post.forwardUserId');

        //if (is_empty($userId) || is_empty($id) || is_empty($noteId)) {//判断参数是否完整
        if (is_empty($userId) || is_empty($id)) {//判断参数是否完整
            $return['status'] = -888;
            $return['message'] = '传参不完整';
        } else {
            $userId = $this->userId;
            $id = decodePass($id);

            $field = 'id,title,address,status,type,integral,warnPhone,userId,collectTotal,lngMax,shareUrl,latMax,lngMin,latMin,pushCityId,pushType,exposeTotalIntegral,extendTotalIntegral,weburl,addTime,proRedStart,proRedEnd,type,startTime,endTime';
            $res = D('Poster')->getPosterAdvert($id, $field); //查询广告基本信息
            if (is_bool($res) && empty($res)) {//判断广告状态
                $return['status'] = -1;
                $return['message'] = '查询失败';
            } else if ((is_array($res) || is_null($res)) && empty($res)) {
                $return['status'] = 36;
                //$return['message'] = '查询成功，暂无数据';
            } else if ($res['status'] != '1') {
                //1 正常;2 下架暂停;3 举报下架;4 未支付; 5 已到期 ; 6 飞币耗完 ;7 举报关闭; 8 待上架; 9 草稿箱
                $return['status'] = -230;
                $return['message'] = '广告已关闭';
                //$return['title'] = $res['title'];
            } else {

                //判断当前时间
                $nowTime = time();
                if ($nowTime < $res['startTime'] || $nowTime > $res['endTime']) {
                    $return['status'] = 39;
                    $return['message'] = '广告已过期';
                    //$return['title'] = $res['title'];
                    echo jsonStr($return);
                    exit(0);
                }

                //判断飞币
                if ($res['integral'] <= ($res['exposeTotalIntegral'] + $res['extendTotalIntegral'])) {
                    $return['status'] = 40;
                    $return['message'] = '广告飞币已经消耗完';
                    //$return['title'] = $res['title'];
                    echo jsonStr($return);
                    exit(0);
                }


                //判断会员是否在广告范围内
                if ($res['pushType'] == '4') {//判断全国投放
                } elseif (($res['pushType'] == '3') || ($res['pushType'] == '2')) {
                    if ($cityId) {//判断会员id是否为空
                        if ($res['pushType'] == '2') {
                            if ($res['pushCityId'] != ',' . $cityId . ',') {//判断广告详情是否相同
                                $return['status'] = 38;
                                $return['message'] = '当前不在广告范围内';
                                //$return['title'] = $res['title'];
                                echo jsonStr($return);
                                exit(0);
                            }
                        } else {//判断是否在区域范围内
                            $cityIdList = explode(',', $res['pushCityId']);
                            $flag = 1;
                            foreach ($cityIdList as $key => $val) {
                                if ($cityId == $val) {
                                    $flag = 2;
                                }
                            }

                            if ($flag == 1) {
                                $return['status'] = 38;
                                $return['message'] = '当前不在广告范围内';
                                //$return['title'] = $res['title'];
                                echo jsonStr($return);
                                exit(0);
                            }
                        }
                    } else {
                        $return['status'] = 38;
                        $return['message'] = '当前不在广告范围内';
                        //$return['title'] = $res['title'];
                        echo jsonStr($return);
                        exit(0);
                    }
                } else {//判断精准投放
                    if ($cityId && $myLng && $myLat) {
                        if ((',' . $cityId . ',' == $res['pushCityId']) && ($myLng > $res['lngMin']) && ($myLng < $res['lngMax']) && ($myLat > $res['latMin']) && ($myLat < $res['latMax'])) {
                            
                        } else {
                            $return['status'] = 38;
                            $return['message'] = '当前不在广告范围内';
                            //$return['title'] = $res['title'];
                            echo jsonStr($return);
                            exit(0);
                        }
                    }
                }

                $return['message'] = '查询成功';
                $return['status'] = 1;
                D('Poster')->addClickTotal($id);
                $mapPs['dataId'] = $id;
                $mapPs['userId'] = $userId;

                if ($userId == 44427) {
                    $res['collectflag'] = 2;
                    $res['isExpose'] = '2';
                    $res['isForward'] = '2';
                } else {
                    if ($forwardUserId) {//转发
                        $forwardUserId = decodePass($forwardUserId);
                        //if($userId ==$forwardUserId){
                        $flag = M('ExposePosterLog')->field('id,integral')->where('dataId=' . $id . ' and userId =' . $userId . ' and type = "1" and status = "1"')->find();
                        if ($res['integral'] <= ($res['exposeTotalIntegral'] + $res['extendTotalIntegral'])) {
                            $res['isExpose'] = '1';
                        } else {
                            if ($flag) {
                                $res['isExpose'] = '1';
                            } else {
                                $res['isExpose'] = '2';
                            }
                        }

                        //}else{
                        //$where = 'userId ='.$forwardUserId.' and friendId ='.$userId.' and dataId ='.$id;
                        //$flagarr = M('FriendForward')->field('id,lookTime,status')->where($where)->select();
                        //if ($flag) {
                        //$res['isForward'] = '2';
                        //foreach($flagarr as $k=>$v){
                        //if($v['lookTime']>0){
                        //$res['isExpose'] ='1';
                        //}
                        //}
                        //}else{
                        //$res['isExpose'] = '2';
                        //}
                        //}
                    } else {
                        $reB = D('ExposePosterLog')->selData($mapPs, 1); //查询揭广告状态
                        $res['isExpose'] = empty($reB[0]) ? '2' : '1';
                    }

                    $rec = D('Members')->getUserCollectStatus($res['id'], $userId); //广告收藏状态
                    $res['collectflag'] = $rec ? 1 : 2;
                }

                //$res['title']=strip_name_badwords($res['title'],2);
                $res['id'] = encodePass($res['id']);
                //$res['forwardUserId'] = encodePass($res['forwardUserId']);
                $return['info'] = $res;
            }
        }
        echo jsonStr($return);exit();
    }

    /*
     * 添加转发飞币--已废除
     * @param  string $version:版本号(如“3.2”)
     * @param  string $userId：会员唯一码
     * @param  string $phone：会员注册手机号
     * @param  string $dataId:广告的ID
     * @param  string $myLng：物理地址经度(即手机GPS定位的“我的位置”)
     * @param  string $myLat: 物理地址纬度(即手机GPS定位的“我的位置”)
     * @param  string $cityId: 城市id
     * @param  string $noteId：记录id
     * @param  string $forwardUserId：转发人id
     * @return json 广告数据的JSON字符串
     */

    public function addForwardIntegral() {
        $return['success'] = true;
        echo '已停止';
        die;


        $version = I('post.version');
        $userId = I('post.userId');
        $phone = I('post.phone');
        $id = I('post.dataId');

        $myLng = I('post.myLng');
        $myLat = I('post.myLat');
        $cityId = I('post.cityId');
        $noteId = I('post.noteId');
        $forwardUserId = I('post.forwardUserId');

        //if (is_empty($userId) || is_empty($id) || is_empty($noteId)) {//判断参数是否完整
        if (is_empty($userId) || is_empty($id)) {//判断参数是否完整
            $return['status'] = -888;
            $return['message'] = '传参不完整';
        } else {
            $userId = $this->userId;
            $id = decodePass($id);

            $field = 'id,title,address,status,integral,warnPhone,userId,collectTotal,lngMax,latMax,lngMin,latMin,pushCityId,pushType,advRedStart,advRedEnd,exposeTotalIntegral,extendTotalIntegral,weburl,addTime,proRedStart,proRedEnd,type,startTime,endTime';
            $res = D('Poster')->getPosterAdvert($id, $field); //查询广告基本信息
            if (is_bool($res) && empty($res)) {//判断广告状态
                $return['status'] = -1;
                $return['message'] = '查询失败';
            } else if ((is_array($res) || is_null($res)) && empty($res)) {
                $return['status'] = 36;
                $return['message'] = '查询成功，暂无数据';
            } else if ($res['status'] != '1') {
                //1 正常;2 下架暂停;3 举报下架;4 未支付; 5 已到期 ; 6 飞币耗完 ;7 举报关闭; 8 待上架; 9 草稿箱
                $return['status'] = -230;
                if ($res['status'] == 2) {
                    $return['message'] = '广告已下架';
                } elseif ($res['status'] == 3) {
                    $return['message'] = '广告已下架';
                } elseif ($res['status'] == 4) {
                    $return['message'] = '广告已下架';
                } elseif ($res['status'] == 5) {
                    $return['message'] = '广告已到期';
                } elseif ($res['status'] == 6) {
                    $return['message'] = '广告飞币已耗完';
                } elseif ($res['status'] == 7) {
                    $return['message'] = '广告已关闭';
                } elseif ($res['status'] == 8) {
                    $return['message'] = '广告已下架';
                } else {
                    $return['message'] = '广告已下架';
                }
            } else {

                //判断当前时间
                $nowTime = time();
                if ($nowTime < $res['startTime'] || $nowTime > $res['endTime']) {
                    $return['status'] = 39;
                    $return['message'] = '广告已过期';
                    echo jsonStr($return);
                    exit(0);
                }

                //判断飞币
                if ($res['integral'] <= ($res['exposeTotalIntegral'] + $res['extendTotalIntegral'])) {
                    $return['status'] = 40;
                    $return['message'] = '广告飞币已经消耗完';
                    echo jsonStr($return);
                    exit(0);
                }


                //判断会员是否在广告范围内
                if ($res['pushType'] == '4') {//判断全国投放
                } elseif (($res['pushType'] == '3') || ($res['pushType'] == '2')) {
                    if ($cityId) {//判断会员id是否为空
                        if ($res['pushType'] == '2') {
                            if ($res['pushCityId'] != ',' . $cityId . ',') {//判断广告详情是否相同
                                $return['status'] = 38;
                                $return['message'] = '当前不在广告范围内';
                                echo jsonStr($return);
                                exit(0);
                            }
                        } else {//判断是否在区域范围内
                            $cityIdList = explode(',', $res['pushCityId']);
                            $flag = 1;
                            foreach ($cityIdList as $key => $val) {
                                if ($cityId == $val) {
                                    $flag = 2;
                                }
                            }

                            if ($flag == 1) {
                                $return['status'] = 38;
                                $return['message'] = '当前不在广告范围内';
                                echo jsonStr($return);
                                exit(0);
                            }
                        }
                    } else {
                        $return['status'] = 38;
                        $return['message'] = '当前不在广告范围内';
                        echo jsonStr($return);
                        exit(0);
                    }
                } else {//判断精准投放
                    if ($cityId && $myLng && $myLat) {
                        if ((',' . $cityId . ',' == $res['pushCityId']) && ($myLng > $res['lngMin']) && ($myLng > $res['lngMin']) && ($myLat > $res['latMin']) && ($myLat < $res['latMax'])) {
                            
                        } else {
                            $return['status'] = 38;
                            $return['message'] = '当前不在广告范围内';
                            echo jsonStr($return);
                            exit(0);
                        }
                    }
                }

                if ($noteId && $forwardUserId) {//判断是否执行添加飞币操作
                    $noteId = decodePass($noteId);
                    $forwardUserId = decodePass($forwardUserId);

                    //if($forwardUserId==$res['userId']){
                    //$return['message'] = '查询成功';
                    //}else{
                    //执行添加飞币
                    $result = D('Poster')->addIntegral($userId, $noteId, $id, $forwardUserId, $res['proRedStart'], $res['proRedEnd'], 1, $res['advRedStart'], $res['advRedEnd'], $res['address']);

                    if ($result) {
                        $return['message'] = '飞币已经添加，请到消息中心查看';
                    } else {
                        $return['message'] = '飞币已获取';
                    }
                    //}
                } else {
                    $return['message'] = '查询成功';
                }

                $return['status'] = 1;
                D('Poster')->addClickTotal($id);
                $mapPs['dataId'] = $id;
                $mapPs['userId'] = $userId;

                if ($userId == 44427) {
                    $res['collectflag'] = 2;
                    $res['isExpose'] = '2';
                } else {
                    $reB = D('ExposePosterLog')->selData($mapPs, 1); //查询揭广告状态
                    $res['isExpose'] = empty($reB[0]) ? '2' : '1';

                    $rec = D('Members')->getUserCollectStatus($res['id'], $userId); //广告收藏状态
                    $res['collectflag'] = $rec ? 1 : 2;
                }

                $res['id'] = encodePass($res['id']);
                //$res['forwardUserId'] = encodePass($res['forwardUserId']);
                $return['info'] = $res;
            }
        }
        echo jsonStr($return);exit();
    }

    /**
     * 3.2添加转发朋友接口
     * @param  string $version:版本号(如“3.2”)
     * @param  string $userId：会员唯一码
     * @param  string $phone：会员注册手机号
     * @param  string $dataId:广告的ID
     * @param  string $frendsId:朋友id串【必填项】
     * @return json 广告数据的JSON字符串
     */
    public function addForward() {
        $return['success'] = true;

        $version = I('post.version');
        $userId = I('post.userId');
        $phone = I('post.phone');
        $id = I('post.dataId');
        $friendsId = I('post.friendsId');

        if (is_empty($userId) || is_empty($id) || is_empty($friendsId)) {//判断参数是否完整
            $return['status'] = -888;
            $return['message'] = '传参不完整';
        } else {

            $userId = $this->userId;
            $id = decodePass($id);
            //echo $id;die;
            $res = D('Poster')->getPosterAdvert($id); //查询广告基本信息
            //var_dump($res);die;
            if (is_bool($res) && empty($res)) {//判断广告状态
                $return['status'] = -1;
                $return['message'] = '查询失败';
            } else if ((is_array($res) || is_null($re)) && empty($res)) {
                $return['status'] = 36;
                $return['message'] = '查询成功，暂无数据';
            }// else if ($res['status'] != '1') {
            //1 正常;2 下架暂停;3 举报下架;4 未支付; 5 已到期 ; 6 飞币耗完 ;7 举报关闭; 8 待上架; 9 草稿箱
            // $return['status'] = -230;
            // $return['message'] = '广告已关闭';
            //} 
            else {

                $friendList = explode(',', $friendsId);
                //$newFrendsId='';
                $friendModel = D("Friend");
                $modelPoster = D('Poster');
                //var_dump($friendList);die;
                //验证朋友关系
                foreach ($friendList as $key => $val) {
                    $data = array();
                    if ($val) {
                        //echo $userId.'-'.decodePass($val);die;
                        $resIsFriend = $friendModel->isFriend($userId, decodePass($val));

                        $dataS['isNew'] = '1';
                        $nowTime = time();
                        $dataS['dataId'] = $id;
                        $dataS['ftime'] = $nowTime;
                        M('Friend')->where('uid =' . $userId . ' and fuid =' . decodePass($val))->data($dataS)->save();

                        if ($resIsFriend) {
                            //$newFrendsId.=decodePass($val).',';
                            //添加转发
                            $data['dataId'] = $id;
                            $data['friendId'] = decodePass($val);
                            $data['userId'] = $userId;
                            $data['integral'] = 0;
                            $data['addTime'] = $nowTime;
                            $data['updateTime'] = $nowTime;
                            $data['isNew'] = '1';
                            //var_dump($data);die;
                            $modelPoster->addForward($data);
                        }
                    }
                }

                if ($res) {

                    $modelPoster->addClickTotal($id, 3);
                    $return['status'] = 1;
                    $return['message'] = '转发成功';
                } else {
                    $return['status'] = 10;
                    $return['message'] = '操作失败';
                }
            }
        }
        echo jsonStr($return);exit();
    }

    /**
     * 计算某个用户对某个广告进行分享时应该获得的飞币
     * @param int $uid 用户ID
     * @param int $pid 广告ID
     * @return int 应该获得飞币数
     */
    public function calculateUserShareIntegral($uid, $pid, $proRedStart, $posters_share_max_integral, $yuIntegral) {
        //每张广告每个人可在最多前多少次获得飞币
        $posters_share_max_times = D("System")->readConfig("posters_share_max_times");

        //统计当前用户对当前广告分享了多少次
        $where = array();
        $where['dataId'] = $pid;
        $where['userId'] = $uid;
        $m = M("share_poster_log");
        $share_count = $m->where($where)->count("id");
        $integral_sum = $m->where($where)->sum('integral');

        //分享第十次之后不再获得飞币
        if ($share_count+1 > $posters_share_max_times) {
            return 0;
        }
        $integral = D('Poster')->getPosterIntegral($proRedStart, $posters_share_max_integral, $yuIntegral);


        return $integral;
    }

    /**
     * 3.2最新广告信息列表
     * @param  string $version 版本号
     * @param  string $myLng 物理地址经度(即手机GPS定位的“我的位置”)
     * @param  string $myLat 物理地址纬度(即手机GPS定位的“我的位置”)
     * @param  string $page 当前页
     * @param  string $pageSize 每页显示数量
     * @param  string $userId 唯一码
     * @param  string $phone 会员注册手机号
     * @param  string $cityId 上一次请求的城市id
     * @param  string $selectTime 每次刷新请求返回的时间【必填项】
     * @return json 广告数据的JSON字符串
     */
    public function newPosterList() {
        $return['success'] = true;

        $version = I('post.version');
        $userId = I('post.userId');
        $phone = I('post.phone');
        $myLng = I('post.myLng');

        $myLat = I('post.myLat');

        $page = I('post.page');
        $pageSize = I('post.pageSize');
        $cityId = I('post.cityId');
        $selectTime = I('post.selectTime');

        if (is_empty($version) || is_empty($userId) || is_empty($phone) || is_empty($page) || is_empty($pageSize)) {//判断参数是否有缺失
            $return['status'] = -888;
            $return['message'] = '传参不完整';
        } else {

            $userId = $this->userId;
            $modelPoster = D('Poster');

            //查询搜索词信息
            $field = 'id,name';

            $searchList = $modelPoster->getNewPoster($selectTime, $page, $pageSize, $field, $cityId, $myLng, $myLat);
            //$searchList = $modelPoster->newPoster($selectTime, $page, $pageSize, $field, $cityId, $myLng, $myLat);
            //var_dump($searchList);die;
            $return['selectTime'] = time();
            if (empty($searchList)) {
                $return['status'] = 36;
                $return['message'] = '查询成功，暂无数据';

                $return['range'] = '';
                $return['info'] = array();
            } else {
                $return['status'] = 1;
                $return['message'] = '查询成功';

                $modelUser = D("Members");
                foreach ($searchList as $k => $v) {
                    //会员相关信息
                    $field = 'id,uniqueId as userId,name,jpush,phone,image,imageUrl,integral,cityId,provinceId,freeze,handlePassword,type';
                    $resUser = $modelUser->getUserInfo($v['userId']);

                    //设置会员信息
                    $searchList[$k]['userId'] = '';
                    $searchList[$k]['nickname'] = '';
                    $searchList[$k]['userImage'] = '';

                    if ($resUser['id']) {
                        $searchList[$k]['userId'] = encodePass($resUser['id']);
                        $searchList[$k]['nickname'] = $resUser['name'];
                        $searchList[$k]['userImage'] = $resUser['imageUrl'];
                    }

                    $searchList[$k]['title'] = base64_encode(jsonStrWithOutBadWordsNew($v['title'], 2));


                    $mapP['dataId'] = $v['id'];
                    $mapP['userId'] = $userId;

                    //获取打包路径
                    $mapPP['dataId'] = $v['id'];
                    $dataP = D('PicturePoster')->selData($mapPP, '', 'field');
                    //$resPoster[$k]['field'] = empty($dataP) ? '':$dataP['field'];
                    $searchList[$k]['field'] = 'http://dev.feibaokeji.com/Application/Home/View/Adinfo/index.html?id=3&userId=1&phone=12345678910';


                    if ($userId == 44427) {
                        $searchList[$k]['collectflag'] = 2;
                        $searchList[$k]['isExpose'] = '2';
                    } else {
                        $reB = D('ExposePosterLog')->selData($mapP, 1); //查询揭广告状态
                        $searchList[$k]['isExpose'] = empty($reB[0]) ? '2' : '1';

                        $rec = $modelUser->getUserCollectStatus($v['id'], $userId); //广告收藏状态
                        $searchList[$k]['collectflag'] = $rec ? 1 : 2;
                    }

                    $searchList[$k]['id'] = encodePass($v['id']);
                }

                //更新会员定位信息
                if ($page == 1 && $myLng && $myLat && $userId != 44427) {
                    $modelAddress = D('Members');
                    $modelAddress->updateUserAddress($userId, $myLng, $myLat, $cityId);
                }

                //$rangelist = array_slice($searchList, -1, 1);
                //$range = GetDistance($myLng, $myLat, $rangelist[0]['lng'], $rangelist[0]['lat']);
                //$return['range'] = judgeDistance($range);
                $return['info'] = $searchList;
            }
        }
        echo jsonStr($return);exit();
    }

    /**
     * 3.2我的关注广告信息列表
     * @param  string $version 版本号
     * @param  string $myLng 物理地址经度(即手机GPS定位的“我的位置”)
     * @param  string $myLat 物理地址纬度(即手机GPS定位的“我的位置”)
     * @param  string $page 当前页
     * @param  string $pageSize 每页显示数量
     * @param  string $userId 唯一码
     * @param  string $phone 会员注册手机号
     * @param  string $cityId 上一次请求的城市id
     * @param  string $selectTime 每次刷新请求返回的时间【必填项】
     * @return json 广告数据的JSON字符串
     */
    public function myAttenitionList() {
        $return['success'] = true;

        $version = I('post.version');
        $userId = I('post.userId');
        $phone = I('post.phone');
        $myLng = I('post.myLng');
        $myLat = I('post.myLat');

        $page = I('post.page');
        $pageSize = I('post.pageSize');
        $cityId = I('post.cityId');
        $selectTime = I('post.selectTime');

        if (is_empty($version) || is_empty($userId) || is_empty($phone) || is_empty($page) || is_empty($pageSize)) {//判断参数是否有缺失
            $return['status'] = -888;
            $return['message'] = '传参不完整';
        } else {
            $userId = $this->userId;
            $modelPoster = D('Poster');

            //查询搜索词信息
            $field = 'id,name';
            //$searchList = $modelPoster->newPoster($selectTime, $page, $pageSize, $field, $cityId, $myLng, $myLat);
            $searchList = $modelPoster->AttenitionList($userId, $selectTime, $page, $pageSize, $field, $cityId, $myLng, $myLat);

            $return['selectTime'] = time();
            if (empty($searchList)) {
                $return['status'] = 36;
                $return['message'] = '查询成功，暂无数据';

                $return['range'] = '';
                $return['info'] = array();
            } else {
                $return['status'] = 1;
                $return['message'] = '查询成功';

                $modelUser = D("Members");
                foreach ($searchList as $k => $v) {
                    //会员相关信息
                    $field = 'id,uniqueId as userId,name,jpush,phone,image,imageUrl,integral,cityId,provinceId,freeze,handlePassword,type';
                    $resUser = $modelUser->getUserInfo($v['userId']);

                    //设置会员信息
                    $searchList[$k]['userId'] = '';
                    $searchList[$k]['nickname'] = '';
                    $searchList[$k]['userImage'] = '';

                    if ($resUser['id']) {
                        $searchList[$k]['userId'] = encodePass($resUser['id']);
                        $searchList[$k]['nickname'] = $resUser['name'];
                        $searchList[$k]['userImage'] = $resUser['imageUrl'];
                    }

                    $searchList[$k]['title'] = base64_encode(jsonStrWithOutBadWordsNew($v['title'], 2));

                    $mapP['dataId'] = $v['id'];
                    $mapP['userId'] = $userId;

                    //获取打包路径
                    $mapPP['dataId'] = $v['id'];
                    $dataP = D('PicturePoster')->selData($mapPP, '', 'field');
                    //$resPoster[$k]['field'] = empty($dataP) ? '':$dataP['field'];
                    $searchList[$k]['field'] = 'http://dev.feibaokeji.com/Application/Home/View/Adinfo/index.html?id=3&userId=1&phone=12345678910';

                    if ($userId == 44427) {
                        $searchList[$k]['collectflag'] = 2;
                        $searchList[$k]['isExpose'] = '2';
                    } else {
                        $reB = D('ExposePosterLog')->selData($mapP, 1); //查询揭广告状态
                        $searchList[$k]['isExpose'] = empty($reB[0]) ? '2' : '1';

                        $rec = $modelUser->getUserCollectStatus($v['id'], $userId); //广告收藏状态
                        $searchList[$k]['collectflag'] = $rec ? 1 : 2;
                    }
                    $searchList[$k]['id'] = encodePass($v['id']);
                }

                //更新会员定位信息
                if ($page == 1 && $myLng && $myLat && $userId != 44427) {
                    $modelAddress = D('Members');
                    $modelAddress->updateUserAddress($userId, $myLng, $myLat, $cityId);
                }

                //$rangelist = array_slice($searchList, -1, 1);
                //$range = GetDistance($myLng, $myLat, $rangelist[0]['lng'], $rangelist[0]['lat']);
                //$return['range'] = judgeDistance($range);
                $return['info'] = $searchList;
            }
        }
        echo jsonStr($return);exit();
    }

    /**
     * 3.2离我最近广告信息列表
     * @param  string $version 版本号
     * @param  string $myLng 物理地址经度(即手机GPS定位的“我的位置”)
     * @param  string $myLat 物理地址纬度(即手机GPS定位的“我的位置”)
     * @param  string $page 当前页
     * @param  string $pageSize 每页显示数量
     * @param  string $userId 唯一码
     * @param  string $phone 会员注册手机号
     * @param  string $cityId 上一次请求的城市id
     * @param  string $selectTime 每次刷新请求返回的时间【必填项】
     * @return json 广告数据的JSON字符串
     */
    public function rangeList() {
        $return['success'] = true;

        $version = I('post.version');
        $userId = I('post.userId');
        $phone = I('post.phone');
        $myLng = I('post.myLng');
        $myLat = I('post.myLat');

        $page = I('post.page');
        $pageSize = I('post.pageSize');
        $cityId = I('post.cityId');
        $selectTime = I('post.selectTime');

        if (is_empty($version) || is_empty($userId) || is_empty($phone) || is_empty($page) || is_empty($pageSize)) {//判断参数是否有缺失
            $return['status'] = -888;
            $return['message'] = '传参不完整';
        } else {
            $userId = $this->userId;
            $modelPoster = D('Poster');

            //查询搜索词信息
            $field = 'id,name';

            //$searchList = $modelPoster->newPoster($selectTime, $page, $pageSize, $field, $cityId, $myLng, $myLat);
            $searchList = $modelPoster->rangeList($selectTime, $page, $pageSize, $field, $cityId, $myLng, $myLat);

            $return['selectTime'] = time();
            if (empty($searchList)) {
                $return['status'] = 36;
                $return['message'] = '查询成功，暂无数据';

                $return['range'] = '';
                $return['info'] = array();
            } else {
                $return['status'] = 1;
                $return['message'] = '查询成功';

                $modelUser = D("Members");
                foreach ($searchList as $k => $v) {
                    //会员相关信息
                    $field = 'id,uniqueId as userId,name,jpush,phone,image,imageUrl,integral,cityId,provinceId,freeze,handlePassword,type';
                    $resUser = $modelUser->getUserInfo($v['userId']);

                    //设置会员信息
                    $searchList[$k]['userId'] = '';
                    $searchList[$k]['nickname'] = '';
                    $searchList[$k]['userImage'] = '';

                    if ($resUser['id']) {
                        $searchList[$k]['userId'] = encodePass($resUser['id']);
                        $searchList[$k]['nickname'] = $resUser['name'];
                        $searchList[$k]['userImage'] = $resUser['imageUrl'];
                    }

                    $searchList[$k]['title'] = base64_encode(jsonStrWithOutBadWordsNew($v['title'], 2));

                    $mapP['dataId'] = $v['id'];
                    $mapP['userId'] = $userId;

                    //获取打包路径
                    $mapPP['dataId'] = $v['id'];
                    $dataP = D('PicturePoster')->selData($mapPP, '', 'field');
                    //$resPoster[$k]['field'] = empty($dataP) ? '':$dataP['field'];
                    $searchList[$k]['field'] = 'http://dev.feibaokeji.com/Application/Home/View/Adinfo/index.html?id=3&userId=1&phone=12345678910';

                    if ($userId == 44427) {
                        $searchList[$k]['collectflag'] = 2;
                        $searchList[$k]['isExpose'] = '2';
                    } else {
                        $reB = D('ExposePosterLog')->selData($mapP, 1); //查询揭广告状态
                        $searchList[$k]['isExpose'] = empty($reB[0]) ? '2' : '1';

                        $rec = $modelUser->getUserCollectStatus($v['id'], $userId); //广告收藏状态
                        $searchList[$k]['collectflag'] = $rec ? 1 : 2;
                    }
                    $searchList[$k]['id'] = encodePass($v['id']);
                }

                //更新会员定位信息
                if ($page == 1 && $myLng && $myLat && $userId != 44427) {
                    $modelAddress = D('Members');
                    $modelAddress->updateUserAddress($userId, $myLng, $myLat, $cityId);
                }

                //$rangelist = array_slice($searchList, -1, 1);
                //$range = GetDistance($myLng, $myLat, $rangelist[0]['lng'], $rangelist[0]['lat']);
                //$return['range'] = judgeDistance($range);
                $return['info'] = $searchList;
            }
        }
        echo jsonStr($return);exit();
    }

    /**
     * 3.2限时推广-广告信息列表
     * @param  string $version 版本号
     * @param  string $myLng 物理地址经度(即手机GPS定位的“我的位置”)
     * @param  string $myLat 物理地址纬度(即手机GPS定位的“我的位置”)
     * @param  string $page 当前页
     * @param  string $pageSize 每页显示数量
     * @param  string $userId 唯一码
     * @param  string $phone 会员注册手机号
     * @param  string $cityId 上一次请求的城市id
     * @param  string $selectTime 每次刷新请求返回的时间【必填项】
     * @return json 广告数据的JSON字符串
     */
    public function promoteList() {
        $return['success'] = true;

        $version = I('post.version');
        $userId = I('post.userId');
        $phone = I('post.phone');
        $myLng = I('post.myLng');
        $myLat = I('post.myLat');

        $page = I('post.page');
        $pageSize = I('post.pageSize');
        $cityId = I('post.cityId');
        $selectTime = I('post.selectTime');

        if (is_empty($version) || is_empty($userId) || is_empty($phone) || is_empty($page) || is_empty($pageSize)) {//判断参数是否有缺失
            $return['status'] = -888;
            $return['message'] = '传参不完整';
        } else {
            $userId = $this->userId;
            $modelPoster = D('Poster');

            //查询搜索词信息
            $field = 'id,name';

            $searchList = $modelPoster->promoteList($selectTime, $page, $pageSize, $field, $cityId, $myLng, $myLat);
            //var_dump($searchList);die;
            //$searchList = $modelPoster->newPoster($selectTime, $page, $pageSize, $field, $cityId, $myLng, $myLat);

            $return['selectTime'] = time();
            if (empty($searchList)) {
                $return['status'] = 36;
                $return['message'] = '查询成功，暂无数据';

                $return['range'] = '';
                $return['info'] = array();
            } else {
                $return['status'] = 1;
                $return['message'] = '查询成功';

                $modelUser = D("Members");
                foreach ($searchList as $k => $v) {
                    //会员相关信息
                    $field = 'id,uniqueId as userId,name,jpush,phone,image,imageUrl,integral,cityId,provinceId,freeze,handlePassword,type';
                    $resUser = $modelUser->getUserInfo($v['userId']);

                    //设置会员信息
                    $searchList[$k]['userId'] = '';
                    $searchList[$k]['nickname'] = '';
                    $searchList[$k]['userImage'] = '';

                    if ($resUser['id']) {
                        $searchList[$k]['userId'] = encodePass($resUser['id']);
                        $searchList[$k]['nickname'] = $resUser['name'];
                        $searchList[$k]['userImage'] = $resUser['imageUrl'];
                    }

                    $searchList[$k]['title'] = base64_encode(jsonStrWithOutBadWordsNew($v['title'], 2));

                    $mapP['dataId'] = $v['id'];
                    $mapP['userId'] = $userId;

                    //获取打包路径
                    $mapPP['dataId'] = $v['id'];
                    $dataP = D('PicturePoster')->selData($mapPP, '', 'field');
                    //$resPoster[$k]['field'] = empty($dataP) ? '':$dataP['field'];
                    $searchList[$k]['field'] = 'http://dev.feibaokeji.com/Application/Home/View/Adinfo/index.html?id=3&userId=1&phone=12345678910';

                    if ($userId == 44427) {
                        $searchList[$k]['collectflag'] = 2;
                        $searchList[$k]['isExpose'] = '2';
                    } else {
                        $reB = D('ExposePosterLog')->selData($mapP, 1); //查询揭广告状态
                        $searchList[$k]['isExpose'] = empty($reB[0]) ? '2' : '1';

                        $rec = $modelUser->getUserCollectStatus($v['id'], $userId); //广告收藏状态
                        $searchList[$k]['collectflag'] = $rec ? 1 : 2;
                    }

                    $searchList[$k]['id'] = encodePass($v['id']);
                }

                //更新会员定位信息
                if ($page == 1 && $myLng && $myLat && $userId != 44427) {
                    $modelAddress = D('Members');
                    $modelAddress->updateUserAddress($userId, $myLng, $myLat, $cityId);
                }

                //$rangelist = array_slice($searchList, -1, 1);
                //$range = GetDistance($myLng, $myLat, $rangelist[0]['lng'], $rangelist[0]['lat']);
                //$return['range'] = judgeDistance($range);
                $return['info'] = $searchList;
            }
        }
        echo jsonStr($return);exit();
    }

    /**
     * 3.2最热广告信息列表
     * @param  string $version 版本号
     * @param  string $myLng 物理地址经度(即手机GPS定位的“我的位置”)
     * @param  string $myLat 物理地址纬度(即手机GPS定位的“我的位置”)
     * @param  string $page 当前页
     * @param  string $pageSize 每页显示数量
     * @param  string $userId 唯一码
     * @param  string $phone 会员注册手机号
     * @param  string $cityId 上一次请求的城市id
     * @param  string $selectTime 每次刷新请求返回的时间【必填项】
     * @return json 广告数据的JSON字符串
     */
    public function hotList() {
        $return['success'] = true;

        $version = I('post.version');
        $userId = I('post.userId');
        $phone = I('post.phone');
        $myLng = I('post.myLng');
        $myLat = I('post.myLat');

        $page = I('post.page');
        $pageSize = I('post.pageSize');
        $cityId = I('post.cityId');
        $selectTime = I('post.selectTime');

        if (is_empty($version) || is_empty($userId) || is_empty($phone) || is_empty($page) || is_empty($pageSize)) {//判断参数是否有缺失
            $return['status'] = -888;
            $return['message'] = '传参不完整';
        } else {
            $userId = $this->userId;
            $modelPoster = D('Poster');

            //查询搜索词信息
            $field = 'id,name';
            //$searchList = $modelPoster->newPoster($selectTime, $page, $pageSize, $field, $cityId, $myLng, $myLat);
            $searchList = $modelPoster->getHeatList($selectTime, $page, $pageSize, $field, $cityId, $myLng, $myLat);

            $return['selectTime'] = time();
            if (empty($searchList)) {
                $return['status'] = 36;
                $return['message'] = '查询成功，暂无数据';

                $return['range'] = '';
                $return['info'] = array();
            } else {
                $return['status'] = 1;
                $return['message'] = '查询成功';

                $modelUser = D("Members");
                foreach ($searchList as $k => $v) {
                    //会员相关信息
                    $field = 'id,uniqueId as userId,name,jpush,phone,image,imageUrl,integral,cityId,provinceId,freeze,handlePassword,type';
                    $resUser = $modelUser->getUserInfo($v['userId']);

                    //设置会员信息
                    $searchList[$k]['userId'] = '';
                    $searchList[$k]['nickname'] = '';
                    $searchList[$k]['userImage'] = '';

                    if ($resUser['id']) {
                        $searchList[$k]['userId'] = encodePass($resUser['id']);
                        $searchList[$k]['nickname'] = $resUser['name'];
                        $searchList[$k]['userImage'] = $resUser['imageUrl'];
                    }

                    $searchList[$k]['title'] = base64_encode(jsonStrWithOutBadWordsNew($v['title'], 2));

                    //if ($res['id']) {//首先判断会员id是否正确
                    $mapP['dataId'] = $v['id'];
                    $mapP['userId'] = $userId;

                    //获取打包路径
                    $mapPP['dataId'] = $v['id'];
                    $dataP = D('PicturePoster')->selData($mapPP, '', 'field');
                    //$resPoster[$k]['field'] = empty($dataP) ? '':$dataP['field'];
                    $searchList[$k]['field'] = 'http://dev.feibaokeji.com/Application/Home/View/Adinfo/index.html?id=3&userId=1&phone=12345678910';
                    if ($userId == 44427) {
                        $searchList[$k]['isExpose'] = '2';
                        $searchList[$k]['collectflag'] = 2;
                    } else {
                        $reB = D('ExposePosterLog')->selData($mapP, 1); //查询揭广告状态
                        $searchList[$k]['isExpose'] = empty($reB[0]) ? '2' : '1';

                        $rec = $modelUser->getUserCollectStatus($v['id'], $userId); //广告收藏状态
                        $searchList[$k]['collectflag'] = $rec ? 1 : 2;
                    }
                    $searchList[$k]['id'] = encodePass($v['id']);
                }

                //更新会员定位信息
                if ($page == 1 && $myLng && $myLat && $userId != 44427) {
                    $modelAddress = D('Members');
                    $modelAddress->updateUserAddress($userId, $myLng, $myLat, $cityId);
                }

                //$rangelist = array_slice($searchList, -1, 1);
                //$range = GetDistance($myLng, $myLat, $rangelist[0]['lng'], $rangelist[0]['lat']);
                //$return['range'] = judgeDistance($range);
                $return['info'] = $searchList;
            }
        }
        echo jsonStr($return);exit();
    }

    public function testPage() {

        $modelPoster = D('Poster');
        $userId = 95010;
        $noteId = 1;
        $id = 254;
        $forwardUserId = 95010;
        $proRedStart = 50;
        $proRedEnd = 50;
        $type = 1;
        $advRedStart = 50;
        $advRedEnd = 50;
        $address = 100;

        //$searchList = $modelPoster->AttenitionList(95050, 0, 1, 20, '', 0, 0, 0);
        $searchList = $modelPoster->rangeList(0, 1, 20, '', 1, '116.493637', '39.923238');
        //$searchList = $modelPoster->promoteList(0, 1, 20, '', 0, 0, 0);


        $res = $modelPoster->addIntegral($userId, $noteId, $id, $forwardUserId, $proRedStart, $proRedEnd, $type, $advRedStart, $advRedEnd, $address);
        echo $res;
        die;

        $search = '123';
        $modelPoster->addSearch($search);
        echo 124;
        die;

        $return['success'] = true;

        $list = M('City')->where('layer=2')->select();
        foreach ($list as $k => $v) {
            echo $v['id'] . '-' . $v['name'] . '-' . $v['layer'] . '-' . $v['firstLetter'] . '-' . $v['wordFirstLetter'] . '-' . $v['fullLetter'];
            echo "\n";
        }
        var_dump($list);
        die;


        echo decodePass('a60acb6cUiE1yhUgs');
        die;

        $version = I('post.version');
        $userId = I('post.userId');
        $phone = I('post.phone');
        $myLng = I('post.myLng');
        $myLat = I('post.myLat');

        $page = I('post.page');
        $pageSize = I('post.pageSize');
        $cityId = I('post.cityId');
        $selectTime = I('post.selectTime');

        if (is_empty($version) || is_empty($userId) || is_empty($phone) || is_empty($page) || is_empty($pageSize)) {//判断参数是否有缺失
            $return['status'] = -888;
            $return['message'] = '传参不完整';
        } else {

            $userId = $this->userId;
            $modelPoster = D('Poster');

            //查询搜索词信息
            $field = 'id,name';
            $searchList = $modelPoster->getNewPoster($selectTime, $page, $pageSize, $field, $cityId, $myLng, $myLat);

            $return['selectTime'] = time();
            if (empty($searchList)) {
                $return['status'] = 36;
                $return['message'] = '查询成功，暂无数据';

                $return['range'] = '';
                $return['info'] = array();
            } else {
                $return['status'] = 1;
                $return['message'] = '查询成功';

                $modelUser = D("Members");
                foreach ($searchList as $k => $v) {
                    //会员相关信息
                    $field = 'id,uniqueId as userId,name,jpush,phone,image,imageUrl,integral,cityId,provinceId,freeze,handlePassword,type';
                    $resUser = $modelUser->getUserInfo($v['userId']);

                    //设置会员信息
                    $searchList[$k]['userId'] = '';
                    $searchList[$k]['nickname'] = '';
                    $searchList[$k]['userImage'] = '';

                    if ($resUser['id']) {
                        $searchList[$k]['userId'] = encodePass($resUser['userId']);
                        $searchList[$k]['nickname'] = $resUser['name'];
                        $searchList[$k]['userImage'] = $resUser['imageUrl'];
                    }

                    //$searchList[$k]['title'] = base64_encode(jsonStrWithOutBadWordsNew($v['title'],2));

                    if ($res['id']) {//首先判断会员id是否正确
                        $mapP['dataId'] = $v['id'];
                        $mapP['userId'] = $userId;

                        //获取打包路径
                        $mapPP['dataId'] = $v['id'];
                        $dataP = D('PicturePoster')->selData($mapPP, '', 'field');
                        //$resPoster[$k]['field'] = empty($dataP) ? '':$dataP['field'];
                        $searchList[$k]['field'] = 'http://dev.feibaokeji.com/Application/Home/View/Adinfo/index.html?id=3&userId=1&phone=12345678910';

                        $reB = D('ExposePosterLog')->selData($mapP, 1); //查询揭广告状态
                        $searchList[$k]['isExpose'] = empty($reB[0]) ? '2' : '1';

                        $rec = $modelUser->getUserCollectStatus($v['id'], $userId); //广告收藏状态
                        $searchList[$k]['collectflag'] = $rec ? 1 : 2;
                    } else {
                        $searchList[$k]['isExpose'] = '2';
                        $searchList[$k]['collectflag'] = '2';
                        $searchList[$k]['field'] = '';
                    }

                    //$searchList[$k]['id'] = encodePass($v['id']);
                }

                //$rangelist = array_slice($searchList, -1, 1);
                //$range = GetDistance($myLng, $myLat, $rangelist[0]['lng'], $rangelist[0]['lat']);
                //$return['range'] = judgeDistance($range);
                $return['info'] = $searchList;
            }
        }
        echo jsonStr($return);exit();
    }

}

?>