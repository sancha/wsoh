(function( $ ){
	var methods = {
		destroy : function() {
		},
		
		init : function (params) {
			return this.each(function(){
				mid = $(this).attr('id');
				$(this)
				.append($('<iframe />', {
					'class'	: 'console_iframe',
					'name' 	: 'console_iframe',
					'id'	:   mid + '.iframe',
					'src'	:  './iframe.html?parentid='+mid
					}))
				.append($('<br />'))	
				.append($('<input />',{
					'id'	:   mid + '.input',
					'class'	: 'console_input'
					}));
				bind_triggers({
					 'mid'		: mid,
					'element'	: $(this).find('.console_input'),
					'friends'	: params.atlist,
					'hashtags' 	: params.hashlist
				});
			});			
		}
	}
	
	function bind_triggers(params) {
		mid = params.mid;
		el = params.element;
		friends = params.friends;
		hashtags = params.hashtags;
		
		$.ui.autocomplete.prototype._renderMenu = function( ul, items ) {
		   var self = this;
		   $.each( items, function( index, item ) {
			  if (index < 10)
				 {self._renderItem( ul, item );}
			  });
		}

		var autocompleteOptions = {
			minLength: 3,
			source: function( request, response ) {
				// delegate back to autocomplete, but extract the required term
				str = el.val();
				WHATPRESSED = str[0] == '@' ? 1 : ( str[0] == '#' ? 2 : ( str[0] == '<' ? 3 : -1 ) );
				el.data("WHATPRESSED",WHATPRESSED);
				if(str != "" && str[0] == '@')
					response( $.ui.autocomplete.filter(friends, str.substr(1) ) );
				else if(str != "" && str[0] == '#')
					response( $.ui.autocomplete.filter(hashtags, str.substr(1) ) );
			},
			focus: function (event, ui) {
				prefixedJson = prefixTrigger(el,ui);
				el.val(prefixedJson.label);
				return true;
			},
			select: function(event,ui){
				prefixedJson = prefixTrigger(el,ui);
				iframe_name = $(document.getElementById('iframe-tester-1.iframe')).attr('name');
				i$ = window.frames[ iframe_name ].$;
				i$(window.frames[ iframe_name ].document).trigger('selection.made',prefixedJson)
					.find('#comment').focus();
				el.val("");	
				return true;
			}
		};
		
		el.autocomplete(autocompleteOptions).keydown(function(event){
			/**
			if( el.val().replace(/&lt;/g,'<')[0] == '<' ){
				el.daterangepicker({
					onClose : function(){
						DATEPRESSED = 0;
						val = el.val();
						if(val != "<")
							console.log(val);
						$(".ui-daterangepickercontain").remove();
					},
					onChange: function(){
						d = Date.parse($(this).val());
						console.log(d); 
					}
				},'#buttonA'+pid,params.parent);
				$('#buttonA'+pid).trigger('click');
			}
			**/
		});
	}

	function prefixTrigger(el,ui){
		var selectedObj = ui.item;
		WHATPRESSED = el.data("WHATPRESSED");
		appender = '';
		switch(WHATPRESSED){
			case -1: 	console.log("faulty"); 	break;
			case 1:		appender = '@';			break;
			case 2:		appender = '#';			break;
			case 3:		appender = '<';			break;
		}
		return {'label': appender + ui.item.label, 'value' : ui.item.value}
	}

	$.fn.powerConsole = function( method ) {
		// Method calling logic
		if ( methods[method] ) {
			return methods[ method ].apply( this, Array.prototype.slice.call( arguments, 1 ));
		} else if ( typeof method === 'object' || ! method ) {
			return methods.init.apply( this, arguments );
		} else {
			$.error( 'Method ' +  method + ' does not exist in power-console' );
		}    
	};
})( jQuery );


