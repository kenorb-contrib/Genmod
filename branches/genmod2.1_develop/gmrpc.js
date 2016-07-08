function sndReq(element, action, mode, param1, val1, param2, val2, param3, val3, param4, val4, param5, val5, param6, val6, param7, val7, param8, val8, param9, val9, param10, val10) {
	sndReq2(element, action, mode, param1, val1, param2, val2, param3, val3, param4, val4, param5, val5, param6, val6, param7, val7, param8, val8, param9, val9, param10, val10, function RPCCallBack( response ){document.getElementById(element).innerHTML="";document.getElementById(element).innerHTML=response;});
}
	

function sndReq2(element, action, mode, param1, val1, param2, val2, param3, val3, param4, val4, param5, val5, param6, val6, param7, val7, param8, val8, param9, val9, param10, val10, RPCCallBack) {
    browser = navigator.appName;
	var element_id;
    if(browser == "Microsoft Internet Explorer"){
        var http = new ActiveXObject("Microsoft.XMLHTTP");
    }else{
        var http = new XMLHttpRequest();
    }
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
    http.onreadystatechange = function() {
		if(http.readyState == 4) {
			if (RPCCallBack) RPCCallBack(http.responseText);
		}
		else if (action_value != 'remembertab' && action_value != 'set_show_changes' && element_id !='dummy') document.getElementById(element_id).innerHTML = '<img src="images/ajax-loader.gif" />';
	}
    http.open('GET', 'gmrpc.php?action='+action+'&'+param1+'='+val1+'&'+param2+'='+val2+'&'+param3+'='+val3+'&'+param4+'='+val4+'&'+param5+'='+val5+'&'+param6+'='+val6+'&'+param7+'='+val7+'&'+param8+'='+val8+'&'+param9+'='+val9+'&'+param10+'='+val10+'&'+sessionname+'='+sessionid+'&wqp='+randomnumber, mode);
    http.send();
}

