/*
in the DOM, one or more divs classed bm_press_viewer are attributed
their models id as the id for the div, and optionally given css styles for height
and width according to optional given arguments
*/
//alert('bm_press?' + bm_press);
( function($) { // within this function, $ always refers to jQuery
	$(document).ready( function () {
		// read ids from html
		$(".bm_press_viewer").each( function() {
			var id = this.id;
			$.viewer.cached[ id ] = {
				id: id,
			};
			//$("#"+id ).html( ich.bm_press_viewer( $.viewer.cached[ id ] ) );
		});
		// add instance info to divs to distringuish instances of same model
		// and load models
		for( var id in $.viewer.cached ) {
			var inst_count = 0;
			// select by attribute to get multiple divs with same id
			$("[id="+id+"]").each( function() {
				$(this).attr("instance", (inst_count++) );
			});
			$.viewer.load_model( id );
			
		}
		
		
	 } );
	
/*
So instead of relying on a global javascript variable, declare a javascript
namespace object with its own property, ajaxurl. As the article suggests, use
wp_localize_script() to make the URL available to your script, and generate it
using this expression: admin_url('admin-ajax.php')
*/
	$.viewer = {
		ajaxurl: bm_press_wp_data.ajaxurl,
		cached: {}, // maps id to received objects
		load_model: function( id ) {
			//$.publish("load_model_before", [ id ]);
			
			if( !id ) {
				console.log("bm_press.viewer.load_model was called without a id");
				return false;
			} /*else
				console.log("Calling on server for contents of model with id: "+ id +".");*/
			$.post(
				$.viewer.ajaxurl,
				{ action: "bm_press_load_full_model", id: id },
				function( response ) {
					// first fill div, then fill sections
					$.viewer.cached[ id ] = JSON.parse( response );
					$.viewer.bm_press_viewer( id );
					//console.log("inni post-response. count em.")
					//$.publish("load_model_after", [ id ]);

				}
			);
		}, // load_model
		bm_press_viewer: function( id ) {
			// select by id attribute to get multiple elements
			var s_vars = window['bm_press_'+id ]; // shortcode variables
			$("[id="+id+"]").each( function() {
				$(this).html(
					ich.bm_press_viewer(
						_.extend(
							_.clone( $.viewer.cached[ id ] ),
							s_vars
						)
					)
				);
				if( s_vars.width )
					$(this).css('width', s_vars.width );
				if( s_vars.height )
					$(this).css('height', s_vars.height );
				
			});
			
			for( var section_key in $.viewer.cached[ id ].jsob ) {
				
				// do as each to be able to get instance info from parent
				$("[id="+id+"] ."+section_key).each( function() {
					$(this).html(
						ich.bm_press_section(
							_.extend( _.clone( $.viewer.cached[ id ] ), {
								instance: $(this).parents("[id="+id+"]").attr("instance"),
								key: section_key,
								name: $.section_keys[ section_key ].name,
								shortname: $.section_keys[ section_key ].shortname,
								hypos: $.viewer.cached[ id ].jsob[ section_key ],
							})
						)
					);
				});
			}
			$("[id="+id+"] .bm_press_hypo").each( function() {
				
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
			$(".collapse").collapse();
			$(".collapse").on('hide', function() {
				$(this).parent().find(".btn").addClass("active");
			});
			$(".collapse").on('show', function() {
				$(this).parent().find(".btn").removeClass("active");
			});
		}, // bm_press_viewer

/* section_keys are defined in data-structure.php as
	key_partners
	key_activities
	key_resources
	value_propositions
	customer_relationships
	channels_
	customer_segments
	cost_structure
	revenue_streams
*/




	};// $.viewer
})(bm_press); // within this function, $ always refers to jQuery
