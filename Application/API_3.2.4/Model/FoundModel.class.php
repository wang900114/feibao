<?php

use Think\Model;

// 发现模型
class FoundModel extends CommonModel {

    public $arrInterval = array(0, 0.2, 0.5, 1, 3, 10);
    /* 自动验证规则 */
    public $_validate = array(
            //array('imei','require','IMEI Required', self::EXISTS_VALIDATE ),
            //array('cityId','require','City ID Required', self::EXISTS_VALIDATE ),
            //array('provinceId','require','Province ID Required', self::EXISTS_VALIDATE ),
            // array('name','require','name必须！'),
    );

    /* 自动完成规则 */
    public $_auto = array(
            // array('image', 'Public/Images/member.gif', self::MODEL_INSERT),
            // array('addTime', 'time', self::MODEL_INSERT,'function'),
    );

    /**
     * 发现详情内容页
     * @param int $id 发现ID
     * @param int $userId 当前查看人ID
     * @return array
     * @author xiaofeng<yuanmingwei@feibaokeji.com>
     */
    public function detailById($id, $userId) {
        $fields = 'id,content,userId,time,lng,lat,htmlPath,sharePath,address_baidu AS address,sharePath,treadNum as stampnumber,praiseNum as praiseTimes';
        $id = decodePass($id); //解密ID
        $selectArray = array(
            'id' => $id,
            'del' => '1'
        );
        $return = array();
        $result = $this->selData($selectArray, 1, $fields);
        if ($result) {
            $return = $result[0];
            $return['id'] = encodePass($return['id']); //加密ID
            $return['content'] = strip_tags(htmlspecialchars_decode($return['content']), '<p>');
            $return['address'] = str_replace('位置：', '', $return['address']);
            //访问量+1
            $PublicOb = A('Public');
            $PublicOb->setAccessCount($id, $userId, 1);
            //图片总数
            $mapP['dataId'] = $id;
            $images = D('PictureFound')->selData($mapP, '', 'image,thumbUrl');
            $return['total'] = count($images);
            $return['images'] = empty($images) ? array() : $images;
            $mapP['userId'] = $userId;
            //是否赞
            $rePraise = D('PraiseFoundLog')->selData($mapP, 1, 'id');
            $return['isPraise'] = empty($rePraise) ? '0' : '1';
            //是否踩
            $reStampflag = D('TreadFoundLog')->selData($mapP, 1, 'id');
            $return['stampflag'] = empty($reStampflag) ? '0' : '1';

            //发布会员信息
            $reMembers = D('Members')->selData(array('id' => $result[0]['userId']), 1, ' name,image');
            $return['nickname'] = $reMembers[0]['name'];
            $return['userimage'] = $reMembers[0]['image'];

            //是否收藏
            $reCollect = D('CollectFoundLog')->selData($mapP, 1, 'id');
            $return['collectflag'] = empty($reCollect) ? '0' : '1';

            //评论的数量
            $mapC['dataId'] = $id;
            $return['commentNum'] = D('CommentsFound')->where('status="1"')->getNum($mapC);
            $return['isSelf'] = $return['userId'] == $userId ? 1 : 0;
            $return['userId'] = encodePass($return['userId']);
            //评论列表
            //$return['infolist'] = $this->getComment($id);
        }
        return $return;
    }

