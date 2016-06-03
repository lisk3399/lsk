<?php
// +----------------------------------------------------------------------
// | OneThink [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013 http://www.onethink.cn All rights reserved.
// +----------------------------------------------------------------------
// | Author: 麦当苗儿 <zuojiazi@vip.qq.com> <http://www.zjzit.cn>
// +----------------------------------------------------------------------

namespace User\Api;
use User\Api\Api;
use User\Model\UcenterMemberModel;

class UserApi extends Api{
    /**
     * 构造方法，实例化操作模型
     */
    protected function _init(){
        $this->model = new UcenterMemberModel();
    }

    /**
     * 注册一个新用户
     * @param  string $username 用户名
     * @param  string $password 用户密码
     * @param  string $email    用户邮箱
     * @param  string $mobile   用户手机号码
     * @return integer          注册成功-用户信息，注册失败-错误编号
     */
    public function register($mobile, $password, $repassword, $verify, $platform, $device_id){
        return $this->model->register($mobile, $password, $repassword, $verify, $platform, $device_id);
    }

    /**
     * 用户登录认证
     * @param  string  $username 用户名
     * @param  string  $password 用户密码
     * @param  integer $type     用户名类型 （1-用户名，2-邮箱，3-手机，4-UID）
     * @return integer           登录成功-用户ID，登录失败-错误编号
     */
    public function login($username, $password){
        return $this->model->login($username, $password);
    }

    /**
     * 获取用户信息
     * @param  string  $uid         用户ID或用户名
     * @param  boolean $is_username 是否使用用户名查询
     * @return array                用户信息
     */
    public function info($uid, $is_username = false){
        return $this->model->info($uid, $is_username);
    }

    /**
     * 检测用户名
     * @param  string  $field  用户名
     * @return integer         错误编号
     */
    public function checkUsername($username){
        return $this->model->checkField($username, 1);
    }

    /**
     * 检测邮箱
     * @param  string  $email  邮箱
     * @return integer         错误编号
     */
    public function checkEmail($email){
        return $this->model->checkField($email, 2);
    }

    /**
     * 检测手机
     * @param  string  $mobile  手机
     * @return integer         错误编号
     */
    public function checkMobile($mobile){
        return $this->model->checkField($mobile, 3);
    }

    /**
     * 更新用户信息
     * @param int $uid 用户id
     * @param string $password 密码，用来验证
     * @param array $data 修改的字段数组
     * @return true 修改成功，false 修改失败
     * @author huajie <banhuajie@163.com>
     */
    public function updateInfo($uid, $password, $data){
        if($this->model->updateUserFields($uid, $password, $data) !== false){
            $return['status'] = true;
        }else{
            $return['status'] = false;
            $return['info'] = $this->model->getError();
        }
        return $return;
    }

    /**
     * 更新昵称
     * @param int $uid
     */
    public function updateNickname($uid) {
		$data['uid'] = $uid;
		$data['nickname'] = '用户_'.$uid;
		if(M('member')->save($data)) {
		    return $data['nickname'];
		} else {
		    return '';
		}
    }
    
    /**
     * 更新密码
     * @param int $uid
     * @param string $oldPwd
     * @param string $newPwd
     */
    public function updatePwd($uid, $oldPwd, $newPwd) {
        if($this->model->updatePwd($uid, $oldPwd, $newPwd) !== false){
            $return['status'] = true;
        }else{
            $return['status'] = false;
            $return['info'] = $this->model->getError();
        }
        return $return;
    }
    
    /**
     * 重置密码
     * @param string $password
     */
    public function resetPwd($mobile, $password) {
        if($this->model->resetPwd($mobile, $password) !== false){
            $return['status'] = true;
        }else{
            $return['status'] = false;
            $return['info'] = $this->model->getError();
        }
        return $return;
    }
    
    /**
     * 检测手机是否注册
     * @param string $mobile
     */
    public function checkMobileExist($mobile) {
        return $this->model->checkMobileExist($mobile);
    }
    
    /**
     * 获取openid用户信息
     * @param string $openid
     */
    public function getOauthUser($openid, $reg_source) {
        $map = array(
            'openid' => $openid,
            'reg_source' => $reg_source
        );
        return M('Member')->where($map)->find();
    }
    
    /**
     * 创建三方登录用户
     * @param array $data (openid,nickname,avatar)
     * @param string $reg_source 注册来源
     */
    public function createOauthUser($data, $reg_source) {
        if($this->model->createOauthuser($data, $reg_source) !== false){
            $return['status'] = true;
        }else{
            $return['status'] = false;
            $return['info'] = $this->model->getError();
        }
        return $return;
    }
    
    /**
     * 更新登录信息 
     */
    public function autoLogin($user) {
        return D('Member')->autoLogin($user); 
    }
    
    /**
     * 批量用户信息
     * @param string $uids 用户id字符串
     */
    public function batchMemberInfo($uids) {
        $info = array();
        $Member = M('member');
        $info = $Member->field('uid,nickname,avatar')->where('uid in ('.$uids.')')->select();
        if(is_array($info)) {
            //默认头像
            foreach ($info as &$row) {
                $row['avatar'] = !empty($row['avatar'])?$row['avatar']:C('USER_INFO_DEFAULT.avatar');
            }
        }
        return $info;
    }
    
