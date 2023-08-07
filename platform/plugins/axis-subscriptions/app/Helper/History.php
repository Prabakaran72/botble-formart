<?php
/**
 * Created by PhpStorm.
 * User: aron-destiny
 * Date: 16/7/16
 * Time: 3:44 PM
 */

namespace Axisubs\Helper;

use Corcel\Comment;

class History
{
    public static $instance = null;

    /**
     * get an instance
     * @param array
     * @return
     * * */
    public static function getInstance(array $config = array())
    {
        if (!self::$instance)
        {
            self::$instance = new self($config);
        }

        return self::$instance;
    }

    /**
     * For adding Subscription History
     * */
    public function addSubscriptionHistory($pid, $content){
        $attributes['comment_post_ID'] = $pid;
        $attributes['comment_content'] = $content;
        $attributes['comment_approved'] = 'hold';
        $attributes['comment_author'] = 'Axis Subscription';
        $attributes['comment_type'] = 'axisubs_history';
        $result = Comment::forceCreate($attributes);
        if(isset($result->comment_ID)){
            return $result->comment_ID;
        } else {
            return false;
        }
    }

    /**
     * get subscription history
     * */
    public function getSubscriptionHistory($pid){
        $result = Comment::findByPostId($pid);
        return $result;
    }
}