<?php
/**
 * Created by PhpStorm.
 * User: aron-destiny
 * Date: 16/7/16
 * Time: 3:44 PM
 */

namespace Axisubs\Helper;

class Duration
{
    public static $instance = null;

    /**
     * get an instance
     * @param array $config
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

    public function getDurationInFormat($period, $unit){
        $text = 'Days';
        switch($unit){
            case 'W':
                $text = 'Weeks';
                break;
            case 'M':
                $text = 'Months';
                break;
            case 'Y':
                $text = 'Years';
                break;
        }
        $duration = $period.' '.$text;
        return $duration;
    }

    public function getDurationInFormatInArray(){
        $duration = array('D' => 'Days',
                        'W' => 'Weeks',
                        'M' => 'Months',
                        'Y' => 'Years');
        return $duration;
    }

    public function getDurationInDays($period, $unit){
        $days = $period;
        switch($unit){
            case 'W':
                $days = $period*7;
                break;
            case 'M':
                $days = $period*30;
                break;
            case 'Y':
                $days = $period*365;
                break;
        }
        return $days;
    }
}