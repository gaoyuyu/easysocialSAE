<?php
namespace Home\Controller;

use Think\Controller;
use sinacloud\sae\Storage as Storage;
use Think\Log;

class PublicController extends ApplicationController
{
    public function showTweet()
    {
        //一页5条数据
        $pagePer = 5;
        //第*页
        $pageNum = (null == $_POST['pageNum']) ? 1 : $_POST['pageNum'];

        $aid = $_POST["aid"];
        $isPersonal = $_POST["isPersonal"];
        $personalQuery = "";
        if (null != $isPersonal)
        {
            $personalQuery = "and b.aid = " . $aid;
        }

        $tm = M("tweet");
        //总记录数
        $pageData = $tm->table("account as a,tweet as b")->where("a.aid = b.aid " . $personalQuery)->field("a.*,b.tid,b.content,b.picture,b.create_time")->order("b.tid desc")->page($pageNum, $pagePer)->select();
        $total = $tm->table("account as a,tweet as b")->where("a.aid = b.aid " . $personalQuery)->field("a.*,b.tid,b.content,b.picture,b.create_time")->order("b.tid")->count();

        if (null != $aid)
        {
            memcache_set(getMemcache(), "personal_tweet_count", $total, 0, 7100);
        }

//        dump($pageData);

        $fm = M("favorite");
        $cm = M("comment");
        foreach ($pageData as $key => $value)
        {
            $tid = $value["tid"];
            $favCount = $fm->where("tid = $tid and isfavor = 1")->count();
            $comCount = $cm->where("tid = $tid")->count();
            $pageData[$key]["favorite_count"] = $favCount;
            $pageData[$key]["comment_count"] = $comCount;
            if (null != $aid)
            {
                $isFavor = $fm->where("aid=$aid and tid=$tid")->getField("isfavor");
            }
            $pageData[$key]["isfavor"] = is_null($isFavor) ? "" : $isFavor;
        }

        $repData = array(
            "aid" => $aid,
            "total" => $total,
            "currentPage" => $pageNum,
            "pageCount" => $this->getPageCount($total, $pagePer),
            "pageData" => $pageData,
        );
        $this->returnResponseOK($repData);

    }

    public function getPageCount($total, $pagePer)
    {
        if (0 == $total % $pagePer)
        {
            $pageCount = (int)($total / $pagePer);
        }
        else
        {
            $pageCount = (int)($total / $pagePer + 1);
        }

        return $pageCount;
    }

    public function showCommentList()
    {
        //一页5条数据
        $pagePer = 5;
        //第*页
        $pageNum = (null == $_POST['pageNum']) ? 1 : $_POST['pageNum'];
        $tid = $_POST['tid'];
        $cm = M();
        $total = $cm->table("comment")->where("tid=$tid")->count();
        $orderQuery = $cm->table("comment as a,account as b")->where("a.tid=$tid and a.aid = b.aid")->field("a.cid,a.tid,a.aid,a.pid,a.comment,b.username,b.avatar,a.time")->order("a.cid desc")->buildSql();
        $subQuery = $cm->table($orderQuery . "as a")->page($pageNum, $pagePer)->buildSql();
        $commentData = $cm->table($subQuery . 'as t')->join("LEFT JOIN account ON t.pid = account.aid")->field("t.*,account.username as pusername")->order("t.cid desc")->select();

        $repData = array(
            "total" => $total,
            "currentPage" => $pageNum,
            "pageCount" => $this->getPageCount($total, $pagePer),
            "commentData" => $commentData,
        );

        $this->returnResponseOK($repData);
    }

    public function showFavList()
    {
        //一页5条数据
        $pagePer = 5;
        //第*页
        $pageNum = (null == $_POST['pageNum']) ? 1 : $_POST['pageNum'];
        $aid = $_POST['aid'];
        $fm = M("favorite");
        $subQuery = $fm->join("LEFT JOIN tweet ON tweet.tid = favorite.tid")->field("favorite.fid,favorite.tid as to_tid,favorite.aid as from_aid,tweet.aid as to_aid,favorite.time")->order("favorite.fid desc ")->where("tweet.aid = $aid")->buildSql();
        $total = $fm->table($subQuery . "as a, tweet as b ")->where("a.to_tid = b.tid")->field("a.*,b.content as to_content,b.picture as to_picture")->count();
        memcache_set(getMemcache(), "personal_fav_count", $total, 0, 7100);
        $favoriteData = $fm->table($subQuery . "as a, tweet as b ")->where("a.to_tid = b.tid")->field("a.*,b.content as to_content,b.picture as to_picture")->page($pageNum, $pagePer)->select();
        $am = M("account");
        foreach ($favoriteData as $key => $value)
        {
            $formAid = $value["from_aid"];
            $toAid = $value["to_aid"];
            $fromName = $am->where("aid = $formAid")->getField("username");
            $toName = $am->where("aid = $toAid")->getField("username");
            $fromAvatar = $am->where("aid = $formAid")->getField("avatar");
            $favoriteData[$key]["from_name"] = $fromName;
            $favoriteData[$key]["to_name"] = $toName;
            $favoriteData[$key]["to_avatar"] = $fromAvatar;
        }
        $repData = array(
            "total" => $total,
            "currentPage" => $pageNum,
            "pageCount" => $this->getPageCount($total, $pagePer),
            "favoriteData" => $favoriteData,
        );
        $this->returnResponseOK($repData);
    }

