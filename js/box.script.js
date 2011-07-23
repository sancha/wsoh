(function( $ ){
	var url_regex = /(ftp|http|https):\/\/(\w+:{0,1}\w*@)?(\S+)(:[0-9]+)?(\/|\/([\w#!:.?+=&%@!\-\/]))?/ ;
	var methods = {
		destroy : function() {
		},
		
		init : function (params) {
			return this.each(function(){
				$pos = $(this).position();
				hgt = $(this).height();
				wdt = $(this).width();
				$dims = {	
					'position': 'absolute',
					'top'	: $pos.top,
					'left'	: $pos.left,
					'height': hgt,
					'width'	: wdt
				};
				$(this).css($dims);
				$dupdiv = $('<div />').css($dims).addClass('dupdiv').attr('id','dupdiv');
				$('body').append($dupdiv);
				bind_triggers($.extend(params,{
					'element': $(this),
					'dupdiv':$dupdiv
				}));				
			});
		}		
	}
	
	function bind_triggers(params){
		BACKSPACE	= 8;
		ENTER		= 13;
		SPACE		= 32;
		ATTHERATEOF	= 64;
		GREATERTHAN	= 62;

		var k=0;
		miniconsole = params.element;
		dupconsole = params.dupdiv;
		parentid = params.parentid;
		var friends = params.atlist; 
		var hashtags = params.hashlist; 

		// 1 == AT, 2 == HASH, 3 == DATE. only one mutually exclusive flag please
		var WHATPRESSED = 0; 
		var userSelection;
		
		$(document).bind('json.get',function(event){
			jsonData = miniconsole.data('jsonData');
			jsonData['str'] = miniconsole.text();
			console.log(jsonData );
			alert(JSON.stringify(jsonData));
			parent.$(parent.document.getElementById(parentid)).find('#output').append(JSON.stringify(jsonData));
		});
		
		$(document).bind('selection.made',function(event,json){
			class_str = ''
			jsonData = miniconsole.data('jsonData');
			if( !jsonData ) jsonData = {};
			switch(json.label[0]){
				case '<':
					json.label = json.label.replace('<','&lt;');
					if( jsonData.hasOwnProperty('date') )
						jsonData['date'].push( json.label );
					else
						jsonData['date'] = new Array( json.label );
					class_str = 'time';
					break;
				case '@':
					class_str = 'user';
					if( jsonData.hasOwnProperty('user') )
						jsonData['friend'].push( json.value );
					else
						jsonData['friend'] = new Array( json.value );
					break;
				case '#':
					class_str = 'tag';
					if( jsonData.hasOwnProperty('tag') )
						jsonData['tag'].push( json.value );
					else
						jsonData['tag'] = new Array( json.value );
					break;
			}
			if( class_str == "" && json.value == "link" ){ 
				class_str = "link";
				if( jsonData.hasOwnProperty('link') )
					jsonData['link'].push( json.label );
				else
					jsonData['link'] = new Array( json.label );
			}	
			else if( class_str == "" && json.value == "time" ){
				if( jjsonData.hasOwnProperty('time') )
					jsonData['time'].push( json.value );
				else
					jsonData['time'] = new Array( json.value );
				class_str = "time";
			}	
			$span = '<span class="' + class_str + '" contenteditable="true">';
			$cspan = '</span>';
			curStr = miniconsole.data('curStr');
			ortxt = miniconsole.get(0).innerHTML;
			duptxt = sync_content(ortxt,dupconsole.get(0).innerHTML)
			ortxt = ortxt.replace(curStr,json.label);
			duptxt = duptxt.replace(curStr,$span + json.label +$cspan);
			dupconsole.html(duptxt)
			miniconsole.html(ortxt);
			miniconsole.data('jsonData',jsonData);
			console.log(ortxt + "][" + curStr + "][" + json.label);
		});
				
		miniconsole.keyup(function(event){
			if (window.getSelection) {
				userSelection = window.getSelection();
			}
			else if (document.selection) { // should come last; Opera!
				userSelection = document.selection.createRange();				
			}
			cursorPosition = userSelection.focusOffset;
			str = miniconsole.get(0).innerHTML;
			strEnds = str.substr(cursorPosition).match(/[^\s]*/g)[0];
			strBegins = str.substr(0,cursorPosition).reverse().match(/[^\s]*/g)[0].reverse();
			
			curStr = $.decode( strBegins + strEnds );
			WHATPRESSED = curStr[0] == '@' ? 1 : ( curStr[0] == '#' ? 2 : ( curStr[0] == '<' ? 3 : -1 ) );
			console.log(WHATPRESSED + " " + curStr + " " + cursorPosition + " [ "+ curStr.replace(/<(.|\n)*?>/g,"") );
			
			if(event.which == 32 ){
				if( WHATPRESSED == 3 ){
					dt = Date.parse(curStr.substr(1));
					console.log(dt + " [" + curStr.substr(1) + "]");
					$(document).trigger('selection.made',{'value': 'time', 'label':curStr});
				}
				else if( url_regex.test(curStr)){
					console.log( "URL " + curStr);
					$(document).trigger('selection.made',{'value': 'link', 'label':curStr});
				}
			}
			parent.$(parent.document.getElementById(parentid)).find('.console_input')
				.keydown().val(  $.trim($.decode(curStr.replace(/<(.|\n)*?>/g,""))) );
			// the keydown is needed here as the keydown trigger works better 
			// than most others I tried.
			
			miniconsole.data('curStr',curStr);
			miniconsole.data('WHATPRESSED',WHATPRESSED);
		});
		
		
		dupconsole.keydown(function(event){
			event.preventDefault();
		});
		
		miniconsole.keydown(function(event){
			str = miniconsole.get(0).innerHTML.replace(/<(.|\n)*?>/g,"");
			if( str.length > 20 )
				miniconsole.get(0).innerHTML = str;				
			
			if( (event.which >= 37 && event.which <= 40) || event.which == 13 ){
				curStr = miniconsole.data('curStr');
				WHATPRESSED = miniconsole.data('WHATPRESSED');		
				var e = jQuery.Event("keydown");
				e.which = event.which;
				e.keyCode = event.which;
				parent.$(parent.document.getElementById(parentid)).find('.console_input')
					.trigger(e).val(curStr); 		
				if( WHATPRESSED != -1 && (event.which == 38 || event.which == 40 || event.which == 13))
					event.preventDefault();
			}
		});
	}
	
	function sync_content(ortext, duphtml){
		duptext = duphtml.replace(/<(.|\n)*?>/g,"");
		dupappend = ortext.substr(duptext.length);
		return duphtml + dupappend;
	}

	$.fn.miniConsole = function( method ) {
		if ( methods[method] ) {
			return methods[ method ].apply( this, Array.prototype.slice.call( arguments, 1 ));
		} else if ( typeof method === 'object' || ! method ) {
			return methods.init.apply( this, arguments );
		} else {
			$.error( 'Method ' +  method + ' does not exist in miniConsole' );
		}    
	};
})( jQuery );
