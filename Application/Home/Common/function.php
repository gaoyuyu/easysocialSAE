<?php
/**
 * Created by PhpStorm.
 * User: gaoyy
 * Date: 2016/4/15/0015
 * Time: 12:59
 */

function getMemcache()
{
    $mmc = memcache_init();
    return $mmc;
}