    /**
     *  以城市 获取。del=1 以发布时间倒序 
     * @param int $userId  用户Id
     * @param 分页条数 $pageSize 
     * @param float $myLng 当前X坐标
     * @param float $myLat 当前Y 坐标
     * @param int $page 页数
     * @return array
     * @author xiaofeng <yuanmingwei@feibaokeji.com>
     */
    public function getMyCollectList($userId, $myLng, $myLat, $page, $pageSize = 10) {
        $result = array();
        $dataIdStr = '';
        $map = array(
            'c.userId' => $userId,
            'c.status' => '1',
            'f.del' => '1'
        );
        $join = " as c LEFT JOIN __FOUND__ AS f ON f.id=c.dataId ";
        $field = 'c.dataId,f.id,f.content,f.userId as uid,f.treadNum AS stampnumber,f.praiseNum AS praisenumuber,f.time,f.commentNum AS commentnumuber,f.sharePath AS sharehtml ,f.lng,f.lat ,f.hotflag';
        $limit = ($page - 1) * $pageSize . "," . $pageSize;
        $result = M("collect_found_log")->field($field)->where($map)->limit($limit)->join($join)->order("c.id desc")->select();
        //echo M("collect_found_log")->getLastSql();
        if ($result) {
            $mfield = 'name AS nickname,image AS userImage';
            foreach ($result as $key => $value) {
                unset($result[$key]['dataId']);
                $where['id'] = $value['uid'];
                $memberData = M("members")->where($where)->field($mfield)->find();
                $result[$key]['nickname'] = $memberData['nickname'];
                $result[$key]['userId'] = $value['uid'];
                $result[$key]['userImage'] = $memberData['userImage'];
                unset($result[$key]['uid']);
            }
            $result = getRelations($result, $userId, $myLng, $myLat);
        }
        return $result;
    }

    /**
     * 发现评论列表 
     * @param int $id 发现ID
     * @return  array
     */
    function getComment($id) {
        $fields = 'c.id ,m.name,m.image AS head,m.id AS userId,c.content,c.time';
        $sql = 'SELECT ' . $fields . ' FROM `lu_comments_found` AS c LEFT JOIN `lu_members` AS m ON c.userid=m.id WHERE c.dataId=' . $id . " ORDER BY c.time DESC";
        $result = $this->query($sql);
        return $result;
    }

    /**
     *  以城市 获取。del=1 以发布时间倒序  
     * @param int $userId 当前人Id
     * @param int $cityId 城市ID
     * @param float $lng 当前X坐标
     * @param float $lat 当前Y 坐标
     * @param int $selectTime 第一次请求接口时间
     * @param int $page  第几页
     * @param int $pageSize  每页条数
     * @author xiaofeng <yuanmingwei@feibaokeji.com>
     */
    public function getListNewData($userId, $cityId, $lng, $lat, $selectTime, $page = 1, $pageSize = 10) {
        $result = array();
        $field = 'f.id,f.content,f.userId,f.treadNum AS stampnumber,f.praiseNum AS praisenumuber,f.time,f.commentNum AS commentnumuber,f.sharePath AS sharehtml ,f.lng,f.lat ,m.name AS nickname,m.image AS userImage,f.hotflag';
        $join = " AS f LEFT JOIN __MEMBERS__ AS m ON f.userId = m.id";
        $fieldPic = 'dataId,image,thumbUrl';
        $selectTime = $selectTime <= 0 ? time() : $selectTime;
        $topData = array();
        //查询条件组合
        $map = array(
            'f.cityId|f.cityId' => array($cityId, 10000, '_multi' => true), //取当前城市和全国的数据
            'del' => '1',
            'time' => array('elt', $selectTime)
        );
        //取置顶数据
        $topSelect = array(
            'topflag' => '1',
            'del' => '1'
        );
        $topData = $this->selData($topSelect, 2, $field, " f.topTime DESC", $join);
        $topData = getRelations($topData, $userId, $lng, $lat);
        if ($topData) {
            foreach ($topData as $k => $v) {
                $topData[$k]['topflag'] = '1';
                $topStrId .= decodePass($v['id']) . ",";
            }
            $topStrId = substr($topStrId, 0, -1);
            //过渡置顶ID
            $map['f.id'] = array('not in', $topStrId);
        }
        if ($page == 1 && $topData) {
            $pageSizeNum = $pageSize - count($topData);
            $limit = ($page - 1) * $pageSize . "," . $pageSizeNum;
        } else {
            $limit = ($page - 1) * $pageSize . "," . $pageSize;
        }
        $result = $this->selData($map, $limit, $field, "f.time DESC", $join);
        //echo $this->getLastSql();
        if ($result) {
            $result = getRelations($result, $userId, $lng, $lat);
        }
        //处理置顶与查询数据合并
        if ($page == 1 && $topData) {
            if ($result) {
                foreach ($topData as $k => $v) {
                    array_unshift($result, $topData[$k]);
                }
            } else {
                $result = $topData;
            }
        }
        return $result;
    }