    /**
     * 检查uid是否存在
     * @param int $uid
     */
    public function checkUidExists($uid) {
        $Member = M('member');
        $map['uid'] = $uid;
        $ret = $Member->field('uid')->where($map)->find();
        if(!$ret['uid']) {
            return false;
        }
        return true;
    }
    
    /**
     * 获取某用户关注用户列表
     * @param int $uid
     * @param int $page
     * @param int $rows
     * @return 返回字符串用户名(如：1,2,3,4)
     */
    public function getUserFollow($uid, $page, $rows) {
        $Follow = M('follow');
        $member_list = $Follow
        ->page($page, $rows)
        ->field('follow_who')
        ->where(array('who_follow'=>$uid))
        ->select();
        
        $memberArr = array();
        foreach ($member_list as $key=>$row) {
            $memberArr[$key] = $row['follow_who'];
        }
        $uids = implode(',', $memberArr);
        
        return $uids;
    }
    
    /**
     * 获取某班级下所有用户
     * @param int $class_id
     * @param int $page
     * @param int $rows
     * @return 返回字符串用户名(如：1,2,3,4)
     */
    public function getClassUser($class_id, $page, $rows) {
        $Member = M('member');
        $member_list = $Member
        ->page($page, $rows)
        ->field('uid')
        ->where(array('classid'=>$class_id))
        ->select();
    
        $memberArr = array();
        foreach ($member_list as $key=>$row) {
            $memberArr[$key] = $row['uid'];
        }
        $uids = implode(',', $memberArr);
    
        return $uids;
    }
    
    /**
     * 获取某个素材用户列表
     * 
     */
    public function getMaterialUser($material_id, $page, $rows) {
        $Work = M('work');
        $member_list = $Work->page($page, $rows)
        ->field('distinct uid')
        ->where(array('material_id'=>$material_id))
        ->order('id desc')
        ->select();

        $memberArr = array();
        foreach ($member_list as $key=>$row) {
            $memberArr[$key] = $row['uid'];
        }
        $uids = implode(',', $memberArr);
        
        return $uids;
    }
    
    /**
     * 获取用户所在班级id
     */
    public function getClassByUid($uid) {
        $Member = M('member');
        $map['uid'] = $uid;
        return $Member->field('classid')->where($map)->find();
    }
    
    /**
     * 给用户发消息
     */
    public function sendMessage($uid, $type) {
        $data['uid'] = $uid;
        $data['type'] = $type;
        
        $data['content'] = '';
        switch ($type) {
            case C('MESSAGE_TYPE.LIKE'):
                $data['content'] = '有人给你点赞，快去看看';
                break;
            case C('MESSAGE_TYPE.AT'):
                $data['content'] = '有人@你，快去看看';
                break;
            case C('MESSAGE_TYPE.LIKE'):
                $data['content'] = '有人评论了你的作品，快去看看';
                break;
            case C('MESSAGE_TYPE.'):
                $data['content'] = '有新的好友加你，快去看看';
                break;
                default:
                    $data['content'] = '有新的系统消息';
        }
        $data['create_time'] = NOW_TIME;
        M('message')->add($data);
    }
    
    //TODO: 消息已读标记
    
    /**
     * 设置默认值
     * @param $info 数组
     * @retur $info
     */
    public function setDefaultAvatar($info) {
        if(is_array($info)) {
            foreach ($info as &$row) {
                $row['avatar'] = !empty($row['avatar'])?$row['avatar']:C('USER_INFO_DEFAULT.avatar');
            }
        }
        return $info;
    }
    
    /**
     * 检查素材是否存在
     */
    public function checkMaterialExists($material_id) {
        $Material = M('document_material');
        $res = $Material->where(array('id'=>$material_id))->field('id')->find();
        if(!$res['id']) {
            return false;
        }
        return true;
    }
    
    /**
     * 是否收藏
     */
    public function isFav($uid, $mid) {
        $Fav = M('material_fav');
        $map['mid'] = $mid; //素材id
        $map['uid'] = $uid; //用户id
        $ret = $Fav->field('id')->where($map)->find();
        if($ret['id']) {
            return 1;
        }
        return 0;
    }
    
    /**
     * 根据文件id获取文件url
     */
    public function getFileUrl($id) {
        $File = M('file');
        $info = $File->getById($id);
        if($info['id']) {
            return $info['url'];
        }
        return '';
    }
    
    /**
     * 用户是否给某用户点赞
     * @param array $list 数组
     * @param int $uid 用户id
     */
    public function getIsLike($list, $uid) {
        if(is_array($list)) {
            foreach ($list as &$row) {
                $row['is_like'] = 0;
                if($this->isLike($uid, $row['id'])){
                    $row['is_like'] = 1;
                }
            }
        }
        return $list;
    }
    
    /**
     * 用户是否给某作品点赞
     * @param int $uid
     * @param int $work_id
     */
    public function isLike($uid, $work_id) {
        $Like = M('likes');
        $map['uid'] = $uid;
        $map['work_id'] = $work_id;
        $ret = $Like->field('id')->where($map)->find();
        if($ret['id']) {
            return true;
        }
        return false;
    }
    
    /**
     * 检测密码格式
     * @param $password
     * @return boolean
     */
    public function checkPwdFormat($password) {
        //之前为包含自护和数字 /^(?![^a-zA-Z]+$)(?!\D+$).{6,20}$/
        if(!preg_match('/^[0-9A-Za-z]{6,20}$/', $password)) {
            return false;
        }
        return true;
    }
}
