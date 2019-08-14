<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

$router->get('/', function () use ($router) {
    return $router->app->version();
});


$router->group(['prefix' => 'account'], function() use ($router)
{
    $router->get('prefixList', [
        'as' => 'prefixList', 'uses' => 'Account\LoginController@prefixList'
    ]);

    $router->post('getNoticeStatus', [
        'as' => 'getNoticeStatus', 'uses' => 'Account\UserController@getNoticeStatus'
    ]);

    $router->post('getUserInfo', [
        'as' => 'getUserInfo', 'uses' => 'Account\UserController@getUserInfo'
    ]);

    $router->post('login', [
        'as' => 'login', 'uses' => 'Account\LoginController@index'
    ]);

    $router->post('logout', [
        'as' => 'logout', 'uses' => 'Account\LoginController@logout'
    ]);

    $router->post('sendCode', [
        'as' => 'sendCode', 'uses' => 'Account\LoginController@sendCode'
    ]);

    $router->post('register', [
        'as' => 'register', 'uses' => 'Account\LoginController@register'
    ]);

    $router->post('modifyPwd', [
        'as' => 'modifyPwd', 'uses' => 'Account\LoginController@modifyPwd'
    ]);

    $router->post('forgetVerify', [
        'as' => 'forgetVerify', 'uses' => 'Account\LoginController@forgetVerify'
    ]);

    $router->post('resetPwd', [
        'as' => 'resetPwd', 'uses' => 'Account\LoginController@resetPwd'
    ]);

    $router->post('resetAssetsPwd', [
        'as' => 'resetAssetsPwd', 'uses' => 'Account\LoginController@resetAssetsPwd'
    ]);
    $router->post('forgetAssetsPwd', [
        'as' => 'forgetAssetsPwd', 'uses' => 'Account\LoginController@forgetAssetsPwd'
    ]);

    $router->post('modifyNotice', [
        'as' => 'modifyNotice', 'uses' => 'Account\UserController@modifyNotice'
    ]);

    $router->post('identityVerify', [
        'as' => 'identityVerify', 'uses' => 'Account\UserController@identityVerify'
    ]);

    $router->post('advice', [
        'as' => 'advice', 'uses' => 'Account\UserController@advice'
    ]);

    $router->post('modifyUserInfo', [
        'as' => 'modifyUserInfo', 'uses' => 'Account\UserController@modifyUserInfo'
    ]);

    $router->post('uploadHeadImage', [
        'as' => 'uploadHeadImage', 'uses' => 'Account\UserController@uploadHeadImage'
    ]);

    $router->post('follow', [
        'as' => 'follow', 'uses' => 'Account\UserController@follow'
    ]);

    $router->post('unFollow', [
        'as' => 'unFollow', 'uses' => 'Account\UserController@unFollow'
    ]);
    //关注我的
    $router->post('followList', [
        'as' => 'followList', 'uses' => 'Account\UserController@followList'
    ]);
    //我关注的
    $router->post('fansList', [
        'as' => 'fansList', 'uses' => 'Account\UserController@fansList'
    ]);

    //我关注的
    $router->post('searchUser', [
        'as' => 'searchUser', 'uses' => 'Account\UserController@searchUser'
    ]);




});


# 动态
$router->group(['prefix' => 'moment'], function() use ($router)
{
    $router->post('create', [
        'as' => 'create', 'uses' => 'Moment\IndexController@create',
    ]);

    $router->post('info', [
        'as' => 'info', 'uses' => 'Moment\IndexController@info',
    ]);

    $router->post('like', [
        'as' => 'like', 'uses' => 'Moment\IndexController@like',
    ]);

    $router->post('share', [
        'as' => 'like', 'uses' => 'Moment\IndexController@share',
    ]);

    $router->post('comment', [
        'as' => 'comment', 'uses' => 'Moment\IndexController@comment',
    ]);


    $router->post('commentList', [
        'as' => 'commentList', 'uses' => 'Moment\IndexController@commentList',
    ]);

    $router->post('cancelLike', [
        'as' => 'cancelLike', 'uses' => 'Moment\IndexController@cancelLike',
    ]);

    $router->post('cancelComment', [
        'as' => 'cancelComment', 'uses' => 'Moment\IndexController@cancelComment',
    ]);

    $router->post('delete', [
        'as' => 'delete', 'uses' => 'Moment\IndexController@delete',
    ]);

    //个人主页
    $router->post('profile', [
        'as' => 'profile', 'uses' => 'Moment\IndexController@profile'
    ]);

    //关注动态列表
    $router->post('followedList', [
        'as' => 'followedList', 'uses' => 'Moment\IndexController@followedList'
    ]);

    //全部动态列表
    $router->post('momentList', [
        'as' => 'momentList', 'uses' => 'Moment\IndexController@momentList'
    ]);

    //全部动态列表
    $router->post('userMoment', [
        'as' => 'userMoment', 'uses' => 'Moment\IndexController@userMoment'
    ]);
    $router->get('reportList', [
        'as' => 'reportList', 'uses' => 'Moment\IndexController@reportList'
    ]);
    $router->post('report', [
        'as' => 'report', 'uses' => 'Moment\IndexController@report'
    ]);

});


# APP
$router->group(['prefix' => 'app'], function() use ($router)
{
    $router->get('version', [
        'as' => 'version', 'uses' => 'App\IndexController@version',
    ]);

    $router->get('info', [
        'as' => 'info', 'uses' => 'App\IndexController@info',
    ]);
});


