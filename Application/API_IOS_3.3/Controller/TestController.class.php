<?php

use Think\Controller;

/**
 * 公共模块
 * @author Jine <luxikun@andlisoft.com>
 */
class TestController extends Controller {

    public function __construct() {
        //封锁线上测试功能

        $debug = $_GET['debug'];
        $host = $_SERVER['HTTP_HOST'];
//        $host = HOST;
//        var_dump($host);var_dump($debug);exit;
        if ($host == 'api.feibaokeji.com' or $host == 'api.pro.feibaokeji.com') {
//            var_dump($host);var_dump($debug);exit;
            $debug || header("Location:" . WEBURL . "/");
        }
//        echo $_SERVER["SERVER_ADDR"];
//        echo $_SERVER["SERVER_NAME"];
    }

    public function showMassage() {
        $massage = "官人,您的版本太老了,很多功能都无法使用了!赶快升级吧!";
        $uid = 43146;
        pushMassageAndWriteMassage($massage, $uid);
    }

    public function show() {
        $this->calculateUserShareIntegral(43146, 1659);
    }

    public function calculateUserShareIntegral($uid, $pid) {
        //每张海报每个人可获得的最大分享飞币数
        $posters_share_max_integral = D("System")->readConfig("posters_share_max_integral");
        //每张海报每个人可在最多前多少次获得飞币
        $posters_share_max_times = D("System")->readConfig("posters_share_max_times");
        //统计当前用户对当前海报分享了多少次
        $where = array();
        $where['dataId'] = $pid;
        $where['userId'] = $uid;
        $m = M("share_poster_log");
        $share_count = $m->where($where)->count("id");
        //分享第十次之后不再获得飞币
        if ($share_count == $posters_share_max_times) {
            return 0;
        }
        //分享第一次到第四次的区间范围的最大值
        if ($share_count) {
            $max_integral = $posters_share_max_integral - M("share_poster_log")->where($where)->sum('integral');
        } else {
            $max_integral = $posters_share_max_integral - 10;
        }

        //使用放大区间并求余的方式让随机值更散列
//        $mt_rand = mt_rand(0, 2100000000);
//        $integral = $mt_rand % $max_integral;
        //或者采用直接的随机值
        $integral = mt_rand(0, $max_integral);
        return $integral;
    }

