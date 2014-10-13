<?php
/***************
// trick: convert array to object: $object = json_decode(json_encode($array), FALSE);
//        will work recursively
*/


//   /$$$$$$$              /$$      /$$$$$$   /$$                                  
//  | $$__  $$            | $$     /$$__  $$ | $$                                  
//  | $$  \ $$  /$$$$$$  /$$$$$$  | $$  \__//$$$$$$    /$$$$$$  /$$   /$$  /$$$$$$$
//  | $$  | $$ |____  $$|_  $$_/  |  $$$$$$|_  $$_/   /$$__  $$| $$  | $$ /$$_____/
//  | $$  | $$  /$$$$$$$  | $$     \____  $$ | $$    | $$  \__/| $$  | $$| $$      
//  | $$  | $$ /$$__  $$  | $$ /$$ /$$  \ $$ | $$ /$$| $$      | $$  | $$| $$      
//  | $$$$$$$/|  $$$$$$$  |  $$$$/|  $$$$$$/ |  $$$$/| $$      |  $$$$$$/|  $$$$$$$
//  |_______/  \_______/   \___/   \______/   \___/  |__/       \______/  \_______/
//                                                                                 
//                                                                                 
//                                                                                                                                                         
class BM_Press_Data_Structure {
	public $section_keys = array(
		'key_partners' => array(
			'name'=> 'Key Partners',
			'shortname'=> 'KP',
		),
		'key_activities' => array(
			'name'=> 'Key Activities',
			'shortname'=> 'KA',
		),
		'key_resources' => array(
			'name'=> 'Key Resources',
			'shortname'=> 'KR',
		),
		'value_propositions' => array(
			'name'=> 'Value Propositions',
			'shortname'=> 'VP',
		),
		'customer_relationships' => array(
			'name'=> 'Customer Relationships',
			'shortname'=> 'CR',
		),
		'channels_' => array( // named with _ for easier searching
			'name'=> 'Channels',
			'shortname'=> 'CH',
		),
		'customer_segments' => array(
			'name'=> 'Customer Segments',
			'shortname'=> 'CS',
		),
		'cost_structure' => array(
			'name'=> 'Cost Structure',
			'shortname'=> 'C$',
		),
		'revenue_streams' => array(
			'name'=> 'Revenue Streams',
			'shortname'=> 'R$',
		),
	);

	// cache of models in database
	public $models = array(); // ( id => model object )


	function __construct() {
		//$this->models = $this->array_of_models_from_db();
	}


	public function get_section_name( $section_key ) {
		return $this->section_keys[$section_key]['name'];
	}
	public function get_section_shortname( $section_key ) {
		return $this->section_keys[$section_key]['shortname'];
	}
	public function output_jsob( $id = null ) {
		echo $this->get_presentable( $id );
	}
	// parameter is optional, null means empty model
	// actually if an invalid m has been provided, the user should be notified.
	public function get_presentable( $id = null) {
		// if a valid business model id is provided
		// return the model
		if( !is_null( $id ) && isset( $this->models[$id] ) )
			return $this->models[$id]->get_presentable();

		// otherwise, return an empty model if a null-argument
		// has been given, otherwise return an error-reporting model.
		
		$m = new BM_Press_Model();
		if( !is_null($m) )
			$m->sections["key_resources"]->add_hyp(
				new BM_Press_Hyp( "Shortcode model ID invalid" ));
		/*
			MMM: consider changing error section to "_error"
			and also having a "_meta" section.
			underscores for searchability, nothing else (not like Python)
		*/

		return $m->get_presentable();
	}


	public function generate_id() {
		global $wpdb, $bm_press;
		// check if gemerated id is already in database
		$prev_id = '';
		$iterations = 0;
		while( true ) {
			$new_id = substr( base64_encode(uniqid()), -10, 8);
			if( $new_id == $prev_id ) {
				// something is wrong, uniqid  must be failing,
				// so this check is to avoid looping eternally
				return '';
			} else {
				// look up in db to see if exists, if so repeat loop.
				/* SQL from http://stackoverflow.com/questions/1676551/best-way-to-test-if-a-row-exists-in-a-mysql-table */
				$vals_returned = $wpdb->get_col( 
					"SELECT EXISTS (SELECT 1
						FROM ".$bm_press->e->tablename."
						WHERE id = '$new_id')"
				);
				if( $vals_returned[0] == 0 )
					return $new_id;
				else
					$prev_id = $new_id;
			}
			if( $iterations++ > 10 ) // something must be wrong
				return '';
		}
	}
} // Data Structure