# Message
$router->group(['prefix' => 'message'], function() use ($router)
{
    $router->post('setting', [
        'as' => 'setting', 'uses' => 'Message\IndexController@setting',
    ]);

    $router->post('list', [
        'as' => 'list', 'uses' => 'Message\IndexController@list',
    ]);

    $router->post('commentAndReply', [
        'as' => 'commentAndReply', 'uses' => 'Message\IndexController@commentAndReply',
    ]);

    $router->post('count', [
        'as' => 'count', 'uses' => 'Message\IndexController@count',
    ]);

    $router->post('read', [
        'as' => 'read', 'uses' => 'Message\IndexController@read',
    ]);

    $router->post('readAll', [
        'as' => 'readAll', 'uses' => 'Message\IndexController@readAll',
    ]);

});

# Banner
$router->group(['prefix' => 'banner'], function() use ($router)
{
    $router->get('index', [
        'as' => 'index', 'uses' => 'Banner\IndexController@index',
    ]);
});


# 发现
$router->group(['prefix' => 'find'], function() use ($router)
{
    $router->get('banner', [
        'as' => 'banner', 'uses' => 'Banner\IndexController@findDynamic',
    ]);

    $router->post('recommendUser', [
        'as' => 'recommendUser', 'uses' => 'Find\RecommendController@recommendUser',
    ]);

    $router->post('recommendMoment', [
        'as' => 'recommendMoment', 'uses' => 'Find\RecommendController@recommendMoment',
    ]);

});


# API
$router->group(['prefix' => 'api'], function() use ($router)
{
    $router->get('wallet/test', [
        'as' => 'test', 'uses' => 'Api\WalletController@test',
    ]);

    $router->get('wallet/aes', [
        'as' => 'aes', 'uses' => 'Api\WalletController@aes',
    ]);
    $router->post('wallet/aes', [
        'as' => 'aes', 'uses' => 'Api\WalletController@aes',
    ]);

    $router->post('wallet/transferIn', [
        'as' => 'transferIn', 'uses' => 'Api\WalletController@transferIn',
    ]);

    $router->post('wallet/confirm', [
        'as' => 'confirm', 'uses' => 'Api\WalletController@confirm',
    ]);

    $router->post('wallet/out', [
        'as' => 'out', 'uses' => 'Asset\WalletController@out',
    ]);



});


# Asset
$router->group(['prefix' => 'asset'], function() use ($router)
{
    $router->post('balance', [
        'as' => 'balance', 'uses' => 'Asset\AssetController@balance',
    ]);

    $router->post('bill', [
        'as' => 'bill', 'uses' => 'Asset\AssetController@bill',
    ]);

    $router->post('coinList', [
        'as' => 'coinList', 'uses' => 'Asset\AssetController@coinList',
    ]);

    $router->post('address', [
        'as' => 'address', 'uses' => 'Asset\AssetController@address',
    ]);

    $router->post('transfer/list', [
        'as' => 'transfer/list', 'uses' => 'Asset\TransferController@list',
    ]);

    $router->post('transfer/info', [
        'as' => 'transfer/info', 'uses' => 'Asset\TransferController@info',
    ]);








    $router->get('redPack/rules', [
        'as' => 'redPack/rules', 'uses' => 'Asset\RedpackController@rules',
    ]);

    $router->post('redPack/config', [
        'as' => 'redPack/config', 'uses' => 'Asset\RedpackController@config',
    ]);

    $router->post('redPack/coin', [
        'as' => 'redPack/coin', 'uses' => 'Asset\RedpackController@coin',
    ]);

    $router->post('redPack/create', [
        'as' => 'redPack/create', 'uses' => 'Asset\RedpackController@create',
    ]);

    $router->post('redPack/cancel', [
        'as' => 'redPack/cancel', 'uses' => 'Asset\RedpackController@cancel',
    ]);

    $router->post('redPack/info', [
        'as' => 'redPack/info', 'uses' => 'Asset\RedpackController@info',
    ]);

    $router->post('redPack/list', [
        'as' => 'redPack/list', 'uses' => 'Asset\RedpackController@list',
    ]);

    $router->post('redPack/grabList', [
        'as' => 'redPack/grabList', 'uses' => 'Asset\RedpackController@grabList',
    ]);

    $router->post('redPack/flow', [
        'as' => 'redPack/flow', 'uses' => 'Asset\RedpackController@flow',
    ]);

    $router->post('redPack/coinSum', [
        'as' => 'redPack/coinSum', 'uses' => 'Asset\RedpackController@coinSum',
    ]);

    $router->post('redPack/active', [
        'as' => 'redPack/active', 'uses' => 'Asset\RedpackController@active',
    ]);

});
# address
$router->group(['prefix' => 'address'], function() use ($router)
{
    $router->get('del', [
        'as' => 'del', 'uses' => 'Asset\AddressController@del',
    ]);

    $router->get('list', [
        'as' => 'list', 'uses' => 'Asset\AddressController@list',
    ]);

    $router->get('create', [
        'as' => 'create', 'uses' => 'Asset\AddressController@create',
    ]);

});
# wallet
$router->group(['prefix' => 'wallet'], function() use ($router)
{
    $router->post('create', [
        'as' => 'create', 'uses' => 'Asset\WalletController@create',
    ]);
    $router->post('cancel', [
        'as' => 'cancel', 'uses' => 'Asset\WalletController@cancel',
    ]);

});
