<?php

namespace Home\Model;
use Think\Model;
use Qiniu;
require 'ThinkPHP/Library/Vendor/Live/Pili_v2.php';

/**
 * 直播模型
 */
class LiveModel extends Model{
    
    protected  $config = array(
        'domain' => NULL,
        'qiniu_api' => NULL,
        'qiniu_ak' => NULL,
        'qiniu_sk' => NULL,
        'live_hub' => NULL
    );
    
    private $hub = NULL;
    private $stream = NULL;
    
    public function __construct() {
        $qiniu_config = C('QINIU');
        $this->config['domain'] = $qiniu_config['live_domain'];
        $this->config['qiniu_api'] = $qiniu_config['api'];
        $this->config['qiniu_ak'] = $qiniu_config['accessKey'];
        $this->config['qiniu_sk'] = $qiniu_config['secrectKey'];
        $this->config['live_hub'] = $qiniu_config['live_hub'];
    
        $mac = new Qiniu\Pili\Mac($this->config['qiniu_ak'], $this->config['qiniu_sk']);
        $client = new Qiniu\Pili\Client($mac);
        $this->hub = $client->hub($this->config['live_hub']);
    
        $streamKey='bipai1479352314';
        $transport = new Qiniu\Pili\Transport($mac);
        $this->stream = new Qiniu\Pili\Stream($transport, $this->config['live_hub'], $streamKey);
    }
    
    /**
     * 创建流
     */
    public function createStream() {
        try{
            $streamKey="bipai".time();
            $resp=$this->hub->create($streamKey);
            print_r($resp);
        }catch(\Exception $e) {
            echo "Error:",$e;
        }
    }
    
    public function getStream() {
        try{
            $streamKey="bipai".time();
            $resp=$this->hub->stream($streamKey);
            print_r($resp);
        }catch(\Exception $e) {
            echo "Error:",$e;
        }
    }
    
    public function getStreamList() {
        try{
            $resp = $this->stream->info();
            print_r($resp);
        }catch(\Exception $e) {
            echo "Error:",$e;
        }
    }
}
