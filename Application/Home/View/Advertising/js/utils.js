/**
 * 
 */
var Utils = {};
Utils.getModelValue = function (model, name) {
    var names = [];
    if (name.indexOf(".") > 0) {
        names = name.split(".");
    }
    else {
        names.push(name)
    }
    for (var i = 0; i < names.length; i++) {
        model = model[names[i]];
        if (names[i] == "addTime") {
            model = model + "000";
            model = formatDate(model);
        }
    }
    return model;
}
Utils.dataBind = function (Scopes, attName, viewModel) {
    var dataBinds = Scopes.querySelectorAll("[" + attName + "]").toAttay();
    $(Scopes).attr("data-root", true);
    dataBinds.push(Scopes);
    for (var i = 0; i < dataBinds.length; i++) {
        var elThis = dataBinds[i];
        if (!$(elThis).attr(attName)) {
            continue;
        }
        var name = elThis.getAttribute(attName);
        //%style%backgroundImage:image
        if (name.indexOf("%style%") == 0) {
            name = name.substr(7);
            var styleName = name.substr(0, name.indexOf(":"));
            var styleValue = name.substr(styleName.length + 1);
            var setStyleValue = Utils.getModelValue(viewModel, styleValue);
            switch (styleName) {
                case "backgroundImage":
                    setStyleValue = "url(" + setStyleValue + ")";
                    break;
            }
            elThis.style[styleName] = setStyleValue;
        } else
            if (name.length > 5 && name.substr(0, 6) == "%this%") {
                //console.log(name);

                name = name.substr(6);
                var iname = name;
                var length = name.split("%}").length;
                for (var i2 = 0; i2 < length; i2++) {
                    valueName = iname.substring(iname.indexOf("{%") + 2, iname.indexOf("%}"));
                    name = name.replace("{%" + valueName + "%}", Utils.getModelValue(viewModel, valueName));
                    iname = iname.substr(iname.indexOf("%}") + 2);
                }
                $(elThis).attr(attName, name);
                //console.log($(elThis).attr(attName));
            }
            else {
                if (attName == "data-tap" || $(elThis).attr("data-root")) {
                    continue;
                }
                var value = Utils.getModelValue(viewModel, name);
                if (value == undefined) {
                    value = "";
                }
                switch (elThis.nodeName) {
                    case "INPUT":
                        elThis.value = value;
                        break;
                    case "IMG":
                    case "AUDIO":
                        $(elThis).attr("src", value);
                        break;
                    default:
                        elThis.innerHTML = value;
                        break;
                }
            }

    }
}
NodeList.prototype.toAttay = function () {
    var arr = [];
    var list = this;
    for (var i = 0; i < list.length; i++) {
        var li = list[i];
        arr.push(li); //arr就是我们要的数组  
    }
    return arr;
}
/**获取url地址中指定参数的值*/
function getQueryString(name) {
    var reg = new RegExp("(^|&)" + name + "=([^&]*)(&|$)", "i");
    var r = window.location.search.substr(1).match(reg);
    if (r != null) return unescape(r[2]);
    return null;
}

function utf8_to_b64(str) {
    return window.btoa(unescape(encodeURIComponent(str)));
}
function b64_to_utf8(str) {
    return decodeURIComponent(escape(window.atob(str)));
}