    /**
     *  以城市 获取。del=1 以发布距离排序 
     * @param int $userId 当前人Id
     * @param int $cityId 城市ID
     * @param float $lng 当前X坐标
     * @param float $lat 当前Y 坐标
     * @param int $selectTime 第一次请求接口时间
     * @param int $page 分页
     * @param type $pageSize 每次取值数量
     * @return array
     * @author xiaofeng <yuanmingwei@feibaokeji.com>
     */
    public function getNearListData($userId, $cityId, $lng, $lat, $selectTime, $page = 1, $pageSize = 10) {

        $page = $page <= 0 ? 1 : $page;
        $limit = ($page - 1) * $pageSize . ' , ' . $pageSize;
        $selectTime = $selectTime <= 0 ? time() : $selectTime;
        $result = array();
        $topData = array();
        $field = 'f.id,f.content,f.userId,f.treadNum AS stampnumber,f.praiseNum AS praisenumuber,f.time,f.commentNum AS commentnumuber,f.sharePath AS sharehtml ,f.lng,f.lat,m.name AS nickname,m.image AS userImage,f.hotflag';
        $fieldBackstage = 'f.id,f.content,f.userId,f.backstage,f.treadNum AS stampnumber,f.praiseNum AS praisenumuber,f.time,f.sharePath AS sharehtml ,f.lng,f.lat,m.name AS nickname,m.image AS userImage,f.hotflag';
        $join = " AS f LEFT JOIN __MEMBERS__ AS m ON f.userId = m.id";
        $fieldPic = 'dataId,image,thumbUrl';
        $map['del'] = '1';
        $map['time'] = array(
            'elt', $selectTime
        );
        //当前城市Id时数据
        if ($cityId) {
            $map['cityId'] = $cityId;
            $map['lng'] = array("neq", '0');
            //$field .= ', distance(f.lng,f.lat,' . $lng . ',' . $lat . ') as distance';
            //根据系数据计算坐标上、下限
            $map['lng'] = array('between', array($lng - C('MAP_LNG_BASIC'), $lng + C('MAP_LNG_BASIC')));
            $map['lat'] = array('between', array($lat - C('MAP_LAT_BASIC'), $lat + C('MAP_LAT_BASIC')));
            $MAP_LNG_BASIC = C("MAP_LNG_BASIC");
            $MAP_LAT_BASICC = C("MAP_LAT_BASIC");
            $order = " ABS(f.lng-{$lng})/{$MAP_LNG_BASIC} + ABS(f.lat-{$lat})/{$MAP_LAT_BASICC}  asc";
            $total = $this->getNum($map);
            //当前城市Id没数据时走后台数据
            if (empty($total)) {
                unset($map['lat']);
                unset($map['lng']);
                unset($map['cityId']);
                $map['lng'] = 0;
                $map['backstage'] = '1';
                $order = " f.id desc";
            } else {
                $backstageMap['lng'] = 0;
                $backstageMap['del'] = '1';
                $backstageTotal = $this->getNum($backstageMap);
                $totalPage = ceil($total / $pageSize); //得到总页数
                $remainder = $total % $pageSize; //得到多于的条数
                $current = $page * $pageSize; //当前要取到最大条数 
                unset($map['cityId']);
                $map['f.cityId'] = $cityId;
            }
        } else {
            $map['lng'] = 0;
            $map['backstage'] = '1';
            $order = " f.id desc";
        }

        //取置顶数据
        $topSelect = array(
            'topflag' => '1',
            'del' => '1'
        );
        $topData = $this->selData($topSelect, 2, $field, " f.topTime DESC", $join);
        $topData = getRelations($topData, $userId, $lng, $lat);
        if ($topData) {
            foreach ($topData as $k => $v) {
                $topData[$k]['topflag'] = '1';
                $topStrId .= decodePass($v['id']) . ",";
            }
            $topStrId = substr($topStrId, 0, -1);
            //过滤置顶ID
            $map['f.id'] = array('not in', $topStrId);
        }
        if ($page == 1 && $topData) {
            $limit = ($page - 1) * $pageSize . ' , ' . ($pageSize - count($topData));
        }
        $result = $this->selData($map, $limit, $field, $order, $join);
        //echo $this->getLastSql();
        //如果要取的值大于数据库里的值则取后台发布的信息
        if ($current > $total) {
            $pageYu = $page - $totalPage;
            $pages = $pageYu > 0 ? $pageYu : 1;
            if ($pageYu == 0) {
                $pageSize = $pageSize - $remainder;
                $limit = ' 0, ' . $pageSize;
            } else {
                if ($pages > 1) {
                    $limit = ($pages - 1) * $pageSize + ($pageSize - $remainder) . ' , ' . $pageSize;
                } else {
                    $limit = $pageSize - $remainder . ' , ' . $pageSize;
                }
            }
            $backstageMap['backstage'] = '1';
            $backstageMap['lng'] = 0;
            $order = " f.id desc";
            $resultBackstage = $this->selData($backstageMap, $limit, $fieldBackstage, $order, $join);
            //echo $this->getLastSql();
            if ($result && $resultBackstage) {
                $result = array_merge($result, $resultBackstage);
            } else if ($resultBackstage) {
                $result = $resultBackstage;
            }
        }

        if ($result) {
            //查询关联信息
            $result = getRelations($result, $userId, $lng, $lat);
            //var_export($result);
            //查询后数组排序 ASC
            //$result = array_sort($result, 'distance');
        } //置顶数据处理

        if ($page == 1 && $topData) {
            if ($result) {
                foreach ($topData as $k => $v) {
                    array_unshift($result, $topData[$k]);
                }
            } else {
                $result = $topData;
            }
        }
        //$result['minId'] = $minId;
        return $result;
    }

