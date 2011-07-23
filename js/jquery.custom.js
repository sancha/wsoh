String.prototype.trim = function() { return this.replace(/^\s\s*/, '').replace(/\s\s*$/, ''); }
String.prototype.reverse=function(){ return this.split("").reverse().join(""); }

jQuery.extend({
    isnull: function(o) { 
        if(o == null || o == undefined) { 
          return true; 
        } 
        return false
    },
	is_string : function( str ){
		return typeof str == 'string';
	},
	isfn : function(fn) { 
		return typeof fn == 'function';
	},
	exists : function(v) {
		return typeof v != 'undefined';
	},
	isarray : function( arr ) {
	  return !!arr && arr.constructor == Array;
	},
	to_csv : function ( json ) {
		out = {};
		$.each(json,function(key,val){
			if( $.isArray(val) )
				out[ key ] = val.join(",") ;
			else
				out[ key ] = val;
		});
		return out;
	},
	uniq: function( arr ) {
		/** very restricted function. supports only
		 *  objects. works best with scalar objects 
		 */
		if( $.isarray( arr ) ){
		    var hash = {}, result = [];
	    	for ( var i = 0, l = arr.length; i < l; ++i ) {
				if ( !hash.hasOwnProperty(arr[i]) ) { 
					hash[ arr[i] ] = true;
					result.push(arr[i]);
				}
			}
			return result;
		}else
			return arr;
	},
	uniqids: function( arr ) {
		if( $.isarray( arr ) ){
		    var hash = {}, result = [];
	    	for ( var i = 0, l = arr.length; i < l; ++i ) {
				if ( !hash.hasOwnProperty(arr[i].id) ) { 
					hash[ arr[i].id ] = true;
					result.push(arr[i]);
				}
			}
			return result;
		}else
			return arr;
	},
	urlparam: function( name ) {
		name = name.replace(/[\[]/,"\\\[").replace(/[\]]/,"\\\]");
		regexS = "[\\?&]"+name+"=([^&#]*)";
		regex = new RegExp( regexS );
		results = regex.exec( window.location.href );
		return ( results == null ) ? "" : results[1];
	},
	decode: function( str ) {
		var ta=document.createElement("textarea");
		ta.innerHTML=str.replace(/</g,"&lt;").replace(/>/g,"&gt;");
		return $.trim(ta.value);
	}
});