    function error() {
        header("Content-type:text/html;charset=utf-8");
        $str = <<<EOF
<div id="think_page_trace" style="position: fixed;bottom:0;right:0;font-size:14px;width:100%;z-index: 999999;color: #000;text-align:left;font-family:'微软雅黑';">
<div id="think_page_trace_tab" style="display: none;background:white;margin:0;height: 250px;">
<div id="think_page_trace_tab_tit" style="height:30px;padding: 6px 12px 0;border-bottom:1px solid #ececec;border-top:1px solid #ececec;font-size:16px">
	    <span style="color:#000;padding-right:12px;height:30px;line-height: 30px;display:inline-block;margin-right:3px;cursor: pointer;font-weight:700">基本</span>
        <span style="color:#000;padding-right:12px;height:30px;line-height: 30px;display:inline-block;margin-right:3px;cursor: pointer;font-weight:700">文件</span>
        <span style="color:#000;padding-right:12px;height:30px;line-height: 30px;display:inline-block;margin-right:3px;cursor: pointer;font-weight:700">流程</span>
        <span style="color:#000;padding-right:12px;height:30px;line-height: 30px;display:inline-block;margin-right:3px;cursor: pointer;font-weight:700">错误</span>
        <span style="color:#000;padding-right:12px;height:30px;line-height: 30px;display:inline-block;margin-right:3px;cursor: pointer;font-weight:700">SQL</span>
        <span style="color:#000;padding-right:12px;height:30px;line-height: 30px;display:inline-block;margin-right:3px;cursor: pointer;font-weight:700">调试</span>
    </div>
<div id="think_page_trace_tab_cont" style="overflow:auto;height:212px;padding: 0; line-height: 24px">
		    <div style="display:none;">
    <ol style="padding: 0; margin:0">
	<li style="border-bottom:1px solid #EEE;font-size:14px;padding:0 12px">请求信息 : 2016-02-25 17:01:57 HTTP/1.1 POST : /index.php/API_IOS_3.3/Advertising/getCityLists</li><li style="border-bottom:1px solid #EEE;font-size:14px;padding:0 12px">运行时间 : 0.0482s ( Load:0.0139s Init:0.0023s Exec:0.0320s Template:-0.0000s )</li><li style="border-bottom:1px solid #EEE;font-size:14px;padding:0 12px">吞吐率 : 20.75req/s</li><li style="border-bottom:1px solid #EEE;font-size:14px;padding:0 12px">内存开销 : 2,957.98 kb</li><li style="border-bottom:1px solid #EEE;font-size:14px;padding:0 12px">查询信息 : 13 queries 4 writes </li><li style="border-bottom:1px solid #EEE;font-size:14px;padding:0 12px">文件加载 : 42</li><li style="border-bottom:1px solid #EEE;font-size:14px;padding:0 12px">缓存信息 : 0 gets 0 writes </li><li style="border-bottom:1px solid #EEE;font-size:14px;padding:0 12px">配置加载 : 186</li><li style="border-bottom:1px solid #EEE;font-size:14px;padding:0 12px">会话信息 : SESSION_ID=08qhbv570r5vkqcaa82h460lf1</li>    </ol>
    </div>
        <div style="display:none;">
    <ol style="padding: 0; margin:0">
	<li style="border-bottom:1px solid #EEE;font-size:14px;padding:0 12px">/home/wwwroot/dev/index.php ( 4.18 KB )</li><li style="border-bottom:1px solid #EEE;font-size:14px;padding:0 12px">/home/wwwroot/dev/ThinkPHP/ThinkPHP.php ( 4.63 KB )</li><li style="border-bottom:1px solid #EEE;font-size:14px;padding:0 12px">/home/wwwroot/dev/ThinkPHP/Library/Think/Think.class.php ( 12.31 KB )</li><li style="border-bottom:1px solid #EEE;font-size:14px;padding:0 12px">/home/wwwroot/dev/ThinkPHP/Library/Think/Storage.class.php ( 1.38 KB )</li><li style="border-bottom:1px solid #EEE;font-size:14px;padding:0 12px">/home/wwwroot/dev/ThinkPHP/Library/Think/Storage/Driver/File.class.php ( 3.54 KB )</li><li style="border-bottom:1px solid #EEE;font-size:14px;padding:0 12px">/home/wwwroot/dev/ThinkPHP/Mode/common.php ( 2.82 KB )</li><li style="border-bottom:1px solid #EEE;font-size:14px;padding:0 12px">/home/wwwroot/dev/ThinkPHP/Common/functions.php ( 49.85 KB )</li><li style="border-bottom:1px solid #EEE;font-size:14px;padding:0 12px">/home/wwwroot/dev/Common/Common/function.php ( 90.50 KB )</li><li style="border-bottom:1px solid #EEE;font-size:14px;padding:0 12px">/home/wwwroot/dev/ThinkPHP/Library/Think/Hook.class.php ( 4.02 KB )</li><li style="border-bottom:1px solid #EEE;font-size:14px;padding:0 12px">/home/wwwroot/dev/ThinkPHP/Library/Think/App.class.php ( 13.23 KB )</li><li style="border-bottom:1px solid #EEE;font-size:14px;padding:0 12px">/home/wwwroot/dev/ThinkPHP/Library/Think/Dispatcher.class.php ( 14.84 KB )</li><li style="border-bottom:1px solid #EEE;font-size:14px;padding:0 12px">/home/wwwroot/dev/ThinkPHP/Library/Think/Route.class.php ( 13.37 KB )</li><li style="border-bottom:1px solid #EEE;font-size:14px;padding:0 12px">/home/wwwroot/dev/ThinkPHP/Library/Think/Controller.class.php ( 11.34 KB )</li><li style="border-bottom:1px solid #EEE;font-size:14px;padding:0 12px">/home/wwwroot/dev/ThinkPHP/Library/Think/View.class.php ( 7.44 KB )</li><li style="border-bottom:1px solid #EEE;font-size:14px;padding:0 12px">/home/wwwroot/dev/ThinkPHP/Library/Behavior/BuildLiteBehavior.class.php ( 3.69 KB )</li><li style="border-bottom:1px solid #EEE;font-size:14px;padding:0 12px">/home/wwwroot/dev/ThinkPHP/Library/Behavior/ParseTemplateBehavior.class.php ( 3.89 KB )</li><li style="border-bottom:1px solid #EEE;font-size:14px;padding:0 12px">/home/wwwroot/dev/ThinkPHP/Library/Behavior/ContentReplaceBehavior.class.php ( 1.96 KB )</li><li style="border-bottom:1px solid #EEE;font-size:14px;padding:0 12px">/home/wwwroot/dev/ThinkPHP/Conf/convention.php ( 11.18 KB )</li><li style="border-bottom:1px solid #EEE;font-size:14px;padding:0 12px">/home/wwwroot/dev/Common/Conf/config.php ( 3.86 KB )</li><li style="border-bottom:1px solid #EEE;font-size:14px;padding:0 12px">/home/wwwroot/dev/Common/Conf/api.dev.feibaokeji.com.config.php ( 0.82 KB )</li><li style="border-bottom:1px solid #EEE;font-size:14px;padding:0 12px">/home/wwwroot/dev/Common/Conf/tags.php ( 0.08 KB )</li><li style="border-bottom:1px solid #EEE;font-size:14px;padding:0 12px">/home/wwwroot/dev/ThinkPHP/Lang/zh-cn.php ( 2.56 KB )</li><li style="border-bottom:1px solid #EEE;font-size:14px;padding:0 12px">/home/wwwroot/dev/ThinkPHP/Conf/debug.php ( 1.70 KB )</li><li style="border-bottom:1px solid #EEE;font-size:14px;padding:0 12px">/home/wwwroot/dev/Common/Conf/alioss.php ( 0.33 KB )</li><li style="border-bottom:1px solid #EEE;font-size:14px;padding:0 12px">/home/wwwroot/dev/Application/API_IOS_3.3/Conf/config.php ( 4.63 KB )</li><li style="border-bottom:1px solid #EEE;font-size:14px;padding:0 12px">/home/wwwroot/dev/Application/API_IOS_3.3/Common/function.php ( 9.48 KB )</li><li style="border-bottom:1px solid #EEE;font-size:14px;padding:0 12px">/home/wwwroot/dev/ThinkPHP/Library/Behavior/ReadHtmlCacheBehavior.class.php ( 5.62 KB )</li><li style="border-bottom:1px solid #EEE;font-size:14px;padding:0 12px">/home/wwwroot/dev/ThinkPHP/Library/Think/Session/Driver/Memcache.class.php ( 0.89 KB )</li><li style="border-bottom:1px solid #EEE;font-size:14px;padding:0 12px">/home/wwwroot/dev/Application/API_IOS_3.3/Controller/AdvertisingController.class.php ( 40.86 KB )</li><li style="border-bottom:1px solid #EEE;font-size:14px;padding:0 12px">/home/wwwroot/dev/Application/API_IOS_3.3/Controller/CommonController.class.php ( 1.51 KB )</li><li style="border-bottom:1px solid #EEE;font-size:14px;padding:0 12px">/home/wwwroot/dev/Application/Dev/Behaviors/testBehavior.class.php ( 2.14 KB )</li><li style="border-bottom:1px solid #EEE;font-size:14px;padding:0 12px">/home/wwwroot/dev/ThinkPHP/Library/Think/Behavior.class.php ( 0.86 KB )</li><li style="border-bottom:1px solid #EEE;font-size:14px;padding:0 12px">/home/wwwroot/dev/Application/API_IOS_3.3/Model/SystemModel.class.php ( 3.90 KB )</li><li style="border-bottom:1px solid #EEE;font-size:14px;padding:0 12px">/home/wwwroot/dev/Application/API_IOS_3.3/Model/CommonModel.class.php ( 18.95 KB )</li><li style="border-bottom:1px solid #EEE;font-size:14px;padding:0 12px">/home/wwwroot/dev/ThinkPHP/Library/Think/Model.class.php ( 65.35 KB )</li><li style="border-bottom:1px solid #EEE;font-size:14px;padding:0 12px">/home/wwwroot/dev/ThinkPHP/Library/Think/Db.class.php ( 34.40 KB )</li><li style="border-bottom:1px solid #EEE;font-size:14px;padding:0 12px">/home/wwwroot/dev/ThinkPHP/Library/Think/Db/Driver/Mysql.class.php ( 10.80 KB )</li><li style="border-bottom:1px solid #EEE;font-size:14px;padding:0 12px">/home/wwwroot/dev/Application/API_IOS_3.3/Controller/CheckController.class.php ( 19.61 KB )</li><li style="border-bottom:1px solid #EEE;font-size:14px;padding:0 12px">/home/wwwroot/dev/Application/API_IOS_3.3/Model/CheckModel.class.php ( 11.79 KB )</li><li style="border-bottom:1px solid #EEE;font-size:14px;padding:0 12px">/home/wwwroot/dev/Application/API_IOS_3.3/Controller/PublicController.class.php ( 3.31 KB )</li><li style="border-bottom:1px solid #EEE;font-size:14px;padding:0 12px">/home/wwwroot/dev/Application/API_IOS_3.3/Model/MembersModel.class.php ( 39.93 KB )</li><li style="border-bottom:1px solid #EEE;font-size:14px;padding:0 12px">/home/wwwroot/dev/ThinkPHP/Library/Behavior/ShowPageTraceBehavior.class.php ( 5.25 KB )</li>    </ol>
    </div>
        <div style="display:none;">
    <ol style="padding: 0; margin:0">
	<li style="border-bottom:1px solid #EEE;font-size:14px;padding:0 12px">[ app_init ] --START--</li><li style="border-bottom:1px solid #EEE;font-size:14px;padding:0 12px">Run Behavior\BuildLiteBehavior [ RunTime:0.000017s ]</li><li style="border-bottom:1px solid #EEE;font-size:14px;padding:0 12px">[ app_init ] --END-- [ RunTime:0.000082s ]</li><li style="border-bottom:1px solid #EEE;font-size:14px;padding:0 12px">[ app_begin ] --START--</li><li style="border-bottom:1px solid #EEE;font-size:14px;padding:0 12px">Run Behavior\ReadHtmlCacheBehavior [ RunTime:0.000398s ]</li><li style="border-bottom:1px solid #EEE;font-size:14px;padding:0 12px">[ app_begin ] --END-- [ RunTime:0.000473s ]</li><li style="border-bottom:1px solid #EEE;font-size:14px;padding:0 12px">[ action_begin ] --START--</li><li style="border-bottom:1px solid #EEE;font-size:14px;padding:0 12px">Run Dev\Behaviors\testBehavior [ RunTime:0.000496s ]</li><li style="border-bottom:1px solid #EEE;font-size:14px;padding:0 12px">[ action_begin ] --END-- [ RunTime:0.000556s ]</li><li style="border-bottom:1px solid #EEE;font-size:14px;padding:0 12px">[ action_begin ] --START--</li><li style="border-bottom:1px solid #EEE;font-size:14px;padding:0 12px">Run Dev\Behaviors\testBehavior [ RunTime:0.000008s ]</li><li style="border-bottom:1px solid #EEE;font-size:14px;padding:0 12px">[ action_begin ] --END-- [ RunTime:-0.010484s ]</li><li style="border-bottom:1px solid #EEE;font-size:14px;padding:0 12px">[ action_begin ] --START--</li><li style="border-bottom:1px solid #EEE;font-size:14px;padding:0 12px">Run Dev\Behaviors\testBehavior [ RunTime:0.000008s ]</li><li style="border-bottom:1px solid #EEE;font-size:14px;padding:0 12px">[ action_begin ] --END-- [ RunTime:-0.015041s ]</li><li style="border-bottom:1px solid #EEE;font-size:14px;padding:0 12px">[ app_end ] --START--</li>    </ol>
    </div>
        <div style="display:none;">
    <ol style="padding: 0; margin:0">
	<li style="border-bottom:1px solid #EEE;font-size:14px;padding:0 12px">1146:Table 'app_20150706.lu_system' doesn't exist
 [ SQL语句 ] : SHOW COLUMNS FROM `lu_system`</li><li style="border-bottom:1px solid #EEE;font-size:14px;padding:0 12px">1048:Column 'user_id' cannot be null
 [ SQL语句 ] : INSERT INTO `lu_ip_access_log` (`ip`,`user_id`,`controller_name`,`action_name`,`access_time`) VALUES ('2093491926',null,'Advertising','getCityLists',1456390917)</li><li style="border-bottom:1px solid #EEE;font-size:14px;padding:0 12px">1048:Column 'user_id' cannot be null
 [ SQL语句 ] : INSERT INTO `lu_ip_access_log_member` (`ip`,`user_id`,`controller_name`,`action_name`,`access_time`) VALUES ('2093491926',null,'Advertising','getCityLists',1456390917)</li><li style="border-bottom:1px solid #EEE;font-size:14px;padding:0 12px">1146:Table 'app_20150706.lu_check' doesn't exist
 [ SQL语句 ] : SHOW COLUMNS FROM `lu_check`</li><li style="border-bottom:1px solid #EEE;font-size:14px;padding:0 12px">1054:Unknown column 'b.cityId' in 'on clause'
 [ SQL语句 ] : select a.id as cityId, a.name as cityName, a.lng, a.lat from lu_city as a right join lu_push_city on a.id = b.cityId where a.id not in (1,9,234,236)</li><li style="border-bottom:1px solid #EEE;font-size:14px;padding:0 12px">[8] Undefined index: newHandlePwd /home/wwwroot/dev/Application/API_IOS_3.3/Controller/CheckController.class.php 第 56 行.</li><li style="border-bottom:1px solid #EEE;font-size:14px;padding:0 12px">[8] Undefined index: handlePwd /home/wwwroot/dev/Application/API_IOS_3.3/Controller/CheckController.class.php 第 56 行.</li><li style="border-bottom:1px solid #EEE;font-size:14px;padding:0 12px">[8] Undefined variable: string_str /home/wwwroot/dev/Common/Common/function.php 第 341 行.</li><li style="border-bottom:1px solid #EEE;font-size:14px;padding:0 12px">[2] filesize(): stat failed for /home/wwwroot/dev/log/2016-02-25.2.html /home/wwwroot/dev/Common/Common/function.php 第 861 行.</li><li style="border-bottom:1px solid #EEE;font-size:14px;padding:0 12px">[2] fopen(/home/wwwroot/dev/log/2016-02-25.2.html): failed to open stream: No such file or directory /home/wwwroot/dev/Common/Common/function.php 第 836 行.</li><li style="border-bottom:1px solid #EEE;font-size:14px;padding:0 12px">[2] flock() expects parameter 1 to be resource, boolean given /home/wwwroot/dev/Common/Common/function.php 第 837 行.</li>    </ol>
    </div>
        <div style="display:none;">
    <ol style="padding: 0; margin:0">
	    </ol>
    </div>
        <div style="display:none;">
    <ol style="padding: 0; margin:0">
	    </ol>
    </div>
    </div>
</div>
<div id="think_page_trace_close" style="display:none;text-align:right;height:15px;position:absolute;top:10px;right:12px;cursor: pointer;"><img style="vertical-align:top;" src="data:image/gif;base64,R0lGODlhDwAPAJEAAAAAAAMDA////wAAACH/C1hNUCBEYXRhWE1QPD94cGFja2V0IGJlZ2luPSLvu78iIGlkPSJXNU0wTXBDZWhpSHpyZVN6TlRjemtjOWQiPz4gPHg6eG1wbWV0YSB4bWxuczp4PSJhZG9iZTpuczptZXRhLyIgeDp4bXB0az0iQWRvYmUgWE1QIENvcmUgNS4wLWMwNjAgNjEuMTM0Nzc3LCAyMDEwLzAyLzEyLTE3OjMyOjAwICAgICAgICAiPiA8cmRmOlJERiB4bWxuczpyZGY9Imh0dHA6Ly93d3cudzMub3JnLzE5OTkvMDIvMjItcmRmLXN5bnRheC1ucyMiPiA8cmRmOkRlc2NyaXB0aW9uIHJkZjphYm91dD0iIiB4bWxuczp4bXA9Imh0dHA6Ly9ucy5hZG9iZS5jb20veGFwLzEuMC8iIHhtbG5zOnhtcE1NPSJodHRwOi8vbnMuYWRvYmUuY29tL3hhcC8xLjAvbW0vIiB4bWxuczpzdFJlZj0iaHR0cDovL25zLmFkb2JlLmNvbS94YXAvMS4wL3NUeXBlL1Jlc291cmNlUmVmIyIgeG1wOkNyZWF0b3JUb29sPSJBZG9iZSBQaG90b3Nob3AgQ1M1IFdpbmRvd3MiIHhtcE1NOkluc3RhbmNlSUQ9InhtcC5paWQ6MUQxMjc1MUJCQUJDMTFFMTk0OUVGRjc3QzU4RURFNkEiIHhtcE1NOkRvY3VtZW50SUQ9InhtcC5kaWQ6MUQxMjc1MUNCQUJDMTFFMTk0OUVGRjc3QzU4RURFNkEiPiA8eG1wTU06RGVyaXZlZEZyb20gc3RSZWY6aW5zdGFuY2VJRD0ieG1wLmlpZDoxRDEyNzUxOUJBQkMxMUUxOTQ5RUZGNzdDNThFREU2QSIgc3RSZWY6ZG9jdW1lbnRJRD0ieG1wLmRpZDoxRDEyNzUxQUJBQkMxMUUxOTQ5RUZGNzdDNThFREU2QSIvPiA8L3JkZjpEZXNjcmlwdGlvbj4gPC9yZGY6UkRGPiA8L3g6eG1wbWV0YT4gPD94cGFja2V0IGVuZD0iciI/PgH//v38+/r5+Pf29fTz8vHw7+7t7Ovq6ejn5uXk4+Lh4N/e3dzb2tnY19bV1NPS0dDPzs3My8rJyMfGxcTDwsHAv769vLu6ubi3trW0s7KxsK+urayrqqmop6alpKOioaCfnp2cm5qZmJeWlZSTkpGQj46NjIuKiYiHhoWEg4KBgH9+fXx7enl4d3Z1dHNycXBvbm1sa2ppaGdmZWRjYmFgX15dXFtaWVhXVlVUU1JRUE9OTUxLSklIR0ZFRENCQUA/Pj08Ozo5ODc2NTQzMjEwLy4tLCsqKSgnJiUkIyIhIB8eHRwbGhkYFxYVFBMSERAPDg0MCwoJCAcGBQQDAgEAACH5BAAAAAAALAAAAAAPAA8AAAIdjI6JZqotoJPR1fnsgRR3C2jZl3Ai9aWZZooV+RQAOw==" /></div>
</div>
<div id="think_page_trace_open" style="height:30px;float:right;text-align: right;overflow:hidden;position:fixed;bottom:0;right:0;color:#000;line-height:30px;cursor:pointer;"><div style="background:#232323;color:#FFF;padding:0 6px;float:right;line-height:30px;font-size:14px">0.0482s </div><img width="30" style="" title="ShowPageTrace" src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAADAAAAAwCAYAAABXAvmHAAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAAAyBpVFh0WE1MOmNvbS5hZG9iZS54bXAAAAAAADw/eHBhY2tldCBiZWdpbj0i77u/IiBpZD0iVzVNME1wQ2VoaUh6cmVTek5UY3prYzlkIj8+IDx4OnhtcG1ldGEgeG1sbnM6eD0iYWRvYmU6bnM6bWV0YS8iIHg6eG1wdGs9IkFkb2JlIFhNUCBDb3JlIDUuMC1jMDYwIDYxLjEzNDc3NywgMjAxMC8wMi8xMi0xNzozMjowMCAgICAgICAgIj4gPHJkZjpSREYgeG1sbnM6cmRmPSJodHRwOi8vd3d3LnczLm9yZy8xOTk5LzAyLzIyLXJkZi1zeW50YXgtbnMjIj4gPHJkZjpEZXNjcmlwdGlvbiByZGY6YWJvdXQ9IiIgeG1sbnM6eG1wPSJodHRwOi8vbnMuYWRvYmUuY29tL3hhcC8xLjAvIiB4bWxuczp4bXBNTT0iaHR0cDovL25zLmFkb2JlLmNvbS94YXAvMS4wL21tLyIgeG1sbnM6c3RSZWY9Imh0dHA6Ly9ucy5hZG9iZS5jb20veGFwLzEuMC9zVHlwZS9SZXNvdXJjZVJlZiMiIHhtcDpDcmVhdG9yVG9vbD0iQWRvYmUgUGhvdG9zaG9wIENTNSBXaW5kb3dzIiB4bXBNTTpJbnN0YW5jZUlEPSJ4bXAuaWlkOjVERDVENkZGQjkyNDExRTE5REY3RDQ5RTQ2RTRDQUJCIiB4bXBNTTpEb2N1bWVudElEPSJ4bXAuZGlkOjVERDVENzAwQjkyNDExRTE5REY3RDQ5RTQ2RTRDQUJCIj4gPHhtcE1NOkRlcml2ZWRGcm9tIHN0UmVmOmluc3RhbmNlSUQ9InhtcC5paWQ6NURENUQ2RkRCOTI0MTFFMTlERjdENDlFNDZFNENBQkIiIHN0UmVmOmRvY3VtZW50SUQ9InhtcC5kaWQ6NURENUQ2RkVCOTI0MTFFMTlERjdENDlFNDZFNENBQkIiLz4gPC9yZGY6RGVzY3JpcHRpb24+IDwvcmRmOlJERj4gPC94OnhtcG1ldGE+IDw/eHBhY2tldCBlbmQ9InIiPz5fx6IRAAAMCElEQVR42sxae3BU1Rk/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"></div>
<script type="text/javascript">
(function(){
var tab_tit  = document.getElementById('think_page_trace_tab_tit').getElementsByTagName('span');
var tab_cont = document.getElementById('think_page_trace_tab_cont').getElementsByTagName('div');
var open     = document.getElementById('think_page_trace_open');
var close    = document.getElementById('think_page_trace_close').childNodes[0];
var trace    = document.getElementById('think_page_trace_tab');
var cookie   = document.cookie.match(/thinkphp_show_page_trace=(\d\|\d)/);
var history  = (cookie && typeof cookie[1] != 'undefined' && cookie[1].split('|')) || [0,0];
open.onclick = function(){
	trace.style.display = 'block';
	this.style.display = 'none';
	close.parentNode.style.display = 'block';
	history[0] = 1;
	document.cookie = 'thinkphp_show_page_trace='+history.join('|')
}
close.onclick = function(){
	trace.style.display = 'none';
this.parentNode.style.display = 'none';
	open.style.display = 'block';
	history[0] = 0;
	document.cookie = 'thinkphp_show_page_trace='+history.join('|')
}
for(var i = 0; i < tab_tit.length; i++){
	tab_tit[i].onclick = (function(i){
		return function(){
			for(var j = 0; j < tab_cont.length; j++){
				tab_cont[j].style.display = 'none';
				tab_tit[j].style.color = '#999';
			}
			tab_cont[i].style.display = 'block';
			tab_tit[i].style.color = '#000';
			history[1] = i;
			document.cookie = 'thinkphp_show_page_trace='+history.join('|')
		}
	})(i)
}
parseInt(history[0]) && open.click();
(tab_tit[history[1]] || tab_tit[0]).click();
})();
</script>


EOF;
        echo $str;
    }