    /**
     *  以城市 获取。del=1 以发布时间倒序 
     * @param type $userId 当前人Id
     * @param type $page 当前页数
     * @param type $pageSize 每次取值数量
     * @return array
     * @author 李丰瀚 <lifenghan@feibaokeji.com>
     */
    public function getMyListNewData($userId, $page = 1, $pageSize = 10) {
        $result = array();
        $page = $page < 1 ? 1 : $page;
        $field = 'f.id,f.content,f.treadNum AS stampnumber,f.praiseNum AS praisenumuber,f.time,f.commentNum AS commentnumuber,f.sharePath AS sharehtml ,f.lng,f.lat ,m.name AS nickname,m.image AS userImage,f.hotflag,f.uniqueMark';
        $join = " AS f LEFT JOIN __MEMBERS__ AS m ON f.userId = m.id and  f.userId=" . $userId;
        $fieldPic = 'dataId,image,thumbUrl';
        $map = array(
            'f.del' => '1',
            'f.userId' => $userId
        );
        $limit = ($page - 1) * $pageSize . "," . $pageSize;
        $result = $this->selData($map, $limit, $field, "f.time DESC", $join);
        //echo $this->getLastSql();
        if ($result) {
            $result = getRelations($result, $userId, 0, 0);
        }

        return $result;
    }

