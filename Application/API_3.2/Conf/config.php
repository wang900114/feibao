<?php

return array(
    // 应用类库不再需要使用命名空间
    'APP_USE_NAMESPACE' => false,
    // 接口版本
    'APIVer' => '1.3',
    // 默认字符集
    'DEFAULT_CHARSET' => 'UTF-8',
    // SQL查询缓存生存时间
    'DATA_CACHE_LIVETIME' => '600',
    // TOKEN认证时间 字符串
    'TOKEN_ALL' => '20140805',
    //海报搜索的查询范围（单位米）
    'POSTERS_FOUND_DISTANCE' => '0', //搜索海报 的额外附加范围（加在海报投放访问里面）
    'POSTERS_PUBLIC_LIMIT' => '20', //公益海报查询限制
    'POSTERS_HOME_CATEGORY_LIMIT' => '500', //广告首页分类查询限制
    'POSTERS_LIST_DISTANCE' => '50000', //列表页 的查询范围上限（单位米）
    'POSTERS_LIST_DISTANCE_BY_SELECT' => '10000', //初始化 列表页 的查询中 范围上限（单位米）
    'POSTERS_SHARE_MIN_INTGRAL' => '0', //兑换飞币最小值
    'POSTERS_SHARE_MAX_INTGRAL' => '50', //兑换飞币最小值
    'POSTERS_SHARE_TIMES' => '10', //海报分享后兑换次数
    'FOUND_LIST_LIMIT' => '20', //列表页 的查询限制
    'LOCAL_NEWS_LIMIT' => '10', //发现-本地新闻-查询条数
    'FOUND_CAROUSEL_LIMIT' => '10', //发现-热图轮播图-查询条数
    //发现
    'FOUND_SEARCH_DISTANCE' => '30000', //搜索的查询范围（单位米）
    'FOUND_SEARCH_LIMIT' => '20', //搜索页 的查询限制
    'FOUND_MAP_DISTANCE' => '20000', //地图页 的查询范围上限（单位米）
    'FOUND_LIST_DISTANCE' => '50000', //列表页 的查询范围上限（单位米）
    'FOUND_LIST_DISTANCE_BY_SELECT' => '5000', //初始化 列表页 的查询中 范围上限（单位米）
    'FOUND_LIST_PRAISE' => '50', //搜索页 按赞数 的查询范围上限（单位个）
    'FOUND_LIST_PRAISE_BY_SELECT' => '50', //初始化 搜索页 按赞数 的查询中 范围上限（单位个）
    'FOUND_LIST_LIMIT' => '20', //列表页 的查询限制
    'LOCAL_NEWS_LIMIT' => '10', //发现-本地新闻-查询条数
    'FOUND_CAROUSEL_LIMIT' => '10', //发现-热图轮播图-查询条数
    
    //默认下载地址
    'DOWNLOAD_ADDRESS'=>'http://' . APP_HOST .'/index.php/Home/Invitation/index',
    
    
    //逛街
    'SHOP_LIST_DISTANCE' => '20000', //列表页 的查询范围上限（单位米）
    'SHOP_RECOMMEND_LIMIT' => '10', //人气店铺-查询条数
    'SHOP_POPULARITY_LIMIT' => '10', //人气店铺-查询条数
    'SHOP_COMMON_RANGE' => '50000', //普通店铺-显示数据的 范围（单位米）
    // 数据获取的时候自动处理字段映射
    'READ_DATA_MAP' => true,
    //邀请人送飞币
    'INVITE_PEOPLE_POINTS_NUM' => 1250,
    //新会员邀请送飞币
    'INVITE_MEMBER_POINTS_NUM' => 1250,
    //地图X坐标基数 1000米范围系数  一千米=1000米=一公里
    'MAP_LNG_BASIC' => 0.0103318,
    //地图Y坐标基数 1000米范围系数
    'MAP_LAT_BASIC' => 0.0089932,
    
    //广告快到期前的时间配置
    'END_BEFORE_BASIC' => 2,
    
    //广告热度比例设置-转发、分享、点击、获取红包、推广
    'POSTER_FORWARDTOTAL_BASIC' => 0.2,
    'POSTER_SHARETOTAL_BASIC' => 0.2,
    'POSTER_CLICKTOTAL_BASIC' => 0.2,
    'POSTER_EXPOSETOTAL_BASIC' => 0.2,
    'POSTER_EXTENDTOTAL_BASIC' => 0.2,
    
    //关注人数最低限制
    'ATTENTION_NUMBER_BASIC' => 50,
    //关注最低频次限制
    'ATTENTION_LOWTIMES_BASIC' => 50,
    
    // 新闻置顶条数
    'top_news_limit' => 5,
    /* 图片上传相关配置 */
    'PICTURE_UPLOAD' => array(
        'mimes' => '', //允许上传的文件MiMe类型
        'maxSize' => 5 * 1024 * 1024, //上传的文件大小限制 (0-不做限制)
        'exts' => 'jpg,gif,png,jpeg', //允许上传的文件后缀
        'autoSub' => true, //自动子目录保存文件
        'subName' => array('date', 'Y-m-d'), //子目录创建方式，[0]-函数名，[1]-参数，多个参数使用数组
        'rootPath' => '/home/wwwroot/' . MP_PACH . '/Uploads/Picture/', //保存根路径
        'savePath' => '', //保存路径
        'saveName' => array('uniqid', ''), //上传文件命名规则，[0]-函数名，[1]-参数，多个参数使用数组
        'saveExt' => '', //文件保存后缀，空则使用原后缀
        'replace' => false, //存在同名是否覆盖
        'hash' => true, //是否生成hash编码
        'callback' => false, //检测文件是否存在回调函数，如果存在返回文件信息数组
    ), //图片上传相关配置（文件上传类配置）
    'PICTURE_UPLOAD_DRIVER' => 'local',
    //本地上传文件驱动配置
    'UPLOAD_LOCAL_CONFIG' => array(),
);
