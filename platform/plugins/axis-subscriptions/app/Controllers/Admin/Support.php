<?php
/**
 * Created by PhpStorm.
 * User: aron-destiny
 * Date: 12/7/16
 * Time: 12:52 PM
 */
namespace Axisubs\Controllers\Admin;

use Herbert\Framework\Http;
use Herbert\Framework\Notifier;
use Axisubs\Controllers\Controller;

class Support extends Controller{
    
    public $_controller = 'Support';

    /**
     * Default page
     * */
    public function index()
    {
        $pagetitle = "Support";
        return view('@Axisubs/Admin/support/list.twig', compact('pagetitle'));
    }

    /**
     * Shortcode 
     * */
    public function shortCodes()
    {
        $pagetitle = "Shortcodes";
        return view('@Axisubs/Admin/support/shortcodes.twig', compact('pagetitle'));
    }
}