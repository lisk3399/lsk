// 模板
(function(window){

    //取得浏览器环境的baidu命名空间，非浏览器环境符合commonjs规范exports出去
    //修正在nodejs环境下，采用baidu.template变量名
    var baidu = typeof module === 'undefined' ? (window.baidu = window.baidu || {}) : module.exports;

    //模板函数（放置于baidu.template命名空间下）
    baidu.template = function(str, data){

        //检查是否有该id的元素存在，如果有元素则获取元素的innerHTML/value，否则认为字符串为模板
        var fn = (function(){

            //判断如果没有document，则为非浏览器环境
            if(!window.document){
                return bt._compile(str);
            };

            //HTML5规定ID可以由任何不包含空格字符的字符串组成
            var element = document.getElementById(str);
            if (element) {
                    
                //取到对应id的dom，缓存其编译后的HTML模板函数
                if (bt.cache[str]) {
                    return bt.cache[str];
                };

                //textarea或input则取value，其它情况取innerHTML
                var html = /^(textarea|input)$/i.test(element.nodeName) ? element.value : element.innerHTML;
                return bt._compile(html);

            }else{

                //是模板字符串，则生成一个函数
                //如果直接传入字符串作为模板，则可能变化过多，因此不考虑缓存
                return bt._compile(str);
            };

        })();

        //有数据则返回HTML字符串，没有数据则返回函数 支持data={}的情况
        var result = bt._isObject(data) ? fn( data ) : fn;
        fn = null;

        return result;
    };

    //取得命名空间 baidu.template
    var bt = baidu.template;

    //标记当前版本
    bt.versions = bt.versions || [];
    bt.versions.push('1.0.6');

    //缓存  将对应id模板生成的函数缓存下来。
    bt.cache = {};
    
    //自定义分隔符，可以含有正则中的字符，可以是HTML注释开头 <! !>
    bt.LEFT_DELIMITER = bt.LEFT_DELIMITER||'<%';
    bt.RIGHT_DELIMITER = bt.RIGHT_DELIMITER||'%>';

    //自定义默认是否转义，默认为默认自动转义
    bt.ESCAPE = true;

    //HTML转义
    bt._encodeHTML = function (source) {
        return String(source)
            .replace(/&/g,'&amp;')
            .replace(/</g,'&lt;')
            .replace(/>/g,'&gt;')
            .replace(/\\/g,'&#92;')
            .replace(/"/g,'&quot;')
            .replace(/'/g,'&#39;');
    };

    //转义影响正则的字符
    bt._encodeReg = function (source) {
        return String(source).replace(/([.*+?^=!:${}()|[\]/\\])/g,'\\$1');
    };

    //转义UI UI变量使用在HTML页面标签onclick等事件函数参数中
    bt._encodeEventHTML = function (source) {
        return String(source)
            .replace(/&/g,'&amp;')
            .replace(/</g,'&lt;')
            .replace(/>/g,'&gt;')
            .replace(/"/g,'&quot;')
            .replace(/'/g,'&#39;')
            .replace(/\\\\/g,'\\')
            .replace(/\\\//g,'\/')
            .replace(/\\n/g,'\n')
            .replace(/\\r/g,'\r');
    };

    //将字符串拼接生成函数，即编译过程(compile)
    bt._compile = function(str){
        var funBody = "var _template_fun_array=[];\nvar fn=(function(__data__){\nvar _template_varName='';\nfor(name in __data__){\n_template_varName+=('var '+name+'=__data__[\"'+name+'\"];');\n};\neval(_template_varName);\n_template_fun_array.push('"+bt._analysisStr(str)+"');\n_template_varName=null;\n})(_template_object);\nfn = null;\nreturn _template_fun_array.join('');\n";
        return new Function("_template_object",funBody);
    };

    //判断是否是Object类型
    bt._isObject = function (source) {
        return 'function' === typeof source || !!(source && 'object' === typeof source);
    };

    //解析模板字符串
    bt._analysisStr = function(str){

        //取得分隔符
        var _left_ = bt.LEFT_DELIMITER;
        var _right_ = bt.RIGHT_DELIMITER;

        //对分隔符进行转义，支持正则中的元字符，可以是HTML注释 <!  !>
        var _left = bt._encodeReg(_left_);
        var _right = bt._encodeReg(_right_);

        str = String(str)
            
            //去掉分隔符中js注释
            .replace(new RegExp("("+_left+"[^"+_right+"]*)//.*\n","g"), "$1")

            //去掉注释内容  <%* 这里可以任意的注释 *%>
            //默认支持HTML注释，将HTML注释匹配掉的原因是用户有可能用 <! !>来做分割符
            .replace(new RegExp("<!--.*?-->", "g"),"")
            .replace(new RegExp(_left+"\\*.*?\\*"+_right, "g"),"")

            //把所有换行去掉  \r回车符 \t制表符 \n换行符
            .replace(new RegExp("[\\r\\t\\n]","g"), "")

            //用来处理非分隔符内部的内容中含有 斜杠 \ 单引号 ‘ ，处理办法为HTML转义
            .replace(new RegExp(_left+"(?:(?!"+_right+")[\\s\\S])*"+_right+"|((?:(?!"+_left+")[\\s\\S])+)","g"),function (item, $1) {
                var str = '';
                if($1){

                    //将 斜杠 单引 HTML转义
                    str = $1.replace(/\\/g,"&#92;").replace(/'/g,'&#39;');
                    while(/<[^<]*?&#39;[^<]*?>/g.test(str)){

                        //将标签内的单引号转义为\r  结合最后一步，替换为\'
                        str = str.replace(/(<[^<]*?)&#39;([^<]*?>)/g,'$1\r$2')
                    };
                }else{
                    str = item;
                }
                return str ;
            });


        str = str 
            //定义变量，如果没有分号，需要容错  <%var val='test'%>
            .replace(new RegExp("("+_left+"[\\s]*?var[\\s]*?.*?[\\s]*?[^;])[\\s]*?"+_right,"g"),"$1;"+_right_)

            //对变量后面的分号做容错(包括转义模式 如<%:h=value%>)  <%=value;%> 排除掉函数的情况 <%fun1();%> 排除定义变量情况  <%var val='test';%>
            .replace(new RegExp("("+_left+":?[hvu]?[\\s]*?=[\\s]*?[^;|"+_right+"]*?);[\\s]*?"+_right,"g"),"$1"+_right_)

            //按照 <% 分割为一个个数组，再用 \t 和在一起，相当于将 <% 替换为 \t
            //将模板按照<%分为一段一段的，再在每段的结尾加入 \t,即用 \t 将每个模板片段前面分隔开
            .split(_left_).join("\t");

        //支持用户配置默认是否自动转义
        if(bt.ESCAPE){
            str = str

                //找到 \t=任意一个字符%> 替换为 ‘，任意字符,'
                //即替换简单变量  \t=data%> 替换为 ',data,'
                //默认HTML转义  也支持HTML转义写法<%:h=value%>  
                .replace(new RegExp("\\t=(.*?)"+_right,"g"),"',typeof($1) === 'undefined'?'':baidu.template._encodeHTML($1),'");
        }else{
            str = str
                
                //默认不转义HTML转义
                .replace(new RegExp("\\t=(.*?)"+_right,"g"),"',typeof($1) === 'undefined'?'':$1,'");
        };

        str = str

            //支持HTML转义写法<%:h=value%>  
            .replace(new RegExp("\\t:h=(.*?)"+_right,"g"),"',typeof($1) === 'undefined'?'':baidu.template._encodeHTML($1),'")

            //支持不转义写法 <%:=value%>和<%-value%>
            .replace(new RegExp("\\t(?::=|-)(.*?)"+_right,"g"),"',typeof($1)==='undefined'?'':$1,'")

            //支持url转义 <%:u=value%>
            .replace(new RegExp("\\t:u=(.*?)"+_right,"g"),"',typeof($1)==='undefined'?'':encodeURIComponent($1),'")

            //支持UI 变量使用在HTML页面标签onclick等事件函数参数中  <%:v=value%>
            .replace(new RegExp("\\t:v=(.*?)"+_right,"g"),"',typeof($1)==='undefined'?'':baidu.template._encodeEventHTML($1),'")

            //将字符串按照 \t 分成为数组，在用'); 将其合并，即替换掉结尾的 \t 为 ');
            //在if，for等语句前面加上 '); ，形成 ');if  ');for  的形式
            .split("\t").join("');")

            //将 %> 替换为_template_fun_array.push('
            //即去掉结尾符，生成函数中的push方法
            //如：if(list.length=5){%><h2>',list[4],'</h2>');}
            //会被替换为 if(list.length=5){_template_fun_array.push('<h2>',list[4],'</h2>');}
            .split(_right_).join("_template_fun_array.push('")

            //将 \r 替换为 \
            .split("\r").join("\\'");

        return str;
    };
})(window);

//下拉刷新
(function ($) {
	var nextpage = {
		isInit : false,
		itemArray : [], // 待处理对象数组
		itemExists : function (item) { // 判断待处理对象是否存在，并返回对象所在数组位置
			if(!item || !item.obj) return -1;
			var length = this.itemArray.length;
			for (var i = 0; i < length ; i++) {
				if(item.obj.selector == this.itemArray[i].obj.selector) return i;
			}
			return -1;
		},
		addItem : function (obj, options) { // 添加待处理对象
			if(!obj) return false;
			var item = {
				obj : obj, // 当前处理对象
				options : options, // 配置参数
				isProcessing : false, // 是否正在处理
				isEnd : false, // 处理完成，无更多数据
				tempPreObj : {} // 预加载缓存数据对象
			};
			var index = this.itemExists(item);
			if(-1 == index) {
				this.itemArray.push(item); // 不存在时添加
			} else {
				this.itemArray[index] = item; // 存在时更新
			}
			return item;
		},
		scrollFn:function(triggerObj,options,iscrollObj){
			if(triggerObj.parent().is(':hidden')) return;
			var isTriggering = false,
			$win = $(window);
			if(isTriggering) return true;
			nextpage.log('trigger...', nextpage.itemArray);
			isTriggering = true;
			var windowHeight = $win.height(),
			scrollTop = $win.scrollTop(),
			objOffsetTop = triggerObj.offset().top;
			nextpage.log('objOffsetTop:' + objOffsetTop + ', windowHeight:' + windowHeight + ', scrollTop:' + scrollTop);
			
			if(!iscrollObj){
				if((windowHeight+scrollTop)<objOffsetTop-(options.offsetY?options.offsetY:0)){
					isTriggering = false;
					return true;
				}
			}else{
				if(iscrollObj.y>iscrollObj.maxScrollY){
					isTriggering = false;
					return true;
				}
			}

			for (var i in nextpage.itemArray) {
				var item = nextpage.itemArray[i];
				if(item.options.bPreLoad) { // 预加载
					nextpage.processPreData(item, item.options.pageCurrent + 1, false);
				} else {
					nextpage.process(item);
				}
			}
			
			isTriggering = false;
		},
		init : function (obj, options) { // 初始化函数
			var item = this.addItem(obj, options),
			$win = $(window),
			isBindIscroll = false;

			options.onInit(obj, options); // 触发初始化完成事件
			if(item.options.bPreLoad) { // 执行预加载
				nextpage.processPreLoad(item, item.options.pageCurrent + 1);
			}
			if(options.bLoadOnInit) { // 立即加载数据
				if(item.options.bPreLoad) {
					this.processPreData(item, item.options.pageCurrent + 1, false);
				} else {
					this.process(item);
				}
			}
			if(this.isInit) return false;
			this.isInit = true;
			var isTriggering = false;

			$win.on('scroll', function(){
				nextpage.scrollFn(obj,options,window.iscrollObj);
				if(window.iscrollObj){
					if(!isBindIscroll){
						iscrollObj.on('scrollEnd',  function(){
							nextpage.scrollFn(obj,options,iscrollObj);
						});
						isBindIscroll = true;
					}
				}else{
					isBindIscroll = false;
				}
			}).trigger('scroll'); // 模拟触发
			
		},
		process : function (item) { // 处理子程序
			//debugger;
			if(item.isProcessing || item.isEnd) return false ;
			nextpage.log('process...', item, nextpage.itemArray);
			item.isProcessing = true;
			item.options.onProcess(item.obj, item.options); // 触发开始处理事件
			$.ajax({
				url : item.options.urlFormater(item.options, item.options.url, ++item.options.pageCurrent),
				data : item.options.paramters(item.options.pageCurrent),
				dataType : null == item.options.jsonp ? 'json' : 'jsonp',
				jsonp : item.options.jsonp,
				success : function (data) {
					//debugger;
					nextpage.log('process data...', item, nextpage.itemArray);
					nextpage.processData(item, data, false);
					if(!item.isEnd) item.options.onPrcessDone(item.obj, data, item.options); // 触发处理结束事件
					item.isProcessing = false;
					nextpage.log('process done...', item, nextpage.itemArray);
					item.options.processCallback(item.obj, data, item.options); // 单次处理结束回调
				}
			});
		},
		/**
		 * 预加载数据
		 * @param page 预加载page页对应的数据
		 */
		processPreLoad : function (item, page) {
			item.tempPreObj[page] = {
				bTrigger : false, // 是否已被请求调用
				bLoaded : false, // 数据是否加载完成
				data : null // 预加载数据
			};
			$.ajax({
				url : item.options.urlFormater(item.options, item.options.url, page),
				data : item.options.paramters(page),
				dataType : null == item.options.jsonp ? 'json' : 'jsonp',
				jsonp : item.options.jsonp,
				success : function (data) {
					item.tempPreObj[page].bLoaded = true;
					item.tempPreObj[page].data = data;
					if(page - item.options.pageCurrent <= 1) { // 先处理数据，后触发中断
						nextpage.processData(item, data, true);
					}
					if(item.tempPreObj[page].bTrigger && !item.isEnd) { // 继续执行用户触发中断
						nextpage.processPreData(item, page, true);
					}
				}
			});
		},
		/**
		 * 预加载数据处理程序
		 * @param page 处理page页对应的数据
		 * @param bFromLoaded 是否来自加载完成调用
		 */
		processPreData : function (item, page, bFromLoaded) {
			var temp = item.tempPreObj[page];
			if(!temp) return false; // 程序异常，当前页未执行预加载
			temp.bTrigger = true;
			if(!bFromLoaded) {
				if(item.isProcessing || item.isEnd) return false ;
				nextpage.log('process...', item, nextpage.itemArray);
				item.isProcessing = true;
				item.options.onProcess(item.obj, item.options); // 触发开始处理事件
			}
			if(!temp.bLoaded) return false; // 数据未加载完成，等待加载完成后触发
			nextpage.log('process data...', item, nextpage.itemArray);
			nextpage.processData(item, temp.data, false);
			if(!item.isEnd) item.options.onPrcessDone(item.obj, temp.data, item.options); // 触发处理结束事件
			item.isProcessing = false;
			nextpage.log('process done...', item, nextpage.itemArray);
			item.options.processCallback(item.obj, temp.data, item.options); // 单次处理结束回调
			delete item.tempPreObj[page]; // 清除已完成缓存
			item.options.pageCurrent++; // 当前页加一
			nextpage.processPreLoad(item, page + 1);
		},
		isEmptyData : function (data) { // 判断数据是否为空
			if(!data || ($.isArray(data) && 0 == data.length) || $.isEmptyObject(data)) {
				return true;
			}
			return false;
		},
		processData : function (item, data, bJustCheck) { // 数据处理子程序
			data = item.options.dataFormater(data);
			if(this.isEmptyData(data)) { // 已处理完
				item.isEnd = true;
				item.options.onDone(item.obj, data, item.options); // 触发数据全部处理完成事件
				item.options.doneCallback(item.obj, data, item.options); // 数据全部处理完成回调
				return false;
			}
			if(bJustCheck) return true;
			var html = item.options.htmlFormater(data);
			$(item.options.containerSelector).append(html);

			window.iscrollObj?iscrollObj.refresh():false;
		},
		log : function () { // 输出日志
			if(!$.fn.nextpage.debug) return false;
			for (var i in arguments) {
				console.log(arguments[i]);
			}
		}
	};

	var nextpage2 = {
		isInit : false,
		itemArray : [], // 待处理对象数组
		itemExists : function (item) { // 判断待处理对象是否存在，并返回对象所在数组位置
			if(!item || !item.obj) return -1;
			var length = this.itemArray.length;
			for (var i = 0; i < length ; i++) {
				if(item.obj.selector == this.itemArray[i].obj.selector) return i;
			}
			return -1;
		},
		addItem : function (obj, options) { // 添加待处理对象
			if(!obj) return false;
			var item = {
				obj : obj, // 当前处理对象
				options : options, // 配置参数
				isProcessing : false, // 是否正在处理
				isEnd : false, // 处理完成，无更多数据
				tempPreObj : {} // 预加载缓存数据对象
			};
			var index = this.itemExists(item);
			if(-1 == index) {
				this.itemArray.push(item); // 不存在时添加
			} else {
				this.itemArray[index] = item; // 存在时更新
			}
			return item;
		},
		scrollFn:function(triggerObj,options,iscrollObj){
			if(triggerObj.parent().is(':hidden')) return;
			var isTriggering = false,
			$win = $(window);
			if(isTriggering) return true;
			nextpage2.log('trigger...', nextpage2.itemArray);
			isTriggering = true;
			var windowHeight = $win.height(),
			scrollTop = $win.scrollTop(),
			objOffsetTop = triggerObj.offset().top;
			nextpage2.log('objOffsetTop:' + objOffsetTop + ', windowHeight:' + windowHeight + ', scrollTop:' + scrollTop);
			
			if(!iscrollObj){
				if((windowHeight+scrollTop)<objOffsetTop-(options.offsetY?options.offsetY:0)){
					isTriggering = false;
					return true;
				}
			}else{
				if(iscrollObj.y>iscrollObj.maxScrollY){
					isTriggering = false;
					return true;
				}
			}

			for (var i in nextpage2.itemArray) {
				var item = nextpage2.itemArray[i];
				if(item.options.bPreLoad) { // 预加载
					nextpage2.processPreData(item, item.options.pageCurrent + 1, false);
				} else {
					nextpage2.process(item);
				}
			}
			
			isTriggering = false;
		},
		init : function (obj, options) { // 初始化函数
			var item = this.addItem(obj, options),
			$win = $(window),
			isBindIscroll = false;

			options.onInit(obj, options); // 触发初始化完成事件
			if(item.options.bPreLoad) { // 执行预加载
				nextpage2.processPreLoad(item, item.options.pageCurrent + 1);
			}
			if(options.bLoadOnInit) { // 立即加载数据
				if(item.options.bPreLoad) {
					this.processPreData(item, item.options.pageCurrent + 1, false);
				} else {
					this.process(item);
				}
			}
			if(this.isInit) return false;
			this.isInit = true;
			var isTriggering = false;

			$win.on('scroll', function(){
				nextpage2.scrollFn(obj,options,window.iscrollObj);
				if(window.iscrollObj){
					if(!isBindIscroll){
						iscrollObj.on('scrollEnd',  function(){
							nextpage2.scrollFn(obj,options,iscrollObj);
						});
						isBindIscroll = true;
					}
				}else{
					isBindIscroll = false;
				}
			}).trigger('scroll'); // 模拟触发
			
		},
		process : function (item) { // 处理子程序
			//debugger;
			if(item.isProcessing || item.isEnd) return false ;
			nextpage2.log('process...', item, nextpage2.itemArray);
			item.isProcessing = true;
			item.options.onProcess(item.obj, item.options); // 触发开始处理事件
			$.ajax({
				url : item.options.urlFormater(item.options, item.options.url, ++item.options.pageCurrent),
				data : item.options.paramters(item.options.pageCurrent),
				dataType : null == item.options.jsonp ? 'json' : 'jsonp',
				jsonp : item.options.jsonp,
				success : function (data) {
					//debugger;
					nextpage2.log('process data...', item, nextpage2.itemArray);
					nextpage2.processData(item, data, false);
					if(!item.isEnd) item.options.onPrcessDone(item.obj, data, item.options); // 触发处理结束事件
					item.isProcessing = false;
					nextpage2.log('process done...', item, nextpage2.itemArray);
					item.options.processCallback(item.obj, data, item.options); // 单次处理结束回调
				}
			});
		},
		/**
		 * 预加载数据
		 * @param page 预加载page页对应的数据
		 */
		processPreLoad : function (item, page) {
			item.tempPreObj[page] = {
				bTrigger : false, // 是否已被请求调用
				bLoaded : false, // 数据是否加载完成
				data : null // 预加载数据
			};
			$.ajax({
				url : item.options.urlFormater(item.options, item.options.url, page),
				data : item.options.paramters(page),
				dataType : null == item.options.jsonp ? 'json' : 'jsonp',
				jsonp : item.options.jsonp,
				success : function (data) {
					item.tempPreObj[page].bLoaded = true;
					item.tempPreObj[page].data = data;
					if(page - item.options.pageCurrent <= 1) { // 先处理数据，后触发中断
						nextpage2.processData(item, data, true);
					}
					if(item.tempPreObj[page].bTrigger && !item.isEnd) { // 继续执行用户触发中断
						nextpage2.processPreData(item, page, true);
					}
				}
			});
		},
		/**
		 * 预加载数据处理程序
		 * @param page 处理page页对应的数据
		 * @param bFromLoaded 是否来自加载完成调用
		 */
		processPreData : function (item, page, bFromLoaded) {
			var temp = item.tempPreObj[page];
			if(!temp) return false; // 程序异常，当前页未执行预加载
			temp.bTrigger = true;
			if(!bFromLoaded) {
				if(item.isProcessing || item.isEnd) return false ;
				nextpage2.log('process...', item, nextpage2.itemArray);
				item.isProcessing = true;
				item.options.onProcess(item.obj, item.options); // 触发开始处理事件
			}
			if(!temp.bLoaded) return false; // 数据未加载完成，等待加载完成后触发
			nextpage2.log('process data...', item, nextpage2.itemArray);
			nextpage2.processData(item, temp.data, false);
			if(!item.isEnd) item.options.onPrcessDone(item.obj, temp.data, item.options); // 触发处理结束事件
			item.isProcessing = false;
			nextpage2.log('process done...', item, nextpage2.itemArray);
			item.options.processCallback(item.obj, temp.data, item.options); // 单次处理结束回调
			delete item.tempPreObj[page]; // 清除已完成缓存
			item.options.pageCurrent++; // 当前页加一
			nextpage2.processPreLoad(item, page + 1);
		},
		isEmptyData : function (data) { // 判断数据是否为空
			if(!data || ($.isArray(data) && 0 == data.length) || $.isEmptyObject(data)) {
				return true;
			}
			return false;
		},
		processData : function (item, data, bJustCheck) { // 数据处理子程序
			data = item.options.dataFormater(data);
			if(this.isEmptyData(data)) { // 已处理完
				item.isEnd = true;
				item.options.onDone(item.obj, data, item.options); // 触发数据全部处理完成事件
				item.options.doneCallback(item.obj, data, item.options); // 数据全部处理完成回调
				return false;
			}
			if(bJustCheck) return true;
			var html = item.options.htmlFormater(data);
			$(item.options.containerSelector).append(html);

			window.iscrollObj?iscrollObj.refresh():false;
		},
		log : function () { // 输出日志
			if(!$.fn.nextpage.debug) return false;
			for (var i in arguments) {
				console.log(arguments[i]);
			}
		}
	};

	$.fn.nextpage = function (options) {
		options = $.extend({}, $.fn.nextpage.defaults, options);
		if(null == options.url) options.url = window.location.href;
		options.classObj.init($(this), options);
	};

	$.fn.nextpage.debug = false;
	$.fn.nextpage.defaults = {
		classObj:nextpage,
		url : null, // 请求地址，若为null则取当前页面地址
		paramters : function (page) { // 请求参数
			return {};
		},
		jsonp : null, // JSONP参数名称
		pageCurrent : 1, // 当前页码
		pageSize : 10, // 分页大小
		containerSelector : '#js-nextpage-container', // 容器选择器
		templateId : 'js-nextpage-template', // 模板ID
		templateHtml:null,
		offsetY : 0, //默认的Y轴偏移量（add lw 2015-5-19）
		urlForceSplite : null, // 强制采用中划线连接分页参数
		urlFormatNum : 1, // 请求数据格式化类型
		/**
		 * 格式化请求地址
		 * @param url 页面地址
		 * @param pageNext 下一页页码
		 */
		urlFormater : function (options, url, pageNext) {
			return url+'&page='+pageNext
		},
		dataFormater : function (data) { // 格式化返回数据
			return data.data;
		},
		htmlFormater : function (data) { // 渲染模板
			var _this = this;
			if(data.list.length){
				var lsHtml = [];
				$.each(data.list,function(i,v){
					lsHtml.push(baidu.template(_this.templateHtml,v));
				});
				//console.log(lsHtml)
				return lsHtml.join('');
			}
		},
		htmlTip : '', // 操作提示
		htmlLoad : '<img src="http://'+location.hostname+'/statics/img/common/loading.gif" width="20" height="20">正在加载中...', // 加载提示
		htmlDone : '下面没有更多视频了：）', // 处理完成提示
		bLoadOnInit : false, // 初始化完成后立即加载数据
		bPreLoad : false, // 预加载下一页
		onInit : function (obj, options) { // 初始化完成
			obj.html(this.htmlTip);
		},
		onProcess : function (obj, options) { // 开始处理
			obj.html(this.htmlLoad);
		},
		onPrcessDone : function (obj, data, options) { // 处理结束
			obj.html(this.htmlTip);
		},
		onDone : function (obj, data, options) { // 数据全部加载完成
			obj.html(this.htmlDone);
		},
		processCallback : function (obj, data, options) {}, // 单次处理完成回调
		doneCallback : function (obj, data) {} // 数据全部加载完成回调
	};
	//具体实现
	$(function(){
		var mid = location.pathname.split(".html")[0].split("/")[2];
		var scid = location.pathname.split('/music/')[1]&&location.pathname.split('/music/')[1].split('.html')[0];

		var pre = 'http://'+location.hostname;
		//mid = 10103;//测试用
		var getUserVod = '/video/web/get_member_videos?memberid='+mid+'&limit=10';
		var getHotVod = '/video/web/get_new_hots?scid='+scid+'&limit=10';
		var getNewVod = '/video/web/get_video_news?scid='+scid+'&limit=10';
		var getjcVod = '/video/web/get_splendid_video?limit=10';
		var getFamousVod = '/video/web/get_famous_videos?limit=30';

		var loadTip2 = '<img src="http://'+location.hostname+'/statics/img/common/loading2.gif" width="20" height="20">正在加载中...';


		var getUserListHtml = '<li class="lv_show">'+
										        '<a href="'+pre+'/v/<%=scid%>.html" target="play" title="<%=title%>" class="lv_show_1">'+
									            '<img src="<%=cover%>" class="lv_show_1_pic" alt="<%=title%>">'+
									            '<div class="lv_show_1_Play"><i class="icon-item-play"></i><h3 class="uk-text-truncate"><%=title%></h3></div>'+
										        '</a> '+
										        '<div class="lv_show_2">'+
							                '<a href="'+location.href+'" target="member" class="dbl" style="width:48px;" title="<%=nickname%>"><img src="<%=avatar%>" width="28" height="28" class="avatar40 m10 " alt="<%=nickname%>"></a>'+
							                '<p class="content-name pa uk-text-truncate"><a href="'+location.href+'" target="member" class="content-name-a " title="<%=nickname%>"><%=nickname%></a></p>'+
							                '<div class="content-like pa"><i class="list_icon icon-like" style=" margin-right:5px;"></i><span><%=praisecount%></span></div>'+
							                '<a href="javascript:void(0);" target="_blank" class="conten-command pa"><i class="list_icon icon-command" style=" margin-right:5px;"></i><span><%=topiccount%></span></a>'+
										        '</div>'+  
										    '</li>';

	  var vodHtml = '<li><a href="'+pre+'/m/<%=scid%>.html" target="play" title="小咖秀<%=title%>"><img src="<%=cover%>" alt="小咖秀<%=title%>" width="100%">'+
						        '<div class="author">'+
						        '<div class="tx"><img src="<%=avatar%>" alt="<%=nickname%>"></div>'+
						        '<div class="name"><%=title%></div>'+
						        '</div>'+
						        '</a>'+
						    	'</li>';
		// 用户视频
		if($('#user-npage').length){
			$('#user-npage').nextpage({
				url:getUserVod,
				containerSelector:'.list_video>ul',
				templateHtml:getUserListHtml
			});
		}
		//touch首页
		if($('#jc-npage').length){
			$('#jc-npage').nextpage({
				url:getjcVod,
				containerSelector:'#jcVod',
				htmlLoad : loadTip2,
				templateHtml:vodHtml
			});
		}

		
		//最热
		if($('#hot-npage').length){
			//if(!$('#hot_video li').length) return;
			$('#hot-npage').nextpage({
				url:getHotVod,
				containerSelector:'#hot_video>ul',
				htmlLoad : loadTip2,
				templateHtml:vodHtml
			});
		}
		
		//名人堂
		if($('#famous-npage').length){
			//if(!$('#hot_video li').length) return;
			$('#famous-npage').nextpage({
				url:getFamousVod,
				containerSelector:'#famous_video>ul',
				htmlLoad : loadTip2,
				templateHtml:vodHtml
			});
		}
		
		
		$('.sd02').one('click',function(){
			//最新
			if($('#new-npage').length){
				//if(!$('#new_video li').length) return;
				$('#new-npage').nextpage({
					classObj:nextpage2,
					url:getNewVod,
					containerSelector:'#new_video>ul',
					htmlLoad : loadTip2,
					templateHtml:vodHtml
				});
			}
		});
	});
})('undefined' == typeof(Zepto) ? jQuery : Zepto);


