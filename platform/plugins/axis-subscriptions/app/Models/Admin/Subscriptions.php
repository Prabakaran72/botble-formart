<?php
/**
 * Created by PhpStorm.
 * User: aron-destiny
 * Date: 12/7/16
 * Time: 1:25 PM
 */

namespace Axisubs\Models\Admin;
use Axisubs\Helper;
use Corcel\Post;
use Herbert\Framework\Models\PostMeta;
use Axisubs\Models\Site\Plans;
use Events\Event;
use Axisubs\Helper\ManageUser;
use Axisubs\Helper\AxisubsRedirect;
use Axisubs\Helper\DateFormat;
use Herbert\Framework\Notifier;
use Herbert\Framework\Session;

class Subscriptions extends Post{
    /**
     * The table associated with the model.
     *
     * @var string
     */

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['title'];

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    public static $_total;
    public static $_start;
    public static $_limit;
    
    public static function populateStates($post){
        if(isset($post['limitstart'])){
            Subscriptions::$_start = $post['limitstart'];
            Session()->set('subscription.limitstart', $post['limitstart']);
        } else {
            Subscriptions::$_start = Session()->get('subscription.limitstart', 0);
        }
        if(isset($post['limit']) && $post['limit']){
            Subscriptions::$_limit = $post['limit'];
            Session()->set('subscription.limit', $post['limit']);
        } else {
            Subscriptions::$_limit = Session()->get('subscription.limit', 10);
        }
    }

    /**
     * get pagination start and limit
     * */
    public static function getPaginationStartAndLimit($total = 0){
        Subscriptions::$_total = $total;
        $balance = Subscriptions::$_total-(Subscriptions::$_limit*Subscriptions::$_start);
        if($balance < Subscriptions::$_limit){
            $limit = $balance;
        } else {
            $limit = Subscriptions::$_limit;
        }

        // For getting start date
        $rem = $total % Subscriptions::$_limit;
        $totalPage = floor($total / Subscriptions::$_limit);
        if($rem>0){
            $totalPage++;
        }
        $result['start'] = Subscriptions::$_start;

        $result['start'] += 1;
        if($totalPage <= $result['start']){
            $result['start'] = 0;
        }

//        $result['start'] = Subscriptions::$_start;
        $result['limit'] = $limit;

        return $result;
    }


    // Load all Subscriptions
    public static function all($columns = ['*']){
        //$postO = new Post();
        //$totalItem = Post::where('post_type', 'axisubs_subscribe')->orderBy('ID','desc')->get();
        $totalItem = Post::type('axisubs_subscribe')->orderBy('ID', 'desc')->get();

        //get pagination start and limit
        $pageLimit = Subscriptions::getPaginationStartAndLimit(count($totalItem));
        //get limited data
        $items = $totalItem->forPage($pageLimit['start'], $pageLimit['limit']);
        if(count($items)){
            foreach ($items as $key => $item){
                $item->meta = $item->meta()->pluck('meta_value', 'meta_key')->toArray();
                if(isset($item->meta[$item->ID.'_axisubs_subscribe_plan_id'])) {
                    $plan = Subscriptions::loadPlan($item->meta[$item->ID . '_axisubs_subscribe_plan_id']);
                    $item->plan = $plan;
                } else {
                    unset($items[$key]);
                }

            }
        }
        $items = $items->sortByDesc('ID');
        return $items;
    }

    //get Total
    public static function getTotal(){
        $postO = new Post();
        $totalItem = $postO->all()->where('post_type', 'axisubs_subscribe');
        return count($totalItem);
    }

    //Load Single Subscriber
    public static function loadSubscriber($id){
        $item = Post::where('post_type', 'axisubs_subscribe')->find($id);
        if($item) {
            $meta = $item->meta()->pluck('meta_value', 'meta_key')->toArray();
            $item->meta = $meta;
        }
        return $item;
    }

    //load plan
    public static function loadPlan($id){
        $plan = Plans::loadPlan($id, 1);
        return $plan;
    }

    /**
     * Get subscriptions based on plan
     * */
    public static function getSubscriptionBasedOnPlan($planId){
        $subscribers = PostMeta::where('meta_key','like','%_axisubs_subscribe_plan_id')
            ->where('meta_value', $planId)
            ->pluck('post_id');

        return $subscribers;
    }

    /**
     * Delete Subscription before deleting plans
     * */
    public static function deleteSubscriptionsOnBeforePlanDeleted($planId){
        $subscriptions = self::getSubscriptionBasedOnPlan($planId);
        if(count($subscriptions)){
            foreach($subscriptions as $key => $value){
                self::deleteSubscriptions($value);
            }
        }
    }

    /**
     * For deleting a Subscription
     * */
    public static function deleteSubscriptions($id){
        $postDB = Post::where('post_type', 'axisubs_subscribe')->find($id);
        if(!empty($postDB)) {
            //On before delete trigger
            Event::trigger('onBeforeSubscriptionDelete', array($id));

            //Remove user Role
            $subscriptionPrefix = '_axisubs_subscribe_';
            $planKey = $postDB->ID . $subscriptionPrefix . 'plan_id';
            $userKey = $postDB->ID . $subscriptionPrefix . 'user_id';
            $plan = Subscriptions::loadPlan($postDB->meta->$planKey, 1);
            $planPrefix = '_axisubs_plans_';
            $removeRoleskey = $plan->ID . $planPrefix . 'remove_roles';
            if (isset($plan->meta[$removeRoleskey]) && $plan->meta[$removeRoleskey] != '') {
                ManageUser::getInstance()->removeUserRole($postDB->meta->$userKey, explode(',', $plan->meta[$removeRoleskey]));
            }

            $postDB->meta()->delete();
            $result = $postDB->delete();
            if ($result) {
                Event::trigger('onAfterSubscriptionDelete', array($id));
            }
            return $result;
        } else {
            AxisubsRedirect::redirect('?page=subscriptions-index');
        }
    }