//   /$$      /$$                 /$$           /$$
//  | $$$    /$$$                | $$          | $$
//  | $$$$  /$$$$  /$$$$$$   /$$$$$$$  /$$$$$$ | $$
//  | $$ $$/$$ $$ /$$__  $$ /$$__  $$ /$$__  $$| $$
//  | $$  $$$| $$| $$  \ $$| $$  | $$| $$$$$$$$| $$
//  | $$\  $ | $$| $$  | $$| $$  | $$| $$_____/| $$
//  | $$ \/  | $$|  $$$$$$/|  $$$$$$$|  $$$$$$$| $$
//  |__/     |__/ \______/  \_______/ \_______/|__/
//                                                 

class BM_Press_Model {
	// json/array comes with one bm_press_model element.
	public $a_model = null;
	public $sections = array(); // ($section_key => section object)
		/*
reading files from db will result in either json or php array
let's say it results in base64decoded, unserialized, json_decoded content,
and gets passed to __construct(); 
		*/
	function __construct( $jsob = null ) {
		global $bm_press;
		global $user_ID, $user_identity;
		get_currentuserinfo(); // fills user_ID and user_identity
		if( is_null( $jsob ) ) { // $jsob has not been provided
			// create new empty model
			$this->a_model = array(
				"bm_press_model" => array(
					"id" => $bm_press->d->generate_id(), // set this now
					"designed_for" => null, // set this by a user ajax call
					"designed_by" => $user_ID,
					"iteration" => 1, // set this by a user ajax call
					"time_saved" => null, // set this when saving
					"time_created" => date("Y m d H:i:s"), // sets this now
					"jsob" => array(),
				)
			);

			foreach( $bm_press->d->section_keys as $index => $key ) {
				$this->sections["$index"] = new BM_Press_Section();
				$this->a_model["bm_press_model"]["jsob"]["$index"] = array();
			}
		} else { // $jsob has been provided
			$this->a_model = json_decode($jsob);
			// create section objects from model
			foreach ($this->a_model["bm_press_model"]["sections"] as $section_key => $section_a ) {
				// section_a is a json section decoded into an array
				$this->sections["$section_key"] = new BM_Press_Section( $section_a );
			}
		}

	}
	public function get_presentable() {
		$copy = $this->a_model;
		$user_info = get_userdata( $copy['bm_press_model']['designed_by'] );
		$copy['bm_press_model']['designed_by'] = $user_info ?
			$user_info->display_name :
			"User who had ID ". $copy['bm_press_model']['designed_by'];
		return json_encode($copy);
	}
	public function set_title( $new_title ) {
		$this->a_model["bm_press_model"]["title"] = $new_title;
	}
} // Model


//    /$$$$$$                        /$$     /$$                    
//   /$$__  $$                      | $$    |__/                    
//  | $$  \__/  /$$$$$$   /$$$$$$$ /$$$$$$   /$$  /$$$$$$  /$$$$$$$ 
//  |  $$$$$$  /$$__  $$ /$$_____/|_  $$_/  | $$ /$$__  $$| $$__  $$
//   \____  $$| $$$$$$$$| $$        | $$    | $$| $$  \ $$| $$  \ $$
//   /$$  \ $$| $$_____/| $$        | $$ /$$| $$| $$  | $$| $$  | $$
//  |  $$$$$$/|  $$$$$$$|  $$$$$$$  |  $$$$/| $$|  $$$$$$/| $$  | $$
//   \______/  \_______/ \_______/   \___/  |__/ \______/ |__/  |__/
//                                                                  
//                                                                  
//                                                                  

