<?php
// +----------------------------------------------------------------------
// | OneThink [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013 http://www.onethink.cn All rights reserved.
// +----------------------------------------------------------------------
// | Author: 麦当苗儿 <zuojiazi@vip.qq.com> <http://www.zjzit.cn>
// +----------------------------------------------------------------------
namespace User\Model;
use Think\Model;
/**
 * 会员模型
 */
class UcenterMemberModel extends Model{
	/**
	 * 数据表前缀
	 * @var string
	 */
	protected $tablePrefix = UC_TABLE_PREFIX;

	/**
	 * 数据库连接
	 * @var string
	 */
	protected $connection = UC_DB_DSN;

	/* 用户模型自动验证 */
	protected $_validate = array(
	    /* 验证手机号码 */
	    array('mobile', 'require', -1, self::MUST_VALIDATE), //手机号码不能为空
	    array('mobile', '/^1(3[0-9]|5[0-35-9]|8[025-9])\d{8}$/', -2, self::MUST_VALIDATE, 'regex'), //手机格式不正确
	    array('mobile', '', -3, self::MUST_VALIDATE, 'unique'), //手机号已经存在
	    array('mobile', 'checkDenyMobile', -7, self::MUST_VALIDATE, 'callback'), //手机禁止注册
	    
		/* 验证密码 */
		array('password', '/^(?![^a-zA-Z]+$)(?!\D+$).{6,20}$/', -4, self::MUST_VALIDATE, 'regex'), //密码6-20位，包含字母和数字
	    array('repassword', 'password', -5, self::MUST_VALIDATE, 'confirm'),  //两次密码输入不一致
	    array('verify', 'require', -6, self::MUST_VALIDATE),
	);

	/* 用户模型自动完成 */
	protected $_auto = array(
		array('password', 'think_ucenter_md5', self::MODEL_BOTH, 'function', UC_AUTH_KEY),
		array('reg_time', NOW_TIME, self::MODEL_INSERT),
		array('reg_ip', 'get_client_ip', self::MODEL_INSERT, 'function', 1),
		array('update_time', NOW_TIME),
		array('status', 'getStatus', self::MODEL_BOTH, 'callback'),
	);

	/**
	 * 检测用户名是不是被禁止注册
	 * @param  string $username 用户名
	 * @return boolean          ture - 未禁用，false - 禁止注册
	 */
	protected function checkDenyMember($username){
		return true; //TODO: 暂不限制，下一个版本完善
	}

	/**
	 * 检测邮箱是不是被禁止注册
	 * @param  string $email 邮箱
	 * @return boolean       ture - 未禁用，false - 禁止注册
	 */
	protected function checkDenyEmail($email){
		return true; //TODO: 暂不限制，下一个版本完善
	}

	/**
	 * 检测手机是不是被禁止注册
	 * @param  string $mobile 手机
	 * @return boolean        ture - 未禁用，false - 禁止注册
	 */
	protected function checkDenyMobile($mobile){
		return true; //TODO: 暂不限制，下一个版本完善
	}

	/**
	 * 根据配置指定用户状态
	 * @return integer 用户状态
	 */
	protected function getStatus(){
		return true; //TODO: 暂不限制，下一个版本完善
	}

	/**
	 * 注册一个新用户
	 * @param  string $mobile   用户手机号码
	 * @param  string $password 用户密码
	 * @return integer          注册成功-用户信息，注册失败-错误编号
	 */
	public function register($mobile, $password, $repassword, $verify){
		$data = array(
			'password' => $password,
			'mobile'   => $mobile,
		    'repassword' => $repassword,
		    'verify' => $verify
		);

		/* 添加用户 */
		if($this->create($data)){
			$uid = $this->add();
			$this->save(array('nickname' => '用户_'.$uid));
			return $uid ? $uid : 0; //0-未知错误，大于0-注册成功
		} else {
			return $this->getError(); //错误详情见自动验证注释
		}
	}

	/**
	 * 用户登录认证
	 * @param  string  $username 用户名
	 * @param  string  $password 用户密码
	 * @param  integer $type     用户名类型 （1-用户名，2-邮箱，3-手机，4-UID）
	 * @return integer           登录成功-用户ID，登录失败-错误编号
	 */
	public function login($mobile, $password){
		$map['mobile'] = $mobile;

		/* 获取用户数据 */
		$user = $this->where($map)->find();
		if(is_array($user) && $user['status']){
			/* 验证用户密码 */
			if(think_ucenter_md5($password, UC_AUTH_KEY) === $user['password']){
				$this->updateLogin($user['id']); //更新用户登录信息
				return $user['id']; //登录成功，返回用户ID
			} else {
				return -2; //密码错误
			}
		} else {
			return -1; //用户不存在或被禁用
		}
	}

