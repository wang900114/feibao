<?php

use Think\Controller;

class UploadController extends Controller {

    public $identification = array();
    public $identification_developer_kid = '';
    public $identification_developer_key = '';
    public $BucketName = '';
    public $base_url = '';
    public $base_url2 = '';
    public $base_url3 = '';
    public $ob = object;
    public $fileType = array();
    public $file_Size = 0;
    public $ExpiredTime = 0;
    public $i = 3;

    public function _initialize() {
        header("Content-Type: text/html; charset=utf-8");
        set_time_limit(60 * 10);
        import("Common.Extend.LenovoCOS", ROOT);
        $this->ob = new LenovoCOS();

        $config = C('LENOVO');
        $this->BucketName = $config['BucketName'];
        $this->base_url = $config['base_url'];
        $this->base_url2 = $config['base_url2'];
        $this->base_url3 = $config['base_url3'];
        $this->identification = array(
            "app_id" => $config['app_id'],
            "user_credential" => $config['user_credential'],
            "developer_kid" => $config['developer_kid'],
            "developer_key" => $config['developer_key']
        );
        $this->identification_developer_kid = $config['developer_kid'];
        $this->identification_developer_key = $config['developer_key'];
        $this->fileType = $config['type'];
        $this->file_Size = $config['size'];
        $this->ExpiredTime = $config['ExpiredTime'];
    }

    public function __destruct() {
        unset($this->identification);
        unset($this->BucketName);
        unset($this->ob);
    }

    public function delFile($path) {
        $pathVal = getUrlValue($path);
        $key = $pathVal['key'];

        if (empty($key))
            return array('status' => -111, 'message' => '丢失文件 key 信息');

        $re1 = $this->connector();
        if (is_array($re1) && $re1['status'] < 0)
            return $re1;

        $session = $this->session($re1);
        if (is_array($session) && $session['status'] < 0)
            return $session;

        $header = array(
            'is_return' => false,
            'header' => array(
                'Authorization: ' . $session,
            )
        );

        $b = $this->ob->del($this->base_url . '/v2/object/link/' . $this->BucketName . '/' . $key, array(), $header);
        $re2 = json_decode($b, true);
        if (empty($re2['bucket_name'])) {
            return array('status' => -113, 'message' => '删除文件外链链接失败');
        } else {
            $a = $this->ob->del($this->base_url . '/v2/object/data/' . $this->BucketName . '/' . $key, array(), $header);
            $re = json_decode($a, true);
            if (empty($re['_id'])) {
                return array('status' => -112, 'message' => '删除文件失败');
            } else {
                return array('status' => 1, 'message' => '删除文件成功');
            }
        }
    }

    public function setSession() {
        $re1 = $this->connector();
		
		//$return['result']=$re1;
        if (is_array($re1) && $re1['status'] < 0)
            return $re1;

        $session = $this->session($re1);
        if (is_array($session) && $session['status'] < 0)
            return $session;

        $re3 = $this->bucket($session);
        if (is_array($re3) && $re3['status'] < 0)
            return $re3;

        // $re4 = $this->objects($session);
        // if( is_array($re4) && $re4['status'] < 0 ) return $re4;


        $bArr = json_decode($re1, true);
        $connector = $bArr["connector"];

        // $dArr = json_decode($re4, true);
        // $key = $dArr['key'];

        return array(
            'connector' => $connector,
            'session' => $session,
            'bucket' => $this->BucketName,
                // 'objects'=>$key
        );
    }

    public function uploadFile($file) {

        $re1 = $this->connector();
        if (is_array($re1) && $re1['status'] < 0)
            return $re1;

        $session = $this->session($re1);
        if (is_array($session) && $session['status'] < 0)
            return $session;

        $re3 = $this->bucket($session);
        if (is_array($re3) && $re3['status'] < 0)
            return $re3;

        $re4 = $this->objects($session);
        if (is_array($re4) && $re4['status'] < 0)
            return $re4;

        $re5 = $this->upload($re4, $session, $file);
        return $re5;
    }

