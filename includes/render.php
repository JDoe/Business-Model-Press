<?php
/***************
*  Render
icanhaz uses script id's to name objects.
being publicly embedded in any number on any number of posts,
templates must be world-unique in their script id's.
this is accomplished by prefixing id's with bm_press_
do not shorten this to simply bm_
Make sure to keep elements easily findable in jQuery
when they have their unique ids.
Contains functions to output scripts.
Contains functions to include less.css.js
Structure of Render
found in output_json_editor_structure
name should coincide with stache and css names of things.
*/
class BM_Press_Render {
	private $url_dir_bm_press;  // url to dir of bm-press plugin
	private $url_dir_ext_js;    // url to dir of external javascript
	private $url_dir_ext_php;   // url to dir of external php
	private $dir_int_scripts;   // dir of internal scripts
///////////////////////////////////////////////             Initialize visibles
	function __construct() {
		// set folder variables
		$this->url = plugins_url("bm-press") .'/';
		$this->dir = dirname(__FILE__) .'/';
		$this->admin_related();
		$this->viewer_related();
	}
////////////////////////////////////////////////////////////////////////// ADMIN
	private function admin_related() {
		add_action('admin_menu', array('BM_Press_Render', '_admin_head_and_page') );
	}

	public function _admin_head_and_page() {
		// Add menu to admin dashboard
		$menu_page = add_menu_page(
			'Business Model Press', // title of page when menu selected
			'BM Press', // On-screen name text for menu
			'edit_others_posts',   // who has access
			'bm_press',  // the menu name
			function () {
				global $bm_press;
				$bm_press->r->output_admin_html();
			},
			plugin_dir_url( __FILE__ ) . 'bmpress_menuicon.png'
		);
		// Put admin headers in <head> of menu_page
		/*	"admin_head-(plugin_page) is triggered within the <head></head>
			section of a specific plugin-generated page."
		*/
		add_action('admin_head-'.$menu_page , function() {
			global $bm_press;
			$bm_press->r->output_admin_head();
		});
	}
	public function output_admin_head() {
		// internal less and mustache files
		$this->stache( 'output','int','admin.mustache' );
		$this->less( 'output','int','admin.less' );
		                                                     // External scripts
		//$this->js( 'output','ext','jquery-1.9.1.min.js' );            //    jQuery
		$this->js( 'output','ext','jquery-ui-1.10.3.custom.min.js' ); // jQuery UI
		//$this->css( 'output','ext','jquery-ui-1.10.3.custom.css' ); // jQuery UI CSS
		$this->js( 'output','ext','ICanHaz.min.js' );                 // I Can Haz
		// make sure to include styles before the less js
		$this->js( 'output','ext','less-1.3.3.min.js' );          //  LESS CSS
		$this->js( 'output','ext','json2.js' );                  // JSON2
		$this->js( 'output','ext','bootstrap/js/bootstrap.min.js' );   // Bootstrap
		$this->css( 'output','ext','bootstrap/css/bootstrap.min.css' );  // Bootstrap CSS
		$this->css( 'output','ext','bootstrap/css/bootstrap-responsive.min.css' );  // Bootstrap Responsive CSS
		$this->js( 'output','ext','underscore-min.js' ); // Underscore
		$this->js( 'output','ext','backbone-min.js' ); // Backbone (for routes)
		echo '<meta name="viewport" content="width=device-width, initial-scale=1.0">';
                                                     // Internal scripts
		$this->js( 'output','int','common.js.php' );
		$this->js( 'output','int','admin.js' );
	}
	// called in action('admin_menu') callback
	public function output_admin_html() {
		echo '<div id="bm_press_admin"></div>';
		// will be filled by internal js in admin.js
	}
///////////////////////////////////////////////////////////////////////// VIEWER
	private function viewer_related() {
		
		add_action('wp_head', function() {
			// does not happen for admin menu
			global $bm_press;
			$bm_press->r->output_nonadmin_head();
		});
		add_shortcode('bm_press', function( $args ) {
			global $bm_press;
			return $bm_press->r->return_viewer($args);
		});
	}
	// called in action('wp_head')
	public function output_nonadmin_head() {
		$this->less( 'output','int','viewer.less');
		$this->stache( 'output','int','viewer.mustache' );
		                                                  // External Javascript
		$this->js( 'output','ext','jquery-1.9.1.min.js' );    //              jQuery
		$this->js( 'output','ext','ICanHaz.min.js' );         //        I Can Haz JS
		// make sure to include styles before the less script
		$this->js( 'output','ext','less-1.3.3.min.js' );      //            LESS CSS
		$this->js( 'output','ext','bootstrap/js/bootstrap.min.js' );   // Bootstrap
		$this->css( 'output','ext','bootstrap/css/bootstrap.min.css' );  // Bootstrap CSS
		$this->css( 'output','ext','bootstrap/css/bootstrap-responsive.min.css' );  // Bootstrap Responsive CSS
		$this->js( 'output','ext','underscore-min.js' ); // Underscore
		                                                  // internal javascript
		$this->js( 'output','int','common.js.php' );
		echo '<meta name="viewport" content="width=device-width, initial-scale=1.0">';
		wp_enqueue_script('bm_press_viewer', plugins_url("bm-press") .'/includes/int-scripts/viewer.js');
		// $this->js( 'output','int','viewer-head.js' );
	}
	// called in shortcode('bm_press')
	public function return_viewer( $args ) {
		$str = '';
		if( isset($args['id']) )
			// $str = '<div id="'.$args['id'].'" class="bm_press_viewer"></div>';
			$str = '<div id="'.$args['id'].'" class="bm_press_viewer">'
				.'<div class="bm_canvas_foot">'
				.	'<a href="http://bmpress.io">Create and Blog Business Models on Canvas in WordPress for FREE with Business Model Press</a>.<br>'
				.	'<a href="http://businessmodelgeneration.com/canvas">Business Model Canvas</a> <a href="http://creativecommons.org/licenses/by-sa/3.0/">CC BY</a> by <a href="http://alexosterwalder.com">Osterwalder</a>.'
				.'</div>'
				.'</div>';
		else
			return $str;

		// send variables to javascript
		wp_localize_script( 'bm_press_viewer',
			'bm_press_wp_data',
			array('ajaxurl' => admin_url( 'admin-ajax.php' ) )
		);
		wp_localize_script( 'bm_press_viewer',
			'bm_press_'.$args['id'],
			$args
		);
		// will be filled by jquery when the document is ready
		// todo: remove "_wrap" in name.
		return $str;
	}
////////////////////////////////////////////////////////////////////// UTILITIES
	function get_include_contents( $file_in_path ) {
		if( is_file( $file_in_path )) {
			ob_start();
			include $file_in_path;
			return ob_get_clean();
		}
		return "file not found: $file_in_path";
	}
	function js( $add_or_output, $int_or_ext, $filename ) {
		// instead of as external. => user gets less latency
		$str = '<script type="text/javascript">';
		$str .= $this->get_include_contents( $this->dir . $int_or_ext .'-scripts/'. $filename);
		$str .= '</script>'.PHP_EOL;
		if( $add_or_output == "add")
			return $str;
		else
			echo $str;
	}
	function less( $add_or_output, $int_or_ext, $filename ) {
		$str = '<style type="text/less">'.PHP_EOL;
		$str .= $this->get_include_contents( $this->dir . $int_or_ext .'-scripts/'. $filename );
		$str .= '</style>'.PHP_EOL;
		if( $add_or_output == "add")
			return $str;
		else
			echo $str;
	}
	function css( $add_or_output, $int_or_ext, $filename ) {
		if( $add_or_output == "output_as_ext" ) {
			$str = '<link rel="stylesheet" type="text/css" '.
				'href="'. $this->url . '/includes/'.
				$int_or_ext .'-scripts/'.
				$filename . '" />';
			echo $str;
		} else {
			$str = '<style type="text/css" media="screen">'.PHP_EOL;
			$str .= $this->get_include_contents( $this->dir . $int_or_ext .'-scripts/'. $filename );
			$str .= '</style>'.PHP_EOL;
			if( $add_or_output == "add")
				return $str;
			else 
				echo $str;
		}
	}
	// files must include <script ... > tags for the staches.
	function stache( $add_or_output, $int_or_ext, $filename ) {
		$str = $this->get_include_contents( $this->dir . $int_or_ext .'-scripts/'. $filename );
		if( $add_or_output == "add")
			return $str;
		else
			echo $str;
	}
} // end of class BM_Press_Render