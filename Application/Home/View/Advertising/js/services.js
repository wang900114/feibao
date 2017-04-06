/// <reference path="jquery.min.js" />
var services = {};
services.base = "/index.php/API_IOS_3.3/";
services.base2 = "/index.php/Home/";
services.getDate2 = function (url, para) {
    var ret;
    data = {
        version: getQueryString("version"),
        userId: getQueryString("userId"),
        phone: getQueryString("phone"),
        //version: 3.2,
        //userId: "8684YmYwNWZjODdkMTA1NjcyZmNiZDUyNzFhYzYxODE4MWY3YjY5YTJiOWJhODNhYzBlYTAyNDVkZjc5YzI0YWFlYjI3ZjMyYjBkN2EwZmE1ZjQ5OWVmZjMwMTVhYjM0MTBiOGU4YjcyZGZhMjMyYTczMGQ2Y2M0ZGFkZjkxOGRjMTk4ZDhiYjdmNDMwZjdjYjQ3YjM4NWIxMjdmYTc1YzJhZmZiNWI;",
        //phone: "18611988820",
        mobile_platform: getQueryString("mobile_platform")
    };
    for (var p in para) {
        if (typeof para != "function") {
            data[p] = para[p];
        }
    }
    console.log(data);
    $.ajax({
        url: services.base2 + url,
        data: data,
        cache: false,
        async: false,
        type: 'POST',
        dataType: 'json',
        success: function (data) {
            if (data.status == 1 || data.status == 36) {
                ret = data;
            }
        },
        error: function (XMLHttpRequest, textStatus, errorThrown) {
            console.log(XMLHttpRequest.status);
            console.log(XMLHttpRequest.readyState);
            console.log(textStatus);
        }
    });
    console.log(ret);
    return ret;
};
/**获取广告详情*/
services.getADDetail2 = function (advId) {
    return services.getDate2("Advertising/advertShare", { advId: advId }).info;
};
/**获取服务、商品、优惠代金券详情
 * @param advId 广告的id
 * @param type 类型：1服务，2商品，3优惠代金券【必填项】
 */
services.getzhDetail2 = function (advId, type) {
    return services.getDate2("Advertising/zhDetailShare", { advId: advId, type: type }).info;
};
services.getDate = function (url,para) {
    var ret;
    data = {
        version: getQueryString("version"),
        userId: getQueryString("userId"),
        phone: getQueryString("phone"),
        //version: 3.2,
        //userId: "8684YmYwNWZjODdkMTA1NjcyZmNiZDUyNzFhYzYxODE4MWY3YjY5YTJiOWJhODNhYzBlYTAyNDVkZjc5YzI0YWFlYjI3ZjMyYjBkN2EwZmE1ZjQ5OWVmZjMwMTVhYjM0MTBiOGU4YjcyZGZhMjMyYTczMGQ2Y2M0ZGFkZjkxOGRjMTk4ZDhiYjdmNDMwZjdjYjQ3YjM4NWIxMjdmYTc1YzJhZmZiNWI;",
        //phone: "18611988820",
        mobile_platform: getQueryString("mobile_platform")
    };
    for (var p in para) {
        if (typeof para!="function") {
            data[p] = para[p];
        }        
    }
    console.log(data);
    $.ajax({
        url: services.base + url,
        data: data,
        cache: false,
        async: false,
        type: 'POST',
        dataType: 'json',
        success: function (data) {
            if (data.status == 1 || data.status == 36 || data.status == 9) {
                ret = data;
            }
        },
        error: function (XMLHttpRequest, textStatus, errorThrown) {
            console.log(XMLHttpRequest.status);
            console.log(XMLHttpRequest.readyState);
            console.log(textStatus);
        }
    });
    console.log(ret);
    return ret;
};
/**获取广告详情*/
services.getADDetail = function (advId) {
    return services.getDate("Advertising/detail", { advId: advId }).info;
};
/**喜欢无感 获取广告详情
 * 1喜欢，2无感
 */
services.setgetADLove = function (advId,type) {
    return services.getDate("Advertising/getRedBag", { advId: advId, type: type });
}
/**获取服务、商品、优惠代金券,活动详情
 * @param advId 广告的id
 * @param type 类型：1服务，2商品，3优惠代金券，4活动【必填项】
 */
services.getzhDetail = function (advId,type) {
    return services.getDate("Advertising/zhDetail", { advId: advId, type: type }).info;
};
/**
 * @param friendId 朋友id
 */
services.getShopInfo = function (friendId) {
    return services.getDate("Friends/getShopInfo", { friendId: friendId}).info;
};
/**领取优惠代金券
 * @param advId 广告id
 */
services.addFavorable = function (advId) {
    return services.getDate("Advertising/addFavorable", { advId: advId, type:1 });
};

/**
 * 我的钱包
 * 返回
 * userId: 会员id
money: 会员钱包金额
integral: 会员飞币数
aliIsSet: 是否绑定了支付宝 1：绑定，2：未绑定
Ali_number: 阿里支付宝帐号
Ali_user_name: 阿里支付宝用户名称 
app_withdrawals_10_on：可提现金额1
app_withdrawals_30_on：可提现金额2
app_withdrawals_50_on：可提现金额3
 */
services.myBag = function () {
    return services.getDate("AppPackage/getUserMoney");
};

/**申请提现
 * @param money 提现金额
 */
services.addWithdrawalsOrder = function (money) {
    return services.getDate("AppPackage/addWithdrawalsOrder", { money: money });
};
/**绑宝支付宝
 * @param ali_number 阿里支付宝帐号
 * @param ali_user_name 阿里支付宝用户名称
 */
services.addWithdrawalsOrder = function (ali_number, ali_user_name) {
    return services.getDate("AppPackage/bingAlipay", { ali_number: ali_number, ali_user_name: ali_user_name });
};
/**获取专题详情
 * @param themeId 专题id
 */
services.getAdtopic = function (themeId, page) {
    var d = services.getDate("Theme/detail", { themeId: themeId, page: page });
    if (typeof d=="undefined") {
        return undefined;
    }
    return d.info;
};
/**获取音乐播放次数
 */
services.getMusicCount = function () {
    return services.getDate("Advertising/recordPV", { type: 1 });
};
/*
* 主题列表
*/
services.getSpecialList = function (dataId,page,lastselTime) {
    var d = services.getDate("Theme/getThemeList", { dataId: dataId, page: page ,lastselTime: lastselTime});
    if (typeof d=="undefined") {
        return undefined;
    }
    return d;
};
/*关注商家
*friendId 朋友id
*/
services.payBusiness = function (friendId,type) {//1关注2取消关注
    return services.getDate("Friend/attention", { friendId: friendId, type:type });
};