    public function pre_connector() {
        $a = $this->ob->post($this->base_url3 . '/v1/app/connector/', $this->identification); 
        if (empty($a)) {
            return array('status' => -1, 'message' => '握手通信失败');
        } else {
            return $a;
        }
    }

    public function connector() {
        $re = $this->pre_connector();
        $i = 0;
        while (($i < $this->i ) && ( is_array($re) && $re['status'] < 0)) {
            $re = $this->pre_connector();
            $i++;
        }
        return $re;
    }

    public function pre_session($a) {
        $bArr = json_decode($a, true);

        $post2 = array(
            "connector" => $bArr["connector"],
            "developer_kid" => $this->identification_developer_kid,
            "developer_key" => $this->identification_developer_key
        );
        $header = array(
            'is_return' => true,
        );
        $b = $this->ob->post($this->base_url3 . '/v1/app/session', $post2, $header);

        $re = $this->ob->info();
        preg_match_all('/Location:(.*)$/isU', $re["header"], $location);

        if (empty($location[1][0])) {
            return array('status' => -2, 'message' => '获取 Authorization 认证信息失败');
        } else {
            return trim($location[1][0]);
        }
    }

    public function session($a) {
        $re = $this->pre_session($a);
        $i = 0;
        while (($i < $this->i ) && ( is_array($re) && $re['status'] < 0)) {
            $re = $this->pre_session($a);
            $i++;
        }
        return $re;
    }

    public function pre_bucket($session) {
        $header = array(
            'is_return' => true,
            'header' => array(
                'Authorization: ' . $session,
                'Content-type: application/json',
                'X-Lenovows-OSS-Access-Control:public-read',
            )
        );
        $c = $this->ob->post($this->base_url2 . '/v2/service?bucket=' . $this->BucketName, array(), $header);

        if ($c != "duplicated" || (is_bool($c) && empty($c))) {
            return array('status' => -3, 'message' => '创建bucket失败');
        } else {
            return true;
        }
    }

    public function bucket($session) {
        $re = $this->pre_bucket($session);
        $i = 0;
        while (($i < $this->i ) && ( is_array($re) && $re['status'] < 0)) {
            $re = $this->pre_bucket($session);
            $i++;
        }
        return $re;
    }

    public function objects($session) {
        $header = array(
            'is_return' => true,
            'header' => array(
                'Authorization: ' . $session,
                'X-Lenovows-OSS-Auto-Link: true'
            )
        );
        $d = $this->ob->post($this->base_url2 . '/v2/bucket/' . $this->BucketName, array(), $header);
        if (empty($d)) {
            return array('status' => -4, 'message' => '创建object失败');
        } else {
            return $d;
        }
    }

    public function upload($d, $session, $file) {
        $dArr = json_decode($d, true);
        $key = $dArr['key'];

        $FILES_ARR = array_keys($file);
        $file_arr_name = $FILES_ARR[0];

        $header = array(
            'is_return' => false,
            'header' => array(
                'Authorization: ' . $session,
                "X-HTTP-Method-Override: put",
                'Content-Type:' . $file[$file_arr_name]['type']
            )
        );

        if (empty($file)) {
            return array('status' => -51, 'message' => '没有上传文件');
        }

        $fileNameArray = explode(".", $file[$file_arr_name]["name"]);
        $fileExtName = end($fileNameArray);
        $fileExtName = strtolower($fileExtName);
        $arrFile = explode(',', $this->fileType);
        if (!in_array($fileExtName, $arrFile)) {
            return array('status' => -52, 'message' => '上传文件类型不正确');
        }

        if ($file[$file_arr_name]["size"] > $this->file_Size) {
            return array('status' => -53, 'message' => '图片大小不正确');
        }


        $re = $this->pre_upload($file, $key, $header, $file_arr_name);
        $i = 0;
        while (($i < $this->i ) && ( is_array($re) && $re['status'] < 0)) {
            $re = $this->pre_upload($file, $key, $header, $file_arr_name);
            $i++;
        }

        if ($re['status'] == 1) {
            $fileUrl = $dArr["link_view_url"] . '?location=' . $dArr["location"];
            $data['file'] = $fileUrl;
            $data['addTime'] = time();
            D('LenovoFile')->add($data);

            $re2['status'] = 1;
            $re2['message'] = $fileUrl;
            return $re2;
        } else {
            return $re;
        }
    }

