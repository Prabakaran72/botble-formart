<?php
/**
 * Created by PhpStorm.
 * User: aron-destiny
 * Date: 16/7/16
 * Time: 3:44 PM
 */

namespace Axisubs\Helper;
use Axisubs\Models\Site\Plans;
use Corcel\Post;
use Herbert\Framework\Models\PostMeta;

class ManageUser
{
    public static $instance = null;

    /**
     * get an instance
     * */
    public static function getInstance(array $config = array())
    {
        if (!self::$instance) {
            self::$instance = new self($config);
        }

        return self::$instance;
    }

    /**
     * Get WP User Details
     * */
    public function getUserDetails($id = 0)
    {
        //For access get user function
        if (is_admin()) require_once(ABSPATH . 'wp-includes/pluggable.php');

        if($id) {
            $user = get_userdata($id);
        } else {
            $user = wp_get_current_user();
        }
        return $user;
    }

    /**
     * Add role for an user
     * */
    public function addUserRole($id, $roles){
        if($id && (is_array($roles) || $roles != '')) {
            $user = $this->getUserDetails($id);
            if (is_array($roles)) {
                foreach ($roles as $role) {
                    $user->add_role($role);
                }
            } else {
                $user->add_role($roles);
            }
            return true;
        } else {
            return false;
        }
    }

    /**
     * Add role for an user
     * */
    public function removeUserRole($id, $roles){
        if($id && (is_array($roles) || $roles != '')) {
            $user = $this->getUserDetails($id);
            $active_roles = $this->getActiveSubscriptionRoles($id);
            if(is_array($roles)){
                foreach ($roles as $role){
                    if(!in_array($role, $active_roles)){
                        $user->remove_role($role);
                    }
                }
            } else {
                if(!in_array($roles, $active_roles)){
                    $user->remove_role($roles);
                }
            }
            return true;
        } else {
            return false;
        }
    }

    /**
     * Get active subscription Roles
     * */
    protected function getActiveSubscriptionRoles($id){
        return $this->getActiveAndTrialSubscriptions($id);
    }

    /**
     * Get active subscriptions
     * */
    protected function getActiveAndTrialSubscriptions($user_id){
        $subscribers = PostMeta::where('meta_key','like','%_axisubs_subscribe_user_id')
            ->where('meta_value', $user_id)
            ->pluck('post_id');
        $roles = array();
        if(count($subscribers)){
            foreach($subscribers as $key => $value){
                $item = Post::where('post_type', 'axisubs_subscribe')->find($value);
                $itemPrefix = $item->ID.'_'.$item->post_type.'_';
                $meta = $item->meta()->pluck('meta_value', 'meta_key')->toArray();
                if($meta[$itemPrefix.'status'] == 'ACTIVE' || $meta[$itemPrefix.'status'] == 'TRIAL'){
                    $plan = Plans::loadPlan($meta[$itemPrefix.'plan_id'], 1);
                    $planPrefix = $plan->ID.'_'.$plan->post_type.'_';
                    if(isset($plan->meta[$planPrefix.'add_roles']) && $plan->meta[$planPrefix.'add_roles'] != ''){
                        $activeRoles = explode(',', $plan->meta[$planPrefix.'add_roles']);
                        if(count($activeRoles)){
                            foreach ($activeRoles as $activeRole){
                                $roles[] = $activeRole;
                            }
                        }
                    }
                }
            }
        }
        return $roles;
    }
}