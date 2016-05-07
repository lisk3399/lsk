<?php
// +----------------------------------------------------------------------
// | OneThink [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013 http://www.onethink.cn All rights reserved.
// +----------------------------------------------------------------------
// | Author: 麦当苗儿 <zuojiazi@vip.qq.com> <http://www.zjzit.cn>
// +----------------------------------------------------------------------

namespace Home\Controller;
use User\Api\UserApi;

/**
 * 用户控制器
 * 包括用户中心，用户登录及注册
 */
class UserController extends HomeController {

	/* 用户中心首页 */
	public function index(){
		
	}

	/* 用户注册接口 */
	public function register(){
        if(!C('USER_ALLOW_REGISTER')){
            $this->renderFailed('注册已关闭');
        }
		if(IS_POST){ //注册用户
		    $mobile = I('post.mobile', '', 'trim');
		    $password = I('post.password', '', 'trim');
		    $repassword = I('post.repassword', '', 'trim');
		    $verify = I('post.verify', '', 'trim');
		    
			//短信验证
            $ret = $this->sms_verify($mobile, $verify);
            if(!ret) {
                $this->renderFailed('短信验证失败~');
            }
            
			/* 调用注册接口注册用户 */
            $User = new UserApi;
			$uid = $User->register($mobile, $password, $repassword, $verify);
			if(0 < $uid){ //注册成功
				//TODO: 发送验证邮件
				$username = '用户_'.$uid;
				$User->updateUsername($uid, $username);
				$data['uid'] = $uid;
				
				//注册成功后登录
				$uid = $User->login($mobile, $password);
				if(0 < $uid){ //UC登录成功
				    /* 登录用户 */
				    $Member = D('Member');
				    if($Member->login($uid)){ //登录用户
				        $data = session('user_auth');
				        $data['session_id'] = session_id();
				    }
				}
				$this->renderSuccess('注册成功！', $data);
			} else { //注册失败，显示错误信息
			    $this->renderFailed($this->showRegError($uid));
			}
		}
	}

	//短信验证
	private function sms_verify($mobile, $verify){
	    $MOB_VERIFY_URL = C('MOB_VERIFY_URL');
	    $MOB_APP_KEY = C('MOB_APP_KEY');
	    $fields = array(
	        'appkey' => $MOB_APP_KEY,
	        'phone' => $mobile,
	        'zone' => '86', //TODO 港澳台地区处理
	        'code' => $verify
	    );
	    $response = postRequest($MOB_VERIFY_URL, $fields);
	    $response = json_decode($response, TRUE);
	    $resultCode = $response['status'];
	    
	    switch ($resultCode) {
	        case '200':
	            return TRUE;
	            break;
	        case '457':
	            $this->renderFailed('手机号码格式错误');
	            break;
	        case '467':
	            $this->renderFailed('请求校验验证码频繁（5分钟内同一号码最多只能校验三次）');
	            break;
	        case '468':
	            $this->renderFailed('验证码错误');
	            break;
	        default:
	            $this->renderFailed('短信验证失败');
	    }
	}
	
	/* 用户是否登录 */
	public function isLogin() {
	    if(IS_POST) {
    	    if(is_login()){
    	        $this->renderSuccess('您已经登录');
    	    } else {
    	        $this->renderFailed('您未登录');
    	    }
	    }
	}
	
	/* 登录接口 */
	public function login(){
	    if(is_login()){
	        $this->renderFailed('您已经登录');
	    }
		if(IS_POST){ //登录验证
		    $mobile = I('post.mobile', '', 'trim');
		    $password = I('post.password', '', 'trim');
		    if(empty($mobile)||empty($password)) {
		        $this->renderFailed('请输入完整登录信息');
		    }
		    
			/* 调用UC登录接口登录 */
			$user = new UserApi;
			$uid = $user->login($mobile, $password);
			if(0 < $uid){ //UC登录成功
				/* 登录用户 */
				$Member = D('Member');
				if($Member->login($uid)){ //登录用户
				    $data = session('user_auth');
				    $data['session_id'] = session_id();
					$this->renderSuccess('登录成功', $data);
				} else {
				    $this->renderFailed($this->showRegError($uid));
					$this->error($Member->getError());
				}

			} else { //登录失败
				switch($uid) {
					case -1: $error = '用户不存在或被禁用！'; break; //系统级别禁用
					case -2: $error = '密码错误！'; break;
					default: $error = '未知错误！'; break; // 0-接口参数错误（调试阶段使用）
				}
				$this->renderFailed($error);
			}
		}
	}

