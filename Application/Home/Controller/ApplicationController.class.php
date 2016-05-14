<?php
namespace Home\Controller;

use Think\Controller;

/**
 * 公共类
 *
 * Class ApplicationController
 * @package Api\Controller
 */
class ApplicationController extends Controller
{

    // 定义错误静态变量
    const ERR_OK = 0;
    const ERR_FALSE = -1;
    const ERR_90001 = 90001;
    const ERR_90002 = 90002;
    const ERR_90003 = 90003;
    const ERR_90004 = 90004;
    const ERR_90005 = 90005;


    const APPNAME = "easysocial";
    const ACCESSKEY = "zzx0k25l32";
    const SECRETKEY = "0mlmkx221h400xlmwhx31y3ywjjj440j1z0ij0ih";
    const MAIN_BUCKET = "public";
    const AVATAR_DIR="avatar";
    const TWEETIMG_DIR="tweetpic";


        // 定义错误信息
        public static $ERRCODE_MAP = array(
            self::ERR_FALSE => '内部错误',
            self::ERR_OK => '请求成功',
            self::ERR_90001 => '不合法的数据类型',
            self::ERR_90002 => '需要POST请求',
            self::ERR_90003 => '解析JSON/XML内容错误',
            self::ERR_90004 => 'POST的数据包为空',
        );

    /**
     * 判断是否为合法的JSON数据
     *
     * @param $string
     * @return bool
     */
    private function is_json($string)
    {
        json_decode($string);
        return (json_last_error() == JSON_ERROR_NONE);
    }

    /**
     * 判断数据是否为JSON格式
     *
     * @param $string
     * @return bool
     */
    private function is_not_json($string)
    {
        return is_null(json_decode($string));
    }

    /**
     * 判断请求是否为POST
     *
     * @return bool
     */
    private function is_post()
    {
        if (!IS_POST)
        {
            return false;
        }
        return true;
    }

    /**
     * 判断数据包是否为空
     *
     * @param $data
     * @return bool
     */
    private function is_empty_post_data($data)
    {
        if (empty($data))
        {
            return false;
        }
        return true;
    }

    /**
     * 获取POST数据
     *
     * @param $data
     * @return mixed|void
     */
    protected function getPostResult($data)
    {
        // 判断是否为POST请求
        if (!$this->is_post())
        {
            return $this->returnResponseResult(self::ERR_90001, self::$ERRCODE_MAP[self::ERR_90001]);
        }

        // 判断POST数据是否为空
        if (!$this->is_empty_post_data($data))
        {
            return $this->returnResponseResult(self::ERR_90004, self::$ERRCODE_MAP[self::ERR_90004]);
        }

        // 判断是否为JSON数据
        if ($this->is_not_json($data))
        {
            return $this->returnResponseResult(self::ERR_90001, self::$ERRCODE_MAP[self::ERR_90001]);
        }

        return json_decode($data, true);
    }

    /**
     * 处理正确,统一返回给调用者
     *
     * @param $data
     */
    protected function returnResponseOK($data)
    {
        return $this->returnResponseResult(self::ERR_OK, self::$ERRCODE_MAP[self::ERR_OK], $data);
    }

    /**
     * 内部错误,统一返回给调用者
     *
     * @param $data
     */
    protected function returnResponseError($data)
    {
        return $this->returnResponseResult(self::ERR_FALSE, self::$ERRCODE_MAP[self::ERR_FALSE], $data);
    }

    /**
     * 返回AJAX数据
     *
     * @param $errcode
     * @param $info
     * @param array|null $data
     */
    private function returnResponseResult($errcode, $info, $data = array())
    {
        $json['code'] = $errcode;
        $json['info'] = $info;
        if (!empty($data))
        {
            $json['data'] = $data;
        }

        return $this->ajaxReturn($json);
    }

}