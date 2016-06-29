<?php
namespace Home\Controller;

use Think\Controller;
use sinacloud\sae\Storage as Storage;

class UserController extends ApplicationController
{
    public function login()
    {
        $email = trim($_POST["email"]);
        $password = trim($_POST["password"]);
        if (empty($email) || empty($password))
        {
            $this->returnResponseError("邮箱或密码为空");
        }
        else
        {
            $am = M("account");
            $exist = $am->where("email = '$email'")->count();
            $account = $am->where("email = '$email'")->find();
            if (0 == $exist)
            {
                $this->returnResponseError("该邮箱未注册");
            }
            else
            {
                if ($account['password'] == $password)
                {
                    $this->returnResponseOK($account);
                }
                else
                {
                    $this->returnResponseError("登录失败，密码错误");
                }
            }
        }
    }

    public function register()
    {
        $username = $_POST["username"];
        $realname = $_POST["realname"];
        $email = $_POST["email"];
        $password = $_POST["password"];

        $account = array(
            "username" => $username,
            "realname" => $realname,
            "email" => $email,
            "password" => $password,
            "signature" => "",
            "gender" => "1",
            "avatar" => "",
        );

        $am = M("account");
        $am->startTrans();


        $isExist = $am->where("username='$username'")->count();
        if($isExist > 0)
        {
            $this->returnResponseError("该用户名已被使用");
        }
        else
        {
            $am->add($account);
            if($am->getDbError())
            {
                $am->rollback();
                $this->returnResponseError("内部错误，请重试");
            }
            else
            {
                $am->commit();
                $this->returnResponseOK("注册成功");
            }
        }

    }

    public function testStorage()
    {
        // 方法一：在SAE运行环境中时可以不传认证信息，默认会从应用的环境变量中取
        $storage = new Storage();
        dump($storage);
        dump($storage->listBuckets(true));
        dump($storage->getBucket("public"));
        dump($storage->listBuckets());
        echo $storage->getUrl(self::MAIN_BUCKET, self::AVATAR_DIR . "/" . "ic_launcher.png");


// 方法二：如果不在SAE运行环境或者要连非本应用的storage，需要传入所连应用的"应用名:应用AccessKey"和"应用SecretKey"
//        $s = new Storage("$AppName:$AccessKey", $SecretKey);
    }

}