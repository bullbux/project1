/**
* Event Class.
* JavaScript Document
*
*/
var Event = new function(){
    this.counter = 1;
    this.html = '';
    this.unloadFrame = false;
    this.popup = '';
    this.scrollXWin = 0;
    this.scrollYWin = 0;
    this.showAjaxLoader = false;
    this.lightBoxH = '40px';
    this.obj = null;
    this.mTop = 100;
    this.requestTimeOut = null;
    this.requestTimeOutTime = 10000;
    this.transmsgBgColor = '#ffe91d';
    ref = this;

    // AJAX call.
    this.link = function(obj, method, asyn, parameters, elementId, dataType, cache, confirmMsg, openIn, position, reLoad, onComplete, requestUrl){        
        this.isAjax = true;
        this.requestAddress = '';
        this.objChild = obj.innerHTML;
        this.method = method;
        this.asyn = asyn;
        this.parameters = parameters;
        this.elementId = elementId;
        this.dataType = dataType;
        this.cache = cache;
        this.position = position;
        this.popup = openIn;
        this.reLoad = reLoad;
        this.onComplete = onComplete;

        if(confirmMsg.length > 0){
            if(!confirm(confirmMsg))
                return false;
        }

        if(typeof(requestUrl)== 'undefined'){
            this.requestAddress = obj.href;
        }else{
            this.requestAddress = requestUrl;
        }

        if(typeof(this.popup.type)== 'string'){
            switch(this.popup.type){
                case 'lightbox':
                    if(this.obj == obj && (typeof(this.popup.reLoad) == 'undefined' || this.popup.reLoad == 'false' || this.popup.reLoad == false)){
                        this.isAjax = false;
                    }else{
                        this.isAjax = true;
                    }
                    this.loadLightbox();
                    this.elementId = 'lb-body';
                    break;

                case 'thickbox':
                    this.isAjax = false;
                    if(this.obj == obj && (typeof(this.popup.reLoad) == 'undefined' || this.popup.reLoad == 'false' || this.popup.reLoad == false)){
                        this.reLoad = false;
                    }else{
                        this.reLoad = true;
                    }
                    this.loadThickbox();
                    this.loadContentInThickbox();
                    this.elementId = 'lb-body';
                    break;
            }
            this.showAjaxLoader = false;
        }else{
            if(this.obj == obj && (typeof(this.reLoad) == 'undefined' || this.reLoad == 'false' || this.reLoad == false)){
                this.isAjax = false;
            }else{
                this.isAjax = true;
                ref.pageTransistionShow('Loading...');
            }
        }

        // Ajax request
        this.obj = obj;
        if(this.isAjax)
            this.ajax();

        return false;
    };

    this.ajax = function(){
        ref.loadTimeOut();
        $.ajax({
            type: ref.method,
            url: ref.requestAddress,
            cache: ref.cache,
            async: ref.asyn,
            data: ref.parameters,
            dataType: ref.dataType,
            error: function(XMLHttpRequest, textStatus, errorThrown){
                ref.pageTransistionShow('Request: ' + textStatus + '...Please try again.');
                clearTimeout(ref.requestTimeOut);
            },
            success: function(contents){
                var jsonRequest = false;

                clearTimeout(ref.requestTimeOut);
                if(ref.showAjaxLoader)
                    ref.obj.innerHTML = ref.objChild;
                contents = ref._decodeSpecialChar(contents);

                try{
                    content = eval('(' + contents + ')');
                    jsonRequest = true;
                }catch(e){
                    jsonRequest = false;
                }

                if(typeof(ref.position) == 'undefined')
                    ref.position = 'INNER';
                else
                    ref.position = ref.position.toUpperCase();
                                
                if(jsonRequest && ref.isArray(ref.elementId)){
                    for(index=0; index < ref.elementId.length ; index++){
                        if($('#' + ref.elementId[index]).length > 0){
                            switch(ref.position){
                                case 'BEFORE':
                                    $('#' + ref.elementId[index]).prepend(content[index]);
                                    break;

                                case 'AFTER':
                                    $('#' + ref.elementId[index]).append(content[index]);
                                    break;

                                case 'REPLACE':
                                    $('#' + ref.elementId[index]).replaceWith($(content[index]));
                                    break;

                                case 'INNER':
                                default:
                                    $('#' + ref.elementId[index]).html(content[index]);
                            }
                        }
                    }
                }else{
                    if($('#' + ref.elementId).length > 0 && ref.elementId != ''){
                        switch(ref.position){
                            case 'BEFORE':
                                $('#' + ref.elementId).prepend(contents);
                                break;

                            case 'AFTER':
                                $('#' + ref.elementId).append(contents);
                                break;

                            case 'INNER':
                                $('#' + ref.elementId).html(contents);
                                break;

                            default:
                                if(ref.popup.type == 'lightbox' || ref.popup.type == 'thickbox')
                                    $('#' + ref.elementId).html(contents);
                                else
                                    $('#' + ref.elementId).replaceWith($(contents));

                        }
                        // If the target element is a lightbox
                        if($('#lightbox .lb-heading').length > 0){
                            $('#lightbox .lb-title').html($('#lightbox .lb-heading').html());
                            $('#lightbox .lb-heading').remove();
                        }
                    }
                }

                if(contents.indexOf('window.location') <= 0)
                    ref.pageTransistionHide();
                               
                if(typeof(ref.onComplete) != 'undefined'){
                    if(ref.isfunctionExists(ref.onComplete)){
                        ref.cal(ref.onComplete);
                    }
                }
            }
        });
    };

    this.isArray = function(obj) {
        return obj.constructor == Array;
    };

    this.isUrl = function(s) {
        var regexp = /(ftp|http|https):\/\/(\w+:{0,1}\w*@)?(\S+)(:[0-9]+)?(\/|\/([\w#!:.?+=&%@!\-\/]))?/
        return regexp.test(s);
    };

    this.isfunctionExists = function(funcName){
        if(typeof funcName == 'string' && eval('typeof ' + funcName) == 'function') {
            return true;
        }
        return false;
    };

    this.cal = function(funcName){
        eval(funcName);
    };

    // Lightbox
    this.loadLightbox = function(){
        var Width = 700;

        if($('#lightbox').length == 0){
            //  this.html = "<table id='lightbox' class='scroll' cellpadding=0 cellspacing=0><tr><td width='25' class='top-left-corner'></td><td class='top-pix'><div id='lb-close'><span style='cursor: pointer;'>&nbsp;</span></div></td><td width='25' class='top-right-corner'></td></tr><tr><td class='left-pix'></td><td bgcolor='#FFF'><div id='lb-body'></div></td><td class='right-pix'></td></tr><tr><td width='25' class='bottom-left-corner'></td><td class='bottom-pix'></td><td width='25' class='bottom-right-corner'></td></tr></table>";
            this.html = '<div id="backlayout"><div style="-moz-border-radius: 8px 8px 8px 8px; -webkit-border-radius: 8px 8px 8px 8px; border-radius: 8px 8px 8px 8px; padding: 10px; position: absolute; z-index: 10000; width: 500px; height: auto; top: 100px; background: rgba(82, 82, 82, 0.7); left: 709px;" class="scroll" id="lightbox"><div id="lightbox-content"><div id="lb-header" style="color:#555555;font-family:tahoma;font-size:14px;font-weight:bold;background: none repeat scroll 0pt 0pt rgb(254, 221, 26); padding: 5px; border-width: 1px 1px 0pt; border-style: solid solid none; border-color: rgb(202, 147, 0);"><span class="lb-title" style="float: left;" title="Close"></span><span title="Close" id="lb-close" style="padding:0 2px; float: right; cursor: pointer;">X</span><div style="clear: both;"></div></div><div id="lb-body" style="background: none repeat scroll 0% 0% rgb(255, 255, 255); margin: 0pt; padding: 10px; border-right: 1px solid rgb(85, 85, 85); border-style: none solid solid; border-width: 1px; border-color: rgb(85, 85, 85);"></div></div></div></div>';
            $('body').append(this.html);
            if(typeof(this.popup.lbHeader)!= 'undefined'){
                if(typeof(this.popup.lbHeader.bgColor)!= 'undefined'){
                    $('#lightbox #lb-header').css({
                        background: this.popup.lbHeader.bgColor
                        });
                }
                if(typeof(this.popup.lbHeader.textColor)!= 'undefined'){
                    $('#lightbox #lb-header').css({
                        color: this.popup.lbHeader.textColor
                        });
                }
                if(typeof(this.popup.lbHeader.borderColor)!= 'undefined'){
                    $('#lightbox #lb-header').css({
                        border: '1px solid '+this.popup.lbHeader.borderColor,
                        borderBottom: 0
                    });
                }
                if(typeof(this.popup.lbHeader.title)!= 'undefined'){
                    $('#lightbox .lb-title').html(this.popup.lbHeader.title);
                }
            }

            // Bind ESC key event to remove the lightbox
            $(document).keyup(function(e){
                if (!e) var e = window.event;
                var code = (e.keyCode ? e.keyCode : e.which);
                // Released key is ESC
                if(code == 27){
                    $('#lightbox').fadeOut();
                }
            });
        }

        // If this is an ajax request, show ajax loader
        if(this.isAjax){
            if(typeof ajaxLoaderPath == 'undefined')
                var ajaxLoaderPath = 'Loading...';
            //if(typeof(this.popup.lbHeader)!= 'undefined' && typeof(this.popup.lbHeader.title) == 'undefined')
            //    $('#lightbox .lb-title').html('');
            $('#lb-body').html("<div style='color:#333333;font-family:tahoma;font-size:24px;padding:10px;padding-top:0;text-align:left;'>"+ajaxLoaderPath+"</div>");
        }

        if(typeof(this.popup.scroll) == 'undefined' || this.popup.scroll == 'true' || this.popup.scroll == true)
            $('#lightbox')[0].className = 'scroll';
        else
            $('#lightbox')[0].className = 'no-scroll';

        if(typeof(this.popup.width)!= 'undefined')
            Width = parseInt(this.popup.width);

        if(typeof(this.popup.position)!= 'undefined' && this.popup.position == 'relative'){
            var offsetValues = $(this.obj).offset();
            Left = offsetValues.left;
            Top = offsetValues.top;
            this.mTop = Top;
        }else{
            if(typeof(this.popup.top)!= 'undefined'){
                Top = parseInt(this.popup.top);
            }
            else{
                var scrl = this.getScrollXY();
                Top = scrl.y + this.mTop;
            }

            if(typeof(this.popup.left)!= 'undefined')
                Left = parseInt(this.popup.left);
            else
                Left = Math.ceil(($(document).width() - Width) / 2);
        }

        if(typeof(this.popup.background)!= 'undefined')
            Background = this.popup.background;
        else
            Background = 'none';

        $('#lightbox').css({
            display: 'none',
            position: 'absolute',
            zIndex: '10000',
            width: Width + 'px',
            height: 'auto',
            left: Left + 'px',
            top: Top + 'px'
            });
        $('#lb-body').css({
            background: '#FFF'
        });
        if(!(typeof(this.popup.backlay) != 'undefined' && (this.popup.backlay == false || this.popup.backlay == 'false')))
            $('#backlayout').css({
                display: 'block',
                filter: 'alpha(opacity = 60)'
            });
        $('#lightbox').fadeIn();
        // Hover effect on close button
        $('#lightbox #lb-close').mouseover(function(){
            $(this).css({background: 'rgba(82, 82, 82, 0.7)', color: '#CCCCCC'});
        }).mouseout(function(){
            $(this).css({background: 'none', color: ''});
        });
        // Close lightbox
        $('#lightbox #lb-close').click(function(){
            $('#lightbox').fadeOut('slow');
            if(!(typeof(ref.popup.backlay) != 'undefined' && (ref.popup.backlay == false || ref.popup.backlay == 'false')))
                $('#backlayout').fadeOut('slow');

            // Lightbox on close event
            if(typeof(ref.popup.onClose) != 'undefined'){
                if(ref.isfunctionExists(ref.popup.onClose)){
                    ref.cal(ref.popup.onClose);
                }
            }
            // unbind the click event
            $(this).unbind( "click" );
        });
    };

    // Thickbox using iframe
    this.loadThickbox = function(){
        var Width = 700;

        if($('#thickbox').length == 0){
            //  this.html = "<table id='thickbox ' class='scroll' cellpadding=0 cellspacing=0><tr><td width='25' class='top-left-corner'></td><td class='top-pix'><div id='lb-close'><span style='cursor: pointer;'>&nbsp;</span></div></td><td width='25' class='top-right-corner'></td></tr><tr><td class='left-pix'></td><td bgcolor='#FFF'><div id='lb-body'></div></td><td class='right-pix'></td></tr><tr><td width='25' class='bottom-left-corner'></td><td class='bottom-pix'></td><td width='25' class='bottom-right-corner'></td></tr></table>";
            this.html = '<div id="backlayout"><div style="-moz-border-radius: 8px 8px 8px 8px; -webkit-border-radius: 8px 8px 8px 8px; border-radius: 8px 8px 8px 8px; padding: 10px; position: absolute; z-index: 10000; width: 500px; height: auto; top: 100px; background: rgba(82, 82, 82, 0.7); left: 709px;" class="scroll" id="thickbox"><div id="thickbox-content"><div id="lb-header" style="color:#555555;font-family:tahoma;font-size:14px;font-weight:bold;background: none repeat scroll 0pt 0pt rgb(254, 221, 26); padding: 5px; border-width: 1px 1px 0pt; border-style: solid solid none; border-color: rgb(202, 147, 0);"><span class="lb-title" style="float: left;" title="Close"></span><span title="Close" id="lb-close" style="padding:0 2px; float: right; cursor: pointer;">X</span><div style="clear: both;"></div></div><div id="lb-body" style="background: none repeat scroll 0% 0% rgb(255, 255, 255); margin: 0pt; padding: 10px; border-right: 1px solid rgb(85, 85, 85); border-style: none solid solid; border-width: 1px; border-color: rgb(85, 85, 85);"><div class="thickbox-loader"></div><div id="thickbox-contents" style="display: none"><iframe style="overflow-x: hidden" class="autoHeight" src="" width="100%" frameborder=0></iframe></div></div></div></div></div>';
            $('body').append(this.html);
            if(typeof(this.popup.lbHeader)!= 'undefined'){
                if(typeof(this.popup.lbHeader.bgColor)!= 'undefined'){
                    $('#thickbox #lb-header').css({
                        background: this.popup.lbHeader.bgColor
                        });
                }
                if(typeof(this.popup.lbHeader.textColor)!= 'undefined'){
                    $('#thickbox #lb-header').css({
                        color: this.popup.lbHeader.textColor
                        });
                }
                if(typeof(this.popup.lbHeader.borderColor)!= 'undefined'){
                    $('#thickbox #lb-header').css({
                        border: '1px solid '+this.popup.lbHeader.borderColor,
                        borderBottom: 0
                    });
                }
                if(typeof(this.popup.lbHeader.title)!= 'undefined'){ 
                    $('#thickbox .lb-title').html(this.popup.lbHeader.title);
                }
            }

            // Bind ESC key event to remove the thickbox
            $(document).keyup(function(e){
                if (!e) var e = window.event;
                var code = (e.keyCode ? e.keyCode : e.which);
                // Released key is ESC
                if(code == 27){
                    $('#thickbox').fadeOut('slow');
                }
            });
        //$('#backlayout').css({width: '100%', height: '0px', position: 'absolute', top: '0px', left: '0px'});
        }

        // Show ajax loader
        if(this.reLoad){
            if(typeof ajaxLoaderPath == 'undefined')
                var ajaxLoaderPath = 'Loading...';
           // if(typeof(this.popup.lbHeader)!= 'undefined' && typeof(this.popup.lbHeader.title) == 'undefined')
          //      $('#thickbox .lb-title').html('');
            $('#thickbox #lb-body .thickbox-loader').html("<div style='color:#333333;font-family:tahoma;font-size:24px;padding:10px;padding-top:0;text-align:left;'>"+ajaxLoaderPath+"</div>");
        }

        if(typeof(this.popup.scroll) == 'undefined' || this.popup.scroll == 'true' || this.popup.scroll == true)
            $('#thickbox')[0].className = 'scroll';
        else
            $('#thickbox')[0].className = 'no-scroll';

        if(typeof(this.popup.width)!= 'undefined')
            Width = parseInt(this.popup.width);

        if(typeof(this.popup.position)!= 'undefined' && this.popup.position == 'relative'){
            var offsetValues = $(this.obj).offset();
            Left = offsetValues.left;
            Top = offsetValues.top;
            this.mTop = Top;
        }else{
            if(typeof(this.popup.top)!= 'undefined'){
                Top = parseInt(this.popup.top);
            }
            else{
                var scrl = this.getScrollXY();
                Top = scrl.y + this.mTop;
            }

            if(typeof(this.popup.left)!= 'undefined')
                Left = parseInt(this.popup.left);
            else
                Left = Math.ceil(($(document).width() - Width) / 2);
        }

        if(typeof(this.popup.background)!= 'undefined')
            Background = this.popup.background;
        else
            Background = 'none';

        $('#thickbox').css({
            display: 'none',
            position: 'absolute',
            zIndex: '10000',
            width: Width + 'px',
            height: 'auto',
            left: Left + 'px',
            top: Top + 'px'
            });
        $('#thickbox #lb-body').css({
            background: '#FFF'
        });
        if(!(typeof(this.popup.backlay) != 'undefined' && (this.popup.backlay == false || this.popup.backlay == 'false')))
            $('#backlayout').css({
                display: 'block',
                filter: 'alpha(opacity = 60)'
            });
        $('#thickbox').fadeIn();
        $('#thickbox #lb-close').click(function(){
            $('#thickbox').fadeOut('slow');
            if(!(typeof(ref.popup.backlay) != 'undefined' && (ref.popup.backlay == false || ref.popup.backlay == 'false')))
                $('#backlayout').fadeOut('slow');

            // Lightbox on close event
            if(typeof(ref.popup.onClose) != 'undefined'){
                if(ref.isfunctionExists(ref.popup.onClose)){
                    ref.cal(ref.popup.onClose);
                }
            }
            // unbind the click event
            $(this).unbind( "click" );
        });
    };

    // Load contents in iframe/thickbox
    this.loadContentInThickbox = function(){
        var cnt = ''
        if(this.requestAddress.match(/\?/))
            cnt = '&amp;tbip=1'
        else
            cnt = '?tbip=1';
        $('#thickbox #thickbox-contents iframe').attr('src', this.requestAddress + cnt);        
        this.eventPush($('#thickbox #thickbox-contents iframe')[0], 'load',function(){
            $('#thickbox .thickbox-loader').hide();
            $('#thickbox #thickbox-contents').show();
            // Auto set the height of iframe
            ref.doIframe();
            if($('#thickbox #thickbox-contents iframe')[0].contentDocument){
                doc = $('#thickbox #thickbox-contents iframe')[0].contentDocument;
            } else {
                doc = $('#thickbox #thickbox-contents iframe')[0].contentWindow.document;
            }

            // Find <form> Tags
            var formTags = doc.getElementsByTagName('form');
            if(formTags.length > 0){
                for(i=0; i<formTags.length; i++){
                    if(formTags[i].action.match(/\?/))
                        formTags[i].action += '&amp;tbip=1'
                    else
                        formTags[i].action += '?tbip=1';
                }
            }
        });
    }

    // Auto close a light box while redirecting from a lightbox
    this.autoCloseLightbox = function(){
        if($('#lightbox').length > 0)
            $('#lightbox').hide();
        if($('#backlayout').length > 0)
            $('#backlayout').hide();
    };

    this.pageTransistionShow = function(msg){
        if($('#transmsg').length > 0){
            $('#transmsg span').text(msg);
            $('#transmsg').show();
        }else
            $('body').append($("<div id='transmsg' style='position: absolute; top: 0;left: 0; width: 98%; text-align: center;'><span style='padding:5px;-moz-border-radius:0 0 5px 5px;line-height:20px;background:"+this.transmsgBgColor+";font-weight:bold;color:#000000;'>"+msg+"</span></div>"));
    };

    this.pageTransistionHide = function(){
        if($('#transmsg').length > 0){
            $('#transmsg').hide();
            $('#transmsg span').text('');
        }
    };

    this.lightbox = function(){
        var h = $('#lb-body').css('height');
        var w = $('#lb-body').css('width');
        $('#lb-body').css({
            height: this.lightBoxH
            });
        this.lightBoxH = h;
        $('#lb-body').hide();
        this.animateZoom(h, w, $('#lb-body'));
    };

    // AUTO RESIZE ANIMATION
    this.animateZoom = function(zheight, zwidth, box){
        $(box).animate({
            width: zwidth,
            height: zheight
        }, 1000,'',function(){
            $(this).css({
                background: 'none',
                border: '0'
            });
        });
    };

    // Add more nodes/elements
    this.add = function(obj, count){
        if(this.counter <= count){
            this.counter++;
            var currentTime = new Date();
            var id = currentTime.getTime();
            $(obj).parent().append('<div id="'+id+'"></div>');
            var inputClone = $(obj).prev().prev().prev().clone(true);
            $('#'+id).append(inputClone);
            $('#'+id).append('<input type="hidden" value="'+this.counter+'" name="uid"/><span id="'+id+'_process"></span><a href="" class="remove" onclick="javascript: return Event.remove(this);">Remove</a>');
            $('#'+id).append('<br/><span id="'+id+'_update"></span>');
        }

        return false;
    };

    // Remove a node/element
    this.remove = function(obj){
        $(obj).parent().remove();
        this.counter--;
        return false;
    };

    // Upload a file using iframe.
    this.upload = function(obj, position){
        var removeIframe = false;
        var iframe = null;

        var d = new Date();
        var randNum = d.getMilliseconds()*Math.floor(Math.random()*100000);
        // Create a new iframe.
        if($.browser.msie){
            iframe = document.createElement('<iframe name="Frame'+ randNum + '" style="display: none;"></iframe>');
        }else{
            iframe = document.createElement('iframe');
            iframe.style.display = 'none';
            iframe.name = 'Frame' + randNum;
        }

        var ajaxUpload = document.createElement('input');
        ajaxUpload.name = 'ajaxUpload';

        if($(obj).next().attr('name') == 'uid'){
            ajaxUpload.value = $(obj).next().val();
        }else{
            ajaxUpload.value = '1';
        }

        ajaxUpload.type = 'hidden';

        var uploadFieldClone = $(obj).clone(true);

        // Create a new file upload form element.
        var formField = document.createElement('form');
        formField.name = 'upload_form';
        formField.method = 'post';
        if(obj.form.action.length == 0){
            formField.action = window.location;
        }else{
            formField.action = obj.form.action;
        }
        if($.browser.msie){
            formField.encoding = 'multipart/form-data';
        }else{
            formField.enctype = 'multipart/form-data';
        }
        formField.target = 'Frame' + randNum;
        formField.style.display = 'none';

        // Append the file input field object last of the form.
        uploadFieldClone.insertAfter($(obj));
        formField.appendChild($(obj)[0]);
        formField.appendChild(ajaxUpload);
                
        // Append the iframe object last of the body contents.
        document.body.appendChild(iframe);
        document.body.appendChild(formField);

        // Auto submit the form.
        uploadFieldClone.next().next().html('processing...');

        formField.submit();

        // Disable Submit button until processing completes.
        uploadFieldClone.parents('form').eq(0).find('input:submit, input:image').attr('disabled', 'disabled');
        ref.eventPush(iframe, 'load',function(){
            uploadFieldClone.next().next().html('');
            var id = String(uploadFieldClone.next().next().attr('id'));
            var updateId = id.replace("process", "update");

            if(typeof(position) == 'undefined')
                position = 'INNER';
            else
                position = position.toUpperCase();
            switch(position){
                case 'BEFORE':
                    $('#' + updateId).prepend(iframe.contentWindow.document.body.innerHTML);
                    break;

                case 'AFTER':
                    $('#' + updateId).append(iframe.contentWindow.document.body.innerHTML);
                    break;

                default:
                    $('#' + updateId).html(iframe.contentWindow.document.body.innerHTML);

            }
            removeIframe = true;
            // Enable Submit button upon complete processing.
            uploadFieldClone.parents('form').eq(0).find('input:submit, input:image').removeAttr('disabled');
        });

        // Remove iframe
        var interval = setInterval(function(){
            if(removeIframe){
                iframe.parentNode.removeChild(iframe);
                formField.parentNode.removeChild(formField);
                uploadFieldClone.val('');
                clearInterval(interval);
            }
        }, 200);
    };


    // Submit a form.
    this.submitForm = function(obj, method, asyn, parameters, elementId, dataType, cache, confirmMsg, openIn, position, reLoad, onComplete){ 
        if(typeof(formValidationErrors) == 'undefined' || !formValidationErrors){
            var submitElem = $(obj).find('input[type="submit"], input[type="image"]');
            var submElem = '&' + submitElem.attr('name') + '=' + escape(submitElem.val());
            var params = $(obj).serialize();
            parameters = params + submElem +  parameters;
            if(obj.action.replace(/^\s+/, "").replace(/\s+$/, "").length < 1){ 
                obj.action = window.location.href;
            }
            this.link($(obj).find('.load-ajax').eq(0)[0], method, asyn, parameters, elementId, dataType, cache, confirmMsg, openIn, position, reLoad, onComplete, obj.action);
        }
        return false;
    };

    this._decodeSpecialChar = function(contents){

        contents = contents.replace(/ /g, ' ');
        contents = contents.replace(/&ldquo;|&rdquo;/g, '"');
        contents = contents.replace(/&ndash;/g, '-');

        return contents;
    };

    // Scroll window
    this.scrollWin = function(){
        var scrl = this.getScrollXY();
        y = scrl.y + ref.mTop;
        if($('#lightbox').length > 0 && $('#lightbox').hasClass('scroll')){
            $('#lightbox').animate({
                top: y + 'px'
                }, 150);
        }else{
            if(y < parseInt($('#lightbox').css('top'))){
                $('#lightbox').animate({
                    top: y + 'px'
                    }, 150);
            }
        }
    };

    this.getScrollXY = function() {
        var scrOfX = 0, scrOfY = 0;
        if( typeof( window.pageYOffset ) == 'number' ) {
            //Netscape compliant
            scrOfY = window.pageYOffset;
            scrOfX = window.pageXOffset;
        } else if( document.body && ( document.body.scrollLeft || document.body.scrollTop ) ) {
            //DOM compliant
            scrOfY = document.body.scrollTop;
            scrOfX = document.body.scrollLeft;
        } else if( document.documentElement && ( document.documentElement.scrollLeft || document.documentElement.scrollTop ) ) {
            //IE6 standards compliant mode
            scrOfY = document.documentElement.scrollTop;
            scrOfX = document.documentElement.scrollLeft;
        }
        return {
            x: scrOfX,
            y: scrOfY
        };
    };

    this.resetCounter = function(){
        this.counter = 1;
    };

    this.checkAll = function(obj, clas){
        if(obj.checked)
            $('.' + clas).attr('checked', 'checked');
        else
            $('.' + clas).removeAttr('checked');
    };

    this.loadTimeOut = function(){
        ref.requestTimeOut = window.setTimeout(function(){
            ref.pageTransistionShow('Page loading is taking time. Please be patient.');
        }, ref.requestTimeOutTime);
    };

    this.flash = function(msg){
        if($('#flashmsg').length > 0){
            $('#flashmsg').html(msg);
        }else{
            var div = $("<div id='flashmsg' style='text-shadow:0 1px 2px #666; text-align: center; position: fixed; top: 0;left: 0; width:100%; background:#FFF; opacity:0.9; filter:alpha(opacity=90); color:#000000;'>"+msg+"</div>");            
            $(div).appendTo('body');
        }
        $('#flashmsg').slideDown('slow');
        setTimeout(function(){
            $('#flashmsg').slideUp('slow');
        }, 5000);
    };

    this.eventPush = function(obj, event, handler) {
        if (obj.addEventListener) {
            obj.addEventListener(event, handler, false);
        } else if (obj.attachEvent) {
            obj.attachEvent('on'+event, handler);
        }
    };

    /**
     * Auto set the height attribute of an iframe
     */
    this.doIframe = function(){
        o = document.getElementsByTagName('iframe');
        for(i=0;i<o.length;i++){
            if(typeof(ref.popup.height) != 'undefined'){
                this.setHeight(o[i], ref.popup.height);
            }else{
                if (/\bautoHeight\b/.test(o[i].className)){
                    this.setAutoHeight(o[i]);
                }
            }
        }
    };

    this.setHeight = function(e, ht){
        var doc = null;
        e.height = ht;
        if(e.contentDocument){
            doc = e.contentDocument;
        } else {            
            doc = e.contentWindow.document;
        }
        var title = $(doc).find('.lb-heading');
        if(title.length > 0){
            $('#thickbox .lb-title').html(title.html());
            $(doc).find('.lb-heading').remove();
        }
    };

    this.setAutoHeight = function(e){
        var doc = null;
        if(e.contentDocument){
            e.height = e.contentDocument.body.offsetHeight;
            doc = e.contentDocument;            
        } else {
            e.height = e.contentWindow.document.body.scrollHeight;
            doc = e.contentWindow.document;
        }
        var title = $(doc).find('.lb-heading');
        if(title.length > 0){
            $('#thickbox .lb-title').html(title.html());
            $(doc).find('.lb-heading').remove();
        }
    };

    // Send a http request
    this.sendRequest =  function(requestAction, updateId, position){
        var removeIframe = false;
        var iframe = null;
        ref.isUploaded = false;
        ref.response = false;
        var d = new Date();
        var randNum = d.getMilliseconds()*Math.floor(Math.random()*100000);
        // Create a new iframe.
        if(jQuery.browser.msie){
            iframe = document.createElement('<iframe name="Frame'+ randNum + '" src="'+requestAction+'" style="display: none;"></iframe>');
        }else{
            iframe = document.createElement('iframe');
            iframe.style.display = 'none';
            iframe.name = 'rFrame' + randNum;
            iframe.src = requestAction;
        }
        // Append the iframe object last of the body contents.
        document.body.appendChild(iframe);
        ref.eventPush(iframe, 'load',function(){
            if(updateId != null && ref.updateFlag){
                if(typeof(position) == 'undefined')
                    position = 'INNER';
                else
                    position = position.toUpperCase();
                switch(position){
                    case 'BEFORE':
                        jQuery('#' + updateId).prepend(iframe.contentWindow.document.body.innerHTML);
                        break;

                    case 'AFTER':
                        jQuery('#' + updateId).append(iframe.contentWindow.document.body.innerHTML);
                        break;

                    default:
                        jQuery('#' + updateId).html(iframe.contentWindow.document.body.innerHTML);

                }
            }
            if(ref.updateFlag)
                ref.response = iframe.contentWindow.document.body.innerHTML;
            else{
                ref.updateFlag = true;
                if(jQuery('img.ajax-loader').length > 0)
                    jQuery('img.ajax-loader').hide();
            }
            removeIframe = true;
        });

        // Remove iframe
        var interval = setInterval(function(){
            if(removeIframe){
                iframe.parentNode.removeChild(iframe);
                clearInterval(interval);
                ref.isUploaded = ref.response;
            }
        }, 200);
    };
}

$(document).ready(function(){    
    $(window).scroll(function(){
        Event.scrollWin();
    });

	$('input.any_checkbox').change(function () {
		$(this).parents('.check_features_bocks').find('.other_checkbox').removeAttr('checked');
	});

	$('input.other_checkbox').change(function () {
		$(this).parents('.check_features_bocks').find('.any_checkbox').removeAttr('checked');
	});
});

$(window).bind('beforeunload', function(){
     Event.loadTimeOut();
     Event.pageTransistionShow('Loading...');
});