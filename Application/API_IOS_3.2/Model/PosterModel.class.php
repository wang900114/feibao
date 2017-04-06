<?php

//广告模型表
class PosterModel extends CommonModel {

    public $_validate = array(
            // array('name','require','必须填写城市名称'),
            // array('firstLetter','require','必须填写首字母'),
            // array('wordFirstLetter','require','必须填写拼音首字母'),
            // array('fullLetter','require','必须填写全拼'),
            // array('lng','require','必须有经度数值'),
            // array('lat','require','必须有纬度数值'),
    );

    //获取轮播图广告数据
    public function getHomePage($cityId, $field = 'id,imageUrl,title,shareUrl,integral') {
        $map =' startTime <' . time(); //是否在开始时间之后
        $map.=' and endTime >' . time(); //是否在结束时间内
        $map.=' and status ="1"'; //广告状态是否正常
        $map.= ' and is_above_display ="2" '; //轮播区域是否展示
        $map.=' and integral >exposeTotalIntegral + extendTotalIntegral';//广告飞币是否正常
        $map .= ' and (type="1" or type="2")';
        
        $order = 'listorder asc,id desc';
        $re = M('PosterAdvert')->field($field)->where($map)->order($order)->limit(C('POSTERS_PUBLIC_LIMIT'))->select();
        //echo M('PosterAdvert')->getLastSql();die;
        if (empty($re))
            $re = array();
        return $re;
    }

    //获取广告首页分类
    public function getHomeCategory($selectTime, $field = 'id,name,status,addTime') {
        $map['status'] = array('eq', '1');
        $map['nickName'] = array('neq', 'APP下载');
        $order = 'listorder asc,id desc';

        $re = M('PosterHomeCategory')->field($field)->where($map)->order($order)->limit(C('POSTERS_HOME_CATEGORY_LIMIT'))->select();
        if (empty($re))
            $re = array();
        return $re;
    }

    //获取热门搜索词
    public function getSearchList($field = 'id') {
        $map['type'] = array('eq', '1');
        $order = 'amount desc';
        $re = M('PosterSerachLog')->field($field)->where($map)->order($order)->limit(500)->select();

        //过滤长度不适合的搜索词
        $res = array();
        if ($re) {
            $j = 0;
            for ($i = 0; $i < count($re); $i++) {
                if (utf8_strlen($re[$i]['content']) <= 12 && count($res) < 10) {
                    $res[$j] = $re[$i];
                    $j = $j + 1;
                }
            }
        }
        return $res;
    }

    //获取分类信息
    public function getCategoryList($field = 'cid as id') {
        $order = 'listorder ASC,cid desc';

        $re = M('PosterCategory')->field($field)->where('status = "1" ')->order($order)->limit(500)->select();
        if (empty($re))
            $re = array();
        return $re;
    }

    //获取广告信息列表
    public function dataList($type, $typeId, $selectTime, $page, $pageSize, $field, $cityId, $myLng, $myLat) {

        $selectTime = $selectTime <= 0 ? time() : $selectTime;
        $page = empty($page) ? 1 : $page;
        $field = 'pa.id,pa.lng,pa.lat,pa.pushType,pa.shareUrl,pa.title,pa.is_above_display,pa.userId,pa.addTime,pa.webUrl,pa.collectTotal,pa.warnPhone,pa.imageUrl,pa.webUrl,pt.categoryType as type,pt.dataId as typeId';
        $join = " AS pt LEFT JOIN lu_poster_advert AS pa ON pt.dataId = pa.id";
        $limits = ($page - 1) * $pageSize;
        $limit = $limits . "," . $pageSize;

        //条件
        //$where = ' pa.is_above_display ="1"'; //轮播区域是否展示
        $where.=' pa.startTime <' . $selectTime; //是否在开始时间之后
        $where.=' and pa.endTime >' . $selectTime; //是否在结束时间内
        $where.=' and pa.status ="1"'; //广告状态是否正常
        $where.=' and pa.integral >pa.exposeTotalIntegral + pa.extendTotalIntegral';
        $where .= ' and (pa.type="1" or pa.type="2")';
        //$where.=' and pa.pushCityId like "%,' . $cityId . ',%"';
        if ($type == 3) {
            $rNameData = M("poster_tags")->field('name')->where(array('cid' => $typeId))->find();
            if ($rNameData['name']) {
                $idData = M("poster_tags")->field('cid')->where(array('name' => $rNameData['name']))->select();
                foreach ($idData as $key => $value) {
                    $idStr.= $value['cid'] . ',';
                }
                $idStr = substr($idStr, 0, -1);
                $where.=' and pt.typeId in(' . $idStr . ')';
                $where.=' and pt.categoryType =' . $type;
            }
        } else {
            $where.=' and pt.typeId =' . $typeId;
            $where.=' and pt.categoryType =' . $type;
        }

        //排序方式
        $order = ' pa.id desc';
        if ($cityId && $myLng && $myLat) {//判断参数是否为空
            //读取经度转化为距离的系数
            $MAP_LNG_BASIC = C("MAP_LNG_BASIC");

            //读取维度转化为距离的系数
            $MAP_LAT_BASICC = C("MAP_LAT_BASIC");
            //$order = " ABS(lng-{$myLng})/{$MAP_LNG_BASIC} + ABS(lat-{$myLat})/{$MAP_LAT_BASICC}  asc";
            //统计精准发布的广告数量
            //$whereFirst.= $where . ' and pa.pushType ="1" and pa.pushCityId =",' . $cityId . '," and pa.lngMin <' . $myLng . ' and pa.lngMax >' . $myLng . ' and pa.latMin <' . $myLat . ' and pa.latMax>' . $myLat;
            $whereFirst.= $where . ' and pa.pushType ="1" and pa.lngMin <' . $myLng . ' and pa.lngMax >' . $myLng . ' and pa.latMin <' . $myLat . ' and pa.latMax>' . $myLat;
            $arrReC = M('PosterTypeRelation')->field($field)->where($whereFirst)->order($order)->join($join)->select();
            $arrReCount = count($arrReC);
            //echo M('PosterTypeRelation')->getLastSql();
            //统计单个城市发布的广告
            $whereSecond.= $where . ' and pa.pushType ="2" and pa.pushCityId =",' . $cityId . ',"';
            $arrRetsC = M('PosterTypeRelation')->field($field)->where($whereSecond)->order($order)->join($join)->select();
            $arrRetsCount = count($arrRetsC);
            //echo M('PosterTypeRelation')->getLastSql();
            //获取区域广告
            //$whereThree.= $where . ' and pa.pushType in ("3","4") and pa.pushCityId like "%,' . $cityId . ',%"';
            $whereThree.= $where . ' and (pa.pushType ="4" or ( pa.pushType ="3" and pa.pushCityId like "%,' . $cityId . ',%"))';

            $arrResC = M('PosterTypeRelation')->field($field)->where($whereThree)->order($order)->join($join)->select();
            $arrResCount = count($arrResC);
            //echo M('PosterTypeRelation')->getLastSql();die;
            //echo $arrReCount.'-'.$arrRetsCount.'-'.$arrResCount;die;

            $resultArray = array();
            if ($page == 1) {
                $limit = 0 . "," . $pageSize; //分页条件
                $resultArray = M('PosterTypeRelation')->field($field)->where($whereFirst)->order($order)->join($join)->limit($limit)->select();

                $yu = $pageSize - count($resultArray);
                //echo $yu.'-'.$pageSize.'-'.count($resultArray);die;
                if ($yu) {
                    $limit = 0 . "," . $yu; //分页条件
                    $arrRets = M('PosterTypeRelation')->field($field)->where($whereSecond)->order($orders)->join($join)->limit($limit)->select();
                    //echo M('PosterTypeRelation')->getLastSql();
                    if ($arrRets) {//获取单个城市发布的广告
                        if (empty($resultArray)) {
                            $resultArray = $arrRets;
                        } else {
                            $resultArray = array_merge($resultArray, $arrRets);
                        }
                    }
                    //var_dump($arrRets);die;

                    $yu = $yu - count($arrRets);
                    if ($yu) {
                        $limit = 0 . "," . $yu; //分页条件
                        $arrRes = M('PosterTypeRelation')->field($field)->where($whereThree)->order($orders)->join($join)->limit($limit)->select();
                        //echo M('PosterTypeRelation')->getLastSql();
                        if ($arrRes) {//获取区域城市发布的广告
                            if (empty($resultArray)) {
                                $resultArray = $arrRes;
                            } else {
                                $resultArray = array_merge($resultArray, $arrRes);
                            }
                        }
                        //var_dump($resultArray);die;
                    }
                }
                return $resultArray;
            }


            if ($arrReCount - $limits > 0 && $arrReCount > 0) {//判断上一次请求的总量与精准数据的大小，确定区间
                $limit = $limits . "," . $pageSize; //分页条件
                $resultArray = M('PosterTypeRelation')->field($field)->where($whereFirst)->order($order)->join($join)->limit($limit)->select();

                $yu = $pageSize - count($resultArray);
                if ($yu) {
                    $limit = 0 . "," . $yu; //分页条件
                    $arrRets = M('PosterTypeRelation')->field($field)->where($whereSecond)->order($order)->join($join)->limit($limit)->select();
                    if ($arrRets) {//获取单个城市发布的广告
                        if (empty($resultArray)) {
                            $resultArray = $arrRets;
                        } else {
                            $resultArray = array_merge($resultArray, $arrRets);
                        }
                    }

                    $yu = $yu - count($arrRets);
                    if ($yu) {
                        $limit = 0 . "," . $yu; //分页条件
                        $arrRes = M('PosterTypeRelation')->field($field)->where($whereThree)->order($order)->join($join)->limit($limit)->select();
                        //echo M('PosterTypeRelation')->getLastSql();die;
                        if ($arrRes) {//获取区域城市发布的广告
                            if (empty($resultArray)) {
                                $resultArray = $arrRes;
                            } else {
                                $resultArray = array_merge($resultArray, $arrRes);
                            }
                        }
                    }
                }
                return $resultArray;
            }

            if ($arrRetsCount + $arrReCount - $limits > 0 && $arrRetsCount > 0) {
                $limit = abs($limits - $arrReCount) . "," . $pageSize; //分页条件
                $resultArray = M('PosterTypeRelation')->field($field)->where($whereSecond)->order($order)->join($join)->limit($limit)->select();
                //echo M('PosterAdvert')->getLastSql();

                $yu = $pageSize - count($resultArray);
                if ($yu) {
                    $limit = 0 . "," . $yu; //分页条件
                    $arrRes = M('PosterTypeRelation')->field($field)->where($whereThree)->order($order)->join($join)->limit($limit)->select();
                    //echo M('PosterAdvert')->getLastSql();die;
                    if ($arrRes) {//获取区域城市发布的广告
                        if (empty($resultArray)) {
                            $resultArray = $arrRes;
                        } else {
                            $resultArray = array_merge($resultArray, $arrRes);
                        }
                    }
                }
                return $resultArray;
            }

            if ($arrResCount > 0) {
                $limit = abs($limits - $arrRetsCount - $arrReCount) . "," . $pageSize; //分页条件
                $resultArray = M('PosterTypeRelation')->field($field)->where($whereThree)->order($order)->join($join)->limit($limit)->select();
                //echo M('PosterTypeRelation')->getLastSql();die;
                return $resultArray;
            }
        } else {//只取全国数据
            $where .= $where . ' and pa.pushType ="4"';
            $resultArray = M('PosterTypeRelation')->field($field)->where($where)->order(' id desc')->join($join)->join($join)->limit($limit)->select();
        }
        return $resultArray;
    }