    public function reply()
    {
        $replyContent = $_POST['replyContent'];
        $replyTweet = $_POST['replyTweet'];
        $replyTo = $_POST['replyTo'];
        $replyFrom = $_POST['replyFrom'];
        $cm = M("comment");
        $cm->startTrans();

        $comment = array(
            "tid" => $replyTweet,
            "aid" => $replyFrom,
            "pid" => $replyTo,
            "comment" => $replyContent,
            "time" => date("Y-m-d H:i:s", time()),
        );
        $cm->add($comment);
        if ($cm->getDbError())
        {
            $cm->rollback();
            $this->returnResponseError("回复失败");
        }
        else
        {
            $cm->commit();
            $this->returnResponseOK("回复成功");
        }
    }

    public function doFavor()
    {
        $tid = $_POST["tid"];
        $aid = $_POST["aid"];
        $fm = M("favorite");
        $insert = array(
            "aid" => $aid,
            "tid" => $tid,
            "isfavor" => 1,
            "time" => date("Y-m-d H:i:s", time())
        );
        $fm->startTrans();
        $fm->add($insert);
        if ($fm->getDbError())
        {
            $fm->rollback();
            $this->returnResponseError("点赞失败");
        }
        else
        {
            $fm->commit();
            $this->returnResponseOK("点赞成功");
        }
    }

    public function getCountInfo()
    {
        $tweetCount = memcache_get(getMemcache(), "personal_tweet_count");
        $favCount = memcache_get(getMemcache(), "personal_fav_count");

        $countData = array(
            "personal_tweet_count" => $tweetCount,
            "personal_fav_count" => $favCount,
        );
        $this->returnResponseOK($countData);
    }

    public function upload()
    {
        $storage = new Storage();
        $file = $storage->inputFile($_FILES['image']['tmp_name']);
        $uploadStatus = $storage->putObject($file, self::MAIN_BUCKET . "/" . self::AVATAR_DIR, $_FILES['image']['name']);
        if ($uploadStatus)
        {
            $this->returnResponseOK($storage->getUrl(self::MAIN_BUCKET, self::AVATAR_DIR . '/' . $_FILES['image']['name']));
        }
        else
        {
            $this->returnResponseError("上传失败");
        }
    }

    public function updatePerson()
    {
        $aid = $_POST['aid'];
        $key = $_POST['key'];
        $value = $_POST['value'];
        $am = M("account");
        $am->startTrans();

        $data[$key] = $value;
        $am->where("aid = $aid")->save($data);

        if ($am->getDbError())
        {
            $am->rollback();
            $this->returnResponseError("更新账户信息失败");
        }
        else
        {
            $am->commit();
            $data = array(
                "key" => $key,
                "value" => $value,
                "status" => "更新账号信息成功",
            );
            $this->returnResponseOK($data);
        }
    }

    public function publishTweet()
    {
        $storage = new Storage();
        $aid = $_POST["aid"];
        $content = $_POST["content"];

        if (empty($_FILES['image']))
        {
            $picture = "";
        }
        $file = $storage->inputFile($_FILES['image']['tmp_name']);
        $uploadStatus = $storage->putObject($file, self::MAIN_BUCKET . "/" . self::TWEETIMG_DIR, $_FILES['image']['name']);
        if ($uploadStatus)
        {
            $picture = $storage->getUrl(self::MAIN_BUCKET, self::TWEETIMG_DIR . '/' . $_FILES['image']['name']);
        }

        $tweet = array(
            "aid" => $aid,
            "content" => $content,
            "picture" => $picture,
            "create_time" => date("Y-m-d H:i:s", time())
        );

        $tm = M("tweet");
        $tm->startTrans();
        $tm->add($tweet);

        if (($picture == null) && ($tm->getDbError()))
        {
            $tm->rollback();
            $this->returnResponseError("发表失败，请重试");
        }
        else
        {
            $tm->commit();
            $this->returnResponseOK("发表成功");
        }


    }

    public function showTweetRank()
    {
        $tm = M("tweet");
        $fm = M("favorite");
        $tids = $tm->table("tweet as a,account as b")->where("a.aid = b.aid")->field("a.*,b.*")->select();
        foreach ($tids as $key => $value)
        {
            $tid = $value["tid"];
            $count = $fm->where("tid = $tid")->count();
            $tids[$key]["count"] = $count;
            $counts[$key] =$count;
        }
        array_multisort($counts, SORT_DESC, $tids);
        $this->returnResponseOK($tids);

    }

}