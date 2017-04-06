/* 
 * 日期插件
 * 滑动选取日期（年，月，日）
 * V1.1
 */
(function ($) {      
    $.fn.data = function (options,Ycallback,Ncallback) {   
        //插件默认选项
        var that = $(this);
        var docType = $(this).is('input');
        var datetime = false;
        var nowdate = new Date();
        var indexY=1,indexM=1,indexD=1;
        var initY=parseInt((nowdate.getYear()+"").substr(1,2));
        $.fn.data.defaultOptions = {
            curdate:false,                   //打开日期是否定位到当前日期
            theme:"date",                    //控件样式（1：日期，2：日期+时间）
            mode:null,                       //操作模式（滑动模式）
            event:"click",                    //打开日期插件默认方式为点击后后弹出日期 
            show:true
        }
        //用户选项覆盖插件默认选项   
        var opts = $.extend( true, {}, $.fn.data.defaultOptions, options );
        if(opts.theme === "datetime"){datetime = true;}
        if(!opts.show){
            that.unbind('click');
        }
		/*www.sucaijiayuan.com*/
        else{
            //绑定事件（默认事件为获取焦点）
            that.bind(opts.event,function () {
                createUL();      //动态生成控件显示的日期
                init_iScrll();   //初始化iscrll
                extendOptions(); //显示控件
                that.blur();
                if(datetime){
                    showdatetime();
                    refreshTime();
                }
                refreshDate();
                bindButton();
            })  
        };
        function refreshDate(){
            yearScroll.refresh();
            resetInitDete();
            yearScroll.scrollTo(0, initY*40, 100, true);
        }
		function resetIndex(){
            indexY=1;
            indexM=1;
            indexD=1;
        }
		function resetInitDete(){
            if(opts.curdate){return false;}
            else if(that.val()===""){return false;}
            initY = parseInt(that.val().substr(2,2));
            initM = parseInt(that.val().substr(5,2));
            initD = parseInt(that.val().substr(8,2));
        }
        function bindButton(){
            resetIndex();
            $("#dateconfirm").click(function () {	
                var datestr = $("#yearwrapper ul li:eq("+indexY+")").html().substr(0,$("#yearwrapper ul li:eq("+indexY+")").html().length);
				//alert("0");
				//alert($("#jf span").text());
				if($("#cz_t").text()>=$("#jf span").text()){
					$("#btn").prop({disabled: true}).css({color:'#ccc'});
					//alert(1);
				}else{
					$("#btn").prop({disabled: false}).css({color:'red'});
					//alert(2);
				}
				/*console.log(indexY);
				alert(datestr);*/
                if(Ycallback===undefined){
                     if(docType){that.val(datestr);}else{that.html(datestr);}
                }else{
                     Ycallback(datestr);          
                }
                $("#datePage").hide(); 
                $("#dateshadow").hide();
				
				//$("")
            });
            $("#datecancle").click(function () {
                $("#datePage").hide(); 
				$("#dateshadow").hide();
                //Ncallback(false);
            });
        }		
        function extendOptions(){
            $("#datePage").show(); 
            $("#dateshadow").show();
        }
        //列表滑动
        function init_iScrll() { 
            var strY = $("#yearwrapper ul li:eq("+indexY+")").html().substr(0,$("#yearwrapper ul li:eq("+indexY+")").html().length-1);
              yearScroll = new iScroll("yearwrapper",{snap:"li",vScrollbar:false,
                  onScrollEnd:function () {
                       indexY = (this.y/40)*(-1)+1;
					   $("#yearwrapper ul li").css('color','#898989');
					   $("#yearwrapper ul li:eq("+indexY+")").css('color','red');
                  }
			  });
        }
        function  createUL(){
            CreateDateUI();
            $("#yearwrapper ul").html(createYEAR_UL());
        }
        function CreateDateUI(){
            var str = ''+
                '<div id="dateshadow"></div>'+
                '<div id="datePage" class="page">'+
                    '<section>'+
                        '<div id="datemark"></div>'+
                        '<div id="datescroll">'+
                            '<div id="yearwrapper">'+
                                '<ul style="-webkit-transform:translateY(0)"></ul>'+
                            '</div>'+
                        '</div>'+
                    '</section>'+
                    '<footer id="dateFooter">'+
                        '<div id="setcancle">'+
                            '<ul>'+
                                '<li id="dateconfirm">确定</li>'+
                                '<li id="datecancle">取消</li>'+
                            '</ul>'+
                        '</div>'+
                    '</footer>'+
                '</div>'
            $("#datePlugin").html(str);
        }
        function addTimeStyle(){
            $("#datePage").css("height","380px");
            $("#datePage").css("top","60px");
            $("#yearwrapper").css("position","absolute");
            $("#yearwrapper").css("bottom","200px");
            $("#monthwrapper").css("position","absolute");
            $("#monthwrapper").css("bottom","200px");
            $("#daywrapper").css("position","absolute");
            $("#daywrapper").css("bottom","200px");
        }

		
        //创建  列表
        function createYEAR_UL(){

		var json = [[1,1000],[10,15000],[30,40000],[50,65000]];

		//console.log(json.length);
            var str="<li>&nbsp;</li>";
			for(var i=0; i<json.length;i++){
                str+="<li><span id='czy'><span id='cz_o'>"+json[i][0]+"</span>元</span>&nbsp;&nbsp;&nbsp;<span id='czjf'>消费<span id='cz_t'>"+json[i][1]+"</span>积分</span></li>";
				//console.log(json[i][0],json[i][1]);
            }
            return str+="<li>&nbsp;</li>";
        }
    }
})(jQuery);  