    //获取我的收藏广告信息列表
    public function myCollectList($userId, $selectTime, $page, $pageSize, $field) {
        $selectTime = $selectTime <= 0 ? time() : $selectTime;
        $page = empty($page) ? 1 : $page;
        $limit = ($page - 1) * $pageSize . "," . $pageSize;
        $field = 'pc.id,pc.userId,pc.dataId';
        //$field = 'pa.id,pa.title,pa.is_above_display,pa.userId,pa.addTime,pa.webUrl,pa.collectTotal,pa.warnPhone,pa.imageUrl,pa.webUrl';
        $join = " AS pc LEFT JOIN lu_poster_advert AS pa ON pc.dataId = pa.id";
        //条件
        //$where =' pa.is_above_display ="1"';//轮播区域是否展示
        //$where.=' pa.startTime <' . $selectTime; //是否在开始时间之后
        //$where.=' pa.endTime >' . $selectTime; //是否在结束时间内
        //$where.=' and pa.status ="1"'; //广告状态是否正常
        //$where.=' and pa.integral >pa.exposeTotalIntegral + pa.extendTotalIntegral';
        //$where.=' and pa.pushCityId like "%,'. $cityId .',%"';
        $where.='pc.userId =' . $userId;
        $where .= ' and (pa.type="1" or pa.type="2")';

        //排序方式
        $order = 'pc.addTime desc';
        $re = M('CollectPosterLog')->field($field)->where($where)->order($order)->join($join)->limit($limit)->select();
        //$re = M('CollectPosterLog')->field($field)->where($where)->order($order)->limit($limit)->select();
        //echo M('CollectPosterLog')->getLastSql();die;

        if (empty($re))
            $re = array();
        return $re;
/*
        $selectTime = $selectTime <= 0 ? time() : $selectTime;
        $page = empty($page) ? 1 : $page;
        $limit = ($page - 1) * $pageSize . "," . $pageSize;
        $field = 'id,userId,dataId';
        //$field = 'pa.id,pa.title,pa.is_above_display,pa.userId,pa.addTime,pa.webUrl,pa.collectTotal,pa.warnPhone,pa.imageUrl,pa.webUrl';
        //$join = " AS pc LEFT JOIN lu_poster_advert AS pa ON pc.dataId = pa.id";
        //条件
        //$where =' pa.is_above_display ="1"';//轮播区域是否展示
        //$where.=' pa.startTime <' . $selectTime; //是否在开始时间之后
        //$where.=' pa.endTime >' . $selectTime; //是否在结束时间内
        //$where.=' and pa.status ="1"'; //广告状态是否正常
        //$where.=' and pa.integral >pa.exposeTotalIntegral + pa.extendTotalIntegral';
        //$where.=' and pa.pushCityId like "%,'. $cityId .',%"';
        $where.='userId =' . $userId;

        //排序方式
        $order = 'addTime desc';
        //$re = M('CollectPosterLog')->field($field)->where($where)->order($order)->join($join)->limit($limit)->select();
        $re = M('CollectPosterLog')->field($field)->where($where)->order($order)->limit($limit)->select();
        //echo M('CollectPosterLog')->getLastSql();die;

        if (empty($re))
            $re = array();
        return $re;
 */
    }

    /*
     * 获取广告搜索信息列表
     */

    public function datasearchList($search, $selectTime, $page, $pageSize, $field, $cityId, $myLng, $myLat) {

        $nowTime = time();
        $selectTime = $selectTime <= 0 ? $nowTime : $selectTime;

        $page = empty($page) ? 1 : $page;
        $limits = ($page - 1) * $pageSize; //上一次请求的总量
        //$limit = $limits . "," . $pageSize;//分页条件

        $where.=' startTime <' . $selectTime;
        $where.=' and endTime >' . $selectTime; //是否在结束时间内
        //$where.=' and is_above_display ="1"'; //展示类型
        $where.=' and status =1 '; //广告状态
        $where.=' and integral >exposeTotalIntegral + extendTotalIntegral';
        $where.=' and title like "%' . $search . '%"';
        $where .= ' and (type="1" or type="2")';
        
        $field = 'id,title,collectTotal,userId,is_above_display,addTime,is_above_display,collectTotal,warnPhone,shareUrl,imageUrl,webUrl';

        //排序方式
        $orders = 'id desc';

        if ($cityId && $myLng && $myLat) {//判断参数是否为空
            //读取经度转化为距离的系数
            $MAP_LNG_BASIC = C("MAP_LNG_BASIC");

            //读取维度转化为距离的系数
            $MAP_LAT_BASICC = C("MAP_LAT_BASIC");
            $order = " ABS(lng-{$myLng})/{$MAP_LNG_BASIC} + ABS(lat-{$myLat})/{$MAP_LAT_BASICC}  asc";

            //统计精准发布的广告数量
            $whereFirst.= $where . ' and pushType ="1" and lngMin <' . $myLng . ' and lngMax >' . $myLng . ' and latMin <' . $myLat . ' and latMax>' . $myLat;
            //$whereFirst.= $where . ' and pushType ="1" and pushCityId =",' . $cityId . '," and lngMin <' . $myLng . ' and lngMax >' . $myLng . ' and latMin <' . $myLat . ' and latMax>' . $myLat;
            $arrReC = M('PosterAdvert')->field($field)->where($whereFirst)->order($order)->select();
            $arrReCount = count($arrReC);
            //echo M('PosterAdvert')->getLastSql();
            //统计单个城市发布的广告
            $whereSecond.= $where . ' and pushType ="2" and pushCityId =",' . $cityId . ',"';
            $arrRetsC = M('PosterAdvert')->field($field)->where($whereSecond)->order($orders)->select();
            $arrRetsCount = count($arrRetsC);
            //echo M('PosterAdvert')->getLastSql();
            //获取区域广告
            //$whereThree.= $where . ' and pushType in ("3","4") and pushCityId like "%,' . $cityId . ',%"';
            $whereThree.= $where . ' and (pushType ="4" or ( pushType ="3" and pushCityId like "%,' . $cityId . ',%"))';
            $arrResC = M('PosterAdvert')->field($field)->where($whereThree)->order($orders)->select();
            $arrResCount = count($arrResC);
            //echo M('PosterAdvert')->getLastSql();die;

            $resultArray = array();
            if ($page == 1) {
                $limit = 0 . "," . $pageSize; //分页条件
                $resultArray = M('PosterAdvert')->field($field)->where($whereFirst)->order($order)->limit($limit)->select();

                $yu = $pageSize - count($resultArray);
                if ($yu) {
                    $limit = 0 . "," . $yu; //分页条件
                    $arrRets = M('PosterAdvert')->field($field)->where($whereSecond)->order($orders)->limit($limit)->select();

                    if ($arrRets) {//获取单个城市发布的广告
                        if (empty($resultArray)) {
                            $resultArray = $arrRets;
                        } else {
                            $resultArray = array_merge($resultArray, $arrRets);
                        }
                    }

                    $yu = $yu - count($arrRets);
                    if ($yu) {
                        $limit = 0 . "," . $yu; //分页条件
                        $arrRes = M('PosterAdvert')->field($field)->where($whereThree)->order($orders)->limit($limit)->select();
                        //echo M('PosterAdvert')->getLastSql();
                        if ($arrRes) {//获取区域城市发布的广告
                            if (empty($resultArray)) {
                                $resultArray = $arrRes;
                            } else {
                                $resultArray = array_merge($resultArray, $arrRes);
                            }
                        }
                    }
                }
                return $resultArray;
            }


            if ($arrReCount - $limits > 0 && $arrReCount > 0) {//判断上一次请求的总量与精准数据的大小，确定区间
                $limit = $limits . "," . $pageSize; //分页条件
                $resultArray = M('PosterAdvert')->field($field)->where($whereFirst)->order($order)->limit($limit)->select();

                $yu = $pageSize - count($resultArray);
                if ($yu) {
                    $limit = 0 . "," . $yu; //分页条件
                    $arrRets = M('PosterAdvert')->field($field)->where($whereSecond)->order($orders)->limit($limit)->select();
                    if ($arrRets) {//获取单个城市发布的广告
                        if (empty($resultArray)) {
                            $resultArray = $arrRets;
                        } else {
                            $resultArray = array_merge($resultArray, $arrRets);
                        }
                    }

                    $yu = $yu - count($arrRets);
                    if ($yu) {
                        $limit = 0 . "," . $yu; //分页条件
                        $arrRes = M('PosterAdvert')->field($field)->where($whereThree)->order($orders)->limit($limit)->select();
                        if ($arrRes) {//获取区域城市发布的广告
                            if (empty($resultArray)) {
                                $resultArray = $arrRes;
                            } else {
                                $resultArray = array_merge($resultArray, $arrRes);
                            }
                        }
                    }
                }
                return $resultArray;
            }

            if ($arrRetsCount + $arrReCount - $limits > 0 && $arrRetsCount > 0) {
                $limit = abs($limits - $arrReCount) . "," . $pageSize; //分页条件
                $resultArray = M('PosterAdvert')->field($field)->where($whereSecond)->order($orders)->limit($limit)->select();
                //echo M('PosterAdvert')->getLastSql();

                $yu = $pageSize - count($resultArray);
                if ($yu) {
                    $limit = 0 . "," . $yu; //分页条件
                    $arrRes = M('PosterAdvert')->field($field)->where($whereThree)->order($orders)->limit($limit)->select();
                    //echo M('PosterAdvert')->getLastSql();die;
                    if ($arrRes) {//获取区域城市发布的广告
                        if (empty($resultArray)) {
                            $resultArray = $arrRes;
                        } else {
                            $resultArray = array_merge($resultArray, $arrRes);
                        }
                    }
                }
                return $resultArray;
            }

            if ($arrResCount > 0) {
                $limit = abs($limits - $arrRetsCount - $arrReCount) . "," . $pageSize; //分页条件
                $resultArray = M('PosterAdvert')->field($field)->where($whereThree)->order($orders)->limit($limit)->select();
                //echo M('PosterAdvert')->getLastSql();
                return $resultArray;
            }
        } else {//只取全国数据
            $limit = $limits . "," . $pageSize; //分页条件
            $wheres.= $where . ' and pushType ="4"';
            $resultArray = M('PosterAdvert')->field($field)->where($wheres)->order('id desc ')->limit($limit)->select();
        }
        return $resultArray;
    }

    /*
     * 获取我发布的广告信息列表-按照id排序
     */

    public function myDatasList($userId, $minId, $page, $pageSize, $field, $selectTime) {
        //$selectTime = $selectTime <= 0 ? time() : $selectTime;
//        if ($minId) {
//            $where = 'id <' . $minId . 'and status in (1,2,3,4,5,6,7) and userId =' . $userId; //是否在开始时间之后
//        } else {
        //$where = 'status in (1,2,3,4,5,6,7) and userId =' . $userId; //是否在开始时间之后
//        }
        if (empty($selectTime)) {
            $selectTime = time();
        }
        if($minId){
            $where['addTime'] = array('lt', $selectTime);
            $where['status'] = array('in', '1,2,3,4,5,6,7');
            $where['type'] = array('in', '1,2');
            $where['userId'] = $userId;
            $where['id'] = array('lt',$minId);
        }else{
            $where['addTime'] = array('lt', $selectTime);
            $where['status'] = array('in', '1,2,3,4,5,6,7');
            $where['type'] = array('in', '1,2');
            $where['userId'] = $userId;
        }
        //$where['addTime'] = array('lt', $selectTime);
        //$where['status'] = array('in', '1,2,3,4,5,6,7');
        //$where['userId'] = $userId;

        $field = 'id,title,collectTotal,userId,integral,exposeTotalIntegral,shareUrl,extendTotalIntegral,status,is_above_display,endTime,addTime,is_above_display,collectTotal,warnPhone,imageUrl,webUrl';
        $page = empty($page) ? 1 : $page;
        $limit = ($page - 1) * $pageSize . "," . $pageSize;

        //条件
        //$where = 'startTime <' . $selectTime; //是否在开始时间之后
        //$where.=' and endTime >' . $selectTime; //是否在结束时间内
        //$where.=' and status in (1,2,3,4,5,6,7)'; //广告状态是否正常
        //$where.=' and integral >exposeTotalIntegral + extendTotalIntegral';
        //$where.=' and userId =' . $userId;
        //$where.=' and title like "%'. $search .'%"';
        //
        //排序方式
        $order = ' id desc';

        $re = M('PosterAdvert')->field($field)->where($where)->order($order)->limit($limit)->select();
        //echo M('PosterAdvert')->getLastSql();
        if (empty($re))
            $re = array();
        return $re;
    }

