<?php
/**
 * Created by PhpStorm.
 * User: aron-destiny
 * Date: 16/7/16
 * Time: 3:44 PM
 */

namespace Axisubs\Helper;

class PlanTypes
{
    /**
     * To load Plan Type
     * */
    public static function getPlanTypeText($type){
        $planTypes = PlanTypes::getPlanTypeInArray();
        if(isset($planTypes[$type])){
            return $planTypes[$type];
        } else {
            return $type;
        }
    }

    /**
     * To load plan type in array
     * */
    public static function getPlanTypeInArray(){
       return array('free' => 'Free',
                        'renewal' => 'Non-recurring',
                        'renewal_with_trial' => 'Non-recurring with trial',
                        'recurring' => 'Recurring',
                        'recurring_with_trial' => 'Recurring with trial');
    }
}