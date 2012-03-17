function Checkall(theForm,currObj){ 
	for (var i = 0; i < theForm.elements.length; i++){ 
		if(theForm.elements[i].type=="checkbox"){
			theForm.elements[i].checked = currObj.checked;
		}
	} 
}
function checkAtLeastOne(theForm,mes){ 
	var count=0;
	for (var i = 0; i < theForm.elements.length; i++){ 
		if(theForm.elements[i].type=="checkbox"){
			id1 = theForm.elements[i].id;
			if(document.getElementById(id1).checked){
				if(document.getElementById(id1).id != 'VerificationVerificationid'){
					count++;
				}
			}
		}
	}
	if(count == 0){
		alert('Please select atleast one record.');
		return false;
	}
	else{
		return true;
	}
}
function CheckSelectAll(obj){
	if(!obj.checked){
		document.getElementById('VerificationVerificationid').checked = false;
	}
	return;
}
function textCounter(field,cntfield,maxlimit) {
	if (field.value.length > maxlimit) // if too long...trim it!
	field.value = field.value.substring(0, maxlimit);
	else
	cntfield.value = maxlimit - field.value.length;
}



