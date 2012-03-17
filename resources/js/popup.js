
var popupStatus = 0;

//loading popup with jQuery magic!
function load_gal_Popup(){
	//loads popup only if it is disabled
	if(popupStatus==0){
		$("#backgroundPopup").css({
			"opacity": "0.7"
		});
		$("#backgroundPopup").fadeIn("slow");
		$("#popup_gallery").fadeIn("slow");
		popupStatus = 1;
	}
}

function load_sendmail_Popup(){
	//loads popup only if it is disabled
	if(popupStatus==0){
		$("#backgroundPopup").css({
			"opacity": "0.7"
		});
		$("#backgroundPopup").fadeIn("slow");
		$("#popup_sendmail").fadeIn("slow");
		popupStatus = 1;
	}
}

function load_contactmail_Popup(){
	//loads popup only if it is disabled
	if(popupStatus==0){
		$("#backgroundPopup").css({
			"opacity": "0.7"
		});
		$("#backgroundPopup").fadeIn("slow");
		$("#popup_contactmail").fadeIn("slow");
		popupStatus = 1;
	}
}

function load_flaglisting_Popup(){
	//loads popup only if it is disabled
	if(popupStatus==0){
		$("#backgroundPopup").css({
			"opacity": "0.7"
		});
		$("#backgroundPopup").fadeIn("slow");
		$("#popup_flaglisting").fadeIn("slow");
		popupStatus = 1;
	}
}

function load_floorplan_Popup(){
	//loads popup only if it is disabled
	if(popupStatus==0){
		$("#backgroundPopup").css({
			"opacity": "0.7"
		});
		$("#backgroundPopup").fadeIn("slow");
		$("#popup_floorplan").fadeIn("slow");
		popupStatus = 1;
	}
}

function load_signup_Popup(){
	//loads popup only if it is disabled
	if(popupStatus==0){
		$("#backgroundPopup").css({
			"opacity": "0.7"
		});
		$("#backgroundPopup").fadeIn("slow");
		$("#popup_signup").fadeIn("slow");
		popupStatus = 1;
	}
}

function load_signup_contactform_Popup(){
	//loads popup only if it is disabled
	if(popupStatus==0){
		$("#backgroundPopup").css({
			"opacity": "0.7"
		});
		$("#backgroundPopup").fadeIn("slow");
		$("#popup_signup_contactform").fadeIn("slow");
		popupStatus = 1;
	}
	else{
		$("#backgroundPopup").css({
			"opacity": "0.7"
		});
		$("#popup_signup").fadeOut("slow");
		$("#popup_signup_contactform").fadeIn("slow");
		popupStatus = 1;
	}
}


function load_addfrnd_Popup(){
	//loads popup only if it is disabled
	if(popupStatus==0){
		$("#backgroundPopup").css({
			"opacity": "0.7"
		});
		$("#backgroundPopup").fadeIn("slow");
		$("#popup_addfrnd").fadeIn("slow");
		popupStatus = 1;
	}
}

function load_remove_contactform_Popup(){
	//loads popup only if it is disabled
	if(popupStatus==0){
		$("#backgroundPopup").css({
			"opacity": "0.7"
		});
		$("#backgroundPopup").fadeIn("slow");
		$("#popup_remove").fadeIn("slow");
		popupStatus = 1;
	}
}



//disabling popup with jQuery magic!
function disablePopup(){
	//disables popup only if it is enabled
	if(popupStatus==1){
		$(".main_search").css("z-index","auto");
		$("#backgroundPopup").fadeOut("slow");
		$("#popup_gallery").fadeOut("slow");
		$("#popup_sendmail").fadeOut("slow");
		$("#popup_contactmail").fadeOut("slow");
		$("#popup_floorplan").fadeOut("slow");
		$("#popup_a_search").fadeOut("slow");
		$("#popup_signup").fadeOut("slow");
		$("#popup_signup_contactform").fadeOut("slow");
		$("#popup_addfrnd").fadeOut("slow");
		$("#popup_remove").fadeOut("slow");
		$("#popup_flaglisting").fadeOut("slow");
		popupStatus = 0;
	}
}

//centering popup
function center_gal_Popup(){
	//request data for centering
	var windowWidth = document.documentElement.clientWidth;
	var windowHeight = document.documentElement.clientHeight;
	var popupHeight = $("#popup_gallery").height();
	var popupWidth = $("#popup_gallery").width();
	//alert(popupHeight);
	//centering
	$("#popup_gallery").css({
		"position": "absolute",
		"top": "80px",
		"left": windowWidth/2-popupWidth/2 
	});
	//only need force for IE6
	
	$("#backgroundPopup").css({
		"height": windowHeight
	});	
}

function center_floorplan_Popup(){
	//request data for centering
	var windowWidth = document.documentElement.clientWidth;
	var windowHeight = document.documentElement.clientHeight;
	var popupHeight = $("#popup_floorplan").height();
	var popupWidth = $("#popup_floorplan").width();
	//alert(popupHeight);
	//centering
	$("#popup_floorplan").css({
		"position": "absolute",
		"top": "80px",
		"left": windowWidth/2-popupWidth/2 
	});
	//only need force for IE6
	
	$("#backgroundPopup").css({
		"height": windowHeight
	});	
}