	/* 退出登录 */
	public function logout(){
		if(is_login()){
			D('Member')->logout();
			$this->renderSuccess('退出成功');
		} else {
			$this->renderFailed('您还未登录');
		}
	}
	
	/* 获取用户信息 */
	public function userInfo(){
	    if(is_login()) {
	        $uid = session('user_auth.uid');
	        $User = new UserApi;
	        $userinfo = $User->info($uid);
            
	        $this->renderSuccess('获取用户信息', $userinfo);
	    }
	}

	/* 修改用户信息 */
	public function updateUser(){
	    if(IS_POST) {
    	    if(is_login()) {
    	        $uid = session('user_auth.uid');
    	        $data['nickname'] = I('post.nickname', '', 'trim');
    	        $data['avatar'] = I('post.avatar', '', 'trim');
    	        $data['signature'] = I('post.signature', '', 'trim');
    	        $data['sex'] = I('post.sex', '0', 'intval');
    	        $data['birthday'] = I('post.birthday', '', 'trim');
    	        
    	        if(empty($data['nickname'])) {
    	            $this->renderFailed('昵称不为空');
    	        }
    	        if(empty($data['avatar'])) {
    	            $this->renderFailed('头像不为空');
    	        }
    	        
    	        //更新用户信息
    	        $Member = D('Member');
    	        $data = $Member->create($data);
    	        if($data){
    	            $ret = $Member->where(array('uid'=>$uid))->save($data);
    	            if($ret) {
    	                $this->renderSuccess('保存成功');
    	            }
    	        }
    	    }
	    }
	}
	
	/* 验证码，用于登录和注册 */
	public function verify(){
		$verify = new \Think\Verify();
		$verify->entry(1);
	}

	/**
	 * 获取用户注册错误信息
	 * @param  integer $code 错误编码
	 * @return string        错误信息
	 */
	private function showRegError($code = 0){
		switch ($code) {
			case -1:  $error = '手机号码不能为空'; break;
			case -2:  $error = '手机格式不正确'; break;
			case -3:  $error = '该手机号已经注册过'; break;
			case -4:  $error = '密码长度在6-20个字符，包含字母和数字'; break;
			case -5:  $error = '两次密码不一致'; break;
			case -6: $error = '手机验证码不能为空'; break;
			case -7: $error = '该手机号码被禁止注册'; break;
			default:  $error = '未知错误';
		}
		return $error;
	}


    /**
     * 修改密码提交
     * @author huajie <banhuajie@163.com>
     */
    public function updatePwd(){
		if ( !is_login() ) {
			$this->renderFailed( '您还没有登陆');
		}
        if ( IS_POST ) {
            //获取参数
            $uid        =   is_login();
            $oldPwd   =   I('post.old', '', 'trim');
            $repassword = I('post.repassword', '', 'trim');
            $data['password'] = I('post.password', '', 'trim');
            empty($oldPwd) && $this->renderFailed('请输入原密码');
            empty($data['password']) && $this->renderFailed('请输入新密码');
            empty($repassword) && $this->renderFailed('请输入确认密码');

            if($data['password'] !== $repassword){
                $this->renderFailed('您输入的新密码与确认密码不一致');
            }

            $Api = new UserApi();
            $res = $Api->updatePwd($uid, $oldPwd, $data['password']);
            if($res['status']){
                $this->renderSuccess('修改密码成功！');
            }else{
                $this->renderFailed($res['info']);
            }
        }
    }
    