    /*
     * 获取我发布的广告信息列表-按照距离排序
     */

    public function myDataList($userId, $selectTime, $page, $pageSize, $field, $myLat, $myLng, $cityId) {
        $nowTime = time();
        $selectTime = $selectTime <= 0 ? $nowTime : $selectTime;
        $startTime = $nowTime - (7 * 24 * 3600);

        $page = empty($page) ? 1 : $page;

        $limits = ($page - 1) * $pageSize; //上一次请求的总量
        //$limit = $limits . "," . $pageSize;//分页条件

        $where.=' startTime <' . $selectTime;
        $where.=' and endTime >' . $selectTime; //是否在结束时间内
        $where.=' and status =1 '; //广告状态
        //$where.=' and is_above_display ="1"'; //展示类型
        $where.=' and userId=' . $userId;
        $where.=' and integral >exposeTotalIntegral + extendTotalIntegral';
        $where .= ' and (type="1" or type="2")';
        
        $field = 'id,title,collectTotal,lng,lat,lngMin,latMin,lngMax,latMax,userId,pushType,shareUrl,is_above_display,addTime,startTime,endTime,is_above_display,collectTotal,warnPhone,imageUrl,webUrl';

        //排序方式
        $orders = 'id desc';

        if ($cityId && $myLng && $myLat) {//判断参数是否为空
            //读取经度转化为距离的系数
            $MAP_LNG_BASIC = C("MAP_LNG_BASIC");

            //读取维度转化为距离的系数
            $MAP_LAT_BASICC = C("MAP_LAT_BASIC");
            $order = " ABS(lng-{$myLng})/{$MAP_LNG_BASIC} + ABS(lat-{$myLat})/{$MAP_LAT_BASICC}  asc";

            $whereFirst.= $where . ' and pushType ="1" and lngMin <' . $myLng . ' and lngMax >' . $myLng . ' and latMin <' . $myLat . ' and latMax>' . $myLat;
            //$whereFirst.= $where . ' and pushType ="1"';
            $arrReC = M('PosterAdvert')->field($field)->where($whereFirst)->order($order)->select();
            $arrReCount = count($arrReC); //统计精准发布的广告数量
            //echo M('PosterAdvert')->getLastSql();

            $whereSecond.= $where . ' and pushType ="2" and pushCityId =",' . $cityId . ',"';
            $arrRetsC = M('PosterAdvert')->field($field)->where($whereSecond)->order($orders)->select();
            $arrRetsCount = count($arrRetsC); //统计单个城市发布的广告
            //echo M('PosterAdvert')->getLastSql();
            //$whereThree.= $where . ' and pushType in ("3","4") and pushCityId like "%,' . $cityId . ',%"';
            $whereThree.= $where . ' and (pushType ="4" or ( pushType ="3" and pushCityId like "%,' . $cityId . ',%"))';
            $arrResC = M('PosterAdvert')->field($field)->where($whereThree)->order($orders)->select();
            $arrResCount = count($arrResC); //获取区域广告
            //echo M('PosterAdvert')->getLastSql();die;

            $resultArray = array();
            $arrRe = array();

            if ($page == 1) {
                $limit = 0 . "," . $pageSize; //分页条件
                $resultArray = M('PosterAdvert')->field($field)->where($whereFirst)->order($order)->limit($limit)->select();

                $yu = $pageSize - count($resultArray);
                if ($yu) {
                    $limit = 0 . "," . $yu; //分页条件
                    $arrRets = M('PosterAdvert')->field($field)->where($whereSecond)->order($orders)->limit($limit)->select();

                    if ($arrRets) {//获取单个城市发布的广告
                        if (empty($resultArray)) {
                            $resultArray = $arrRets;
                        } else {
                            $resultArray = array_merge($resultArray, $arrRets);
                        }
                    }

                    $yu = $yu - count($arrRets);
                    if ($yu) {
                        $limit = 0 . "," . $yu; //分页条件
                        $arrRes = M('PosterAdvert')->field($field)->where($whereThree)->order($orders)->limit($limit)->select();
                        //echo M('PosterAdvert')->getLastSql();
                        if ($arrRes) {//获取区域城市发布的广告
                            if (empty($resultArray)) {
                                $resultArray = $arrRes;
                            } else {
                                $resultArray = array_merge($resultArray, $arrRes);
                            }
                        }
                    }
                }
                return $resultArray;
            }


            if ($arrReCount - $limits > 0 && $arrReCount > 0) {//判断上一次请求的总量与精准数据的大小，确定区间
                $limit = $limits . "," . $pageSize; //分页条件
                $resultArray = M('PosterAdvert')->field($field)->where($whereFirst)->order($order)->limit($limit)->select();

                $yu = $pageSize - count($resultArray);
                if ($yu) {
                    $limit = 0 . "," . $yu; //分页条件
                    $arrRets = M('PosterAdvert')->field($field)->where($whereSecond)->order($orders)->limit($limit)->select();
                    if ($arrRets) {//获取单个城市发布的广告
                        if (empty($resultArray)) {
                            $resultArray = $arrRets;
                        } else {
                            $resultArray = array_merge($resultArray, $arrRets);
                        }
                    }

                    $yu = $yu - count($arrRets);
                    if ($yu) {
                        $limit = 0 . "," . $yu; //分页条件
                        $arrRes = M('PosterAdvert')->field($field)->where($whereThree)->order($orders)->limit($limit)->select();
                        if ($arrRes) {//获取区域城市发布的广告
                            if (empty($resultArray)) {
                                $resultArray = $arrRes;
                            } else {
                                $resultArray = array_merge($resultArray, $arrRes);
                            }
                        }
                    }
                }
                return $resultArray;
            }

            if ($arrRetsCount + $arrReCount - $limits > 0 && $arrRetsCount > 0) {
                $limit = abs($limits - $arrReCount) . "," . $pageSize; //分页条件
                $resultArray = M('PosterAdvert')->field($field)->where($whereSecond)->order($orders)->limit($limit)->select();
                //echo M('PosterAdvert')->getLastSql();

                $yu = $pageSize - count($resultArray);
                if ($yu) {
                    $limit = 0 . "," . $yu; //分页条件
                    $arrRes = M('PosterAdvert')->field($field)->where($whereThree)->order($orders)->limit($limit)->select();
                    //echo M('PosterAdvert')->getLastSql();die;
                    if ($arrRes) {//获取区域城市发布的广告
                        if (empty($resultArray)) {
                            $resultArray = $arrRes;
                        } else {
                            $resultArray = array_merge($resultArray, $arrRes);
                        }
                    }
                }
                return $resultArray;
            }

            if ($arrResCount > 0) {
                $limit = abs($limits - $arrRetsCount - $arrReCount) . "," . $pageSize; //分页条件
                $resultArray = M('PosterAdvert')->field($field)->where($whereThree)->order($orders)->limit($limit)->select();
                //echo M('PosterAdvert')->getLastSql();
                return $resultArray;
            }
        } else {//只取全国数据
            $limit = $limits . "," . $pageSize; //分页条件
            $wheres.= $where . ' and pushType ="4"';
            $resultArray = M('PosterAdvert')->field($field)->where($wheres)->order($orders)->limit($limit)->select();
        }

        return $resultArray;
        /*
          $selectTime = $selectTime <= 0 ? time() : $selectTime;

          $field = 'id,title,collectTotal,userId,status,is_above_display,addTime,is_above_display,collectTotal,warnPhone,imageUrl,webUrl';
          $page = empty($page) ? 1 : $page;
          $limit = ($page - 1) * $pageSize . "," . $pageSize;

          //条件
          $where = 'startTime <' . $selectTime; //是否在开始时间之后
          $where.=' and endTime >' . $selectTime; //是否在结束时间内
          $where.=' and status ="1"'; //广告状态是否正常
          $where.=' and integral >exposeTotalIntegral + extendTotalIntegral';
          $where.=' and userId =' . $userId;
          //$where.=' and title like "%'. $search .'%"';
          //
          //排序方式
          $order = ' addTime desc';

          $re = M('PosterAdvert')->field($field)->where($where)->order($order)->limit($limit)->select();
          //echo M('PosterAdvert')->getLastSql();die;
          if (empty($re))
          $re = array();
          return $re;
         */
    }

    /*
     * 添加搜索次数
     */
    public function addSearch($content) {
        $where['content'] = $content;
        $resSearch = M('PosterSerachLog')->field('id')->where($where)->find();
        //echo M('PosterSerachLog')->getLastSql();die;

        if ($resSearch['id']) {//添加数量
            M('PosterSerachLog')->where('id =' . $resSearch['id'])->setInc("amount", 1);
        } else {//添加搜索词
            $data['content'] = $content;
            $data['addTime'] = time();
            M('PosterSerachLog')->add($data);
        }
    }
    

    /*
     * 广告下架
     */
    public function undercarriage($userId, $id) {
        $field = 'id,status,endTime,startTime';
        $re = M('PosterAdvert')->field($field)->where('id =' . $id)->find();

        if ($re) {

            if ($re['status'] != 1) {
                if ($re['status'] == 2) {
                    return 2;
                } else {
                    return 3;
                }
            } else {
                $data['status'] = 2;
                $result = M('PosterAdvert')->where('id =' . $id . ' and userId =' . $userId)->save($data);
                //echo M('PosterAdvert')->getLastSql();die;

                if ($result) {
                    return 1;
                } else {
                    return 3;
                }
            }
        } else {
            return 3;
        }
    }

    /*
     * 获取单个广告基本信息
     */

    public function getPosterAdvert($dataId, $field = 'id,title,lngMin,imageUrl,lngMax,latMax,pushCityId,latMin,address,type,status,pushType,warnPhone,shareUrl,collectTotal,weburl,addTime,startTime,endTime,integral,exposeTotalIntegral,extendTotalIntegral') {
        $re = M('PosterAdvert')->field($field)->where('id =' . $dataId)->find();
        //var_dump(M('PosterAdvert')->getLastSql());die;

        if ($re) {//获取标签
            $categoryList = M('PosterTypeRelation')->field('typeId,categoryType')->where('dataId =' . $dataId)->select();

            if ($categoryList) {
                $i = 0;
                foreach ($categoryList as $key => $val) {
                    if ($val['categoryType'] == 2) {
                        $posterCategoryData = M('PosterCategory')->field('cid,name')->where('cid =' . $val['typeId'])->find();
                        $category[$i]['cid'] = encodePass($posterCategoryData['cid']);
                        $category[$i]['name'] = $posterCategoryData['name'];
                        $category[$i]['type'] = 2;
                        $i++;
                    } elseif ($val['categoryType'] == 3) {
                        $posterTagsData = M('PosterTags')->field('cid,name')->where('cid =' . $val['typeId'])->find();
                        $category[$i]['cid'] = encodePass($posterTagsData['cid']);
                        $category[$i]['name'] = $posterTagsData['name'];
                        $category[$i]['type'] = 3;
                        $i++;
                    }
                }

                $re['category'] = $category;
            } else {
                $re['category'] = array();
            }
            $where = array(
                'dataId' => $re['id']
            );
            $fields = '*';
            if ($re['type'] == 1) {//取商品信息
                $htmlData = M("poster_goods")->where($where)->field($fields)->find();
            }

            //echo $re['type'];die;
            if ($re['type'] == 2) {//取优惠信息
                $htmlData = M("poster_discount")->where($where)->field($fields)->find();
                //echo  M("poster_discount")->getLastSql();die;
            }

            $re['htmlData'] = $htmlData['htmlData'];
            $re['weburl'] = $htmlData['goodsLink'];
            //$re['htmlData'] =$htmlData;
        }

        if (empty($re))
            $re = array();
        return $re;
    }

    /*
     * 获取广告商品基本信息
     */

