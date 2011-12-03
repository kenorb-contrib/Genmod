function createRequestObject() {
    var ro;
    var browser = navigator.appName;
    if(browser == "Microsoft Internet Explorer"){
        ro = new ActiveXObject("Microsoft.XMLHTTP");
    }else{
        ro = new XMLHttpRequest();
    }
    return ro;
}

var http = createRequestObject();
var element_id;
var action_value;

function sndReq(element, action, mode, param1, val1, param2, val2, param3, val3, param4, val4, param5, val5, param6, val6, param7, val7, param8, val8, param9, val9, param10, val10) {
	param1 = typeof(param1) != 'undefined' ? param1 : "dummy1";
	param2 = typeof(param2) != 'undefined' ? param2 : "dummy2";
	param3 = typeof(param3) != 'undefined' ? param3 : "dummy3";
	param4 = typeof(param4) != 'undefined' ? param4 : "dummy4";
	param5 = typeof(param5) != 'undefined' ? param5 : "dummy5";
	param6 = typeof(param6) != 'undefined' ? param6 : "dummy6";
	param7 = typeof(param7) != 'undefined' ? param7 : "dummy7";
	param8 = typeof(param8) != 'undefined' ? param8 : "dummy8";
	param9 = typeof(param9) != 'undefined' ? param9 : "dummy9";
	param10 = typeof(param10) != 'undefined' ? param10 : "dummy10";
	val1 = typeof(val1) != 'undefined' ? val1 : "";
	val2 = typeof(val2) != 'undefined' ? val2 : "";
	val3 = typeof(val3) != 'undefined' ? val3 : "";
	val4 = typeof(val4) != 'undefined' ? val4 : "";
	val5 = typeof(val5) != 'undefined' ? val5 : "";
	val6 = typeof(val6) != 'undefined' ? val6 : "";
	val7 = typeof(val7) != 'undefined' ? val7 : "";
	val8 = typeof(val8) != 'undefined' ? val8 : "";
	val9 = typeof(val9) != 'undefined' ? val9 : "";
	val10 = typeof(val10) != 'undefined' ? val10 : "";
	var randomnumber = Math.floor(Math.random()*1000001)
	element_id = element;
	action_value = action;
    http.open('GET', 'gmrpc.php?action='+action+'&'+param1+'='+val1+'&'+param2+'='+val2+'&'+param3+'='+val3+'&'+param4+'='+val4+'&'+param5+'='+val5+'&'+param6+'='+val6+'&'+param7+'='+val7+'&'+param8+'='+val8+'&'+param9+'='+val9+'&'+param10+'='+val10+'&'+sessionname+'='+sessionid+'&wqp='+randomnumber, mode);
    http.send(null);
    http.onreadystatechange = handleResponse;

}

function handleResponse() {
    if(http.readyState == 4) {
        document.getElementById(element_id).innerHTML = http.responseText;
        // Fix for IE not rendering the div after AJAX load. 
        // We have to preserve the focus because it is reset by switching display
        el = document.activeElement;
        document.getElementById('GenmodContainer').style.display="none";
        document.getElementById('GenmodContainer').style.display="block";
        if (el) el.focus();
    }
   	else if (action_value != 'remembertab') document.getElementById(element_id).innerHTML = '<img src="images/ajax-loader.gif" />';
}