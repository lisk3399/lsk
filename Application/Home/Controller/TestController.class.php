<?php

namespace Home\Controller;

/**
 * 测试
 */
class TestController extends HomeController {

	public function index(){
	}
	
	public function classwork() {
	    $info = array();
	    
	    $info[0]['id'] = 1;
	    $info[0]['uid'] = '1';
	    $info[0]['username'] = '一树一花';
	    $info[0]['create_time'] = '2016-08-22';
	    $info[0]['title'] = '分享一下7月份琴谱，想要什么谱子下方评论下，推荐几个app给大家看看';
	    $info[0]['text'] = '推荐大家一款琴谱软件，这个软件非常好，大家自己可以在家查看琴谱，还有一看调弦app，大家没事在家可以多试试，很给力的，我就经常用来调弦，这样准确度就高多了，也不用担心不准了';
	    $info[0]['pic'] = array(array('url'=>'http://img0.bdstatic.com/img/image/%E6%91%84%E5%BD%B1820.jpg', 'type'=>'pic'),
	    array('url'=>'http://img0.bdstatic.com/img/image/%E9%B9%BF%E6%99%97820.jpg', 'type'=>'video'),
	    array('url'=>'http://img0.bdstatic.com/img/image/%E5%A4%B4%E5%83%8F820.jpg', 'type'=>'audio'));
	    $info[0]['comments'] = 33;
	    $info[0]['likes'] = 12;
	    $info[0]['is_like'] = 1;
	    
	    $info[1]['id'] = 2;
	    $info[1]['uid'] = '2';
	    $info[1]['username'] = '两树两花';
	    $info[1]['create_time'] = '刚刚';
	    $info[1]['title'] = '分享一下7月份琴谱，想要什么谱子下方评论下，推荐几个app给大家看看';
	    $info[1]['text'] = '推荐大家一款琴谱软件，这个软件非常好，大家自己可以在家查看琴谱，还有一看调弦app，大家没事在家可以多试试，很给力的，我就经常用来调弦，这样准确度就高多了，也不用担心不准了';
	    $info[1]['pic'] = array(array('url'=>'http://img0.bdstatic.com/img/image/%E6%91%84%E5%BD%B1820.jpg', 'type'=>'pic'));
	    $info[1]['comments'] = 22;
	    $info[1]['likes'] = 123;
	    $info[1]['is_like'] = 0;
	    
	    $info[2]['id'] = 3;
	    $info[2]['uid'] = '3';
	    $info[2]['username'] = '三树三花';
	    $info[2]['create_time'] = '3分钟前';
	    $info[2]['title'] = '分享一下7月份琴谱，想要什么谱子下方评论下，推荐几个app给大家看看';
	    $info[2]['text'] = '推荐大家一款琴谱软件，这个软件非常好，大家自己可以在家查看琴谱，还有一看调弦app，大家没事在家可以多试试，很给力的，我就经常用来调弦，这样准确度就高多了，也不用担心不准了';
	    $info[2]['pic'] = array(array('url'=>'http://img0.bdstatic.com/img/image/%E6%91%84%E5%BD%B1820.jpg', 'type'=>'pic'),
	    array('url'=>'http://img0.bdstatic.com/img/image/%E9%B9%BF%E6%99%97820.jpg', 'type'=>'video'));
	    $info[2]['comments'] = 44;
	    $info[2]['likes'] = 121;
	    $info[2]['is_like'] = 1;
	    
	    $info[3]['id'] = 4;
	    $info[3]['uid'] = '4';
	    $info[3]['username'] = '无树无花';
	    $info[3]['create_time'] = '1小时前';
	    $info[3]['title'] = '分享一下7月份琴谱，想要什么谱子下方评论下，推荐几个app给大家看看';
	    $info[3]['text'] = '推荐大家一款琴谱软件，这个软件非常好，大家自己可以在家查看琴谱，还有一看调弦app，大家没事在家可以多试试，很给力的，我就经常用来调弦，这样准确度就高多了，也不用担心不准了';
	    $info[3]['comments'] = 331;
	    $info[3]['likes'] = 333;
	    $info[3]['is_like'] = 0;
	    
	    $this->renderSuccess('数据加载', $info);
	}
}