    public function getGoods($id, $field = '*') {
        $re = M('PosterGoods')->field($field)->where('dataId =' . $id)->find();
        //echo M('PosterGoods')->getLastSql();die;
        if (empty($re))
            $re = array();
        return $re;
    }

    /*
     * 添加统计进入商品次数
     */

    public function addGoodsClickTimes($id, $userId, $type = '1') {
        $where['dataId'] = $id;
        $where['type'] = $type;
        $resSearch = M('PosterGoodsClick')->field('id')->where($where)->find();
        //var_dump($resSearch);die;

        if ($resSearch['id']) {//添加数量
            $res = M('PosterGoodsClick')->where('id =' . $resSearch['id'])->setInc("amount", 1);
            //echo M('PosterGoodsClick')->getLastSql();die;
        } else {//添加内容
            $data['dataId'] = $id;
            $data['userId'] = $userId;
            $data['addTime'] = time();
            $data['amount'] = 1;
            $data['type'] = '1';
            $res = M('PosterGoodsClick')->add($data);
        }
        return $res;
    }

    /*
     * 获取符合范围内的我的朋友
     */

    public function getListData($userId, $pushCityId, $ragne, $myLng, $myLat, $cityId, $type, $lngMax, $latMax, $lngMin, $latMin) {
        $limit = '500';
        //echo $userId;die;

        if ($type == 1) {//精准经纬度
            $field = 'm.image,m.name AS nickName,m.integral,f.fuid AS userId,m.groupType';
            $join = " AS f RIGHT JOIN __MEMBERS__ AS m ON f.fuid = m.id";

            $where = 'f.uid=' . $userId;
            $where .= ' and m.name!="飞报官方推荐"';
            $where.= ' and f.status ="1"';
            $where.= ' and m.myLng >' . $lngMin;
            $where.= ' and m.myLat >' . $latMin;
            $where.= ' and m.myLng <' . $lngMax;
            $where.= ' and m.myLat <' . $latMax;

            $order = ' f.addTime desc';
            $res = M('Friend')->field($field)->where($where)->order($order)->join($join)->limit($limit)->select();
            //echo M('Friend')->getLastSql();die;
        } elseif ($type == 2) {//单个城市
            $field = 'm.image,m.name AS nickName,m.integral,f.fuid AS userId,m.groupType';
            $join = " AS f RIGHT JOIN __MEMBERS__ AS m ON f.fuid = m.id";

            $where = 'f.uid=' . $userId;
            $where .= ' and m.name!="飞报官方推荐"';
            $where.= ' and f.status ="1"';
            $pushCityId = substr($pushCityId, 1, -1);
            $where.= ' and m.cityId=' . $pushCityId;

            $order = ' f.addTime desc';
            $res = M('Friend')->field($field)->where($where)->order($order)->join($join)->limit($limit)->select();
            //echo M('Friend')->getLastSql();die;
        } elseif ($type == 3) {//多区域
            $field = 'm.image,m.name AS nickName,m.integral,f.fuid AS userId,m.groupType';
            $join = " AS f RIGHT JOIN __MEMBERS__ AS m ON f.fuid = m.id";

            $where = 'f.uid=' . $userId;
            $where .= ' and m.name!="飞报官方推荐"';
            $where.= ' and f.status ="1"';
            $pushCityId = substr($pushCityId, 1, -1);
            $where.=' and m.cityId in(' . $pushCityId . ')';

            $order = ' f.addTime desc';
            $res = M('Friend')->field($field)->where($where)->order($order)->join($join)->limit($limit)->select();
            //echo M('Friend')->getLastSql();die;
        } elseif ($type == 4) {
            $field = 'm.image,m.name AS nickName,m.integral,f.fuid AS userId,m.groupType';
            $join = " AS f RIGHT JOIN __MEMBERS__ AS m ON f.fuid = m.id";

            $where = 'f.uid=' . $userId;
            $where .= ' and m.name!="飞报官方推荐"';
            $where.= ' and f.status ="1"';

            $order = ' f.addTime desc';
            $res = M('Friend')->field($field)->where($where)->order($order)->join($join)->limit($limit)->select();
        } else {
            $res = array();
            return $res;
        }
        //echo M('Friend')->getLastSql();die;

        if (empty($res))
            $res = array();
        return $res;
    }

    /*
     * 添加转发朋友广告记录
     */

    public function addForward($data) {
        //不用lu_poster_forward_log，启用lu_friend_forward
        M('FriendForward')->add($data);
        //echo M('FriendForward')->getLastsql();die;
        return TRUE;
    }

    /*
     * 获取飞币的飞币值
     */

    public function getPosterIntegral($start, $end, $yuIntegral) {
        if ($start == $end) {
            $integral = $start > $yuIntegral ? $yuIntegral : $start;
        } else {

            //使用放大区间并求余的方式让随机值更散列
            $mt_rand = mt_rand(0, 2100000000);
            $integral = $mt_rand % $end;

            if ($integral > $end) {
                $integral = mt_rand($start, $end);
            }elseif ($integral < $start) {
                $integral = $start;
            }
            
            /*
            $integral = mt_rand($start, $end);
            if ($integral > $end) {//判断随机飞币是否超出最大值的范围
                $integral = $end;
            } elseif ($integral < $start) {
                $integral = $start;
            }
            */
            
            if ($yuIntegral < $integral) {//判断剩余飞币与赠送的飞币大小
                $integral = $yuIntegral;
            }
        }

        return $integral;
    }

    /*
     * 添加操作广告添加飞币记录
     */

    public function addIntegral($userId, $noteId, $id, $forwardUserId, $proRedStart, $proRedEnd, $type, $advRedStart, $advRedEnd, $address) {
        //$type = $type ? $type : 1;
        //if ($type == 1) {//查看广告获取飞币
        //echo $userId.'-'.$forwardUserId.'-'.$noteId;die;
        if ($userId == $forwardUserId) {//判断转发人和查看人是否是同一人
            $flag = M('ExposePosterLog')->field('id,integral,exposeTotalIntegral,extendTotalIntegral')->where('dataId=' . $id . ' and userId =' . $userId . ' and type = "1" and status = "1" and friendId='.$userId)->find();
            //echo M('ExposePosterLog')->getLastSql();die;
            
            if ($flag) {//判断已经获取
                return -1;
            } else {
                $field = 'id,title,integral,status,exposeTotalIntegral,extendTotalIntegral,advRedStart,advRedEnd,proRedStart,proRedEnd,extendTotalIntegral,addTime';
                $resPoster = $this->getPosterAdvert($id, $field);
                if (empty($resPoster)) {
                    return -1;
                }

                $tmpsIntegral = $resPoster['integral'] - $resPoster['exposeTotalIntegral'] - $resPoster['extendTotalIntegral'];
                $integral = $this->getPosterIntegral($resPoster['advRedStart'], $resPoster['advRedEnd'], $tmpsIntegral);

                if ($integral) {
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
                    //echo $reBillLog;die;
                    //添加飞币
                    D("Members")->addUsersIntegral($userId, $integral);
                    
                    $content = '你通过点击“' . $resPoster['title'] . '”的获取飞币，送飞币';
                    D("Members")->addMemberDope($userId, $content, '1', $integral, $id, '3');
                    //echo D('ExposePosterLog')->getLastSql();die;
                    //更新数据
                    M('PosterAdvert')->where('id =' . $id)->setInc("exposeTotal", 1);
                    M('PosterAdvert')->where('id =' . $id)->setInc("exposeTotalIntegral", $integral);

                    return $integral;
                } else {
                    return 0;
                }
            }
        } else {
            //先验证是否已经获取
            $where = ' friendId =' . $userId . ' and id=' . $noteId . ' and dataId =' . $id.' and userId='.$forwardUserId;
            $res = M('FriendForward')->field('id,status')->where($where)->find();
            //echo M('FriendForward')->getLastSql();
            
            if (empty($res['id'])) {//判断是否为空
                return -1;
            } else {
                if ($res['status'] == '2') {//判断已获取状态
                    return 0;
                }
            }

            //验证广告是否正常
            $field = 'id,title,integral,status,exposeTotalIntegral,extendTotalIntegral,advRedStart,advRedEnd,proRedStart,proRedEnd,addTime';
            $resPoster = $this->getPosterAdvert($id, $field);
            //var_dump($resPoster);die;

            if ($resPoster) {
                if ($resPoster['status'] == 1) {//判断广告状态
                    $tmpIntegral = $resPoster['integral'] - ( $resPoster['exposeTotalIntegral'] + $resPoster['extendTotalIntegral']);
                    //echo $tmpIntegral;die;

                    if ($tmpIntegral) {//判断飞币是否充足
                        $integral = $this->getPosterIntegral($resPoster['advRedStart'], $resPoster['advRedEnd'], $tmpIntegral);
                        //echo $resPoster['advRedStart'].'-'.$resPoster['$advRedEnd'].'-'.$tmpIntegral;die;

                        if ($integral) {
                            
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

                            //添加飞币
                            D("Members")->addUsersIntegral($userId, $integral);
                            $content = '你查看朋友转发的“' . $resPoster['title'] . '”，送飞币';
                            D("Members")->addMemberDope($userId, $content, '1', $integral, $id, '3');
                            //echo M("MembersDope")->getLastSql();die;
                            //更新数据
                            M('PosterAdvert')->where('id =' . $id)->setInc("exposeTotal", 1);
                            M('PosterAdvert')->where('id =' . $id)->setInc("exposeTotalIntegral", $integral);

                            //为转发人添加飞币
                            $resPoster = $this->getPosterAdvert($id, $field);
                            
                            $yu = $resPoster['integral'] - ( $resPoster['exposeTotalIntegral'] + $resPoster['extendTotalIntegral']);
                            
                            if ($yu > 0) {
                                $userIdIntegral = $this->getPosterIntegral($resPoster['proRedStart'], $resPoster['proRedEnd'], $yu);
                                //echo $userIdIntegral.'-'.$resPoster['proRedStart'].'-'.$resPoster['proRedEnd'].'-'.$yu;die;
                                
                                if ($userIdIntegral) {
                                    //添加飞币
                                    D("Members")->addUsersIntegral($forwardUserId, $userIdIntegral);

                                    //更新相关信息
                                    M('PosterAdvert')->where('id =' . $id)->setInc("extendTotal", 1);
                                    M('PosterAdvert')->where('id =' . $id)->setInc("extendTotalIntegral", $userIdIntegral);

                                    $whereNoteId = 'id = ' . $noteId;
                                    $datas['userIdIntegral'] = $userIdIntegral;
                                    $datas['status'] = "2";

                                    M('FriendForward')->where($whereNoteId)->data($datas)->save();
                                    //echo M('FriendForward')->getLastSql();die;

                                    $content = '你转发的“' . $resPoster['title'] . '”被朋友查看，送飞币';
                                    D("Members")->addMemberDope($forwardUserId, $content, '1', $userIdIntegral, $id, '4');
                                    //echo M("MembersDope")->getLastSql();die;
                                    
                                }
                            }
                        }

                        return $integral;
                    } else {
                        return 0;
                    }
                }
            } else {
                return -1;
            }
        }
    }

    /**
     * 更新揭广告限制地址的相关统计
     * @param int $pid 被揭的广告的ID
     * @param string $address 揭广告的地址
     * @param int $uid 揭广告的用户的ID
     */
    public function updataPostLimitLog($pid, $address, $uid) {
        //监测此地址是否有记录
        //如果有记录更新揭广告地址限制表中的总操作数
        //否则插入记录并设定总操作数为1
        $address_limit_m = M("address_limit");
        $where = array();
        $where['address'] = $address;
        $address = $address_limit_m->where($where)->find();

        if ($address) {
            $address_limit_m->where($where)->save("all_num=all_num+1");
            $aid = $address['id'];
        } else {
            $data['all_num'] = 1;
            $data['address'] = $address;
            $data['status'] = 1;
            $aid = $address_limit_m->add($data);
        }
        return $aid;
    }

    /*
     * 添加统计次数
     */