function dataListBind(ViewModel) {
    var dataList = document.querySelectorAll("[data-list]");
    for (var i = 0; i < dataList.length; i++) {
        var elThis = dataList[i];//要循环重复的元素
        var name = elThis.getAttribute("data-list");
        var viewModel = Utils.getModelValue(ViewModel, name);//要循环的数据
        var $elThis = $(elThis);
        $elThis.hide();
        if(!name)continue;
        if(!viewModel)break;
        var $newEl = $elThis;
        for (var i2 = 0; i2 < viewModel.length; i2++) {
            var newel = $elThis.clone();
            newel.show();
            newel.removeAttr("data-list");
            $newEl.after(newel);
            $newEl = newel;
            Utils.dataBind($newEl.get(0), "data-list-bind", viewModel[i2]);
            Utils.dataBind($newEl.get(0), "data-list-tap", viewModel[i2]);
        }
        //$elThis.remove();
    }
}
function formatDate(date) {
    console.log(date);
    var d = new Date(parseInt(date));
    console.log(d);
    var cd = new Date();
    if (d.getFullYear() == cd.getFullYear() && d.getMonth() == cd.getMonth() && d.getDate() == cd.getDate()) {
        return "今天 " + d.format("hh:mm");
    }
    else {
        return d.format("yyyy-MM-dd hh:mm:ss");//.getFullYear().toString().substr(2) + "." + (d.getMonth() + 1) + "." + d.getDate() + " " + d.getHours() + ":" + d.getMinutes();
    }
};
$(function () {
    if (typeof ViewModel != "undefined") {
        Utils.dataBind(document, "data-bind", ViewModel);
        Utils.dataBind(document, "data-tap", ViewModel);
        dataListBind(ViewModel);
    }
   /* if(ViewModel.specialList){
        if(ViewModel.specialList.length>1){
            for(var a=1;a<ViewModel.specialList.length;a++){
                Utils.dataBind(document, "data-bind", ViewModel.specialList[a]);
                Utils.dataBind(document, "data-tap", ViewModel.specialList[a]);
            }
            dataListBind(ViewModel);
        }
    }else if (typeof ViewModel != "undefined") {
        Utils.dataBind(document, "data-bind", ViewModel);
        Utils.dataBind(document, "data-tap", ViewModel);
        dataListBind(ViewModel);
    }*/



    var taps = document.querySelectorAll("[data-tap]");
    for (var i = 0; i < taps.length; i++) {
        var elThis = taps[i];
        new Hammer(elThis).on("tap", function () {
            window.location = this.getAttribute("data-tap");
        }.bind(elThis));
    };
    var taps = document.querySelectorAll("[data-list-tap]");
    for (var i = 0; i < taps.length; i++) {
        var elThis = taps[i];
        new Hammer(elThis).on("tap", function () {
            window.location = this.getAttribute("data-list-tap");
        }.bind(elThis));
    };
    $("body").css("visibility", "visible");
});
Date.prototype.format = function (format) {
    var o = {
        "M+": this.getMonth() + 1, //month
        "d+": this.getDate(),    //day
        "h+": this.getHours(),   //hour
        "m+": this.getMinutes(), //minute
        "s+": this.getSeconds(), //second
        "q+": Math.floor((this.getMonth() + 3) / 3), //quarter
        "S": this.getMilliseconds() //millisecond
    }
    if (/(y+)/.test(format)) format = format.replace(RegExp.$1,
(this.getFullYear() + "").substr(4 - RegExp.$1.length));
    for (var k in o) if (new RegExp("(" + k + ")").test(format))
        format = format.replace(RegExp.$1,
RegExp.$1.length == 1 ? o[k] :
("00" + o[k]).substr(("" + o[k]).length));
    return format;
}