    /**
     * 用户重置密码
     */
    public function resetPwd() {
        if(IS_POST){
            $mobile = I('post.mobile', '', 'trim');
            $password = I('post.password', '', 'trim');
            $verify = I('post.verify', '', 'trim');
            
            if(empty($mobile)) {
                $this->renderFailed('请输入手机号');
            }
            $Api = new UserApi;
            if(!$Api->checkMobileExist($mobile)) {
                $this->renderFailed('该手机号码未注册');
            }
            if(empty($password)) {
                $this->renderFailed('请输入密码');
            }
            if(empty($verify)) {
                $this->renderFailed('请输入验证码');
            }
            //后端短信验证
            $ret = $this->sms_verify($mobile, $verify);
            if(!ret) {
                $this->renderFailed('短信验证失败~');
            }
            $res = $Api->resetPwd($mobile, $password);
            if($res['status']){
                $this->renderSuccess('重置成功');
            }
            else {
                $this->renderFailed($res['info']);
            }
        }
    }
    
    /**
     * 检查用户是否存在
     */
    public function checkUser() {
        $mobile = I('get.mobile', '', 'trim');
        if(empty($mobile)) {
            $this->renderFailed('请输入手机号');
        }
        $Api = new UserApi;
        if(!$Api->checkMobileExist($mobile)) {
            $this->renderSuccess('该手机号码未注册');
        } else {
            $this->renderFailed('该用户已注册');
        }
    }
    
    /**
     * 微信登录
     */
    public function weixinLogin() {
        if(IS_POST) {
            if(is_login()) {
                $this->renderFailed('您已经登录');
            }
            $res = $this->oauthLogin('WEIXIN');
            if($res['uid']) {
                $data = $this->getOauthLoginInfo();
                $this->renderSuccess('登录成功', $data);
            } else {
                $this->renderFailed('微信登录遇到了点麻烦，请重试');
            }
        }
    }
    
    /**
     * 微博登录
     */
    public function weiboLogin() {
        if(IS_POST) {
            if(is_login()) {
                $this->renderFailed('您已经登录');
            }
            $res = $this->oauthLogin('WEIBO');
            if($res['uid']) {
                $data = $this->getOauthLoginInfo();
                $this->renderSuccess('登录成功', $data);
            } else {
                $this->renderFailed('微博登录遇到了点麻烦，请重试');
            }
        }
    }
    
    /**
     * QQ登录
     */
    public function qqLogin() {
        if(IS_POST){
            if(is_login()) {
                $this->renderFailed('您已经登录');
            }
            $res = $this->oauthLogin('QQ');
            if($res['uid']) {
                $data = $this->getOauthLoginInfo();
                $this->renderSuccess('登录成功', $data);
            } else {
                $this->renderFailed('QQ登录遇到了点麻烦，请重试');
            }
        }
    }
    
    /**
     * 三方登录
     * @param string $reg_source 注册来源
     */
    private function oauthLogin($reg_source) {
        $openid = I('post.openid', '', 'trim');
        $nickname = I('post.nickname', '', 'trim');
        $avatar = I('post.avatar', '', 'trim');
        
        if(empty($openid)) {
            $this->renderFailed('用户id不存在');
        }
        if(empty($nickname)) {
            $this->renderFailed('昵称不存在');
        }
        $Api = new UserApi;
        $user = $Api->getOauthUser($openid, $reg_source);
        
        if(!$user) {
            //不存在则创建新用户
            $data['openid'] = $openid;
            $data['nickname'] = $nickname;
            $data['avatar'] = $avatar;
            $data['login'] = 1;
            $data['reg_ip'] = get_client_ip(1);
            $data['reg_time'] = NOW_TIME;
            $data['status'] = 1;
            $res = $Api->createOauthUser($data, $reg_source);
            if(!$res['status']){
                $this->renderFailed($res['info']);
            }
            $user = $Api->getOauthUser($openid, $reg_source);
        }
        
        if(!$user['status']) {
            $this->renderFailed('用户被禁用');
        }
        //更新登录信息
        $Api->autoLogin($user);
        //行为记录
        action_log('user_login', 'member', $user['uid'], $user['uid']);
        
        return $user;
    }
    
    /**
     * 获取三方登录信息
     */
    private function getOauthLoginInfo() {
        $data = array();
        $data = session('user_auth');
        $data['session_id'] = session_id();
        unset($data['username']);
        return $data;
    }
}