	/**
	 * 获取用户信息
	 * @param  string  $uid         用户ID或用户名
	 * @param  boolean $is_username 是否使用用户名查询
	 * @return array                用户信息
	 */
	public function info($uid, $is_username = false){
		$map = array();
		if($is_username){ //通过用户名获取
			$map['nickname'] = $uid;
		} else {
			$map['uid'] = $uid;
		}
		$user = D('Member')->where($map)->field('uid,nickname,avatar,signature,sex,birthday,status')->find();
		if(is_array($user) && $user['status'] == 1){
			return $user;
		} else {
			return -1; //用户不存在或被禁用
		}
	}

	/**
	 * 检测用户信息
	 * @param  string  $field  用户名
	 * @param  integer $type   用户名类型 1-用户名，2-用户邮箱，3-用户电话
	 * @return integer         错误编号
	 */
	public function checkField($field, $type = 1){
		$data = array();
		switch ($type) {
			case 1:
				$data['username'] = $field;
				break;
			case 2:
				$data['email'] = $field;
				break;
			case 3:
				$data['mobile'] = $field;
				break;
			default:
				return 0; //参数错误
		}

		return $this->create($data) ? 1 : $this->getError();
	}

	/**
	 * 更新用户登录信息
	 * @param  integer $uid 用户ID
	 */
	protected function updateLogin($uid){
		$data = array(
			'id'              => $uid,
			'last_login_time' => NOW_TIME,
			'last_login_ip'   => get_client_ip(1),
		);
		$this->save($data);
	}

	/**
	 * 更新用户信息
	 * @param int $uid 用户id
	 * @param string $password 密码，用来验证
	 * @param array $data 修改的字段数组
	 * @return true 修改成功，false 修改失败
	 * @author huajie <banhuajie@163.com>
	 */
	public function updateUserFields($uid, $password, $data){
		if(empty($uid) || empty($password) || empty($data)){
			$this->error = '参数错误！';
			return false;
		}

		//更新前检查用户密码
		if(!$this->verifyUser($uid, $password)){
			$this->error = '验证出错：密码不正确！';
			return false;
		}
		//更新用户信息
		$data = $this->create($data);
		if($data){
			return $this->where(array('id'=>$uid))->save($data);
		}
		return false;
	}

	/**
	 * 验证用户密码
	 * @param int $uid 用户id
	 * @param string $password_in 密码
	 * @return true 验证成功，false 验证失败
	 * @author huajie <banhuajie@163.com>
	 */
	protected function verifyUser($uid, $password_in){
		$password = $this->getFieldById($uid, 'password');
		if(think_ucenter_md5($password_in, UC_AUTH_KEY) === $password){
			return true;
		}
		return false;
	}

	/**
	 * 更新昵称
	 * @param int $uid
	 * @param string $username
	 */
	public function updateUsername($uid, $username){
	    $data = array(
	        'id' => $uid,
	        'username' => $username
	    );
	    return $this->save($data);
	}
	
	/**
	 * 更新密码
	 * @param int $uid
	 * @param string $oldPwd
	 * @param string $newPwd
	 */
	public function updatePwd($uid, $oldPwd, $newPwd) {
	    if(empty($uid) || empty($oldPwd) || empty($newPwd)){
	        $this->error = '参数错误！';
	        return false;
	    }
	    //更新前检查用户密码
	    if(!$this->verifyUser($uid, $oldPwd)){
	        $this->error = '验证出错：密码不正确！';
	        return false;
	    }
	    $data['password'] = think_ucenter_md5($newPwd, UC_AUTH_KEY);
	    return $this->where(array('id'=>$uid))->save($data);
	}

	/**
	 * 重置密码
	 * @param string $password
	 */
	public function resetPwd($mobile, $password) {
	    if(empty($password) || empty($mobile)){
	        $this->error = '参数错误！';
	        return false;
	    }
	    //密码格式验证
	    if(!$this->checkPwdFormat($password)) {
	        return false;
	    }
	    $data['password'] = think_ucenter_md5($password, UC_AUTH_KEY);
	    return $this->where(array('mobile'=>$mobile))->save($data);
	}
	
	/**
	 * 检测手机是否注册
	 * @param string $mobile
	 */
	public function checkMobileExist($mobile) {
	    return $this->where(array('mobile'=>$mobile))->find();
	}
	
	/**
	 * 检测密码格式
	 * @param $password
	 * @return boolean
	 */
	private function checkPwdFormat($password) {
	    if(!preg_match('/^(?![^a-zA-Z]+$)(?!\D+$).{6,20}$/', $password)) {
	        $this->error = '密码长度在6-20个字符，包含字母和数字';
	        return false;
	    }
	    return true;
	}
}
