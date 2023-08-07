<?php
/**
 * Created by PhpStorm.
 * User: aron-destiny
 * Date: 12/7/16
 * Time: 1:25 PM
 */

namespace Axisubs\Models\Admin;

use Corcel\Post;
use Herbert\Framework\Models\PostMeta;

class InstallationWizard extends Post
{
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

    public static $instance = null;

    public static function getInstance(array $config = array())
    {
        if (!self::$instance)
        {
            self::$instance = new self($config);
        }

        return self::$instance;
    }

    /**
     * To load paypal config
     * */
    public function getPaypalConfig()
    {
        $item = parent::all()->where('post_type', 'axis_paypal_config')->first();
        if(!empty($item)) {
            if ($item->meta() != null) {
                $item->meta = $item->meta()->pluck('meta_value', 'meta_key')->toArray();
            }
        }
        return $item;
    }

    /**
     * Save Paypal Config
     * */
    public function savePaypalConfig($post)
    {
        $postDB = \Corcel\Post::where('post_type', 'axis_paypal_config')->get();
        $postTable = $postDB->first();
        if ($postDB->count() == 0) {
            $postTable = new Post();
            $postTable->post_name = 'axisubs_app_paypal_config';
            $postTable->post_title = 'axisubs_app_paypal_config';
            $postTable->post_type = 'axis_paypal_config';
            $postTable->save();
            $postDB = \Corcel\Post::where('post_type', 'axis_paypal_config')->get();
            $postTable = $postDB->first();
        }
        foreach ($post['axisubs']['payment'] as $key => $val) {
            $key = $postTable->ID . '_'.'axis_paypal_config'.'_' . $key;
            $postTable->meta->$key = $val;
        }
        $result = $postTable->save();

        return $result;
    }
}