    function html() {
//        version:版本号(如“1.2”)
//        userId:唯一码【必填项】
//        phone：会员注册手机号【必填项】
        header("Content-type:text/html;charset=utf-8");
        $html = '<form action="http://api.ol.feibaokeji.com/index.php/API_ANDROID_3.3/Advertising/advGetLog" method="post">
                    <p>版本号: <input type="text" name="version" value="3.3" /></p>
                    <p>唯一码: <input type="text" name="userId" value="egcvzbGAWSmceYuIW34Orni23Plb37rnjlSRO14AB6wfMN5qZnRwWCRelFiDiZOJfDo3GqL5KxUEJaA0FBb00qtC/aew" /></p>
                    <p>phone: <input type="text" name="phone" value="13681260087" /></p>
                    <p>agreement: <input type="text" name="agreement" value="3.2" /></p>
                    <p>mobile_platform: <input type="text" name="mobile_platform" value="ios" /></p>
                    <p>mobile_platform: <input type="text" name="mobile_platform1" value="android" /></p>
                    <p>friendId: <input type="text" name="friendId" value="3968NmZlYTM4ZDYzY2RhMTkzMzJlMzkzMjhjNjBiODUyODVlODgzZTAwZmE0M2QxZTczZmU3NGM5NzViOTRjZmNlZDBjZDRhYTA4MWJhNDk3ZGQ3ZmQyODIzNzRiM2M2ZTJhZTk0ZWM1M2YwMzQ2ZTUyZWIzODVmY2NhOWExMGZmZTQ0MzMxYTEwN2ZmZWE3YTgzZWEyOWZmMDNlMDE5ZTU2Yzk4ZmU" /></p>
                    <p>type: <input type="text" name="type" value="" /></p>
                    <p>category: <input type="text" name="category" value="" /></p>
                    <p>pageSize: <input type="text" name="pageSize" value="" /></p>
                    <p>page: <input type="text" name="page" value="" /></p>
                    <p>selectTime: <input type="text" name="selectTime" value="" /></p>
                    <p>dataId: <textarea name="dataId" >039fdcf5cNtnKdUwkAUQ</textarea></p>
                    <p>vouchersId : <textarea name="vouchersId" ></textarea></p>
                    <p>advId: <textarea name="advId" ></textarea></p>
                    <p>themeId: <textarea name="themeId" ></textarea></p>
                    <p>lastAddTime: <textarea name="lastAddTime" disable>0</textarea></p>
                    <p>options: <textarea name="options" >7,1</textarea></p>
                    <p>search: <textarea name="search"></textarea></p>
                    <p>isMe: <textarea name="isMe">1</textarea></p>
                    <input type="submit" value="Submit" />
                  </form>';
        echo $html;
    }