    public function addClickTotal($id, $type = 1) {
        if ($type == 1) {
            $res = M('PosterAdvert')->where('id =' . $id)->setInc("clickTotal", 1);
        } elseif ($type == 2) {
            $res = M('PosterAdvert')->where('id =' . $id)->setInc("shareTotal", 1);
        } elseif ($type == 3) {
            $res = M('PosterAdvert')->where('id =' . $id)->setInc("forwardTotal", 1);
        }

        //echo M('PosterAdvert')->getLastSql();die;
        return $res;
    }

    /*
     * 获取最新广告信息列表-测试
     */

    public function newPoster($selectTime, $page, $pageSize, $field, $cityId, $myLng, $myLat) {
        $selectTime = $selectTime <= 0 ? time() : $selectTime;

        $field = 'id,title,collectTotal,userId,is_above_display,shareUrl,addTime,is_above_display,collectTotal,warnPhone,imageUrl,webUrl';
        $limit = ($page - 1) * $pageSize . "," . $pageSize;

        //条件
        //$where='addTime <'.$selectTime;
        $where.='startTime <' . $selectTime; //是否在开始时间之后
        $where.=' and endTime >' . $selectTime; //是否在结束时间内
        $where.=' and status ="1"'; //广告状态是否正常
        $where.=' and integral >exposeTotalIntegral + extendTotalIntegral';
        $where .= ' and (type="1" or type="2")';
        //$where.=' and pushCityId like "%,'. $cityId .',%"';
        //$where.=' and title like "%'. $search .'%"';
        //排序方式
        $order = ' pushType ASC';

        $re = M('PosterAdvert')->field($field)->where($where)->order($order)->limit($limit)->select();
        //echo M('PosterAdvert')->getLastSql();die;
        if (empty($re))
            $re = array();
        return $re;
    }

    /*
     * 获取限时推广的广告-old
     */

    public function promoteListOld($selectTime, $page, $pageSize, $field, $cityId, $myLng, $myLat) {
        $selectTime = $selectTime <= 0 ? time() : $selectTime;
        $page = empty($page) ? 1 : $page;

        $limits = ($page - 1) * $pageSize; //上一次请求的总量
        $limit = $limits . "," . $pageSize;

        $field = 'pa.id,pa.title,pa.is_above_display,pa.userId,pa.shareUrl,pa.addTime,pa.webUrl,pa.collectTotal,pa.warnPhone,pa.imageUrl'; //pt.conditionContent,pt.wayContent';
        $join = " AS pt LEFT JOIN lu_poster_advert AS pa ON pt.dataId = pa.id";

        //条件
        //$where = ' pa.is_above_display ="1"'; //轮播区域是否展示
        $where.=' and pa.startTime <' . $selectTime; //是否在开始时间之后
        $where.=' and pa.endTime >' . $selectTime; //是否在结束时间内
        $where.=' and pa.status =1 '; //广告状态是否正常
        $where.=' and pa.integral >pa.exposeTotalIntegral + pa.extendTotalIntegral';
        $where .= ' and (type="1" or type="2")';
        
        //$where.=' and pt.cid =3';
        $where.=' and pt.categoryType = "1"';
        $where.=' and pt.typeId = 3';

        //排序方式
        $order = ' pa.endTime desc';

        if ($cityId && $myLng && $myLat) {//判断参数是否为空
            //读取经度转化为距离的系数
            $MAP_LNG_BASIC = C("MAP_LNG_BASIC");

            //读取维度转化为距离的系数
            $MAP_LAT_BASICC = C("MAP_LAT_BASIC");
            //$order = " ABS(lng-{$myLng})/{$MAP_LNG_BASIC} + ABS(lat-{$myLat})/{$MAP_LAT_BASICC}  asc";
            //统计精准发布的广告数量

            $whereFirst.= $where . ' and pa.pushType ="1" and pa.lngMin <' . $myLng . ' and pa.lngMax >' . $myLng . ' and pa.latMin <' . $myLat . ' and pa.latMax>' . $myLat;
            //$whereFirst.= $where . ' and pa.pushType ="1" and pa.pushCityId =",' . $cityId . '," and pa.lngMin <' . $myLng . ' and pa.lngMax >' . $myLng . ' and pa.latMin <' . $myLat . ' and pa.latMax>' . $myLat;
            $arrReC = M('PosterTypeRelation')->field($field)->where($whereFirst)->order($order)->join($join)->select();
            //echo M('PosterTypeRelation')->getLastSql();

            $arrReCount = count($arrReC);
            //echo M('PosterTypeRelation')->getLastSql();
            //统计单个城市发布的广告
            $whereSecond.= $where . ' and pa.pushType ="2" and pa.pushCityId =",' . $cityId . ',"';
            $arrRetsC = M('PosterTypeRelation')->field($field)->where($whereSecond)->order($order)->join($join)->select();
            $arrRetsCount = count($arrRetsC);
            //echo M('PosterTypeRelation')->getLastSql();
            //获取区域广告
            //$whereThree.= $where . ' and pa.pushType in ("3","4") and pa.pushCityId like "%,' . $cityId . ',%"';
            $whereThree.= $where . ' and (pa.pushType ="4" or ( pa.pushType ="3" and pa.pushCityId like "%,' . $cityId . ',%"))';
            $arrResC = M('PosterTypeRelation')->field($field)->where($whereThree)->order($order)->join($join)->select();
            $arrResCount = count($arrResC);
            //echo M('PosterDiscount')->getLastSql();die;

            $resultArray = array();
            if ($page == 1) {
                $limit = 0 . "," . $pageSize; //分页条件
                $resultArray = M('PosterTypeRelation')->field($field)->where($whereFirst)->order($order)->join($join)->limit($limit)->select();
                //echo M('PosterTypeRelation')->getLastSql();

                $yu = $pageSize - count($resultArray);
                if ($yu) {
                    $limit = 0 . "," . $yu; //分页条件
                    $arrRets = M('PosterTypeRelation')->field($field)->where($whereSecond)->order($orders)->join($join)->limit($limit)->select();

                    if ($arrRets) {//获取单个城市发布的广告
                        if (empty($resultArray)) {
                            $resultArray = $arrRets;
                        } else {
                            $resultArray = array_merge($resultArray, $arrRets);
                        }
                    }

                    $yu = $yu - count($arrRets);
                    if ($yu) {
                        $limit = 0 . "," . $yu; //分页条件
                        $arrRes = M('PosterTypeRelation')->field($field)->where($whereThree)->order($orders)->join($join)->limit($limit)->select();
                        //echo M('PosterAdvert')->getLastSql();
                        if ($arrRes) {//获取区域城市发布的广告
                            if (empty($resultArray)) {
                                $resultArray = $arrRes;
                            } else {
                                $resultArray = array_merge($resultArray, $arrRes);
                            }
                        }
                    }
                }
                return $resultArray;
            }


            if ($arrReCount - $limits > 0 && $arrReCount > 0) {//判断上一次请求的总量与精准数据的大小，确定区间
                $limit = $limits . "," . $pageSize; //分页条件
                $resultArray = M('PosterTypeRelation')->field($field)->where($whereFirst)->order($order)->join($join)->limit($limit)->select();

                $yu = $pageSize - count($resultArray);
                if ($yu) {
                    $limit = 0 . "," . $yu; //分页条件
                    $arrRets = M('PosterTypeRelation')->field($field)->where($whereSecond)->order($orders)->join($join)->limit($limit)->select();
                    if ($arrRets) {//获取单个城市发布的广告
                        if (empty($resultArray)) {
                            $resultArray = $arrRets;
                        } else {
                            $resultArray = array_merge($resultArray, $arrRets);
                        }
                    }

                    $yu = $yu - count($arrRets);
                    if ($yu) {
                        $limit = 0 . "," . $yu; //分页条件
                        $arrRes = M('PosterTypeRelation')->field($field)->where($whereThree)->order($orders)->join($join)->limit($limit)->select();
                        if ($arrRes) {//获取区域城市发布的广告
                            if (empty($resultArray)) {
                                $resultArray = $arrRes;
                            } else {
                                $resultArray = array_merge($resultArray, $arrRes);
                            }
                        }
                    }
                }
                return $resultArray;
            }

            if ($arrRetsCount + $arrReCount - $limits > 0 && $arrRetsCount > 0) {
                $limit = abs($limits - $arrReCount) . "," . $pageSize; //分页条件
                $resultArray = M('PosterTypeRelation')->field($field)->where($whereSecond)->order($orders)->join($join)->limit($limit)->select();
                //echo M('PosterAdvert')->getLastSql();

                $yu = $pageSize - count($resultArray);
                if ($yu) {
                    $limit = 0 . "," . $yu; //分页条件
                    $arrRes = M('PosterTypeRelation')->field($field)->where($whereThree)->order($orders)->join($join)->limit($limit)->select();
                    //echo M('PosterAdvert')->getLastSql();die;
                    if ($arrRes) {//获取区域城市发布的广告
                        if (empty($resultArray)) {
                            $resultArray = $arrRes;
                        } else {
                            $resultArray = array_merge($resultArray, $arrRes);
                        }
                    }
                }
                return $resultArray;
            }

            if ($arrResCount > 0) {
                $limit = abs($limits - $arrRetsCount - $arrReCount) . "," . $pageSize; //分页条件
                $resultArray = M('PosterTypeRelation')->field($field)->where($whereThree)->order($orders)->join($join)->limit($limit)->select();
                //echo M('PosterAdvert')->getLastSql();
                return $resultArray;
            }
        } else {//只取全国数据
            $wheres = $where . ' and pa.pushType ="4"';
            $resultArray = M('PosterTypeRelation')->field($field)->where($wheres)->order('id desc')->join($join)->limit($limit)->select();
            //echo M('PosterTypeRelation')->getLastSql();die;
        }

        if ($resultArray) {
            foreach ($resultArray as $k => $v) {
                $list = M('PosterDiscount')->field('conditionContent,wayContent')->where('id =' . $v['id'])->find();
                $resultArray[$k]['conditionContent'] = $list['conditionContent'];
                $resultArray[$k]['wayContent'] = $list['wayContent'];
                //pt.conditionContent,pt.wayContent
            }
        }
        return $resultArray;
    }

    
    
     /*
     * 获取限时推广的广告
     */

