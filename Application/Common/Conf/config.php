<?php
// +----------------------------------------------------------------------
// | OneThink [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013 http://www.onethink.cn All rights reserved.
// +----------------------------------------------------------------------
// | Author: 麦当苗儿 <zuojiazi@vip.qq.com> <http://www.zjzit.cn>
// +----------------------------------------------------------------------

/**
 * 系统配文件
 * 所有系统级别的配置
 */
return array(
    /* 站点配置 */
    'WEBSITE_URL' => 'http://42.120.11.155',
    //'WEBSITE_URL' => 'http://192.168.20.3',
    
    /* 模块相关配置 */
    'AUTOLOAD_NAMESPACE' => array('Addons' => ONETHINK_ADDON_PATH), //扩展模块列表
    'DEFAULT_MODULE'     => 'Home',
    'MODULE_DENY_LIST'   => array('Common','User','Admin','Install'),
    //'MODULE_ALLOW_LIST'  => array('Home','Admin'),

    /* 系统数据加密设置 */
    'DATA_AUTH_KEY' => 'wM>kP+KFoY0jp*_iA]}@Z|1(e7lcV,:T%&8ER!$H', //默认数据加密KEY

    /* 用户相关设置 */
    'USER_MAX_CACHE'     => 1000, //最大缓存用户数
    'USER_ADMINISTRATOR' => 1, //管理员用户ID

    /* URL配置 */
    'URL_CASE_INSENSITIVE' => true, //默认false 表示URL区分大小写 true则表示不区分大小写
    'URL_MODEL'            => 0, //URL模式
    'VAR_URL_PARAMS'       => '', // PATHINFO URL参数变量
    'URL_PATHINFO_DEPR'    => '/', //PATHINFO URL分割符

    /* 全局过滤配置 */
    'DEFAULT_FILTER' => 'strip_tags,htmlspecialchars', //全局过滤函数

    /* 数据库配置 */
    'DB_TYPE'   => 'mysql', // 数据库类型
    'DB_HOST'   => '127.0.0.1', // 服务器地址
    'DB_NAME'   => 'doubihai', // 数据库名
    'DB_USER'   => 'root', // 用户名
    'DB_PWD'    => 'fo9B8OMD76Z2',  // 密码
    'DB_PORT'   => '3306', // 端口
    'DB_PREFIX' => 'dbh_', // 数据库表前缀
    'DB_PREFIXXB' => 'dbh_xb_', // 数据库表前缀

    /* 文档模型配置 (文档模型核心配置，请勿更改) */
    'DOCUMENT_MODEL_TYPE' => array(2 => '主题', 1 => '目录', 3 => '段落'),
    
    /* 短信网关相关配置mob.com 文档：http://wiki.mob.com/webapi2-0/ */
    'MOB_VERIFY_URL' => 'https://webapi.sms.mob.com/sms/verify',
    'MOB_APP_KEY' => '11f3481134820',
    'MOB_APP_KEY_ANDROID' => '1693e93843627',
    
    /* API每次请求最大数量 */
    'API_MAX_ROWS' => 50,
    'AUTH_COOKIE' => 'DBH_AUTH',
    
    /* 默认用户信息 */
    'USER_INFO_DEFAULT' => array(
        'avatar' => 'http://vod.doushow.com/dbh_avatar_default.png',
        'signature' => '这家伙很懒，什么签名都没写~'
    ),
    
    /* 发送消息类型 */
    'MESSAGE_TYPE' => array(
        'ADD_GROUP' => 'ADD_GROUP',
        'LIKE' => 'LIKE',
        'AT' => 'AT',
        'COMMENT' => 'COMMENT',
        'FRIENDS' => 'FRIENDS',
        'SYSTEM' => 'SYSTEM',
        'WAP' => 'WAP'
    ),
    /* 七牛配置文件 */
    'QINIU' => array(
        'img_domain'=>'http://vod.doushow.com/',
        'api'=>'http://api.qiniu.com/',
        'accessKey'=>'BX3FxNDH3aFGwGSb8Yue745EgiumlqGpqthQ8x1u',
        'secrectKey'=>'JqFwvXfT8TuLIb_UxyohIdnIS8oqzY-I9ifMqHyc',
        'bucket'=>'doushow',
        'live_publish_domian'=>'pili-publish.bipai.tv',
        'live_rtmp_play_domain'=>'pili-live-rtmp.bipai.tv',
        'live_snapshot_domain'=>'pili-live-snapshot.bipai.tv',
        'live_hub'=>'bipai-streams',
        'live_storage'=>'http://l-storage.bipai.tv'
    ),
    'GETUI' => array(
        'APPID' => 'OS2C1zlfph5zrwrdjFr882',
        'APPKEY' => 'XfkfLDPBxUAalEV5PGr8y9',
        'MASTERSECRET' => 'HZpICc3tUpA5M6rzpHoCOA'
    ),
    'TX_YUNTONGXIN' => array(
        'APPID' => '1400022822',
        'ACCOUNTTYPE' => '9946',
        'PRIVATEKEY_PATH' => '/usr/local/tools/tx_keys/private_key'
    )
);
