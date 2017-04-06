<?php

/**
 * 店铺 - 数据接口
 * @author Miko <wangmeihui@andlisoft.com>
 */
class ShopController extends CommonController {

    /**
     * 初始化
     */
    public function _initialize() {
        parent::_initialize();

        //A('API_3.2/Public')->testPublicToken();//验证 公共 token
    }

    /**
     * 店铺接口 - 返回指定范围内所有的店铺列表
     * @param  string $token 令牌token:令牌【必填项】
     * @param  string $version:版本号(如“1.2”)
     * @param  string $lng:经度【必填项】
     * @param  string $lat：纬度【必填项】
     * @param  string $myLng: 物理地址经度(我的位置)
     * @param  string $myLat: 物理地址纬度(我的位置)
     * @param  string $range:范围，比如“100”，服务器自动添加单位“米”【必填项】
     * @param  string $type:类型：1加载，0刷新(默认值) 【必填项】
     * @param  string $pageSize：每次取数据量，比如500条【必填项】
     * @param  id: 店铺id（上一次返回最小店铺id）【必填项】
     * @return JSON 返回店铺列表的json字符串
     */
    public function mapList() {
        $return['success'] = true;

        //在Controller层判断数据的合法性，可以防止脏数据进入Model层（能保证Model层数据是干净的、可以直接用$_POST直接取）
        $lng = I('post.lng', '', 'trim,float2int');
        $lat = I('post.lat', '', 'trim,float2int');
        $type = I('post.type', '', 'trim');
        $range = I('post.range', '5000', 'trim');
        $token = I('post.token', '', 'trim');
        $pageSize = I('post.pageSize', '500', 'trim');
        $myLng = I('post.myLng', '', 'trim,float2int');
        $myLat = I('post.myLat', '', 'trim,float2int');

        if (is_empty($token) || is_empty($lng) || is_empty($lat) || is_empty($range) || is_empty($type) || is_empty($pageSize)) {
            $return['status'] = -888;
            $return['message'] = '传参不完整';
        } else {
            if ($range > C('SHOP_LIST_DISTANCE')) {//超过范围，直接终止查询
                $info = array();
            } else {
                $info = D('Shop')->getListData();
            }

            if (is_bool($info) && empty($info)) {
                $return['status'] = -1;
                $return['message'] = '查询失败';
            } else if ((is_array($info) || is_null($info)) && empty($info)) {
                $return['status'] = 0;
                $return['message'] = '没有数据了';
            } else {
                $return['status'] = 1;
                $return['message'] = '查询成功';

                if (!empty($info)) {
                    foreach ($info as $key => $val) {
                        if (!empty($myLng) && !empty($myLat)) {
                            $info[$key]['distance'] = GetDistance($myLng, $myLat, $val['lng'], $val['lat']);
                        } else {
                            $info[$key]['distance'] = 0;
                        }
                        $info[$key]['isShow'] = 0; //APP端用来判断是“展开”还是“收起”
                    }
                }

                $return['info'] = $info;
                $return['recommendStoreIds'] = D('Shop')->getRecommendStoreIds();
            }
        }

        echo jsonStr($return);exit();
    }

    /**
     * 店铺接口 - 返回所有的店铺列表
     * @param  string $token 令牌token:令牌【必填项】
     * @param  string $version:版本号(如“1.2”)
     * @param  string $lng:经度【必填项】
     * @param  string $lat：纬度【必填项】
     * @param  string $myLng: 物理地址经度(我的位置)
     * @param  string $myLat: 物理地址纬度(我的位置)
     * @param  string $range:范围，比如“100”，服务器自动添加单位“米”【必填项】
     * @param  string $type:类型：1加载，0刷新(默认值) 【必填项】
     * @param  string $pageSize：每次取数据量，比如500条【必填项】
     * @param  id: 店铺id（上一次返回最小店铺id）【必填项】
     * @param  string $cityId：城市ID【必填项】
     * @return JSON 返回店铺列表的json字符串
     */
    public function allStoresList() {
        $return['success'] = true;
        //在Controller层判断数据的合法性，可以防止脏数据进入Model层（能保证Model层数据是干净的、可以直接用$_POST直接取）
        $token = I('post.token', '', 'trim');
        $version = I('post.version', '', 'trim');
        $lng = I('post.lng', '', 'trim,float2int');
        $lat = I('post.lat', '', 'trim,float2int');
        $type = I('post.type', '', 'trim');
        $pageSize = I('post.pageSize', '500', 'trim');
        $cityId = I('post.cityId');
        $id = I('post.id');

        if (is_empty($token) || is_empty($lng) || is_empty($lat) || is_empty($type) || is_empty($pageSize) || is_empty($id) || is_empty($cityId)) {
            $return['status'] = -888;
            $return['message'] = '传参不完整';
        } else {
            if (empty($type)) {//刷新
                $recommendStores = D('Shop')->getRecommendStores(); //推荐店铺
                if (empty($recommendStores))
                    $recommendStores = array();
                $popularityStores = D('Shop')->getPopularityStores(); //人气店铺
                if (empty($popularityStores))
                    $popularityStores = array();
            }else {//加载,不需要获取人气店铺和推荐店铺
                $recommendStores = array();
                $popularityStores = array();
            }
            $commonStores = D('Shop')->getCommonStores();
            //echo D('Shop')->getLastSql();die;
            if (is_null($commonStores))
                $commonStores = array();

            if ($commonStores == -10) {//加载流程中的、id不存在的情况；中止即可
                $return['status'] = -10;
                $return['message'] = '数据不存在、或非法传参';
            } else {
                if (!empty($commonStores)) {
                    foreach ($commonStores as $key => $val) {
                        if (!empty($val['tag1']))
                            $commonStores[$key]['tag'][] = $val['tag1'];
                        if (!empty($val['tag2']))
                            $commonStores[$key]['tag'][] = $val['tag2'];
                        if (!empty($val['tag3']))
                            $commonStores[$key]['tag'][] = $val['tag3'];
                        if (!empty($val['tag4']))
                            $commonStores[$key]['tag'][] = $val['tag4'];

                        $commonStores[$key]['id'] = base64_encode($val['id']); //加密
                        unset($commonStores[$key]['tag1']);
                        unset($commonStores[$key]['tag2']);
                        unset($commonStores[$key]['tag3']);
                        unset($commonStores[$key]['tag4']);
                        unset($commonStores[$key]['distanceRange']);
                    }
                }

                if (!empty($recommendStores) || !empty($popularityStores) || !empty($commonStores)) {
                    $info = array(
                        'recommendStores' => $recommendStores ? $recommendStores : array(),
                        'popularityStores' => $popularityStores ? $popularityStores : array(),
                        'commonStores' => $commonStores ? $commonStores : array()
                    );
                    $return['status'] = 1;
                    $return['message'] = '查询成功';
                    $return['info'] = $info;
                } else if (is_bool($commonStores) && empty($commonStores)) {
                    $return['status'] = -1;
                    $return['message'] = '查询失败';
                } else {
                    // (is_array($info) || is_null($info)) && empty($info) 
                    $return['status'] = 0;
                    $return['message'] = '没有数据了';
                    $info = array(
                        'recommendStores' => array(),
                        'popularityStores' => array(), //popularityStores 
                        'commonStores' => array()
                    );
                    $return['info'] = $info;
                }
            }
        }
        echo jsonStr($return);exit();
    }