    public function promoteList($selectTime, $page, $pageSize, $field, $cityId, $myLng, $myLat) {
        $nowTime = time();
        $selectTime = $selectTime <= 0 ? $nowTime : $selectTime;
        //$startTime = $nowTime - (7 * 24 * 3600);

        $page = empty($page) ? 1 : $page;

        $limits = ($page - 1) * $pageSize; //上一次请求的总量
        //$limit = $limits . "," . $pageSize;//分页条件

        //$where.=' startTime >=' . $startTime; //广告开始时间在一周以前
        $where.=' startTime <' . $selectTime;
        $where.=' and endTime >' . $selectTime; //是否在结束时间内
        $where.=' and status =1 '; //广告状态
        //$where.=' and is_above_display ="1"'; //展示类型
        $where.=' and integral >exposeTotalIntegral + extendTotalIntegral';
        $where .= ' and (type="1" or type="2")';
        
        $field = 'id,title,collectTotal,userId,is_above_display,shareUrl,addTime,is_above_display,collectTotal,warnPhone,imageUrl,webUrl';

        //排序方式
        $orders = 'endTime desc';

        if ($cityId && $myLng && $myLat) {//判断参数是否为空
            //读取经度转化为距离的系数
            $MAP_LNG_BASIC = C("MAP_LNG_BASIC");

            //读取维度转化为距离的系数
            $MAP_LAT_BASICC = C("MAP_LAT_BASIC");
            $order = 'endTime desc';
            //$order = " ABS(lng-{$myLng})/{$MAP_LNG_BASIC} + ABS(lat-{$myLat})/{$MAP_LAT_BASICC}  asc";
            //$whereFirst.= $where . ' and pushType ="1" and pushCityId =",' . $cityId . '," and lngMin <' . $myLng . ' and lngMax >' . $myLng . ' and latMin <' . $myLat . ' and latMax>' . $myLat;
            $whereFirst.= $where . ' and pushType ="1" and lngMin <' . $myLng . ' and lngMax >' . $myLng . ' and latMin <' . $myLat . ' and latMax>' . $myLat;

            $arrReC = M('PosterAdvert')->field($field)->where($whereFirst)->order($order)->select();
            //echo M('PosterAdvert')->getLastSql();die;
            $arrReCount = count($arrReC); //统计精准发布的广告数量

            $whereSecond.= $where . ' and pushType ="2" and pushCityId =",' . $cityId . ',"';
            $arrRetsC = M('PosterAdvert')->field($field)->where($whereSecond)->order($orders)->select();
            $arrRetsCount = count($arrRetsC); //统计单个城市发布的广告
            //$whereThree.= $where . ' and pushType in ("3","4") and pushCityId like "%,' . $cityId . ',%"';
            $whereThree.= $where . ' and (pushType ="4" or ( pushType ="3" and pushCityId like "%,' . $cityId . ',%"))';
            $arrResC = M('PosterAdvert')->field($field)->where($whereThree)->order($orders)->select();
            $arrResCount = count($arrResC); //获取区域广告
            //echo M('PosterAdvert')->getLastSql();die;

            $resultArray = array();
            $arrRe = array();

            if ($page == 1) {
                $limit = 0 . "," . $pageSize; //分页条件
                $resultArray = M('PosterAdvert')->field($field)->where($whereFirst)->order($order)->limit($limit)->select();
                //var_dump($resultArray);die;
                $yu = $pageSize - count($resultArray);
                if ($yu) {
                    $limit = 0 . "," . $yu; //分页条件
                    $arrRets = M('PosterAdvert')->field($field)->where($whereSecond)->order($orders)->limit($limit)->select();

                    if ($arrRets) {//获取单个城市发布的广告
                        if (empty($resultArray)) {
                            $resultArray = $arrRets;
                        } else {
                            $resultArray = array_merge($resultArray, $arrRets);
                        }
                    }

                    $yu = $yu - count($arrRets);
                    if ($yu) {
                        $limit = 0 . "," . $yu; //分页条件
                        $arrRes = M('PosterAdvert')->field($field)->where($whereThree)->order($orders)->limit($limit)->select();
                        //echo M('PosterAdvert')->getLastSql();
                        if ($arrRes) {//获取区域城市发布的广告
                            if (empty($resultArray)) {
                                $resultArray = $arrRes;
                            } else {
                                $resultArray = array_merge($resultArray, $arrRes);
                            }
                        }
                    }
                }
                //var_dump($resultArray);die;
                return $resultArray;
            }


            if ($arrReCount - $limits > 0 && $arrReCount > 0) {//判断上一次请求的总量与精准数据的大小，确定区间
                $limit = $limits . "," . $pageSize; //分页条件
                $resultArray = M('PosterAdvert')->field($field)->where($whereFirst)->order($order)->limit($limit)->select();

                $yu = $pageSize - count($resultArray);
                if ($yu) {
                    $limit = 0 . "," . $yu; //分页条件
                    $arrRets = M('PosterAdvert')->field($field)->where($whereSecond)->order($orders)->limit($limit)->select();
                    if ($arrRets) {//获取单个城市发布的广告
                        if (empty($resultArray)) {
                            $resultArray = $arrRets;
                        } else {
                            $resultArray = array_merge($resultArray, $arrRets);
                        }
                    }

                    $yu = $yu - count($arrRets);
                    if ($yu) {
                        $limit = 0 . "," . $yu; //分页条件
                        $arrRes = M('PosterAdvert')->field($field)->where($whereThree)->order($orders)->limit($limit)->select();
                        if ($arrRes) {//获取区域城市发布的广告
                            if (empty($resultArray)) {
                                $resultArray = $arrRes;
                            } else {
                                $resultArray = array_merge($resultArray, $arrRes);
                            }
                        }
                    }
                }
                return $resultArray;
            }

            if ($arrRetsCount + $arrReCount - $limits > 0 && $arrRetsCount > 0) {
                $limit = abs($limits - $arrReCount) . "," . $pageSize; //分页条件
                $resultArray = M('PosterAdvert')->field($field)->where($whereSecond)->order($orders)->limit($limit)->select();
                //echo M('PosterAdvert')->getLastSql();

                $yu = $pageSize - count($resultArray);
                if ($yu) {
                    $limit = 0 . "," . $yu; //分页条件
                    $arrRes = M('PosterAdvert')->field($field)->where($whereThree)->order($orders)->limit($limit)->select();
                    //echo M('PosterAdvert')->getLastSql();die;
                    if ($arrRes) {//获取区域城市发布的广告
                        if (empty($resultArray)) {
                            $resultArray = $arrRes;
                        } else {
                            $resultArray = array_merge($resultArray, $arrRes);
                        }
                    }
                }
                return $resultArray;
            }

            if ($arrResCount > 0) {
                $limit = abs($limits - $arrRetsCount - $arrReCount) . "," . $pageSize; //分页条件
                $resultArray = M('PosterAdvert')->field($field)->where($whereThree)->order($orders)->limit($limit)->select();
                //echo M('PosterAdvert')->getLastSql();
                return $resultArray;
            }
        } else {//只取全国数据
            $limit = $limits . "," . $pageSize; //分页条件
            $wheres.= $where . ' and pushType ="4"';
            $resultArray = M('PosterAdvert')->field($field)->where($wheres)->order('id desc')->limit($limit)->select();
        }

        return $resultArray;
    }

    
    
    /*
     * 获取一周以内最新的广告数据
     */

    public function getNewPoster($selectTime, $page, $pageSize, $field, $cityId, $myLng, $myLat) {
        $nowTime = time();
        $selectTime = $selectTime <= 0 ? $nowTime : $selectTime;
        $startTime = $nowTime - (7 * 24 * 3600);

        $page = empty($page) ? 1 : $page;

        $limits = ($page - 1) * $pageSize; //上一次请求的总量
        //$limit = $limits . "," . $pageSize;//分页条件

        $where.=' startTime >=' . $startTime; //广告开始时间在一周以前
        $where.=' and startTime <' . $selectTime;
        $where.=' and endTime >' . $selectTime; //是否在结束时间内
        $where.=' and status =1 '; //广告状态
        //$where.=' and is_above_display ="1"'; //展示类型
        $where.=' and integral >exposeTotalIntegral + extendTotalIntegral';
        $where .= ' and (type="1" or type="2")';
        
        $field = 'id,title,collectTotal,userId,is_above_display,shareUrl,addTime,is_above_display,collectTotal,warnPhone,imageUrl,webUrl';

        //排序方式
        $orders = 'id desc';

        if ($cityId && $myLng && $myLat) {//判断参数是否为空
            //读取经度转化为距离的系数
            $MAP_LNG_BASIC = C("MAP_LNG_BASIC");

            //读取维度转化为距离的系数
            $MAP_LAT_BASICC = C("MAP_LAT_BASIC");
            $order = 'id desc';
            //$order = " ABS(lng-{$myLng})/{$MAP_LNG_BASIC} + ABS(lat-{$myLat})/{$MAP_LAT_BASICC}  asc";
            //$whereFirst.= $where . ' and pushType ="1" and pushCityId =",' . $cityId . '," and lngMin <' . $myLng . ' and lngMax >' . $myLng . ' and latMin <' . $myLat . ' and latMax>' . $myLat;
            $whereFirst.= $where . ' and pushType ="1" and lngMin <' . $myLng . ' and lngMax >' . $myLng . ' and latMin <' . $myLat . ' and latMax>' . $myLat;

            $arrReC = M('PosterAdvert')->field($field)->where($whereFirst)->order($order)->select();
            //echo M('PosterAdvert')->getLastSql();die;
            $arrReCount = count($arrReC); //统计精准发布的广告数量

            $whereSecond.= $where . ' and pushType ="2" and pushCityId =",' . $cityId . ',"';
            $arrRetsC = M('PosterAdvert')->field($field)->where($whereSecond)->order($orders)->select();
            $arrRetsCount = count($arrRetsC); //统计单个城市发布的广告
            //$whereThree.= $where . ' and pushType in ("3","4") and pushCityId like "%,' . $cityId . ',%"';
            $whereThree.= $where . ' and (pushType ="4" or ( pushType ="3" and pushCityId like "%,' . $cityId . ',%"))';
            $arrResC = M('PosterAdvert')->field($field)->where($whereThree)->order($orders)->select();
            $arrResCount = count($arrResC); //获取区域广告
            //echo M('PosterAdvert')->getLastSql();die;

            $resultArray = array();
            $arrRe = array();

            if ($page == 1) {
                $limit = 0 . "," . $pageSize; //分页条件
                $resultArray = M('PosterAdvert')->field($field)->where($whereFirst)->order($order)->limit($limit)->select();
                //var_dump($resultArray);die;
                $yu = $pageSize - count($resultArray);
                if ($yu) {
                    $limit = 0 . "," . $yu; //分页条件
                    $arrRets = M('PosterAdvert')->field($field)->where($whereSecond)->order($orders)->limit($limit)->select();

                    if ($arrRets) {//获取单个城市发布的广告
                        if (empty($resultArray)) {
                            $resultArray = $arrRets;
                        } else {
                            $resultArray = array_merge($resultArray, $arrRets);
                        }
                    }

                    $yu = $yu - count($arrRets);
                    if ($yu) {
                        $limit = 0 . "," . $yu; //分页条件
                        $arrRes = M('PosterAdvert')->field($field)->where($whereThree)->order($orders)->limit($limit)->select();
                        //echo M('PosterAdvert')->getLastSql();
                        if ($arrRes) {//获取区域城市发布的广告
                            if (empty($resultArray)) {
                                $resultArray = $arrRes;
                            } else {
                                $resultArray = array_merge($resultArray, $arrRes);
                            }
                        }
                    }
                }
                //var_dump($resultArray);die;
                return $resultArray;
            }


            if ($arrReCount - $limits > 0 && $arrReCount > 0) {//判断上一次请求的总量与精准数据的大小，确定区间
                $limit = $limits . "," . $pageSize; //分页条件
                $resultArray = M('PosterAdvert')->field($field)->where($whereFirst)->order($order)->limit($limit)->select();

                $yu = $pageSize - count($resultArray);
                if ($yu) {
                    $limit = 0 . "," . $yu; //分页条件
                    $arrRets = M('PosterAdvert')->field($field)->where($whereSecond)->order($orders)->limit($limit)->select();
                    if ($arrRets) {//获取单个城市发布的广告
                        if (empty($resultArray)) {
                            $resultArray = $arrRets;
                        } else {
                            $resultArray = array_merge($resultArray, $arrRets);
                        }
                    }

                    $yu = $yu - count($arrRets);
                    if ($yu) {
                        $limit = 0 . "," . $yu; //分页条件
                        $arrRes = M('PosterAdvert')->field($field)->where($whereThree)->order($orders)->limit($limit)->select();
                        if ($arrRes) {//获取区域城市发布的广告
                            if (empty($resultArray)) {
                                $resultArray = $arrRes;
                            } else {
                                $resultArray = array_merge($resultArray, $arrRes);
                            }
                        }
                    }
                }
                return $resultArray;
            }

            if ($arrRetsCount + $arrReCount - $limits > 0 && $arrRetsCount > 0) {
                $limit = abs($limits - $arrReCount) . "," . $pageSize; //分页条件
                $resultArray = M('PosterAdvert')->field($field)->where($whereSecond)->order($orders)->limit($limit)->select();
                //echo M('PosterAdvert')->getLastSql();

                $yu = $pageSize - count($resultArray);
                if ($yu) {
                    $limit = 0 . "," . $yu; //分页条件
                    $arrRes = M('PosterAdvert')->field($field)->where($whereThree)->order($orders)->limit($limit)->select();
                    //echo M('PosterAdvert')->getLastSql();die;
                    if ($arrRes) {//获取区域城市发布的广告
                        if (empty($resultArray)) {
                            $resultArray = $arrRes;
                        } else {
                            $resultArray = array_merge($resultArray, $arrRes);
                        }
                    }
                }
                return $resultArray;
            }

            if ($arrResCount > 0) {
                $limit = abs($limits - $arrRetsCount - $arrReCount) . "," . $pageSize; //分页条件
                $resultArray = M('PosterAdvert')->field($field)->where($whereThree)->order($orders)->limit($limit)->select();
                //echo M('PosterAdvert')->getLastSql();
                return $resultArray;
            }
        } else {//只取全国数据
            $limit = $limits . "," . $pageSize; //分页条件
            $wheres.= $where . ' and pushType ="4"';
            $resultArray = M('PosterAdvert')->field($field)->where($wheres)->order('id desc')->limit($limit)->select();
        }

        return $resultArray;
    }

