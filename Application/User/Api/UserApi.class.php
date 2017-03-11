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
        $map['status'] = 1;
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
        ->order('id desc')
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
        ->order('uid desc')
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
    public function sendMessage($uid, $type, $extra_info) {
        $data['uid'] = $uid;
        $data['type'] = $type;
        $data['content'] = '';
        switch ($type) {
            case C('MESSAGE_TYPE.ADD_GROUP'):
                $username = $this->getUsername($extra_info['uid']);
                $data['content'] = $username.'申请加入班级:'.$extra_info['group_name'];
                $data['extra'] = 'member_groupid#'.$extra_info['member_groupid'];
                break;
            case C('MESSAGE_TYPE.LIKE'):
                $data['content'] = '有人给你点赞，快去看看';
                break;
            case C('MESSAGE_TYPE.AT'):
                $data['content'] = '有人@你，快去看看';
                break;
            case C('MESSAGE_TYPE.COMMENT'):
                $data['content'] = '有人评论了你的作品，快去看看';
                break;
            case C('MESSAGE_TYPE.FRIENDS'):
                $data['content'] = '有新的好友加你，快去看看';
                break;
                default:
                    if(!empty($extra_info['content'])) {
                        $data['content'] = $extra_info['content'];
                    } else {
                        $data['content'] = '有新的系统消息';
                    }
        }
        $data['create_time'] = NOW_TIME;
        if(M('message')->add($data)) {
            $this->sendNotice($uid, $data['content']); //发送通知
        }
    }
    
    /*
     * 发送通知
     * @param $uid 用户id
     * @param $content 发送内容
     * @return boolean 
     */
    public function sendNotice($uid, $title, $content) {
        vendor('Getui/Getui');
        $appid = C('GETUI.APPID');
        $appkey = C('GETUI.APPKEY');
        $masterSecret = C('GETUI.MASTERSECRET');
        $igt = new \IGeTui(NULL, $appkey, $masterSecret, false);
        
        //消息模版：
        // 1.TransmissionTemplate:透传功能模板
        // 2.LinkTemplate:通知打开链接功能模板
        // 3.NotificationTemplate：通知透传功能模板
        // 4.NotyPopLoadTemplate：通知弹框下载功能模板
        
        //    	$template = IGtNotyPopLoadTemplateDemo();
        //    	$template = IGtLinkTemplateDemo();
        //    	$template = IGtNotificationTemplateDemo();
        $template = $this->IGtNotificationTemplateDemo($appid, $appkey, $title, $content);
        
        //个推信息体
        $message = new \IGtSingleMessage();
        
        $message->set_isOffline(true);//是否离线
        $message->set_offlineExpireTime(3600*12*1000);//离线时间
        $message->set_data($template);//设置推送消息类型
        //	$message->set_PushNetWorkType(0);//设置是否根据WIFI推送消息，1为wifi推送，0为不限制推送
        //接收方
        $target = new \IGtTarget();
        $target->set_appId($appid);
        $target->set_clientId($uid);
        //    $target->set_alias(Alias);
        
        
        try {
            $rep = $igt->pushMessageToSingle($message, $target);
            var_dump($rep);
            echo ("<br><br>");
        
        }catch(\RequestException $e){
            $requstId =$e->getRequestId();
            $rep = $igt->pushMessageToSingle($message, $target, $requstId);
            var_dump($rep);
            echo ("<br><br>");
        }
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
    
    //根据uid获取用户名
    public function getUsername($uid) {
        return M('member')->where(array('uid'=>$uid))->getField('nickname');
    }
    
    private function IGtNotificationTemplateDemo($appid, $appkey, $title='消息通知', $content){
        $template =  new \IGtNotificationTemplate();
        $template->set_appId($appid);//应用appid
        $template->set_appkey($appkey);//应用appkey
        $template->set_transmissionType(1);//透传消息类型
        $template->set_transmissionContent($content);//透传内容
        $template->set_title($title);//通知栏标题
        $template->set_text($content);//通知栏内容
        //$template->set_logo("http://wwww.igetui.com/logo.png");//通知栏logo
        $template->set_isRing(true);//是否响铃
        $template->set_isVibrate(true);//是否震动
        $template->set_isClearable(true);//通知栏是否可清除
        //$template->set_duration(BEGINTIME,ENDTIME); //设置ANDROID客户端在此时间区间内展示消息
        //iOS推送需要设置的pushInfo字段
        //        $apn = new IGtAPNPayload();
        //        $apn->alertMsg = "alertMsg";
        //        $apn->badge = 11;
        //        $apn->actionLocKey = "启动";
        //    //        $apn->category = "ACTIONABLE";
        //    //        $apn->contentAvailable = 1;
        //        $apn->locKey = "通知栏内容";
        //        $apn->title = "通知栏标题";
        //        $apn->titleLocArgs = array("titleLocArgs");
        //        $apn->titleLocKey = "通知栏标题";
        //        $apn->body = "body";
        //        $apn->customMsg = array("payload"=>"payload");
        //        $apn->launchImage = "launchImage";
        //        $apn->locArgs = array("locArgs");
        //
        //        $apn->sound=("test1.wav");;
        //        $template->set_apnInfo($apn);
        return $template;
    }
    
    private function IGtTransmissionTemplateDemo($appid, $appkey, $content){
        $template =  new \IGtTransmissionTemplate();
        $template->set_appId($appid);//应用appid
        $template->set_appkey($appkey);//应用appkey
        $template->set_transmissionType(1);//透传消息类型
        $template->set_transmissionContent($content);//透传内容
        //$template->set_duration(BEGINTIME,ENDTIME); //设置ANDROID客户端在此时间区间内展示消息
        //APN简单推送
        //        $template = new IGtAPNTemplate();
        //        $apn = new IGtAPNPayload();
        //        $alertmsg=new SimpleAlertMsg();
        //        $alertmsg->alertMsg="";
        //        $apn->alertMsg=$alertmsg;
        ////        $apn->badge=2;
        ////        $apn->sound="";
        //        $apn->add_customMsg("payload","payload");
        //        $apn->contentAvailable=1;
        //        $apn->category="ACTIONABLE";
        //        $template->set_apnInfo($apn);
        //        $message = new IGtSingleMessage();
    
        //APN高级推送
        $apn = new \IGtAPNPayload();
        $alertmsg=new \DictionaryAlertMsg();
        $alertmsg->body="body";
        $alertmsg->actionLocKey="ActionLockey";
        $alertmsg->locKey="LocKey";
        $alertmsg->locArgs=array("locargs");
        $alertmsg->launchImage="launchimage";
        //        IOS8.2 支持
        $alertmsg->title="Title";
        $alertmsg->titleLocKey="TitleLocKey";
        $alertmsg->titleLocArgs=array("TitleLocArg");
    
        $apn->alertMsg=$alertmsg;
        $apn->badge=7;
        $apn->sound="";
        $apn->add_customMsg("payload","payload");
        $apn->contentAvailable=1;
        $apn->category="ACTIONABLE";
        $template->set_apnInfo($apn);
    
        //PushApn老方式传参
        //    $template = new IGtAPNTemplate();
        //          $template->set_pushInfo("", 10, "", "com.gexin.ios.silence", "", "", "", "");
    
        return $template;
    }
}