    /**
     * 店铺接口 - 店铺搜索
     * @param  string $token 令牌token:令牌【必填项】
     * @param  string $version:版本号(如“1.2”)
     * @param  string $lng:经度【必填项】
     * @param  string $lat：纬度【必填项】
     * @param  string $myLng: 物理地址经度(我的位置)
     * @param  string $myLat: 物理地址纬度(我的位置)
     * @param  string $type:类型：1加载，0刷新(默认值) 【必填项】
     * @param  string $id: 店铺id（上一次返回最小店铺id）【必填项】
     * @param  string $pageSize：每次取数据量，比如500条【必填项】
     * @param  string $search：搜索关键词【必填项】
     * @return JSON 返回店铺列表的json字符串
     */
    public function searchStores() {
        $return['success'] = true;
        //在Controller层判断数据的合法性，可以防止脏数据进入Model层（能保证Model层数据是干净的、可以直接用$_POST直接取）
        $lng = I('post.lng', '', 'trim,float2int'); //无用参数
        $lat = I('post.lat', '', 'trim,float2int'); //无用参数
        $myLng = I('post.myLng', '', 'trim');
        $myLat = I('post.myLat', '', 'trim');
        $type = I('post.type', '', 'trim');
        $pageSize = I('post.pageSize', '500', 'trim');
        $cityId = I('post.cityId');
        // $id    = I('post.id');
        $search = I('post.search', '', 'trim');

        //引入统计
        $countKeywords = trim($_POST['search']);
        if (!empty($countKeywords) || strlen($countKeywords) > 0) {
            $countOb = A('Count');
            $countOb->index($countKeywords, 3);
        }

        if (is_empty($cityId) || is_empty($type) || is_empty($pageSize) || is_empty($search)) {
            $return['status'] = -888;
            $return['message'] = '传参不完整';
        } else {
            $res = D('Shop')->getSearchStores();
            // echo D('Shop')->getLastSql();die;

            if ((is_array($res) || is_null($res)) && empty($res)) {
                $return['status'] = 0;
                $return['message'] = '没有数据了';
            } else if (is_bool($res) && empty($res)) {
                $return['status'] = -1;
                $return['message'] = '查询失败';
            } else {
                $return['status'] = 1;
                $return['message'] = '查询成功';
                if (!empty($res)) {
                    foreach ($res as $key => $val) {
                        // if( !empty($myLng) && !empty($myLat) ){
                        // $res[$key]['distance'] = GetDistance($myLng, $myLat, $val['lng'], $val['lat']);
                        // }else{
                        // $res[$key]['distance'] = '';
                        // }
                        if (!empty($val['tag1']))
                            $res[$key]['tag'][] = $val['tag1'];
                        if (!empty($val['tag2']))
                            $res[$key]['tag'][] = $val['tag2'];
                        if (!empty($val['tag3']))
                            $res[$key]['tag'][] = $val['tag3'];
                        if (!empty($val['tag4']))
                            $res[$key]['tag'][] = $val['tag4'];
                        $res[$key]['id'] = base64_encode($val['id']); //加密

                        unset($res[$key]['tag1']);
                        unset($res[$key]['tag2']);
                        unset($res[$key]['tag3']);
                        unset($res[$key]['tag4']);
                    }
                }
                $return['info'] = $res;
            }
        }

        echo jsonStr($return);exit();
    }

}
