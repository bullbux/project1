var popupStatus = 0;
function load_a_search_Popup(){
	//loads popup only if it is disabled
	if(popupStatus==0){
		$("#backgroundPopup").css({
			"opacity": "0.6"
		});
		$(".main_search").css("z-index","-2");
		$("#backgroundPopup").fadeIn("slow");
		$("#popup_a_search").fadeIn("slow");		
		popupStatus = 1;
	}
}
//disabling popup with jQuery magic!
function disablePopup_search(){
	//disables popup only if it is enabled
	if(popupStatus==1){
		$(".main_search").css("z-index","auto");
		$("#backgroundPopup").fadeOut("slow");
		$("#popup_a_search").fadeOut("slow");
		
		$("#index_backgroundPopup").css({
			"height": "0"
		});
		popupStatus = 0;
	}
}

function center_a_search_Popup(){
	var windowWidth = document.documentElement.clientWidth;
	var windowHeight = document.documentElement.clientHeight;
	var popupHeight = $("#popup_a_search").height();
	var popupWidth = $("#popup_a_search").width();
	//alert(windowHeight/2-popupHeight/2);
	$("#popup_a_search").css({
		"position": "absolute",
		"top": "-50px",
		"left": windowWidth/2-popupWidth/2 
	});
	//only need force for IE6
	
	$("#backgroundPopup").css({
		"height": windowHeight
	});	
	$("#index_backgroundPopup").css({
		"height": windowHeight
	});	
}

//CONTROLLING EVENTS IN jQuery
$(document).ready(function(){
	
	$("#a_search_popup").click(function(){
		center_a_search_Popup();
		load_a_search_Popup();
	});

	//Click out event!
	$("#backgroundPopup").click(function(){
		disablePopup_search();
	});
	//Click out event!
	$("#index_backgroundPopup").click(function(){
		disablePopup_search();
	});
	
	//Press Escape event!
	$(document).keypress(function(e){
		if(e.keyCode==27 && popupStatus==1){
			disablePopup();
	}
	});

});