/*
 * t: current time（当前时间）
 * b: beginning value（初始值）
 * c: change in value（变化量）
 * d: duration（持续时间）
*/
var Tween = {
    Linear: function (t, b, c, d) { return c * t / d + b; },
    Quad: {
        easeIn: function (t, b, c, d) {
            return c * (t /= d) * t + b;
        },
        easeOut: function (t, b, c, d) {
            return -c * (t /= d) * (t - 2) + b;
        },
        easeInOut: function (t, b, c, d) {
            if ((t /= d / 2) < 1) return c / 2 * t * t + b;
            return -c / 2 * ((--t) * (t - 2) - 1) + b;
        }
    },
    Cubic: {
        easeIn: function (t, b, c, d) {
            return c * (t /= d) * t * t + b;
        },
        easeOut: function (t, b, c, d) {
            return c * ((t = t / d - 1) * t * t + 1) + b;
        },
        easeInOut: function (t, b, c, d) {
            if ((t /= d / 2) < 1) return c / 2 * t * t * t + b;
            return c / 2 * ((t -= 2) * t * t + 2) + b;
        }
    },
    Quart: {
        easeIn: function (t, b, c, d) {
            return c * (t /= d) * t * t * t + b;
        },
        easeOut: function (t, b, c, d) {
            return -c * ((t = t / d - 1) * t * t * t - 1) + b;
        },
        easeInOut: function (t, b, c, d) {
            if ((t /= d / 2) < 1) return c / 2 * t * t * t * t + b;
            return -c / 2 * ((t -= 2) * t * t * t - 2) + b;
        }
    },
    Quint: {
        easeIn: function (t, b, c, d) {
            return c * (t /= d) * t * t * t * t + b;
        },
        easeOut: function (t, b, c, d) {
            return c * ((t = t / d - 1) * t * t * t * t + 1) + b;
        },
        easeInOut: function (t, b, c, d) {
            if ((t /= d / 2) < 1) return c / 2 * t * t * t * t * t + b;
            return c / 2 * ((t -= 2) * t * t * t * t + 2) + b;
        }
    },
    Sine: {
        easeIn: function (t, b, c, d) {
            return -c * Math.cos(t / d * (Math.PI / 2)) + c + b;
        },
        easeOut: function (t, b, c, d) {
            return c * Math.sin(t / d * (Math.PI / 2)) + b;
        },
        easeInOut: function (t, b, c, d) {
            return -c / 2 * (Math.cos(Math.PI * t / d) - 1) + b;
        }
    },
    Expo: {
        easeIn: function (t, b, c, d) {
            return (t == 0) ? b : c * Math.pow(2, 10 * (t / d - 1)) + b;
        },
        easeOut: function (t, b, c, d) {
            return (t == d) ? b + c : c * (-Math.pow(2, -10 * t / d) + 1) + b;
        },
        easeInOut: function (t, b, c, d) {
            if (t == 0) return b;
            if (t == d) return b + c;
            if ((t /= d / 2) < 1) return c / 2 * Math.pow(2, 10 * (t - 1)) + b;
            return c / 2 * (-Math.pow(2, -10 * --t) + 2) + b;
        }
    },
    Circ: {
        easeIn: function (t, b, c, d) {
            return -c * (Math.sqrt(1 - (t /= d) * t) - 1) + b;
        },
        easeOut: function (t, b, c, d) {
            return c * Math.sqrt(1 - (t = t / d - 1) * t) + b;
        },
        easeInOut: function (t, b, c, d) {
            if ((t /= d / 2) < 1) return -c / 2 * (Math.sqrt(1 - t * t) - 1) + b;
            return c / 2 * (Math.sqrt(1 - (t -= 2) * t) + 1) + b;
        }
    },
    Elastic: {
        easeIn: function (t, b, c, d, a, p) {
            if (t == 0) return b; if ((t /= d) == 1) return b + c; if (!p) p = d * .3;
            if (!a || a < Math.abs(c)) { a = c; var s = p / 4; }
            else var s = p / (2 * Math.PI) * Math.asin(c / a);
            return -(a * Math.pow(2, 10 * (t -= 1)) * Math.sin((t * d - s) * (2 * Math.PI) / p)) + b;
        },
        easeOut: function (t, b, c, d, a, p) {
            if (t == 0) return b; if ((t /= d) == 1) return b + c; if (!p) p = d * .3;
            if (!a || a < Math.abs(c)) { a = c; var s = p / 4; }
            else var s = p / (2 * Math.PI) * Math.asin(c / a);
            return (a * Math.pow(2, -10 * t) * Math.sin((t * d - s) * (2 * Math.PI) / p) + c + b);
        },
        easeInOut: function (t, b, c, d, a, p) {
            if (t == 0) return b; if ((t /= d / 2) == 2) return b + c; if (!p) p = d * (.3 * 1.5);
            if (!a || a < Math.abs(c)) { a = c; var s = p / 4; }
            else var s = p / (2 * Math.PI) * Math.asin(c / a);
            if (t < 1) return -.5 * (a * Math.pow(2, 10 * (t -= 1)) * Math.sin((t * d - s) * (2 * Math.PI) / p)) + b;
            return a * Math.pow(2, -10 * (t -= 1)) * Math.sin((t * d - s) * (2 * Math.PI) / p) * .5 + c + b;
        }
    },
    Back: {
        easeIn: function (t, b, c, d, s) {
            if (s == undefined) s = 1.70158;
            return c * (t /= d) * t * ((s + 1) * t - s) + b;
        },
        easeOut: function (t, b, c, d, s) {
            if (s == undefined) s = 1.70158;
            return c * ((t = t / d - 1) * t * ((s + 1) * t + s) + 1) + b;
        },
        easeInOut: function (t, b, c, d, s) {
            if (s == undefined) s = 1.70158;
            if ((t /= d / 2) < 1) return c / 2 * (t * t * (((s *= (1.525)) + 1) * t - s)) + b;
            return c / 2 * ((t -= 2) * t * (((s *= (1.525)) + 1) * t + s) + 2) + b;
        }
    },
    Bounce: {
        easeIn: function (t, b, c, d) {
            return c - Tween.Bounce.easeOut(d - t, 0, c, d) + b;
        },
        easeOut: function (t, b, c, d) {
            if ((t /= d) < (1 / 2.75)) {
                return c * (7.5625 * t * t) + b;
            } else if (t < (2 / 2.75)) {
                return c * (7.5625 * (t -= (1.5 / 2.75)) * t + .75) + b;
            } else if (t < (2.5 / 2.75)) {
                return c * (7.5625 * (t -= (2.25 / 2.75)) * t + .9375) + b;
            } else {
                return c * (7.5625 * (t -= (2.625 / 2.75)) * t + .984375) + b;
            }
        },
        easeInOut: function (t, b, c, d) {
            if (t < d / 2) return Tween.Bounce.easeIn(t * 2, 0, c, d) * .5 + b;
            else return Tween.Bounce.easeOut(t * 2 - d, 0, c, d) * .5 + c * .5 + b;
        }
    }
}
window.requestAnimFrame = (function () {
    return window.requestAnimationFrame ||
            window.webkitRequestAnimationFrame ||
            window.mozRequestAnimationFrame ||
            function (callback) {
                window.setTimeout(callback, 1000 / 60);
            };
})();

function message(at) {
    if ($(".promptbox1").length == 0) {
        $("body").first().append('<div class="promptbox1" style="position:fixed;background:#ffffff;color:#000000;top:0">' + at + '</div>')
        //$("body")[0].append('<div class="promptbox"><div class="mask"></div><div class="promptbox-content"><p>任务成功完成!</p><p>获得<span>2.5</span>元奖励!</p></div></div>');
    }
    else {
        $(".promptbox1").html(at);
    }
    $(".promptbox1").show();
}