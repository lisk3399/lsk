<?php
namespace Common\Api;
use Qiniu;
require 'ThinkPHP/Library/Vendor/Live/Pili_v2.php';

class LiveApi {
    
    protected  $config = array(
        'domain' => NULL,
        'qiniu_api' => NULL,
        'qiniu_ak' => NULL,
        'qiniu_sk' => NULL,
        'live_hub' => NULL
    );
    
    private $hub = NULL;
    
    public function __construct() {
        $qiniu_config = C('QINIU');
        $this->config['live_publish_domian'] = $qiniu_config['live_publish_domian'];
        $this->config['live_rtmp_play_domain'] = $qiniu_config['live_rtmp_play_domain'];
        $this->config['live_snapshot_domain'] = $qiniu_config['live_snapshot_domain'];
        $this->config['qiniu_api'] = $qiniu_config['api'];
        $this->config['qiniu_ak'] = $qiniu_config['accessKey'];
        $this->config['qiniu_sk'] = $qiniu_config['secrectKey'];
        $this->config['live_hub'] = $qiniu_config['live_hub'];
    
        $mac = new Qiniu\Pili\Mac($this->config['qiniu_ak'], $this->config['qiniu_sk']);
        $client = new Qiniu\Pili\Client($mac);
        $this->hub = $client->hub($this->config['live_hub']);
    }
    
    //创建流
    public function createStream($stream_key) {

        $rtmp_publish_url = Qiniu\Pili\RTMPPublishURL($this->config['live_publish_domian'],
            $this->config['live_hub'], $stream_key, 3600, $this->config['qiniu_ak'], $this->config['qiniu_sk']);
        
        $rtmp_play_url = Qiniu\Pili\RTMPPlayURL($this->config['live_rtmp_play_domain'], $this->config['live_hub'], $stream_key);
        $cover_url = Qiniu\Pili\SnapshotPlayURL($this->config['live_snapshot_domain'], $this->config['live_hub'], $stream_key);
        
        $data['publish'] = $rtmp_publish_url;
        $data['play'] = $rtmp_play_url;
        $data['cover_url'] = $cover_url;
        
        return $data;
    }
    
    //直播内容转存
    public function saveLive() {
        //调用saveas接口
    }
}