    function test() {
        $sql = "select count(*) as total from lu_focus_merchants where userId = 1";
        $focusNum = M()->query($sql);
        var_dump($focusNum);exit;
        $data = array(
            'userId' => 1,
            'focusId' => 2,
            'addTime' => time()
        );
        $rs = D('focus_merchants')->add($data);
        var_dump(D()->getLastSql(), $rs);
        exit;
        $time = 1435052537;
        echo substr($time, 1);exit;
        $time = str_replace(substr($time, 3, 0), '****', $time);
        echo $time;
        exit;
        $return = date('Y-m-d H:i:s', $time);
        
        var_dump($return);
        exit;
        echo '<pre>';
        var_dump($_SERVER);exit;
        $urls = $this->shorturl('http://www.bianceng.cn');
      
        var_dump($urls);
        exit;
        echo date('Y-m-d His', 1456243200 + 32400);
        exit;
        header("Content-type:text/html;charset=utf-8");
        $str = decodePass(a59f9f4d432c4b5a8ca803c71a2f68a9901549c2);
        var_dump($str);
        exit;
        $hot_sql = "select id as cityId, name as cityName, lng, lat from lu_city where id in ($hot_city)";
        $return['info']['hotCity'] = M()->query($hot_sql);

        $other_sql = "select a.id as cityId, a.name as cityName, a.lng, a.lat from lu_city as a right join lu_push_city on a.id = b.cityId where a.id not in ($hot_city)";
        $return['info']['otherCity'] = M()->query($other_sql);

        echo $hot_sql;

        echo $other_sql;
        var_dump($return);
        exit;
        $reList = D('Friends')->getSearchListData(42900, '安');
        var_dump($reList);
        eixt;
        $str = ' */1A-X=a2c';
        $str = preg_replace("#[^0-9]#", '', $str);
        echo $str;
        exit;
        echo decodePass('1135087eEAn9Y8UwcJBgQ');
        exit;
        $arr = array(18500540715, 18201415541);
        echo json_encode($arr);
        exit;
        $str = '7a14a8fb72210bf41c1133dec8f18568299bea07';
        $str1 = '00cd4baca9305afa03f64c9f65ddc28506f557c0';

        echo decodePass($str) . '<br>';
        echo encodePass($str) . '<br>';
        echo decodePass($str1) . '<br>';
        echo encodePass($str1) . '<br>';
    }
    
