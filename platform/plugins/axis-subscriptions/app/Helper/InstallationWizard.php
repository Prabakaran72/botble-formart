<?php
/**
 * Created by PhpStorm.
 * User: aron-destiny
 * Date: 16/7/16
 * Time: 3:44 PM
 */

namespace Axisubs\Helper;

class InstallationWizard
{
    public static $instance = null;

    /**
     * get an instance
     * */
    public static function getInstance(array $config = array())
    {
        if (!self::$instance)
        {
            self::$instance = new self($config);
        }

        return self::$instance;
    }

    /**
     * check to execute wizard
     * */
    public function checkToExecuteWizard(){
        
    }
}