    /*
     * 获取广告热度排行
     */

    public function getHeatList($selectTime, $page, $pageSize, $field, $cityId, $myLng, $myLat) {
        $nowTime = time();
        $selectTime = $selectTime <= 0 ? $nowTime : $selectTime;

        $page = empty($page) ? 1 : $page;
        $limits = ($page - 1) * $pageSize; //上一次请求的总量
        //$limit = $limits . "," . $pageSize;//分页条件

        $where = ' startTime <' . $selectTime;
        $where.=' and endTime >' . $selectTime; //是否在结束时间内
        //$where.=' and is_above_display ="1"'; //展示类型
        $where.=' and status =1 '; //广告状态
        $where.=' and integral >exposeTotalIntegral + extendTotalIntegral';
        $where .= ' and (type="1" or type="2")';
        
        $field = 'id,title,collectTotal,userId,is_above_display,shareUrl,addTime,is_above_display,collectTotal,warnPhone,imageUrl,webUrl';

        //排序方式
        $orders = 'forwardTotal*' . C("POSTER_FORWARDTOTAL_BASIC") . '+ shareTotal*' . C("POSTER_SHARETOTAL_BASIC") . '+clickTotal*' . C("POSTER_CLICKTOTAL_BASIC") .
                '+exposeTotal*' . C("POSTER_EXPOSETOTAL_BASIC") . '+extendTotal*' . C("POSTER_EXTENDTOTAL_BASIC") . '  desc';
        if ($cityId && $myLng && $myLat) {//判断参数是否为空
            //读取经度转化为距离的系数
            $MAP_LNG_BASIC = C("MAP_LNG_BASIC");

            //读取维度转化为距离的系数
            $MAP_LAT_BASICC = C("MAP_LAT_BASIC");
            $order = " ABS(lng-{$myLng})/{$MAP_LNG_BASIC} + ABS(lat-{$myLat})/{$MAP_LAT_BASICC}  asc";

            $whereFirst.= $where . ' and pushType ="1" and pushCityId =",' . $cityId . '," and lngMin <' . $myLng . ' and lngMax >' . $myLng . ' and latMin <' . $myLat . ' and latMax>' . $myLat;
            $arrReC = M('PosterAdvert')->field($field)->where($whereFirst)->order($order)->select();
            $arrReCount = count($arrReC); //统计精准发布的广告数量

            $whereSecond.= $where . ' and pushType ="2" and pushCityId =",' . $cityId . ',"';
            $arrRetsC = M('PosterAdvert')->field($field)->where($whereSecond)->order($orders)->select();
            $arrRetsCount = count($arrRetsC); //统计单个城市发布的广告
            //$whereThree.= $where . ' and pushType in ("3","4") and pushCityId like "%,' . $cityId . ',%"';
            $whereThree.= $where . ' and (pushType ="4" or ( pushType ="3" and pushCityId like "%,' . $cityId . ',%"))';
            $arrResC = M('PosterAdvert')->field($field)->where($whereThree)->order($orders)->select();
            $arrResCount = count($arrResC); //获取区域广告
            //echo M('PosterAdvert')->getLastSql();die;

            $resultArray = array();
            $arrRe = array();

            if ($page == 1) {
                $limit = 0 . "," . $pageSize; //分页条件
                $resultArray = M('PosterAdvert')->field($field)->where($whereFirst)->order($order)->limit($limit)->select();

                $yu = $pageSize - count($resultArray);
                if ($yu) {
                    $limit = 0 . "," . $yu; //分页条件
                    $arrRets = M('PosterAdvert')->field($field)->where($whereSecond)->order($orders)->limit($limit)->select();

                    if ($arrRets) {//获取单个城市发布的广告
                        if (empty($resultArray)) {
                            $resultArray = $arrRets;
                        } else {
                            $resultArray = array_merge($resultArray, $arrRets);
                        }
                    }

                    $yu = $yu - count($arrRets);
                    if ($yu) {
                        $limit = 0 . "," . $yu; //分页条件
                        $arrRes = M('PosterAdvert')->field($field)->where($whereThree)->order($orders)->limit($limit)->select();
                        //echo M('PosterAdvert')->getLastSql();
                        if ($arrRes) {//获取区域城市发布的广告
                            if (empty($resultArray)) {
                                $resultArray = $arrRes;
                            } else {
                                $resultArray = array_merge($resultArray, $arrRes);
                            }
                        }
                    }
                }
                return $resultArray;
            }


            if ($arrReCount - $limits > 0 && $arrReCount > 0) {//判断上一次请求的总量与精准数据的大小，确定区间
                $limit = $limits . "," . $pageSize; //分页条件
                $resultArray = M('PosterAdvert')->field($field)->where($whereFirst)->order($order)->limit($limit)->select();

                $yu = $pageSize - count($resultArray);
                if ($yu) {
                    $limit = 0 . "," . $yu; //分页条件
                    $arrRets = M('PosterAdvert')->field($field)->where($whereSecond)->order($orders)->limit($limit)->select();
                    if ($arrRets) {//获取单个城市发布的广告
                        if (empty($resultArray)) {
                            $resultArray = $arrRets;
                        } else {
                            $resultArray = array_merge($resultArray, $arrRets);
                        }
                    }

                    $yu = $yu - count($arrRets);
                    if ($yu) {
                        $limit = 0 . "," . $yu; //分页条件
                        $arrRes = M('PosterAdvert')->field($field)->where($whereThree)->order($orders)->limit($limit)->select();
                        if ($arrRes) {//获取区域城市发布的广告
                            if (empty($resultArray)) {
                                $resultArray = $arrRes;
                            } else {
                                $resultArray = array_merge($resultArray, $arrRes);
                            }
                        }
                    }
                }
                return $resultArray;
            }

            if ($arrRetsCount + $arrReCount - $limits > 0 && $arrRetsCount > 0) {
                $limit = abs($limits - $arrReCount) . "," . $pageSize; //分页条件
                $resultArray = M('PosterAdvert')->field($field)->where($whereSecond)->order($orders)->limit($limit)->select();
                //echo M('PosterAdvert')->getLastSql();

                $yu = $pageSize - count($resultArray);
                if ($yu) {
                    $limit = 0 . "," . $yu; //分页条件
                    $arrRes = M('PosterAdvert')->field($field)->where($whereThree)->order($orders)->limit($limit)->select();
                    //echo M('PosterAdvert')->getLastSql();die;
                    if ($arrRes) {//获取区域城市发布的广告
                        if (empty($resultArray)) {
                            $resultArray = $arrRes;
                        } else {
                            $resultArray = array_merge($resultArray, $arrRes);
                        }
                    }
                }
                return $resultArray;
            }

            if ($arrResCount > 0) {
                $limit = abs($limits - $arrRetsCount - $arrReCount) . "," . $pageSize; //分页条件
                $resultArray = M('PosterAdvert')->field($field)->where($whereThree)->order($orders)->limit($limit)->select();
                //echo M('PosterAdvert')->getLastSql();
                return $resultArray;
            }
        } else {//只取全国数据
            $limit = $limits . "," . $pageSize; //分页条件
            $wheres.= $where . ' and pushType ="4"';
            $resultArray = M('PosterAdvert')->field($field)->where($wheres)->order('id desc')->limit($limit)->select();
        }

        return $resultArray;
    }

    /*
     * 获取我朋友发布的广告
     */

    public function AttenitionList($userId, $selectTime, $page, $pageSize, $field, $cityId, $myLng, $myLat) {
        $nowTime = time();
        $selectTime = $selectTime <= 0 ? $nowTime : $selectTime;

        $page = empty($page) ? 1 : $page;
        $limits = ($page - 1) * $pageSize; //上一次请求的总量
        //$limit = $limits . "," . $pageSize;//分页条件

        $join = ' AS pt LEFT JOIN lu_poster_advert AS pa ON pt.fuid=pa.userId';

        $where = ' pa.startTime <' . $selectTime;
        $where.=' and pa.endTime >' . $selectTime; //是否在结束时间内
        //$where.=' and pa.is_above_display ="1"'; //展示类型
        $where.=' and pa.status =1 '; //广告状态
        $where.=' and pa.integral >pa.exposeTotalIntegral + pa.extendTotalIntegral';
        $where.=' and pt.fuid not in (select id from lu_friend_shield where uid=' . $userId . ' and status ="1")';
        $where.=' and pt.uid =' . $userId;
        $where.=' and pt.status ="1"';
        $where .= ' and (pa.type="1" or pa.type="2")';
        
        $field = 'pa.id,pa.title,pa.collectTotal,pa.userId,pa.is_above_display,pa.shareUrl,pa.addTime,pa.is_above_display,pa.collectTotal,pa.warnPhone,pa.imageUrl,pa.webUrl';

        //排序方式
        $orders = 'pa.startTime desc';

        if ($cityId && $myLng && $myLat) {//判断参数是否为空
            //读取经度转化为距离的系数
            $MAP_LNG_BASIC = C("MAP_LNG_BASIC");

            //读取维度转化为距离的系数
            $MAP_LAT_BASICC = C("MAP_LAT_BASIC");
            $order = " ABS(lng-{$myLng})/{$MAP_LNG_BASIC} + ABS(lat-{$myLat})/{$MAP_LAT_BASICC}  asc";

            //$whereFirst.= $where . ' and pa.pushType ="1" and pa.pushCityId =",' . $cityId . '," and pa.lngMin <' . $myLng . ' and pa.lngMax >' . $myLng . ' and pa.latMin <' . $myLat . ' and pa.latMax>' . $myLat;
            $whereFirst.= $where . ' and pa.pushType ="1" and pa.lngMin <' . $myLng . ' and pa.lngMax >' . $myLng . ' and pa.latMin <' . $myLat . ' and pa.latMax>' . $myLat;
            $arrReC = M('Friend')->field($field)->where($whereFirst)->order($order)->join($join)->select();
            $arrReCount = count($arrReC); //统计精准发布的广告数量
            //echo M('Friend')->getLastSql();die;

            $whereSecond.= $where . ' and pa.pushType ="2" and pa.pushCityId =",' . $cityId . ',"';
            $arrRetsC = M('Friend')->field($field)->where($whereSecond)->order($orders)->join($join)->select();
            $arrRetsCount = count($arrRetsC); //统计单个城市发布的广告
            //echo M('Friend')->getLastSql();
            //$whereThree.= $where . ' and pa.pushType in ("3","4") and pa.pushCityId like "%,' . $cityId . ',%"';
            //$whereThree.= $where . ' and pa.pushType in ("3","4") and pa.pushCityId like "%,' . $cityId . ',%"';
            $whereThree.= $where . ' and (pa.pushType ="4" or ( pa.pushType ="3" and pa.pushCityId like "%,' . $cityId . ',%"))';
            $arrResC = M('Friend')->field($field)->where($whereThree)->order($orders)->join($join)->select();
            $arrResCount = count($arrResC); //获取区域广告
            //echo M('Friend')->getLastSql();die;

            $resultArray = array();
            $arrRe = array();

            if ($page == 1) {
                $limit = 0 . "," . $pageSize; //分页条件
                $resultArray = M('Friend')->field($field)->where($whereFirst)->order($order)->join($join)->limit($limit)->select();

                $yu = $pageSize - count($resultArray);
                if ($yu) {
                    $limit = 0 . "," . $yu; //分页条件
                    $arrRets = M('Friend')->field($field)->where($whereSecond)->order($orders)->join($join)->limit($limit)->select();

                    if ($arrRets) {//获取单个城市发布的广告
                        if (empty($resultArray)) {
                            $resultArray = $arrRets;
                        } else {
                            $resultArray = array_merge($resultArray, $arrRets);
                        }
                    }

                    $yu = $yu - count($arrRets);
                    if ($yu) {
                        $limit = 0 . "," . $yu; //分页条件
                        $arrRes = M('Friend')->field($field)->where($whereThree)->order($orders)->join($join)->limit($limit)->select();
                        //echo M('PosterAdvert')->getLastSql();
                        if ($arrRes) {//获取区域城市发布的广告
                            if (empty($resultArray)) {
                                $resultArray = $arrRes;
                            } else {
                                $resultArray = array_merge($resultArray, $arrRes);
                            }
                        }
                    }
                }
                return $resultArray;
            }


            if ($arrReCount - $limits > 0 && $arrReCount > 0) {//判断上一次请求的总量与精准数据的大小，确定区间
                $limit = $limits . "," . $pageSize; //分页条件
                $resultArray = M('Friend')->field($field)->where($whereFirst)->order($order)->join($join)->limit($limit)->select();

                $yu = $pageSize - count($resultArray);
                if ($yu) {
                    $limit = 0 . "," . $yu; //分页条件
                    $arrRets = M('Friend')->field($field)->where($whereSecond)->order($orders)->join($join)->limit($limit)->select();
                    if ($arrRets) {//获取单个城市发布的广告
                        if (empty($resultArray)) {
                            $resultArray = $arrRets;
                        } else {
                            $resultArray = array_merge($resultArray, $arrRets);
                        }
                    }

                    $yu = $yu - count($arrRets);
                    if ($yu) {
                        $limit = 0 . "," . $yu; //分页条件
                        $arrRes = M('Friend')->field($field)->where($whereThree)->order($orders)->join($join)->limit($limit)->select();
                        if ($arrRes) {//获取区域城市发布的广告
                            if (empty($resultArray)) {
                                $resultArray = $arrRes;
                            } else {
                                $resultArray = array_merge($resultArray, $arrRes);
                            }
                        }
                    }
                }
                return $resultArray;
            }

            if ($arrRetsCount + $arrReCount - $limits > 0 && $arrRetsCount > 0) {
                $limit = abs($limits - $arrReCount) . "," . $pageSize; //分页条件
                $resultArray = M('Friend')->field($field)->where($whereSecond)->order($orders)->join($join)->limit($limit)->select();
                //echo M('PosterAdvert')->getLastSql();

                $yu = $pageSize - count($resultArray);
                if ($yu) {
                    $limit = 0 . "," . $yu; //分页条件
                    $arrRes = M('Friend')->field($field)->where($whereThree)->order($orders)->join($join)->limit($limit)->select();
                    //echo M('PosterAdvert')->getLastSql();die;
                    if ($arrRes) {//获取区域城市发布的广告
                        if (empty($resultArray)) {
                            $resultArray = $arrRes;
                        } else {
                            $resultArray = array_merge($resultArray, $arrRes);
                        }
                    }
                }
                return $resultArray;
            }

            if ($arrResCount > 0) {
                $limit = abs($limits - $arrRetsCount - $arrReCount) . "," . $pageSize; //分页条件
                $resultArray = M('Friend')->field($field)->where($whereThree)->order($orders)->join($join)->limit($limit)->select();
                //echo M('PosterAdvert')->getLastSql();
                return $resultArray;
            }
        } else {//只取全国数据
            $limit = $limits . "," . $pageSize; //分页条件
            $where = $where . ' and pa.pushType ="4"';
            $resultArray = M('Friend')->field($field)->where($where)->order('id desc')->join($join)->limit($limit)->select();
            //echo M('Friend')->getLastSql();die;
            
        }
        return $resultArray;
    }