    /*
     * 配置选项监测
     */
    public function configTest() {
        //mysql监测
        $m = M('config');
        $a = $m->find();
        showTestInfo($a);
    }
    
    public function shorturl($url='', $prefix='', $suffix='') {
      
   $base32 = array (
      
   'a', 'b', 'c', 'd', 'e', 'f', 'g', 'h',
      
   'i', 'j', 'k', 'l', 'm', 'n', 'o', 'p',
      
   'q', 'r', 's', 't', 'u', 'v', 'w', 'x',
      
   'y', 'z', '0', '1', '2', '3', '4', '5');
      
   $hex = md5($prefix.$url.$suffix);
      
   $hexLen = strlen($hex);
      
   $subHexLen = $hexLen / 8;
      
   $output = array();
      
   for ($i = 0; $i < $subHexLen; $i++) {
      
   $subHex = substr ($hex, $i * 8, 8);
      
   $int = 0x3FFFFFFF & (1 * ('0x'.$subHex));
      
   $out = '';
      
   for ($j = 0; $j < 6; $j++) {
      
   $val = 0x0000001F & $int;
      
   $out .= $base32[$val];
      
   $int = $int > 5;
      
   }
      
   $output[] = $out;
      
   }
      
   return $output;
      
   }
      //生成短链
function code62($x) {
    $show = '';
    while ($x > 0) {
        $s = $x % 62;
        if ($s > 35) {
            $s = chr($s + 61);
        } elseif ($s > 9 && $s <= 35) {
            $s = chr($s + 55);
        }
        $show .= $s;
        $x = floor($x / 62);
    }
    return $show;
}

function short_url($url) {
    $url = crc32($url);
    $result = sprintf("%u", $url);
    return code62($result);
}
   

}