    /**
     * For activating a Subscription
     * */
    public static function activateSubscriptions($id){
        $subscription = Subscriptions::loadSubscriber($id);
        if(!empty($subscription)) {
            $subscriptionPrefix = $subscription->ID.'_'.$subscription->post_type.'_';
            if($subscription->meta[$subscriptionPrefix.'status'] != 'ACTIVE'){
                $date = DateFormat::getInstance();
                $today = $date->getCarbonDate();
                $todayTime = strtotime($today);
                $startDate = $date->getCarbonDate($subscription->meta[$subscriptionPrefix.'start_on']);
                $startTime = strtotime($startDate);
                $endDate = $date->getCarbonDate($subscription->meta[$subscriptionPrefix.'end_on']);
                $endTime = strtotime($endDate);
                if($startTime >= $todayTime && $endTime <= $todayTime){
                    $activeSubscription = Plans::getActiveSubscriptionsBasedOnPlan($subscription->meta[$subscriptionPrefix.'plan_id'], $subscription->meta[$subscriptionPrefix.'user_id']);
                    if(count($activeSubscription)){
                        Notifier::error(esc_html__('Already has an active subscription for this plan', 'axis-subscriptions'));
                        return false;
                    } else {
                        return Plans::getInstance()->markActive($subscription->ID);
                    }
                } else {
                    Notifier::error(esc_html__('Date doesn\'t matches to activate', 'axis-subscriptions'));
                    return false;
                }
            }
        }
        AxisubsRedirect::redirect('?page=subscriptions-index');
    }

    /**
     * For ark as future Subscription
     * */
    public static function futureSubscriptions($id){
        $subscription = Subscriptions::loadSubscriber($id);
        if(!empty($subscription)) {
            $subscriptionPrefix = $subscription->ID.'_'.$subscription->post_type.'_';
            if($subscription->meta[$subscriptionPrefix.'status'] != 'FUTURE'){
                $date = DateFormat::getInstance();
                $today = $date->getCarbonDate();
                $todayTime = strtotime($today);
                $startDate = $date->getCarbonDate($subscription->meta[$subscriptionPrefix.'start_on']);
                $startTime = strtotime($startDate);
                $endDate = $date->getCarbonDate($subscription->meta[$subscriptionPrefix.'end_on']);
                $endTime = strtotime($endDate);
                if($startTime > $todayTime && $endTime > $todayTime){
                    return Plans::getInstance()->markFuture($subscription->ID);
                } else {
                    Notifier::error(esc_html__('Date doesn\'t matches to mark as future', 'axis-subscriptions'));
                    return false;
                }
            }
        }
        AxisubsRedirect::redirect('?page=subscriptions-index');
    }

    /**
     * For cancel a Subscription
     * */
    public static function cancelSubscriptions($id){
        $subscription = Subscriptions::loadSubscriber($id);
        if(!empty($subscription)) {
            $subscriptionPrefix = $subscription->ID.'_'.$subscription->post_type.'_';
            if($subscription->meta[$subscriptionPrefix.'status'] != 'CANCELED'){
                $result = Plans::getInstance()->markCancel($subscription->ID);
                if($result){
                    if(isset($subscription->meta[$subscriptionPrefix.'payment_processor'])){
                        $payment_processor = $subscription->meta[$subscriptionPrefix.'payment_processor'];
                        Event::trigger($payment_processor.'_cancelSubscription', array($subscription));
                    }
                }
                return $result;
            }
        }
        AxisubsRedirect::redirect('?page=subscriptions-index');
    }

    /**
     * For mark as pending
     * */
    public static function pendingSubscriptions($id){
        $subscription = Subscriptions::loadSubscriber($id);
        if(!empty($subscription)) {
            $subscriptionPrefix = $subscription->ID.'_'.$subscription->post_type.'_';
            if($subscription->meta[$subscriptionPrefix.'status'] != 'PENDING'){
                return Plans::getInstance()->markPending($subscription->ID);
            }
        }
        AxisubsRedirect::redirect('?page=subscriptions-index');
    }

    public static function loadPlanSelectbox($selected = '', $name = 'axisubs_plan', $id = 'axisubs_plan'){
        $plans = Plans::getItems(0);
        if(count($plans)){
            $select = '<select name="'.$name.'" id="'.$id.'" class="required" onchange="autoPopulatePlanDetails(this.value);">';
            $select .= '<option value="">Select Plan</option>';
            foreach ($plans as $key => $plan){
                if($selected == $plan->ID){
                    $selectedText = ' selected="selected"';
                } else {
                    $selectedText = '';
                }
                $planMeta = $plan->meta;
                $planPrefix = $plan->ID.'_'.$plan->post_type.'_';
                $select .= '<option value="'.$plan->ID.'"'.$selectedText.'>'.$planMeta[$planPrefix.'name'].'</option>';
            }
            $select .= '</select>';
        } else {
            $select = 'No Plans';
        }

        return $select;
    }
    
    public function addSubscription($user_id, $plan_id, $sub_id = 0, $start_on = ''){
        $planObj = Plans::getInstance();
        $result = $planObj->addSubscriptionThroughBackend($user_id, $plan_id, $sub_id, $start_on);
        return $result;
    }
}