    /*
     * 获取距离内的广告
     */

    public function rangeList($selectTime, $page, $pageSize, $field, $cityId, $myLng, $myLat) {

        $nowTime = time();
        $selectTime = $selectTime <= 0 ? $nowTime : $selectTime;

        $page = empty($page) ? 1 : $page;
        $limits = ($page - 1) * $pageSize; //上一次请求的总量
        //$limit = $limits . "," . $pageSize;//分页条件

        $where.=' startTime <' . $selectTime;
        $where.=' and endTime >' . $selectTime; //是否在结束时间内
        //$where.=' and is_above_display ="1"'; //展示类型
        $where.=' and status =1 '; //广告状态
        $where.=' and integral >exposeTotalIntegral + extendTotalIntegral';
        $where .= ' and (type="1" or type="2")';
        
        $field = 'id,title,collectTotal,userId,is_above_display,addTime,shareUrl,is_above_display,collectTotal,warnPhone,imageUrl,webUrl';

        //排序方式
        $orders = 'id desc';

        if ($cityId && $myLng && $myLat) {//判断参数是否为空
            //读取经度转化为距离的系数
            $MAP_LNG_BASIC = C("MAP_LNG_BASIC");

            //读取维度转化为距离的系数
            $MAP_LAT_BASICC = C("MAP_LAT_BASIC");

            $flag = 0.5; //5表示10000米
            $lngMax = $myLng + $MAP_LNG_BASIC * $flag;
            $latMax = $myLat + $MAP_LAT_BASICC * $flag;
            $lngMin = $myLng - $MAP_LNG_BASIC * $flag;
            $latMin = $myLat - $MAP_LAT_BASICC * $flag;

            $order = " ABS(lng-{$myLng})/{$MAP_LNG_BASIC} + ABS(lat-{$myLat})/{$MAP_LAT_BASICC}  asc";
            //echo $myLng.'-'.$myLat;echo '<br />';
            //echo $lngMax.'-'.$lngMin.'-'.$latMin.'-'.$latMax;
            //统计精准发布的广告数量
            //$whereFirst.= $where . ' and pushType ="1" and lngMin >' . $lngMin . ' and lngMax <' . $lngMax . ' and latMin >' . $latMin . ' and latMax <' . $latMax;
            $whereFirst.= $where . ' and pushType ="1" and lng >' . $lngMin . ' and lng <' . $lngMax . ' and lat >' . $latMin . ' and lat <' . $latMax;
            //$whereFirst.= $where . ' and pushType ="1" and pushCityId =",' . $cityId . '," and lngMin >' . $lngMin . ' and lngMax <' . $lngMax . ' and latMin >' . $latMin . ' and latMax <' . $latMax;
            $arrReC = M('PosterAdvert')->field($field)->where($whereFirst)->order($order)->select();
            //echo  M('PosterAdvert')->getLastSql();
            $arrReCount = count($arrReC);
            //
            //统计单个城市发布的广告
            $whereSecond.= $where . ' and pushType ="2" and pushCityId =",' . $cityId . ',"';
            $arrRetsC = M('PosterAdvert')->field($field)->where($whereSecond)->order($orders)->select();
            $arrRetsCount = count($arrRetsC);
            //echo M('PosterAdvert')->getLastSql();
            //获取区域广告
            //$whereThree.= $where . ' and pushType in ("3","4") and pushCityId like "%,' . $cityId . ',%"';
            $whereThree.= $where . ' and (pushType ="4" or ( pushType ="3" and pushCityId like "%,' . $cityId . ',%"))';
            $arrResC = M('PosterAdvert')->field($field)->where($whereThree)->order($orders)->select();
            $arrResCount = count($arrResC);
            //echo M('PosterAdvert')->getLastSql();die;

            $resultArray = array();

            if ($page == 1) {
                $limit = 0 . "," . $pageSize; //分页条件
                $resultArray = M('PosterAdvert')->field($field)->where($whereFirst)->order($order)->limit($limit)->select();
                //echo M('PosterAdvert')->getLastSql();

                $yu = $pageSize - count($resultArray);
                if ($yu) {
                    $limit = 0 . "," . $yu; //分页条件
                    $arrRets = M('PosterAdvert')->field($field)->where($whereSecond)->order($orders)->limit($limit)->select();
                    //echo M('PosterAdvert')->getLastSql();
                    if ($arrRets) {//获取单个城市发布的广告
                        if (empty($resultArray)) {
                            $resultArray = $arrRets;
                        } else {
                            $resultArray = array_merge($resultArray, $arrRets);
                        }
                    }

                    $yu = $yu - count($arrRets);
                    if ($yu) {
                        $limit = 0 . "," . $yu; //分页条件
                        $arrRes = M('PosterAdvert')->field($field)->where($whereThree)->order($orders)->limit($limit)->select();
                        //echo M('PosterAdvert')->getLastSql();
                        if ($arrRes) {//获取区域城市发布的广告
                            if (empty($resultArray)) {
                                $resultArray = $arrRes;
                            } else {
                                $resultArray = array_merge($resultArray, $arrRes);
                            }
                        }
                    }
                }
                return $resultArray;
            }


            if ($arrReCount - $limits > 0 && $arrReCount > 0) {//判断上一次请求的总量与精准数据的大小，确定区间
                $limit = $limits . "," . $pageSize; //分页条件
                $resultArray = M('PosterAdvert')->field($field)->where($whereFirst)->order($order)->limit($limit)->select();
                //echo M('PosterAdvert')->getLastSql();

                $yu = $pageSize - count($resultArray);
                if ($yu) {
                    $limit = 0 . "," . $yu; //分页条件
                    $arrRets = M('PosterAdvert')->field($field)->where($whereSecond)->order($orders)->limit($limit)->select();
                    //echo M('PosterAdvert')->getLastSql();
                    if ($arrRets) {//获取单个城市发布的广告
                        if (empty($resultArray)) {
                            $resultArray = $arrRets;
                        } else {
                            $resultArray = array_merge($resultArray, $arrRets);
                        }
                    }

                    $yu = $yu - count($arrRets);
                    if ($yu) {
                        $limit = 0 . "," . $yu; //分页条件
                        $arrRes = M('PosterAdvert')->field($field)->where($whereThree)->order($orders)->limit($limit)->select();
                        //echo M('PosterAdvert')->getLastSql();
                        if ($arrRes) {//获取区域城市发布的广告
                            if (empty($resultArray)) {
                                $resultArray = $arrRes;
                            } else {
                                $resultArray = array_merge($resultArray, $arrRes);
                            }
                        }
                    }
                }
                return $resultArray;
            }
            
            if ($arrRetsCount + $arrReCount - $limits > 0 && $arrRetsCount > 0) {
                $limit = abs($limits - $arrReCount) . "," . $pageSize; //分页条件
                $resultArray = M('PosterAdvert')->field($field)->where($whereSecond)->order($orders)->limit($limit)->select();
                //echo M('PosterAdvert')->getLastSql();

                $yu = $pageSize - count($resultArray);
                if ($yu) {
                    $limit = 0 . "," . $yu; //分页条件
                    $arrRes = M('PosterAdvert')->field($field)->where($whereThree)->order($orders)->limit($limit)->select();
                    //echo M('PosterAdvert')->getLastSql();die;
                    if ($arrRes) {//获取区域城市发布的广告
                        if (empty($resultArray)) {
                            $resultArray = $arrRes;
                        } else {
                            $resultArray = array_merge($resultArray, $arrRes);
                        }
                    }
                }
                return $resultArray;
            }

            if ($arrResCount > 0) {
                $limit = abs($limits - $arrRetsCount - $arrReCount) . "," . $pageSize; //分页条件
                $resultArray = M('PosterAdvert')->field($field)->where($whereThree)->order($orders)->limit($limit)->select();
                //echo M('PosterAdvert')->getLastSql();
                return $resultArray;
            }
        } else {//只取全国数据
            $limit = $limits . "," . $pageSize; //分页条件
            $wheres.= $where . ' and pushType ="4"';
            $resultArray = M('PosterAdvert')->field($field)->where($wheres)->order('id desc')->limit($limit)->select();
        }
        return $resultArray;
    }

}
