/* admin.js */
( function($) { // within this function, $ always refers to jQuery
	$(document).ready( function () {
/* // set up routes
must happen after ready otherwise ich isn't ready
*/
		var Router = Backbone.Router.extend({
			routes: {
				'': 'index',
				'models': 'models',
				'settings': 'settings',
				'model/:id': 'model',
				'new_model': 'new_model',
			},
		});
		$.router = new Router();
		$.router.on('route:index', function() {
			$.view.initial();
			$.server.fetch_models({ do_display: true });
		});
		$.router.on('route:models', function() {
			if( !$("#initial_tabs") )
				$.view.initial();

			$("#initial_tabs a[href='#tab_models']").click();
		});
		$.router.on('route:settings', function() {
			if( !$("#bm_press_admin").find("#initial_tabs").length )
				$.view.initial();

			$("#initial_tabs a[href='#tab_settings']").click();
		});
		$.router.on('route:model', function( id ) {
			$.server.fetch_model( id );
		});
		$.router.on('route:new_model', function( id ) {
			$.server.new_model();
		});
		Backbone.history.start();

	} );

	$.ajaxurl = ajaxurl;
/*
            /$$                        
           |__/                        
 /$$    /$$ /$$  /$$$$$$  /$$  /$$  /$$
|  $$  /$$/| $$ /$$__  $$| $$ | $$ | $$
 \  $$/$$/ | $$| $$$$$$$$| $$ | $$ | $$
  \  $$$/  | $$| $$_____/| $$ | $$ | $$
   \  $/   | $$|  $$$$$$$|  $$$$$/$$$$/
    \_/    |__/ \_______/ \_____/\___/ 
    */
	$.view = {
		initial: function() {
			// called on to view initial page
			$("#bm_press_admin").html(
				ich.bm_press_admin_initial()
			);
			// activate tabs
			 // didn't work by itself.. maybe just do routes
			$("#bm_press_admin .nav-tabs a").click( function (e) {
				e.preventDefault();
				$(this).tab('show');
			});
		},
		models: function() {
			// called on to view models in cache
			var html = "No models in database."
			if( ! _.isEmpty( $.server.cached_list ) ) {
				html = ich.bm_press_model_table({ models: $.server.cached_list });
			}
			$("#bm_press_admin").find("#tab_models").html(
				html
			);
		},
		model: function() {
			$("#bm_press_admin").html(
				ich.bm_press_admin_model(
					$.server.cached_model
				)
			);
			for( var section_key in $.server.cached_model.jsob ) {
				$("."+section_key).html(
					ich.bm_press_section({
						id: $.server.cached_model.id,
						key: section_key,
						name: $.section_keys[section_key].name,
						shortname: $.section_keys[section_key].shortname,
						hypos: $.server.cached_model.jsob[section_key],
					})
				);
				$(".bm_press_info_"+section_key).html(
					ich["bm_press_info_"+section_key]()
				);
			}
			$(".sortable").sortable({
				helper: "clone",
				appendTo: document.body, // where dragged element is appended
				connectWith: '.sortable',
				zIndex: 9999,
				placeholder: 'placeholder',
				cursor: "move",
				update: function( event, ui ) {
					$.model.has_changed();
				},
			});
			$(".sortable").disableSelection();
			$(".bm_press_hypo").each( function() {
				switch( $(this).attr('state') ) {
					case "C": 
						$(this).addClass("alert-success");
						break;
					case "U":
						$(this).addClass("alert-block" );
						break;
					case "D":
						$(this).addClass("alert-error" );
						break;
					case "A":
						$(this).addClass("alert-info" );
						break;
				}
			});


			$.shortcode.update( $.server.cached_model.id );
			document.title = ($.server.cached_model.designed_for || "New business model" )
				+ " — # " + $.server.cached_model.iteration
				+ " — Business Model Press";
		}
	};
/*
  /$$$$$$$  /$$$$$$   /$$$$$$  /$$    /$$ /$$$$$$   /$$$$$$ 
 /$$_____/ /$$__  $$ /$$__  $$|  $$  /$$//$$__  $$ /$$__  $$
|  $$$$$$ | $$$$$$$$| $$  \__/ \  $$/$$/| $$$$$$$$| $$  \__/
 \____  $$| $$_____/| $$        \  $$$/ | $$_____/| $$      
 /$$$$$$$/|  $$$$$$$| $$         \  $/  |  $$$$$$$| $$      
|_______/  \_______/|__/          \_/    \_______/|__/      
*/
	$.server = {
		never_been_saved: {},
		cached_list: [],
		cached_list_id: {},
		cached_model: {},
		fetch_models: function( config ) {
			$.post(
				ajaxurl,
				{ action: "bm_press_model_list" },
				function( response ) {
					// TODO: if response [], do special rendering.
					$.server.cached_list = JSON.parse( response );
					// build table indexed by id
					$.server.cached_list_id = {};
					for( var ii in $.server.cached_list ) {
						$.server.cached_list_id[
							$.server.cached_list[ ii ].id
						] = $.server.cached_list[ ii ];
					}
					if( config && config.do_display )
						$.view.models();

					if( config && config.then_fetch ) {
						/* do check for id in cached list,
						otherwise invalid ids
						will cause eternal loops */
						if( $.server.cached_list_id[ config.then_fetch ] )
							$.server.fetch_model( config.then_fetch );
						else {
							console.log("No model with id "+
								config.then_fetch +" in database.");
							window.location.assign("#");
						}
					}



				} // function(response)
			); // post
		}, // fetch_models
		fetch_model: function( id ) {
			// $.publish("load_model_before");
			// display what info we have so user knows something is happening
			$("#bm_press_admin").html(
				ich.bm_press_admin_model(
					$.server.cached_list_id[ id ]
				)
			);
			// if user has gone directly to #model/id, model list isn't fetched yet
			// MM eternal loop?
			if( ! $.server.cached_list_id[id] ) {
				$.server.fetch_models({ do_display: false, then_fetch: id, });
				return;
			}
			$.post(
				ajaxurl,
				{ action: "bm_press_load_model", id: id },
				function( response ) {
					/*
d som skjer e at nu æ kjæm me en invalid id,
så går d her i løkka å løkka.
view.model() kalle antagelivis på fetch_model,
right?
men æ burde kunne ta en sjekk her. if response.no_model
					*/
					if( JSON.parse( response ).no_model ) {
						console.log("No model with id "+id+" in database.");
						$.server.cached_model = {};
						window.location.assign("#");
					} else {
						$.server.cached_model = $.server.cached_list_id[id];
						$.server.cached_model.jsob = JSON.parse( response ).jsob;
						$.view.model();
					}
				}
			);
		},
		create_table: function() {
			//$.publish( "create_table_before");
			$.post( ajaxurl,
				{ action: "bm_press_create_database_table" },
				function( response ) {
					// $.publish( "create_table_after");
				}
			);
		},
		delete_table: function() {
			// $.publish( "delete_table_before");
			$.post( ajaxurl,
				{ action: "bm_press_delete_database_table" },
				function( response ) {
					// $.publish( "delete_table_after");
				}
			);
		},
		new_model: function() {
			$.post(
				ajaxurl,
				{ action: "bm_press_create_new_model" },
				function( response ) {
					var model = $.server.cached_model = JSON.parse(response).bm_press_model;
					$.server.cached_list.push( model );
					$.server.cached_list_id[model.id] = model;
					$.server.never_been_saved[model.id] = model;
					$.view.model();
					$.model.has_changed();
					/* for some reason the following line of code
					causes collapsibles to get their heights set to
					20px. */
					$("#model_tabs a[href='#tab_meta']").click();
					$("#inputDesignedFor").focus();
				}
			);
		}
	};
/*
 /$$                                    
| $$                                    
| $$$$$$$  /$$   /$$  /$$$$$$   /$$$$$$ 
| $$__  $$| $$  | $$ /$$__  $$ /$$__  $$
| $$  \ $$| $$  | $$| $$  \ $$| $$  \ $$
| $$  | $$| $$  | $$| $$  | $$| $$  | $$
| $$  | $$|  $$$$$$$| $$$$$$$/|  $$$$$$/
|__/  |__/ \____  $$| $$____/  \______/ 
           /$$  | $$| $$                
          |  $$$$$$/| $$                
           \______/ |__/                
           */
	$.hypo = {
		title: function( hypo_luid ) {
			return $("#"+hypo_luid+"_title").html();
		},
		state: function( hypo_luid ) {
			return $("#"+hypo_luid ).attr("state");
		},
		set_state: function( hypo_luid, state ) {
			var elem = $('#'+hypo_luid );
			if( elem.attr("state") == state ) {
				// attempted statechange from same to same.
				return;
			}
			elem.attr("state", state );
			elem
				.removeClass("alert-success")
				.removeClass("alert-block")
				.removeClass("alert-error")
				.removeClass("alert-info");
			switch( elem.attr('state') ) {
				case "C": 
					elem.addClass("alert-success");
					break;
				case "U":
					elem.addClass("alert-block" );
					break;
				case "D":
					elem.addClass("alert-error" );
					break;
				case "A":
					elem.addClass("alert-info" );
					break;
			}
			$.model.has_changed();
		},
		get: function( hypo_luid ) {
			return {
				hypo_luid: hypo_luid,
				title: $.hypo.title( hypo_luid ),
				state: $.hypo.state( hypo_luid )
			};
		},
		set: function( hypo_luid ) {
			$("#"+hypo_luid +"_title").html( $("#"+hypo_luid+"_field").val() );
			$.model.has_changed(); // even if new = old, but won't implement check now
		},
		edit: function( hypo_luid ) {
			$("#"+hypo_luid +"_title").html(
				ich.bm_press_hypo_title_field(
					$.hypo.get( hypo_luid )
				)
			);
			$("#"+hypo_luid +"_field").focus();
			$("#"+hypo_luid +"_field").bind('keypress',
				function( e ) {
					if( (e.keyCode ? e.keyCode : e.which) == 13 )
						$.hypo.set( hypo_luid );
				}
			);
		},
		delete: function( hypo_luid  ) {
			$("#"+hypo_luid ).remove();
		},
		luid: function() {
			var str = $.base64.encode( ""+(new Date()).getTime() );
			return str.substring( str.length - 8, str.length - 2 );
		},
		new: function( key ) {
			var luid = $.hypo.luid();
			var field = $('#'+key+"_hypo_maker");
			var new_hypo = {
				hypo_luid: luid,
				title: field.val(),
				state: 'U',
			};
			field.val("");
			$('#'+$.server.cached_model.id+"_"+key+"_hyps .sortable").append(
				ich.bm_press_hypo( new_hypo )
			);
			// $('#'+$.server.cached_model.id+"_"+key+'_hyps').css('height','none');
			$.model.has_changed();
		},
	};
/*
                               /$$           /$$
                              | $$          | $$
 /$$$$$$/$$$$   /$$$$$$   /$$$$$$$  /$$$$$$ | $$
| $$_  $$_  $$ /$$__  $$ /$$__  $$ /$$__  $$| $$
| $$ \ $$ \ $$| $$  \ $$| $$  | $$| $$$$$$$$| $$
| $$ | $$ | $$| $$  | $$| $$  | $$| $$_____/| $$
| $$ | $$ | $$|  $$$$$$/|  $$$$$$$|  $$$$$$$| $$
|__/ |__/ |__/ \______/  \_______/ \_______/|__/
*/
	$.model = {
		unsaved: false,
		has_changed: function() {
			$.model.unsaved = true;
			$(".saveButton").addClass("btn-primary");
			$(".saveButton").html(
				'<i class="icon-chevron-right icon-white"></i> Save'
			);
			// update headerbar if designed_for has changed
			var old_for = $.server.cached_model.designed_for;
			var new_for = $("#inputDesignedFor").val();
			if( old_for != new_for ) {
				$.server.cached_model.designed_for = new_for;
				$("#bm_press_admin .navbar .brand").html(
					$.server.cached_model.designed_for
					+ " &mdash; #" + $.server.cached_model.iteration
				)
			}
			window.onbeforeunload = function() {
				if( $.model.unsaved ) // might not need this check, but can't hurt
					return "Your business model has changed since the last time it was saved.";
			}
		},
		been_saved: function( id ) {
			$.model.unsaved = false;
			/* delete model from neverbeensaved in case it was new
			(will not show shortcode until model is saved) */
			delete $.server.never_been_saved[ id ];
			delete window.onbeforeunload;
			$(".saveButton").html(
				'<i class="icon-ok"></i> Saved'
			);
			$(".saveButton").removeClass("btn-primary");
		},
		get: function() {
			var ob = {
				id : $.server.cached_model.id,
				designed_for : $.model.designed_for(),
				designed_by : $.model.designed_by(),
				iteration: $.server.cached_model.iteration,
				time_created : $.server.cached_model.time_created,
				time_saved : $.server.cached_model.time_saved,
				jsob : {},
			};
			// for each section in jsob, use the key
			// to find jquery objects, then map a function
			// from them into the jsob
			for( section_key in $.server.cached_model.jsob ) {
				ob.jsob[section_key] = [];
				$("."+section_key +" .bm_press_hypo").each( function() {
					ob.jsob[section_key].push({
						hypo_luid: this.id,
						title: $.hypo.title( this.id ),
						state: $.hypo.state( this.id ),
					});
				});
			}
			return ob;
		},
		designed_for: function() {
			return $("#inputDesignedFor").val();
		},
		designed_by: function() {
			return $("#inputDesignedBy").val();
		},
		close: function() {
			if( $.model.unsaved ) {
				$.modal.close_without_saving();
			} else
				$.model.confirmed_close();
		},
		confirmed_close: function() {
			$.server.cached_model = {};
			$.model.been_saved();
			$("#close_without_saving").modal('hide');
			window.location.assign('#');
		},
		iterate: function() {
			$.list.iterate( $.model.get().id );
		},
		id: function() {
			var str = $.base64.encode( ""+(new Date()).getTime() );
			return str.substring( str.length - 10, str.length - 2 );
		},
		save: function( config ) { // { then_close: true/false }
			$(".saveButton").addClass("btn-primary");
			$(".saveButton").html(
				'<i class="icon-chevron-right icon-white"></i> Saving...'
			);
			var model = $.model.get();
			$.post( ajaxurl,
				{
					action: "bm_press_save_model",
					data: JSON.stringify( model ),
				},
				function( response ) {
					$.model.been_saved( model.id );
					$.shortcode.update( model.id );
					if( config && config.then_close )
						$.model.confirmed_close();
				}
			);
		},
		delete: function() {
			$.modal.delete_model();
		},
		confirmed_delete: function() {
			$(".deleteButton").html(
				'<i class="icon-trash icon-white"></i> Deleting...'
			);
			$.post( ajaxurl,
				{ action: "bm_press_delete_model", id: $.server.cached_model.id },
				function( response ) {
					$.server.cached_list = [];
					$.server.cached_list_id = {};
					$.server.cached_model = {};
					$('#delete_model').modal('hide'); /// MMM THERE MIGHT BE SEVERAL OF THESE
					window.location.assign("#");
				}
			);
		},
	};
/*
 /$$ /$$             /$$    
| $$|__/            | $$    
| $$ /$$  /$$$$$$$ /$$$$$$  
| $$| $$ /$$_____/|_  $$_/  
| $$| $$|  $$$$$$   | $$    
| $$| $$ \____  $$  | $$ /$$
| $$| $$ /$$$$$$$/  |  $$$$/
|__/|__/|_______/    \___/  
*/
	// handlers for items in list
	$.list = {
		confirmed_delete: function( id ) {
			$(".deleteButton").html(
				'<i class="icon-trash icon-white"></i> Deleting...'
			);
			$.post( ajaxurl,
				{ action: "bm_press_delete_model", id: id },
				function( response ) {
					$.server.cached_list = [];
					$.server.cached_list_id = {};
					$.server.cached_model = {};
					$('#delete_model_'+ id ).modal('hide');
					$.server.fetch_models( { do_display: true });
				}
			);
		},
		iterate: function( id ) {
			$(".iterateButton").addClass("btn-primary");
			$(".iterateButton").html(
				'<i class="icon-refresh icon-white"></i> Iterating...'
			);
			$.post( ajaxurl,
				{ action: "bm_press_iterate", id: id },
				function( response ) {
					var model = JSON.parse( response );

					$.server.cached_list.push(model);
					$.server.cached_list_id[model.id] = model;
					$.server.cached_model = model;
					window.location.assign('#model/'+model.id);
				}
			);
		}
	}
	$.shortcode = {
		generate: function( config ) {
			var str = "[bm_press"  // TODO: note which commands in which files this corresponds to
			/* 3 clauses.
			1st: if we haven't been passed a id, fail. IN ADDITION
			2nd: if we currently have an open model that has never been saved, fail. OR
			3rd: if we are in list-view, clicked model must have been saved. cached_model should be empty
			*/
			if( config.id &&
					( !$.server.never_been_saved[config.id]
						|| _.isEmpty( $.server.cached_model )
					)
				)
				str += " id='"+config.id+"'";
			else
				return "Save model to get shortcode";
			/*if( config.width )
				str += " width='"+config.width+"'";
			if( config.height )
				str += " height='"+config.height+"'";
			if( config.hide_title )
				str +=" hide_title=1"
			if( config.hide_attribution )
				str +=" hide_attribution=1"*/ // keeping comment so that if i want to add i know how
			str += "]"
			return str;
		},
		update: function( id ) {
			$("#outputShortcode").val(
				$.shortcode.generate({
					id: id,
					width: $("#inputWidth").val(), 
					height: $("#inputHeight").val(), 
					hide_title: $("#checkboxHideTitle").is(':checked'),
					hide_attribution: $("#checkboxHideAttribution").is(':checked'),
				})
			);
		},
	}
/*
                               /$$           /$$
                              | $$          | $$
 /$$$$$$/$$$$   /$$$$$$   /$$$$$$$  /$$$$$$ | $$
| $$_  $$_  $$ /$$__  $$ /$$__  $$ |____  $$| $$
| $$ \ $$ \ $$| $$  \ $$| $$  | $$  /$$$$$$$| $$
| $$ | $$ | $$| $$  | $$| $$  | $$ /$$__  $$| $$
| $$ | $$ | $$|  $$$$$$/|  $$$$$$$|  $$$$$$$| $$
|__/ |__/ |__/ \______/  \_______/ \_______/|__/
*/
	$.modal = {
		close_without_saving: function() {
			$('#close_without_saving').modal();
		},
		delete_model: function() {
			$('#delete_model').modal();
		},
		list_delete: function( id ) {
			$("#delete_model_"+id ).modal();
		},
		list_shortcode: function( id ) {
			$("#shortcode_model_"+id ).modal();
			$.shortcode.update( id );
		},
	}

/*
 /$$                                      /$$$$$$  /$$   /$$
| $$                                     /$$__  $$| $$  | $$
| $$$$$$$   /$$$$$$   /$$$$$$$  /$$$$$$ | $$  \__/| $$  | $$
| $$__  $$ |____  $$ /$$_____/ /$$__  $$| $$$$$$$ | $$$$$$$$
| $$  \ $$  /$$$$$$$|  $$$$$$ | $$$$$$$$| $$__  $$|_____  $$
| $$  | $$ /$$__  $$ \____  $$| $$_____/| $$  \ $$      | $$
| $$$$$$$/|  $$$$$$$ /$$$$$$$/|  $$$$$$$|  $$$$$$/      | $$
|_______/  \_______/|_______/  \_______/ \______/       |__/
*/
	$.base64 = {
		// from http://www.webtoolkit.info/javascript-base64.html
		_keyStr : "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+-_",
		encode: function (input) {
		    var output = "";
		    var chr1, chr2, chr3, enc1, enc2, enc3, enc4;
		    var i = 0;
		    input = $.base64._utf8_encode(input);
		    while (i < input.length) {
		        chr1 = input.charCodeAt(i++);
		        chr2 = input.charCodeAt(i++);
		        chr3 = input.charCodeAt(i++);
		        enc1 = chr1 >> 2;
		        enc2 = ((chr1 & 3) << 4) | (chr2 >> 4);
		        enc3 = ((chr2 & 15) << 2) | (chr3 >> 6);
		        enc4 = chr3 & 63;
		        if (isNaN(chr2)) enc3 = enc4 = 64;
		        else if (isNaN(chr3)) enc4 = 64;

		        output = output +
		        	this._keyStr.charAt(enc1) +
		        	this._keyStr.charAt(enc2) +
		        	this._keyStr.charAt(enc3) +
		        	this._keyStr.charAt(enc4);
		    }
		    return output;
		},
		_utf8_encode : function (string) {
			string = string.replace(/\r\n/g,"\n");
			var utftext = "";
			for (var n = 0; n < string.length; n++) {
				var c = string.charCodeAt(n);
				if (c < 128) {
					utftext += String.fromCharCode(c);
				} else if((c > 127) && (c < 2048)) {
					utftext += String.fromCharCode((c >> 6) | 192);
					utftext += String.fromCharCode((c & 63) | 128);
				} else {
					utftext += String.fromCharCode((c >> 12) | 224);
					utftext += String.fromCharCode(((c >> 6) & 63) | 128);
					utftext += String.fromCharCode((c & 63) | 128);
				}
			}
			return utftext;
		},
	}; // base64
})(bm_press); // within this function, $ always refers to jQuery
