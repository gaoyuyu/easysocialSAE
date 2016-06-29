# easysocialSAE
easysocialSAE

####How to set Local Data: use F()

```Java
function setLocalData($key,$value)
{
    F($key,$value);
}

function getLocalData($key)
{
    $value = F($key);
    return $value;
}

//        memcache_set(getMemcache(), "personal_fav_count", $total, 0, 7100);
        setLocalData("personal_fav_count",$total);
        
//        $tweetCount = memcache_get(getMemcache(), "personal_tweet_count");
//        $favCount = memcache_get(getMemcache(), "personal_fav_count");
        $tweetCount = getLocalData("personal_tweet_count");
        $favCount = getLocalData("personal_fav_count");

```