function center_sendmail_Popup(){
	//request data for centering
	var windowWidth = document.documentElement.clientWidth;
	var windowHeight = document.documentElement.clientHeight;
	var popupHeight = $("#popup_sendmail").height();
	var popupWidth = $("#popup_sendmail").width();
	//alert(windowHeight/2-popupHeight/2);
	//centering
	$("#popup_sendmail").css({
		"position": "absolute",
		"top": "29px",
		"left": windowWidth/2-popupWidth/2 
	});
	//only need force for IE6
	
	$("#backgroundPopup").css({
		"height": windowHeight
	});	
}

function center_contactmail_Popup(){
	//request data for centering
	var windowWidth = document.documentElement.clientWidth;
	var windowHeight = document.documentElement.clientHeight;
	var popupHeight = $("#popup_contactmail").height();
	var popupWidth = $("#popup_contactmail").width();
	//alert(windowHeight/2-popupHeight/2);
	//centering
	$("#popup_contactmail").css({
		"position": "absolute",
		"top": "390px",
		"left": windowWidth/2-popupWidth/2 
	});
	//only need force for IE6
	
	$("#backgroundPopup").css({
		"height": windowHeight
	});	
}

function center_signup_Popup(){
	//request data for centering
	var windowWidth = document.documentElement.clientWidth;
	var windowHeight = document.documentElement.clientHeight;
	var popupHeight = $("#popup_signup").height();
	var popupWidth = $("#popup_signup").width();
	//alert(windowHeight/2-popupHeight/2);
	//centering
	$("#popup_signup").css({
		"position": "absolute",
		"top": "110px",
		"left": windowWidth/2-popupWidth/2 
	});
	//only need force for IE6
	
	$("#backgroundPopup").css({
		"height": windowHeight
	});	
}


function center_signup_contactform_Popup(){
	//request data for centering
	var windowWidth = document.documentElement.clientWidth;
	var windowHeight = document.documentElement.clientHeight;
	var popupHeight = $("#popup_signup_contactform").height();
	var popupWidth = $("#popup_signup_contactform").width();
	//alert(windowHeight/2-popupHeight/2);
	//centering
	
	
	$("#popup_signup_contactform").css({
		"position": "absolute",
		"top": "110px",
		"left": windowWidth/2-popupWidth/2 
	});
	//only need force for IE6
	
	$("#backgroundPopup").css({
		"height": windowHeight
	});	
}

function center_addfrnd_popup(){
	//request data for centering
	var windowWidth = document.documentElement.clientWidth;
	var windowHeight = document.documentElement.clientHeight;
	var popupHeight = $("#popup_addfrnd").height();
	var popupWidth = $("#popup_addfrnd").width();
	//alert(windowHeight/2-popupHeight/2);
	//centering
	$("#popup_addfrnd").css({
		"position": "absolute",
		"top": "183px",
		"left": windowWidth/2-popupWidth/2 
	});
	//only need force for IE6
	
	$("#backgroundPopup").css({
		"height": windowHeight
	});	
}

function center_remove_contactform_Popup(){
	//request data for centering
	var windowWidth = document.documentElement.clientWidth;
	var windowHeight = document.documentElement.clientHeight;
	var popupHeight = $("#popup_remove").height();
	var popupWidth = $("#popup_remove").width();
	//alert(windowHeight/2-popupHeight/2);
	//centering
	
	
	$("#popup_remove").css({
		"position": "absolute",
		"top": "183px",
		"left": windowWidth/2-popupWidth/2 
	});
	//only need force for IE6
	
	$("#backgroundPopup").css({
		"height": windowHeight
	});	
}

function center_flaglisting_Popup(){
	//request data for centering
	var windowWidth = document.documentElement.clientWidth;
	var windowHeight = document.documentElement.clientHeight;
	var popupHeight = $("#popup_flaglisting").height();
	var popupWidth = $("#popup_flaglisting").width();
	//alert(windowHeight/2-popupHeight/2);
	//centering
	$("#popup_flaglisting").css({
		"position": "absolute",
		"top": "29px",
		"left": windowWidth/2-popupWidth/2 
	});
	//only need force for IE6
	
	$("#backgroundPopup").css({
		"height": windowHeight
	});	
}

//CONTROLLING EVENTS IN jQuery
$(document).ready(function(){
	$("#gallery_popup").click(function(){
		center_gal_Popup();
		load_gal_Popup();
	});
	
	$("#sendmail_popup").click(function(){ 
		center_sendmail_Popup();
		load_sendmail_Popup();
	});

        $("#contactmail_popup").click(function(){
		center_contactmail_Popup();
		load_contactmail_Popup();
	});
	
	$("#floorplan_popup").click(function(){
		center_floorplan_Popup();
		load_floorplan_Popup();
	});
	
	$("#signup_popup").click(function(){
		center_signup_Popup();
		load_signup_Popup();
	});
	
	$("#signup_contactform_popup").click(function(){
		center_signup_contactform_Popup();
		load_signup_contactform_Popup();
	});
	
		
	$(".close").click(function(){
		disablePopup();
	});	
	
	//Click out event!
	$("#backgroundPopup").click(function(){
		disablePopup();
	});
	
	$("#addfrnd_popup").click(function(){
		center_addfrnd_popup();
		load_addfrnd_Popup();
	});
	
	$("#remove_contactform_popup").click(function(){
		center_remove_contactform_Popup();
		load_remove_contactform_Popup();
	});

	$("#flaglisting_popup").click(function(){
		center_flaglisting_Popup();
		load_flaglisting_Popup();
	});	
	//Press Escape event!
	//$(document).keypress(function(e){
	//	if(e.keyCode==27 && popupStatus==1){
	//		disablePopup();
	//	}
	//});

});