    public function pre_upload($file, $key, $header, $file_arr_name) {
        $theFileName = urlencode($file[$file_arr_name]["name"]);
        $upload_file = $file[$file_arr_name]["tmp_name"];
        $hReadHandle = fopen($upload_file, 'r');
        $put = array(
            'method' => 'put',
            'resource' => $hReadHandle,
            'maxsize' => filesize($upload_file),
        );

        $e = $this->ob->put($this->base_url . '/v2/object/data/' . $this->BucketName . '/' . $key . '?display_name=' . $theFileName, $put, $header);

        fclose($hReadHandle);

        $thumb_url = json_decode($e, true);

        if (empty($thumb_url)) {
            return array('status' => -54, 'message' => '上传 联想 失败');
        } else {
            return array('status' => 1, 'message' => '上传 联想 成功');
        }
    }

    public function makeUrl($session, $obImg) {
        $key = $obImg['key'];
        $bucket_name = $obImg['bucket_name'];
        $header = array(
            'is_return' => false,
            'header' => array(
                'Authorization: ' . $session
            )
        );

        $i = $this->ob->post($this->base_url . '/v2/object/link/' . $this->BucketName . '/' . $key . '?timeout=' . $this->ExpiredTime . '', array(), $header);
        $imgArr = json_decode($i, true);
        if (empty($imgArr)) {
            return array('status' => -9, 'message' => '创建上传文件的HTTP访问链接失败');
        } else {
            $fileUrl = $imgArr['link_url'] . '?key=' . $key . '&bucket_name=' . $bucket_name;
            //保存到 数据库 中
            $data['file'] = $fileUrl;
            $data['addTime'] = time();
            D('LenovoFile')->add($data);

            return array('status' => 1, 'message' => $fileUrl, 'object' => $obImg);
        }
    }

    public function getAllFileOfTheService($page = 1, $count = 10) {
        $re1 = $this->connector();
        if (is_array($re1) && $re1['status'] < 0)
            return $re1;

        $session = $this->session($re1);
        if (is_array($session) && $session['status'] < 0)
            return $session;

        $re3 = $this->bucket($session);
        if (is_array($re3) && $re3['status'] < 0)
            return $re3;

        $arr['page'] = $page;
        $arr['count'] = $count;


        $i = $this->ob->get($this->base_url2 . '/v2/bucket/' . $this->BucketName, $arr);
        return json_decode($i, true);
    }

    public function pre_delFileOfTheService($bucket_name, $key) {
        $re1 = $this->connector();
        if (is_array($re1) && $re1['status'] < 0)
            return $re1;

        $session = $this->session($re1);
        if (is_array($session) && $session['status'] < 0)
            return $session;

        $header = array(
            'is_return' => false,
            'header' => array(
                'Authorization: ' . $session,
            )
        );

        $i = $this->ob->del($this->base_url2 . '/v2/object/' . $bucket_name . '/' . $key, array(), $header);
        return json_decode($i, true);
    }

    public function delFileOfTheService($image) {
        $location = getUrlValue($image);

        $arr = explode('/object/', $location['location']);
        $arr2 = explode('/', end($arr));
        $bucket_name = $arr2[0];
        $key = $arr2[1];
        $i = $this->pre_delFileOfTheService($bucket_name, $key);
        return $i;
    }

}
