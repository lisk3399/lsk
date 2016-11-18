<?php
/**
 * Class LiveController
 * @name 直播管理
 */
 
namespace Home\Controller;


class LiveController extends HomeController { 
    
	public function index(){
	    $domain = 'bipai.tv';
	    $streamKey = 'bipai'.time();
	    $expireAfterSeconds = 30;
	   echo \Qiniu\Pili\RTMPPublishURL($domain, $this->config['live_hub'], $streamKey, $expireAfterSeconds, $this->config['qiniu_ak'], $this->config['qiniu_sk']);
	}
	
}
