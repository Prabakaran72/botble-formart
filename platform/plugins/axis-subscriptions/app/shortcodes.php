<?php namespace Axisubs;

/** @var \Herbert\Framework\Shortcode $shortcode */

//For displaying plans
$shortcode->add(
    'AxisubsAllPlans',
    __NAMESPACE__ . '\Controllers\Site\Plan@execute',
    [
        'post_id' => 'postId'
    ]
);

//For displaying selected plans
$shortcode->add(
    'AxisubsSelectedPlans',
    __NAMESPACE__ . '\Controllers\Site\Plan@selectedPlans',
    [
        'id' => 'id',
        'subscribe_btn_text' => 'subscribe_btn_text'
    ]
);

//For Loading Subscribe button [AxisubsSubscribeButton id=5]
$shortcode->add(
    'AxisubsSubscribeButton',
    __NAMESPACE__ . '\Controllers\Site\Plan@showSubscribeButton',
    [
        'id' => 'id',
        'subscribe_btn_text' => 'subscribe_btn_text'
    ]
);

//For Loading plan price price [AxisubsPlanPrice id=5]
$shortcode->add(
    'AxisubsPlanPrice',
    __NAMESPACE__ . '\Controllers\Site\Plan@showPlanPrice',
    [
        'id' => 'id'
    ]
);

//For Loading plan price Image [AxisubsPlanImage id=5]
$shortcode->add(
    'AxisubsPlanImage',
    __NAMESPACE__ . '\Controllers\Site\Plan@showPlanImage',
    [
        'id' => 'id'
    ]
);

//For displaying Subscriptions
$shortcode->add(
    'AxisubsAllSubscriptions',
    __NAMESPACE__ . '\Controllers\Site\Subscribe@execute',
    [
        'post_id' => 'postId'
    ]
);

//For displaying Profile View
$shortcode->add(
    'AxisubsMyProfile',
    __NAMESPACE__ . '\Controllers\Site\Profile@execute',
    [
        'post_id' => 'postId'
    ]
);