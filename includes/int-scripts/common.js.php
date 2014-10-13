// common.js (.php) defines the bm_press javascript object as a jquery instance,
// it adds peter higgins' pubsub to the object, and then defines
// the empty business model and section keys. section_keys is the object
// { key_partners: { name: , shortname: }, ... }
<?php
/*
this javascript file uses php to read the same models that are used in the
global BM_Press_Structure object $bm_press->d.
*/
/*
exists in global space in a WP action context ('wp_head'),
so all WP and $bm_press variables are available.
// included by $bm_press->visibles->output_html_head_elements(),
// which is called by the action handler defined in bm-press.php
// let's do wp ajax first and worry about this LATER
*/
/*
( function($) { // within this function, $ always refers to jQuery
	$(document).ready( function () { 
		// business model in json
		bm_model_j =
			<?php $bm_press->data_structure->output_json_model(); ?>;
			*/
global $bm_press;
?>
///////////////////////////////////////  all code in this file is in <head>
///////////////////////////////////////  independent of viewer | editor
// now true: alert(!("bm_press" in window));
if( !("bm_press" in window) )
  bm_press = jQuery.noConflict();
// now false alert(!("bm_press" in window));
// add pubsub to bmpress jquery object:
(function($){
	// the topic/subscription hash
	var cache = {};
	$.publish = function(topic, args){
		cache[topic] && $.each(cache[topic], function(){
			this.apply($, args || []);
		});
	};
	$.subscribe = function(topic, callback){
		if(!cache[topic]){ cache[topic] = []; }
		cache[topic].push(callback);
		return [topic, callback]; // Array
	};
	$.unsubscribe = function(handle){		
		var t = handle[0];
		cache[t] && $.each(cache[t], function(idx){
			if(this == handle[1]){
				cache[t].splice(idx, 1);
			}
		});
	};
})( bm_press );
////////////////////////////////////////////////              Start of BM Press JS Code
( function($) { // within this function, $ always refers to jQuery and BM Press
	$.section_keys = <?php echo json_encode( $bm_press->d->section_keys ); ?>;
	// section_keys is { key_partners: { name: , shortname: }, ... }	
})( bm_press ); // within this function, $ always refers to the bm_press jQuery instance.
