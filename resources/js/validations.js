// Form validations
var formValidationErrors = false;
$(document).ready(function(){
        var val = '';
	var title1 = 'There are following ';
	var title2 = ' error(s) in the information you have provided.' + "\n" + '========================================' + "\n" + "\n";
        var elems = new Array();
	$('form').each(function(){
                $(this).find('input, textarea, select').each(function(){                    
                    // New Version to get attributes of fields
                    var attrs = getAttributes($(this)[0].className);
                    if(attrs && typeof attrs['dft'] != 'undefined'){
                        dv = $(this).val().replace(/^\s+/, "").replace(/\s+$/, "");
                        if(dv == attrs['dft']){
                            $(this).css({color:'#CCC'});
                        }else{
                            if(dv == '')
                                $(this).val(attrs['dft']).css({color:'#CCC'});
                        }
                        $(this).focus(function(){
                            var val = $(this).val().replace(/^\s+/, "").replace(/\s+$/, "");
                            if(val == attrs['dft'])
                                $(this).val('').css({color:''});
                        });
                        $(this).blur(function(){
                            var val = $(this).val().replace(/^\s+/, "").replace(/\s+$/, "");
                            if(val == '')
                                $(this).val(attrs['dft']).css({color:'#CCC'});
                        });
                    }else{
                        // ==========DEPRECATED===============
                        if($(this)[0].className.indexOf('default') != -1){
                            var defaultValue = $(this)[0].className.split('=');
                            var dv = $(this).val().replace(/^\s+/, "").replace(/\s+$/, "");
                            if(dv == defaultValue[1]){
                                $(this).css({color:'#CCC'});
                            }else{
                                if(dv == '')
                                    $(this).val(defaultValue[1]).css({color:'#CCC'});
                            }
                            $(this).focus(function(){
                                var val = $(this).val().replace(/^\s+/, "").replace(/\s+$/, "");
                                if(val == defaultValue[1])
                                    $(this).val('').css({color:''});
                            });
                            $(this).blur(function(){
                                var val = $(this).val().replace(/^\s+/, "").replace(/\s+$/, "");
                                if(val == '')
                                    $(this).val(defaultValue[1]).css({color:'#CCC'});
                            });
                        }
                    }
                });
		$(this).submit(function(e){ 
			var errors = '';                        
			var count = 0;
                        var elems = new Array();
                        var attrs = {};
                        var title = '';
                        $(this).find('input, select, textarea').removeClass('field-error');
			$(this).find('.mandatory').each(function(){                                
                                var val = $(this).val().replace(/^\s+/, "").replace(/\s+$/, "");
                                attrs = getAttributes($(this)[0].className);
                                if(attrs && typeof attrs.title != 'undefined'){
                                    title = attrs.title;
                                }else{
                                    title = $(this).attr('title'); 
                                }
				// Validate empty text fields
				if(($(this).attr('type') == 'text' || $(this).attr('type') == 'password' || $(this).attr('type') == 'hidden')){                                                                               
					if($(this)[0].className.indexOf('default') != -1){ 
						var defaultValue = $(this)[0].className.split('=');                                                
						if(val == defaultValue[1] || (attrs && typeof attrs.dft != 'undefined' && attrs.dft == val)){
							errors += '- ' + title + ' field can not be empty.' + "\n\n";
                                                        elems[count] = $(this);
                                                        $(this).addClass('field-error');  
														 $(this).parents('.input_left:eq(0)').addClass('field-error');
                                                        count++;
                                                }
					}else{                                                
                                                if(val == ''){                                                    
                                                    errors += '- ' + title + ' field can not be empty.' + "\n\n";
                                                    elems[count] = $(this);
                                                    $(this).addClass('field-error');
													 $(this).parents('.input_left:eq(0)').addClass('field-error');
                                                    count++;
                                                }
                                        }                                        					
				}

                                // Validate email address
				if((val != '' || (typeof defaultValue != 'undefined' && val != defaultValue[1])) && $(this).hasClass('email') && !validateEmail(val)){
					errors += '- ' + 'Email address is not valid.' + "\n\n";
                                        elems[count] = $(this);
                                        $(this).addClass('field-error');
					count++;
				}
				
				// Validate numeric text fields
				if($(this).attr('type') == 'text' && $(this).hasClass('numeric') && (isNaN(val))){
					errors += '- ' + title + ' field must be numeric.' + "\n\n";
                                        elems[count] = $(this);
                                        $(this).addClass('field-error');
										 $(this).parents('.input_left:eq(0)').addClass('field-error');
					count++;
				}

                                // Validate integer text fields
                                var patt = /\./g;
				if($(this).attr('type') == 'text' && $(this).hasClass('integer') && (isNaN(val) || val<0 || val.match(patt))){
					errors += '- ' + title + ' field must be integer.' + "\n\n";
                                        elems[count] = $(this);
                                        $(this).addClass('field-error');
					count++;
				}
				
				// Validate select fields
				if($(this)[0].nodeName == 'SELECT' && (val === '' || val === '0')){
					errors += '- ' + title + ' field can not be empty.' + "\n\n";
                                        elems[count] = $(this);
                                        $(this).addClass('field-error');
										$(this).parents('.select_box:eq(0)').addClass('field-error');
					count++;
				}
				
				// Validate textarea fields
				if($(this)[0].nodeName == 'TEXTAREA' && (val === '' || (attrs && typeof attrs.dft != 'undefined' && attrs.dft == val))){
					errors += '- ' + title + ' field can not be empty.' + "\n\n";
                                        elems[count] = $(this);
                                        $(this).addClass('field-error');
										$(this).parents('.box_left:eq(0)').addClass('field-error');
					count++;
				}

                                // Validate checkbox fields
				if($(this).attr('type') == 'checkbox' && !$(this)[0].checked){
					errors += '- ' + title + ' field must be checked.' + "\n\n";
                                        elems[count] = $(this);
                                        $(this).addClass('field-error');
										$(this).parents('.check:eq(0)').addClass('field-error');
										
					count++;
				}
				
				// Validate file fields
				if($(this).attr('type') == 'file' && val === ''){
					errors += '- ' + title + ' field can not be empty.' + "\n\n";
                                        elems[count] = $(this);
                                        $(this).parents('.input_left:eq(0)').addClass('field-error');
					count++;
				}
			});
                        
			if(errors.length > 1){
				e.preventDefault();
				alert(title1+count+title2+errors + "\n" + "========================================");
                                elems[0].focus().select();
                                formValidationErrors = true;
			}else{
                            formValidationErrors = false;
                        }
		});
	});
});

function validateEmail(email) {
   var reg = /^([A-Za-z0-9_\-\.])+\@([A-Za-z0-9_\-\.])+\.([A-Za-z]{2,4})$/;
   var address = email;
   if(reg.test(address) == false) {
      return false;
   }
   return true;
}

function getAttributes(clas){
    var attrs = {};
    var attributes = clas.match(/{(.*)}/gi);
    if(attributes && attributes.length > 0){
        // IE hack "replace default with dft"
        attributes = String(attributes).replace('default', 'dft');
        attrs = eval('('+attributes+')');
    }

    return attrs;
}