///////////////////////////////////////////////             Section
class BM_Press_Section {
	// json/array comes without a bm_section_element,
	// but instead always produces a { hyps: { ...} } object/array.
	private $a_hyps = null;  // array representations of hyps
	private $hyps = array(); // php hyp objects
/*
hyps arrive from MB_Press_Model constructor as a json_decoded
array from within each [$section_key] */
	function __construct( $a_hyps = null ) {
		if( is_null( $a_hyps ) ) {
			$this->a_hyps = array();
		} else { // hyps are provided in constructor as a json_decoded array
			$this->a_hyps = $a_hyps;
			foreach( $a_hyps as $a_hyp ) {
				$this->hyps[] = new BM_Press_Hyp( $a_hyp );
			}
		}
	}
	// receives a "non-associative" array of hyps
	// unordered on principle because all ordering will be clientside.
	public function set_hyps_from_a( $a_hyps = null ) {
		if( is_null( $a_hyps ) ) {
		} else {
			$this->a_hyps = $a_hyps;
			foreach( $a_hyps as $a_hyp ) {
				$this->hyps[] = new BM_Press_Hyp( $a_hyp );
			}
		}
	}
	// accepts either title or title and state
	public function add_str_hyp( $title, $state = null ) {
		$this->add_hyp( new BM_Press_Hyp( $title, $state ) );
	}
	public function add_hyp( $bm_hyp ) {
		array_push( $this->hyps, $bm_hyp );
	}
	public function add_several( $bm_hyps ) {
		// array_merge will overwrite values if key strings exist in $hyps
		array_merge( $this->hyps, $bm_hyps );
	}
	public function get_a() {
		return $this->a_hyps;
	}
	public function get_hyps() {
		return $this->hyps;
	}
}
///////////////////////////////////////////////             Hypothesis
/*	Three possible states of a hyp:
	"C" - Confirmed
	"U" - Unknown   <- this is default
	"D" - Denied
// if people jsontexts this themselves,
// both upper and lower letters should work
*/
	class BM_Press_Hyp {
	private $a_hyp;
/* __construct accepts either an array or a string or a string and a
second string as arguments. if an array is provided as the first
argument, it is an array of hyps and we want to set $a_hyp to it.
if we have two strings, the first one is the title, and the second
one is the state (either case, all will be made lowercase)
*/
	function __construct( $ahot = null, $state = null ) {
		// $ahot means a_hyp or title, it can be either
		if( is_null($ahot) && is_null($state) ) {
			// no arguments have been given, create empty object.
			$this->a_hyp = array(
				"state" => "U",
				"title" => null,
			);
		}
		// if an array has been given, the second argument doesn't matter
		else if( is_array($ahot) ) {
			$a = $ahot;
			if( !isset($a['state']) || !isset($a['title']) ) {
				// invalid hypothesis array provided
				$this->a_hyp = array(
					"state" => "U",
					"title" => "Invalid hypothesis (missing state and/or title) provided to BM_Press_Hyp constructor",
				);
			} else {
				$this->a_hyp = $a;
				$this->a_hyp["state"] = strtoupper( $this->a_hyp["state"] );
			}
		}

		// both arguments aren't null, and the first one isn't an array.
		// therefore the first argument is the title, and the optional second
		// argument is the state	
		else if( is_string($ahot) ) {
			if( is_null( $state ) ) $state = "U";
			$this->a_hyp = array(
				"state" => $state,
				"title" => $ahot,
			);
		} else {
			// neither a string nor an array was passed as the first
			// argument
			if( is_a( $ahot, "BM_Press_Hyp" ) ) {
				// a BM_Press_Hyp has been provided, create duplicate
				$this->a_hyp = $ahot->get_a;
			} else {
				// a non-valid first argument has been provided
				$this->a_hyp = array(
					"state" => "U",
					"title" => "Invalid first argument passed to BM_Press_Hyp constructor",
				);
			}
		}
	}
	public function get_a() {
		return $this->a_hyp;
	}
	public function set_title( $new_title ) {
		$this->a_hyp["title"] = $new_title;
	}
	public function set_state( $new_state ) {
		$this->a_hyp["state"] = strtoupper($new_state);
	}
	public function get_title() {
		return $this->a_hyp["title"];
	}
	public function get_state() {
		return $this->a_hyp["state"];
	}
}
