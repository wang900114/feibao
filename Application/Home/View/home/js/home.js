/**
 * Created by Ena on 2016/6/6.
 */
    $(function(){
        //返回顶部
        var aboutStr = "<div id='rightQQ'><ul><li><img src='http://www.feibaokeji.com/templates/new/images/ten.png' width='80' /><ul><li class='f-h-T'><span class='kf-zt'>客服QQ</span><a href='http://wpa.qq.com/msgrd?v=3&amp;uin=2590369801&amp;site=qq&amp;menu=yes' target='_blank'><img alt='点击这里给我发消息' border='0' src='http://www.feibaokeji.com/templates/new/images/QQ.png' title='点击这里给我发消息' /></a><a href='http://wpa.qq.com/msgrd?v=3&amp;uin=1523405080&amp;site=qq&amp;menu=yes' target='_blank'><img alt='点击这里给我发消息' border='0' src='http://www.feibaokeji.com/templates/new/images/QQ.png' title='点击这里给我发消息' /></a></li><li><sapn class='kf-zt'>商务QQ<a target='_blank' href='http://wpa.qq.com/msgrd?v=3&amp;uin=369986663&amp;site=qq&amp;menu=yes'><img src='http://www.feibaokeji.com/templates/new/images/QQ.png' /></a></sapn></li><li class='f-h-b'><sapn class='kf-zt'>技术QQ<a href='http://wpa.qq.com/msgrd?v=3&amp;uin=2260243064&amp;site=qq&amp;menu=yes' target='_blank'><img alt='点击这里给我发消息' border='0' src='http://www.feibaokeji.com/templates/new/images/QQ.png' title='点击这里给我发消息' /></a></sapn></li></ul></li><li><img src='http://www.feibaokeji.com/templates/new/images/tel.png' /><ul><li class='kf-two'><span>客服电话</span><a href='tel:400-652-0458'>400-652-0458</a></li></ul></li></ul></div>";

        $("body").append(aboutStr);
        if ($(window).width()<500){
            $("#phonel").css({"display":"block"});
            $("#tell").css({"display":"none"});


            //客服右侧的手机上面效果
            $("#rightQQ ul li").click(function(){
                if( $(this).find("ul").is(":hidden")){
                    $(this).find("ul").css("display","block");
                    $(this).siblings().find("ul").hide();

                }else {
                    $(this).find("ul").css("display","none");
                }


            })


        } else {

            $("#phonel").css({"display":"none"});
            $("#tell").css({"display":"block"});

            //客服右侧的PC上面效果
            $("#rightQQ ul li").hover(
                function(){
                    $(this).find("ul").css("display","block");
                },
                function(){
                    $(this).find("ul").css("display","none");

                }
            )

            $(".kf-two a").removeAttr("href");
        }



//客服右侧的点击到顶部

        $("#top_btn").click(function(){
            //if(scroll=="off") return;
            $("html,body").animate({
                scrollTop: 0
            }, 600);
        });




    })