    /**
     * 发现热图数据处理
     * @param int $cityId
     * @param int $userID 会员ID
     * @return array
     */
    public function getTopListData($cityId, $userId) {
        $model = M('found_hotmap');
        $map['cityId'] = $cityId;
        $reList = $model->where('cityId=10000')->field("info")->find();
        $reListArray = unserialize($reList['info']);
        $listArray = array();
        if ($reListArray) {//判断后台热图是否为空
            $reList1 = $model->where($map)->field("info")->find();
            $reListCityArray = unserialize($reList1['info']);
            if ($reListCityArray) {
                $listArray = array_merge($reListArray, $reListCityArray);
            } else {
                $listArray = $reListArray;
            }
        } else {
            $reList1 = $model->where($map)->field("info")->find();
            $reListCityArray = unserialize($reList1['info']);
            if ($reListCityArray) {
                $listArray = $reListCityArray;
            }
        }
        $list = array();
        if ($listArray) {
            foreach ($listArray as $key => $value) {
                $listNewArray[$value['id']] = $value;
            }
            $i = 0;
            foreach ($listNewArray as $key => $value) {
                if ($i < 20) {
                    $list[$i] = $value;
                    $i++;
                }
            }
        }
        if ($list) {
            foreach ($list as $key => $value) {
                $data = D("Found")->where(array('id' => $value['id']))->field('praiseNum,treadNum,isImage,sharePath')->find();
                $list[$key]['praiseNum'] = $data['praiseNum'];
                $list[$key]['treadNum'] = $data['treadNum'];
                $list[$key]['isImage'] = $data['isImage'];
                $list[$key]['sharePath'] = $data['sharePath'];

                //查询会员信息并赋值
                $mapU['id'] = $value['userId'];
                $reUser = D('Members')->selData($mapU, 1, 'image');
                $list[$key]['userImage'] = $reUser[0]['image'];
                //查询发现图集图片数量并赋值
                $mapP['dataId'] = $value['id'];
                $images = D('PictureFound')->selData($mapP, '', 'id');
                $list[$key]['pictureCount'] = empty($images) ? 0 : count($images);

                if ($userId) {
                    /////////////////////////是否收藏/////////////////////////
                    $mapC = array();
                    $mapC['userId'] = $userId;
                    $mapC['dataId'] = $value['id'];
                    $reCollect = D('CollectFoundLog')->selData($mapC, 1, 'id');
                    $list[$key]['collectflag'] = empty($reCollect) ? '0' : '1';

                    ///////////////////是否赞///////////////////////
                    $mapA = array();
                    $mapA['userId'] = $userId;
                    $mapA['dataId'] = $value['id'];
                    $rePraise = D('PraiseFoundLog')->selData($mapA, 0, 'id');
                    $list[$key]['praiseflag'] = empty($rePraise) ? '0' : '1';

                    ////////////////////是否踩/////////////////////////
                    $mapB = array();
                    $mapB['userId'] = $userId;
                    $mapB['dataId'] = $value['id'];
                    $reTreads = M('tread_found_log')->where($mapB)->find();
                    $list[$key]['stampflag'] = empty($reTreads) ? '0' : '1';
                } else {
                    $list[$key]['praiseflag'] = 0;
                    $list[$key]['stampflag'] = 0;
                    $list[$key]['collectflag'] = 0;
                }
                $list[$key]['id'] = encodePass($list[$key]['id']);
                $list[$key]['userId'] = encodePass($list[$key]['userId']);
            }
        }
        return $list;
    }

    /**
     * 通过唯一标识查发现信息
     * @param string $unique 唯一标识
     * @return array
     */
    function getFoundByUniqueMar($unique) {
        $data = array('uniqueMark' => $unique);
        $uniqueMarkData = D('Found')->where($data)->find();
        return $uniqueMarkData;
    }

    /**
     * 通过ID获得发现信息
     * @param int $dataId 发现ID
     * @param string $fields 字段
     * @param string $del 1：正常，0:删除
     * @return array
     */
    function getFoundById($dataId, $fields = '*', $del = '1') {
        $where = array('id' => $dataId, "del" => $del);
        $result = $this->where($where)->field($fields)->find();
        return $result;
    }

    /**
     * 添加举报
     * @param int $userId 用户ID
     * @param int $dataId 数据ID
     * @param string $content 举报信息
     * @return type
     */
    function addAccusation($userId, $dataId, $content) {
        $data = array(
            'userId' => $userId,
            'dataId' => $dataId,
            'time' => time(),
            'type' => $content
        );

        $res = M('AccusationFound')->add($data);
        return $res;
    }

}
