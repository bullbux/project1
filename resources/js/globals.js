/**
 * @author Marco Alionso Ramirez, marco@onemarco.com
 * @url http://onemarco.com
 * This code is public domain
 */

var IS_IE = false;
var IS_LT_IE7;
//@cc_on IS_IE = true;
//@cc_on IS_LT_IE7 = @_jscript_version < 5.7;

/**
 * @param {Object} e A JSON Object literal representing a DOM node and optionally,
 * its children
 * @return {HTMLElement}
 */
function jsonToDom(e){
	if(null != e.txt) return document.createTextNode(e.txt);
	if(null == e.el) return null;
	var node = document.createElement(e.el.toUpperCase());
	if(null != e.att){
		for(var i in e.att){
			if(IS_IE){		
				node = applyAttribute(node,i,e.att[i]);
			}else{
				applyAttribute(node,i,e.att[i]);
			}
		}
	}
	if(null != e.ch){
		for(var j in e.ch){
			var childNode = jsonToDom(e.ch[j]);
			if(null !== childNode) node.appendChild(childNode);
		}
	}
	return node;
}

/**
 * @param {HTMLElement} node
 * @param {string} attribute
 * @param {string} value
 * @return {HTMLElement}
 */
function applyAttribute(node,attribute,value){
	if(IS_IE){
		return ieApplyAttribute(node,attribute,value);
	}else{
		node.setAttribute(attribute,value);
	}	
}

/**
 * @param {HTMLElement} node
 * @param {String} attribute
 * @param {String} value
 * @return {HTMLElement}
 */
function ieApplyAttribute(node,attribute,value){
	switch(attribute.toLowerCase()){
		case 'for':
			node.htmlFor = value;
			break;
		case 'class':
			node.className = value;
			break;
		case 'style':
			node.style.textCss = value;
			break;
		case 'name':
			node = document.createElement(node.outerHTML.replace(">"," name=" + value + ">"));
			break;
		default:
			node.setAttribute(attribute,value);
	}
	return node;
}

/**
 * @param {HTMLElement} element
 * @param {String} className
 */
function addClassToElement(element, className){
	if((new RegExp('\\b' + className + '\\b')).test(element.className)) return;
	element.className += ' ' + className;
}

/**
 * @param {HTMLElement} element
 * @param {String} className
 */
function removeClassFromElement(element, className){
	element.className = element.className.replace(
		new RegExp('(\\W+)?\\b' + className + '\\b(\\W+)?','g'),' ');
}

/**
 * @param {HTMLElement} node
 */
function clearChildren(node){
	while(node.childNodes.length > 0){
		node.removeChild(node.childNodes[0]);
	}
}

/**
 * @param {Object} args
 */
function argsToArray(args){
	var ret = [];
	for(var i = 0; i < args.length; i++){
		ret.push(args[i]);
	}
	return ret;
}

/**
 * @param {Object} evt
 */
function standardizeEvent(evt){
	if(!evt) evt = window.event;
	if(evt.srcElement) evt.target = evt.srcElement;
	if(!evt.stopPropagation) evt.stopPropagation = function(){this.cancelBubble = true};
	if(!evt.preventDefault) evt.preventDefault = function(){this.returnValue = false;};
	return evt;
}

function createPngElement(src){
	var img = document.createElement('img');
	img.setAttribute('src',src);	
	if(IS_IE && IS_LT_IE7){
		img.style.visibility = 'hidden';
		var div = document.createElement('div');
		div.appendChild(img);
		div.style.filter = 'progid:DXImageTransform.Microsoft.AlphaImageLoader(src=\'' + src + '\',sizingMethod=\'crop\')';
		return div;
	}
	return img;	
}