<?php
class BM_Press_Events {
	public $tablename = null;

	function __construct() {
		global $wpdb;
		$this->tablename = $wpdb->prefix . "bm_press_models";
		add_action('wp_ajax_bm_press_create_database_table', array($this, 'create_database_table') );
		add_action('wp_ajax_bm_press_delete_database_table', array($this, 'delete_database_table') );
		add_action('wp_ajax_bm_press_create_new_model', array($this, 'create_new_model') );
		add_action('wp_ajax_bm_press_save_model', array($this, 'save_model') );
		add_action('wp_ajax_bm_press_load_model', array($this, 'load_model_jsob') );
		add_action('wp_ajax_bm_press_delete_model', array($this, 'delete_model') );
		add_action('wp_ajax_bm_press_model_list', array($this, 'model_list') );
		add_action('wp_ajax_bm_press_iterate', array($this, 'iterate') );
		// for when admin is in view-mode:
		add_action('wp_ajax_bm_press_load_full_model', array($this, 'load_full_model') );
		// for regular viewers
		/* actually shouldn't be necessary once we start doing progressive enhancement */
		add_action( 'wp_ajax_nopriv_bm_press_load_full_model', array($this, 'load_full_model') );
	}

	public function create_database_table() {
		$sql = "CREATE TABLE ".$this->tablename." (
		  id VARCHAR(8) NOT NULL,
		  designed_for VARCHAR(140),
		  designed_by BIGINT(20),
		  iteration INT,
		  time_created VARCHAR(20),
		  time_saved VARCHAR(20),
		  jsob text NOT NULL,
		  UNIQUE KEY (id)
		);";
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $sql );
		die();
	}
	public function delete_database_table() {
		global $wpdb;

		$wpdb->query("DROP TABLE IF EXISTS ".$this->tablename.";");
		die();
	}
	public function model_list() {
		global $wpdb;
		global $user_ID, $user_identity;
		get_currentuserinfo(); // fills user_ID and user_identity
		//echo "current user id is ".$user_ID;
		// determine if table exists, create if it does not
		if($wpdb->get_var("SHOW TABLES LIKE '".$this->tablename."'") != $this->tablename) {
			// this one appearently does not work.
			echo "[]";
			$this->create_database_table( true );
			return;
		}
		// get models designed by current user
		// don't include designed_by in selection because
		// we will not return the users id, but the users name.
		$entries = $wpdb->get_results(
			"SELECT id, designed_for, iteration, time_created, time_saved
			FROM ".$this->tablename."
			WHERE designed_by = '$user_ID'
			ORDER BY time_created DESC"
		);

		if( $entries === false )
			echo '[{"designed_for":"Attempted SQL read caused an error."}]';
		else if( empty( $entries ) )
			echo "[]";
		else {
			// return users name as designer instead of user id
			foreach( $entries as $index => $entry ) {
				$entries[$index]->designed_by = $user_identity;
			}
			echo json_encode( $entries );
		}
		die(); // to prevent further sending of content from server
		return;
	}
	public function create_new_model() {
		global $bm_press;
		$model = new BM_Press_Model();
		// check to see if id is in database. if it is, create a new
		// model, until we have a id that does not exist.
		echo $model->get_presentable();
		die();
	}
	public function save_model() {
		global $wpdb, $bm_press;
		global $user_ID, $user_identity;
		get_currentuserinfo(); // fills user_ID and user_identity
		if( !isset( $_POST['data'] )) {
			echo '{"designed_for":"Model save request without model data provided."}';
			die();
			return;
		}

		$model = json_decode( stripslashes($_POST["data"]) , true );
		if( !isset( $model['id'] ) ) {
			echo '{"designed_for":"Model save request without model ID provided. Object sent to PHP was '.print_r($model).'"}';
			die();
			return;
		}

		// check if row with id exists
		// if it does, update if user is owner
		// if it does not, insert row with user as owner
		$vals_returned = $wpdb->get_col( $wpdb->prepare(
			"SELECT designed_by FROM ".$this->tablename." WHERE id = '%s'",
			$model['id']
		) );

		// $model['designed_by'] contains the username of $user_ID
		// SQL designed_by contains the id number of the user from wp_users
		// $user_identity is the name, $user_ID is the number

		if( !( count( $vals_returned ) === 0 )) {
			// model with id exists. only update if correct user
			
			if( $vals_returned[0] == $user_ID ) {
				$rows_affected = $wpdb->update(
					$this->tablename,
					array(
						'designed_for' => $model['designed_for'],
						'time_saved' => date("Y m d H:i:s"),
						'jsob' => json_encode( $model['jsob'] ),
					),
					array( 'id' => $model['id'] )
				);
				if( $rows_affected === false )
					echo '{"designed_for":"Attempted SQL update caused an error."}';
				else if ( $rows_affected == 0 )
					echo '{"designed_for":"SQL caused updates in 0 rows."}';
				else
					echo '{"id":"'.$model['id'].'"}';
				die();
				return;
			} else {
				echo '{"designed_for":"Since you did not design this model you cannot update it. If you iterate it, a new version will be created with you as the designer."}';
				die();
				return;
			}
		} else { // there are no models with the given id in database.
			// therefore insert a new model.
			$is_successful = $wpdb->insert( $this->tablename,
				array( 'id' => $model['id'],
					'designed_for' => $model['designed_for'],
					'designed_by' => $user_ID,
					'iteration' => $model['iteration'],
					'time_created' => date("Y m d H:i:s"),
					'time_saved' => date("Y m d H:i:s"),
					'jsob' => json_encode( $model['jsob'] ),
				)
			);
			if( $is_successful )
				echo '{"id":"'.$model['id'].'"}';
			else
				echo '{"designed_for":"SQL would not insert model ('.$model['id'].')."}';
			die();
			return;
		}
	}
	public function load_model_jsob() {
		// read from models table, find the row with the key $id
		// and return whatever's there. user is asking for a read.
		global $wpdb;
		global $user_ID, $user_identity;
		get_currentuserinfo(); // fills user_ID and user_identity

		if( !isset( $_POST['id'] ) ) {
			echo '{"designed_for":"Model load request without model ID provided."}';
			die();
			return;
		}

		/*
		willfully not with AND designed_by = '$user_ID', by design we are public
		*/
		$vals_returned = $wpdb->get_col( $wpdb->prepare(
			"SELECT jsob FROM ".$this->tablename." WHERE id = '%s'",
			$_POST['id']
		) );

		# print_r( $vals_returned ); # ok så den bei ikkje lagra. #MMM

		if( $vals_returned === false )
			echo '{"designed_for":"Attempted SQL read caused an error."}';
		else if ( count( $vals_returned ) === 0 )
			echo '{"designed_for":"SQL couldn\'t find model ('.$_POST['id'].')."}';
		else
			echo '{"jsob":'.$vals_returned[0].'}';
		die();
	}
	public function load_full_model() {
		// read from models table, find the row with the key $id
		// and return whatever's there. user is asking for a read.
		global $wpdb;
		
		if( !isset( $_POST['id'] ) ) {
			echo '{"designed_for":"Model load request without model ID provided."}';
			die();
			return;
		}
		$id = $_POST['id'];

		$entries = $wpdb->get_results( $wpdb->prepare(
			"SELECT * FROM ".$this->tablename." WHERE id = '%s'",
			$id
		) );

		if( !$entries ) {
			echo '{"designed_for":"SQL couldn\'t find model ('.$_POST['id'].')."}';
			die();
			return;
		} else {
			$user_info = get_userdata( $entries[0]->designed_by );
			$entries[0]->designed_by = $user_info ?
				$user_info->display_name :
				"User who had ID ". $entries[0]->designed_by;
			/* don't do json_encode on jsob because that causes the dbdata to be encoded as a string */
			$jsob = $entries[0]->jsob;
			unset($entries[0]->jsob);
			$str = json_encode( $entries[0] );
			// remove final } in object and attach jsob.
			$str = substr( $str, 0, -1 ) . ',"jsob":'.$jsob.'}';
			echo $str;
			die();
			return;
		}
	}
	public function delete_model() {
		global $wpdb;
		global $user_ID, $user_identity;
		get_currentuserinfo(); // fills user_ID and user_identity
		if( !isset( $_POST['id'] ) ) {
			echo '{"designed_for":"Model delete request without model ID provided."}';
			die();
			return;
		}
		$id = $_POST['id'];
		$num_rows_affected = $wpdb->query( 
			$wpdb->prepare(
				"DELETE FROM ".$this->tablename."
				WHERE id = '%s' AND designed_by = '$user_ID'",
				$id
			)
		);
		if( $num_rows_affected === false )
			echo '{"designed_for":"Attempted SQL deletion caused an error."}';
		else if ( $num_rows_affected == 0 )
			echo '{"designed_for":"SQL couldn\'t delete model ('.$_POST['id'].')."}';
		else
			echo '{"designed_for":"Deletion of model ('.$_POST['id'].') successful."}';
		die();
	}
	public function iterate() {
		global $wpdb, $bm_press;

		if( !isset( $_POST['id'] ) ) {
			echo '{"designed_for":"Model iteration request without model ID provided."}';
			die();
			return;
		}
		$id = $_POST['id'];
		$myrow = $wpdb->get_results( $wpdb->prepare(
			"SELECT * FROM ".$this->tablename." WHERE id = '%s'",
			$id
		) );

		if( !$myrow ) {
			echo '{"designed_for":"SQL couldn\'t find model ('.$_POST['id'].')."}';
			die();
			return;
		} else {
			// todo: check if user is current user, if not, change inspired_by-fields.
			$model = $myrow[0];
			$model->id = $bm_press->d->generate_id();
			$model->iteration++;
			$insertion_array = array( 'id' => $model->id,
				'designed_for' => $model->designed_for,
				'designed_by' => $model->designed_by,
				'iteration' => $model->iteration,
				'time_created' => date("Y m d H:i:s"),
				'time_saved' => date("Y m d H:i:s"),
				'jsob' => $model->jsob,
			);
			$rows_affected = $wpdb->insert( $this->tablename,
				$insertion_array
			);
			unset( $insertion_array['jsob'] );
			$str = json_encode( $insertion_array );
			// remove final } in object and attach jsob.
			$str = substr( $str, 0, -1 ) . ',"jsob":'.$myrow[0]->jsob.'}';
			echo $str;
		}
		die();
	}
}
