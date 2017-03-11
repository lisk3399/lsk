<?php
// +----------------------------------------------------------------------
// | OneThink [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013 http://www.onethink.cn All rights reserved.
// +----------------------------------------------------------------------
// | Author: 麦当苗儿 <zuojiazi@vip.qq.com> <http://www.zjzit.cn>
// +----------------------------------------------------------------------

namespace Home\Model;
use Think\Model;
use User\Api\UserApi;

/**
 * 文档基础模型
 */
class MemberModel extends Model{

    /* 用户模型自动完成 */
    protected $_auto = array(
        array('login', 0, self::MODEL_INSERT),
        array('reg_ip', 'get_client_ip', self::MODEL_INSERT, 'function'),
        array('reg_time', NOW_TIME, self::MODEL_INSERT),
        array('last_login_ip', 0, self::MODEL_INSERT),
        array('last_login_time', 0, self::MODEL_INSERT),
        array('status', 1, self::MODEL_INSERT),
    );

    /**
     * 登录指定用户
     * @param  integer $uid 用户ID
     * @return boolean      ture-登录成功，false-登录失败
     */
    public function login($uid){
        $user = $this->field(true)->find($uid);
        if(!$user){ //未注册
        	$Api = new UserApi();
        	$info = $Api->info($uid);
            $user = $this->create(array('nickname' => $info[1], 'status' => 1));
            $user['uid'] = $uid;
            if(!$this->add($user)){
                $this->error = '前台用户信息注册失败，请重试！';
                return false;
            }
        } elseif(1 != $user['status']) {
            $this->error = '用户未激活或已禁用！'; //应用级别禁用
            return false;
        }

        /* 登录用户 */
        $this->autoLogin($user);

        //记录行为
        action_log('user_login', 'member', $uid, $uid);

        return true;
    }

    /**
     * 注销当前用户
     * @return void
     */
    public function logout(){
        session('user_auth', null);
        session('user_auth_sign', null);
        
        $cookie_name = C('AUTH_COOKIE');
        setcookie($cookie_name, '', -time(), '/', '', false, true);
    }

    /**
     * 自动登录用户
     * @param  integer $user 用户信息数组
     */
    public function autoLogin($user){
        /* 更新登录信息 */
        $data = array(
            'uid'             => $user['uid'],
            'login'           => array('exp', '`login`+1'),
            'last_login_time' => NOW_TIME,
            'last_login_ip'   => get_client_ip(),
        );
        $this->save($data);

        /* 记录登录SESSION和COOKIES */
        $avatar = !empty($user['avatar'])?$user['avatar']: C('USER_INFO_DEFAULT.avatar');
        $signature = !empty($user['signature'])?$user['signature']: C('USER_INFO_DEFAULT.signature');
        
        $role = 'USER';
        //如果是官方用户
        $auth_info = M('auth_group_access')->field('uid')->where(array('uid'=>$user['uid'], 'group_id'=>3))->find();
        if($auth_info['uid']) {
            $role = 'OFFICIAL';
        }
        $auth = array(
            'uid'             => $user['uid'],
            'nickname'        => $user['nickname'],
            'avatar'          => $avatar,
            'signature'          => $signature,
            'last_login_time' => $user['last_login_time'],
            'role' => $role
        );

        session('user_auth', $auth);
        session('user_auth_sign', data_auth_sign($auth));

        $cookie_name = C('AUTH_COOKIE');
        setcookie($cookie_name, $auth['uid'], time()+86400*60, '/', '', false, true);
        $_COOKIE[$cookie_name] = $auth['uid'];
    }

    /**
     * 创建三方登录用户
     * @param string $openid
     */
    public function createOauthUser($data, $reg_source) {
        $data['reg_source'] = $reg_source;
        $ret = $this->add($data);
        if($ret) {
            return $ret;
        } else {
            $this->error = '登录遇到了点麻烦，请重试';
            return false;
        }
    }
}
