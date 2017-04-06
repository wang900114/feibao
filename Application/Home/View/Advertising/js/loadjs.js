function linkScript(src) {
    var flushjs = src; //+ "?timeStamp=" + new Date().getTime();
    var jsOne = document.createElement("script");
    var head = document.getElementsByTagName("head")[0];
    head.appendChild(jsOne);
    jsOne.setAttribute("src", flushjs);
}
linkScript("js/hammer.min.js?timeStamp=1");
linkScript("js/jquery.min.js?timeStamp=1");
linkScript("js/utils.js?timeStamp=1");
linkScript("js/services.js?timeStamp=1");