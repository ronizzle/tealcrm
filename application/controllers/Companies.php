<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
// -----------------------------------------------------------------------
/**
 * Companies Controller
 *
 * @package SecretCRM
 * @subpackage Controller
 * @author SecretCRM Team
 * @since 1.0
 * @version 1.0
 */
class Companies extends App_Controller {

	/**
	 * construct
	 *
	 * @param void
	 */
	function __construct()
	{
		// call parent
		parent::__construct("Company","companies","company");
	}




	/**
	 * View existing
	 *
	 * @param varchar $company_id
	 * @return void
	 */
	public function view( $company_id ){

		$CI =& get_instance();
		$CI->teal_global_vars->set_all_global_vars();

		// data
		$data = array();

		// init
		$acct = new Company();

		// find
		$acct->where('company_id', $company_id)->get();

		// check
		if( isset($acct->company_id) && $acct->deleted!=0){
			// set flash
			notify_set( array('status'=>'error', 'message'=>sprintf('Record does not exist anymore.') ) );

			// redirect, don't continue the code
			redirect( 'companies' );
		}
		else if( ! isset($acct->company_id) ){
				// set flash
				notify_set( array('status'=>'error', 'message'=>sprintf('Record does not exist.') ) );

				// redirect, don't continue the code
				redirect( 'companies' );
			}

		// set
		$data['company'] = $acct;

		//fetch activity feed list
		$this->load->model("feed_list");

		//getFeedList($company_id, $category)
		$data['feed_list'] = $this->feed_list->getFeedList($company_id,1);

		// 8-25-14 - Arthur

		//*** CONTACTS ***/
		// Get 5 Related Contacts to this Company
		$related_people = new Person();
		$related_people->limit(10);
		$related_people->where('deleted',0);
		$related_people->where('company_id', $company_id)->get();
		$data['related_people'] = $related_people;

		// Get Total Related Contacts
		$query = $this->db->query("SELECT * FROM sc_people WHERE company_id='".$company_id."' and deleted=0");
		$data['rc_rows'] = $query->num_rows();

		//*** Tasks ***/
		// Get 5 Related Tasks to this Company
		$related_tasks = new Task();
		$related_tasks->limit(10);
		$related_tasks->where('deleted',0);
		$related_tasks->where('company_id', $company_id)->get();
		$data['related_tasks'] = $related_tasks;

		// Get Total Related Tasks
		$query = $this->db->query("SELECT * FROM sc_tasks WHERE company_id='".$company_id."' and deleted=0");
		$data['rt_rows'] = $query->num_rows();


		//*** Deals ***/
		// Get 5 Related Deals to this Company
		$related_deals = new Deal();
		$related_deals->limit(10);
		$related_deals->where('deleted',0);
		$related_deals->where('company_id', $company_id)->get();
		$data['related_deals'] = $related_deals;

	
		// Get Total Related Tasks
		$query = $this->db->query("SELECT * FROM sc_deals WHERE company_id='".$company_id."' and deleted=0");
		$data['rd_rows'] = $query->num_rows();






		//*** Notes ***/

		// Get 5 Related Notes to this Company
		$related_notes = new Note();
		$related_notes->limit(10);
		$related_notes->where('deleted',0);
		$related_notes->where('company_id', $company_id)->get();
		$data['related_notes'] = $related_notes;

		// Get Total Related Notes
		$query = $this->db->query("SELECT * FROM sc_notes WHERE company_id='".$company_id."' and deleted=0");
		$data['rn_rows'] = $query->num_rows();

		//*** Meetings ***/

		// Get 5 Related Meetings to this Company
		$related_meetings = new Meeting();
		$related_meetings->limit(10);
		$related_meetings->where('deleted',0);
		$related_meetings->where('company_id', $company_id)->get();
		$data['related_meetings'] = $related_meetings;

		// Get Total Related Meetings
		$query = $this->db->query("SELECT * FROM sc_meetings WHERE company_id='".$company_id."' and deleted=0");
		$data['rm_rows'] = $query->num_rows();

		// GET 5 RELATED MAILS TO THIS COMPANY

		$this->db->select('message_id,subject,message,from_name,from_email,timestamp,category,status,relationship_id');
		$this->db->from('sc_messages');
		$this->db->where('relationship_id',$company_id);
		$this->db->limit(10);
		$query = $this->db->get();

		$related_mail = $query->result();
		$data['related_mail'] = $related_mail;

		// End 8-25-14 Arthur

		//custom field
		$check_value = 0;
		$check_field = 0;
		if (isset($_SESSION['custom_field']['118']))
		{
			$data['more_info'] = 1;
			$custom_field_values = $_SESSION['custom_field']['118'];
			$data['custom_field_values'] = $custom_field_values;
			foreach($custom_field_values as $custom)
			{
				$check_field++;
				$custom_query = $this->db->query("SELECT * FROM sc_custom_fields_data WHERE companies_id ='".$company_id."' and custom_fields_id = '".$custom['cf_id']."'")->result();



				if(array_key_exists(0,$custom_query))
				{
					$data[$custom['cf_name']] = $custom_query[0]->data_value;
				}
				else
				{
					$data[$custom['cf_name']] = " ";
					$check_value++;
				}

			}
			$data['is_custom_fields'] = 1;
		}
		else
		{
			$data['is_custom_fields'] = 0;
		}

		if($check_value == $check_field)
		{
			$data['more_info'] = 0;
		}

		//custom field

		// set last viewed
		//update_last_viewed($company_id, 1, $acct->company_name);

		// load view
		$this->layout->view('/companies/view', $data);

	}

	/**
	 * Delete
	 *
	 * @param void
	 * @return void
	 */
	public function delete( $company_id ){
		// check
		if( isset($company_id) ){
			// init
			$acct = new Company();
			// find
			$acct->where('company_id', $company_id)->get();

			// soft_delete(array(fields=>values):where clause)
			if( $acct->soft_delete(array("company_id"=>$company_id)) ){
				// set flash
				notify_set( array('status'=>'success', 'message'=>'Successfully deleted company.') );
			}else{
				// set flash
				notify_set( array('status'=>'error', 'message'=>'Company delete failed.') );
			}
		}

		// redirect
		redirect( 'companies' );
	}

	/**
	 * Delete all
	 *
	 * @param void
	 * @return void
	 */
	public function delete_all( ){
		// post
		$post = $this->input->post(null, true);
		// check
		if( isset($post['ids']) && ! empty($post['ids']) ){
			// ids
			$ids = $post['ids'];

			// init
			$accts = new Company();

			// find in
			$accts->where_in('company_id', $ids)->get();

			// init
			$deleted = 0;
			// loop
			foreach ($accts->all as $acct)
			{
				// delete
				if( $acct->soft_delete(array("company_id"=>$post['ids'][$deleted])) ){
					$deleted++;
				}
			}

			// message
			if( $deleted ){
				// set flash
				notify_set( array('status'=>'success', 'message'=>sprintf('Successfully deleted %d company(s).', $deleted) ) );
			}else{
				// set flash
				notify_set( array('status'=>'error', 'message'=>'Company delete failed.') );
			}
		}

		// redirect
		redirect( 'companies' );
	}


	/**
	 * Export CSV File
	 *
	 * @param void
	 * @return void
	 */
	public function export()
	{
		$companies = new Company();

		$companies->select('company_name,email1,company_id,date_entered,company_type,city');


		// show newest first
		$companies->order_by('date_entered', 'DESC');

		// show non-deleted
		$companies->group_start()
		->where('deleted','0')
		->group_end();

		$companies->where("deleted", 0);
		if(!empty($_SESSION['search']['companies'])){

			$companies->group_start();

			foreach($_SESSION['search']['companies'] as $key => $value){
				if($key != "search_type" && $key != "date_entered_start" && $key != "date_entered_end" && $key != "date_modified_start" && $key != "date_modified_end"){
					$companies->like($key, $value);
				}

				if($key == "date_entered_start" || $key == "date_entered_end" || $key == "date_modified_start" || $key == "date_modified_end"){

					switch($key){
						case'date_entered_start':$companies->where('date_entered >=', gmdate('Y-m-d 00:00:00', strtotime($value)));break;
						case'date_entered_end':$companies->where('date_entered <=', gmdate('Y-m-d 23:59:59', strtotime($value)));break;
						case'date_modified_start':$companies->where('date_modified >=', gmdate('Y-m-d 00:00:00', strtotime($value)));break;
						case'date_modified_end':$companies->where('date_modified <=', gmdate('Y-m-d 23:59:59', strtotime($value)));break;
					}
					
				}

			}

			// set display settings
			if(isset($_SESSION['search']['companies']['search_type'])){
				if($_SESSION['search']['companies']['search_type'] == "adv_search_go"){
					$search_tab = "advanced";
				}
				elseif($_SESSION['search']['companies']['search_type'] == "saved"){
					$search_tab = "saved";
					$data['search_id'] = '';
				}
			}

			$companies->group_end();

		}

		// check for session variables related to search
		$this->index(TRUE);
		// run export

		// load all users
		$companies->get();
		// Output $u->all to /tmp/output.csv, using all database fields.
		$companies->csv_export('../attachments/Companies.csv');

		$this->load->helper('download_helper');
		force_download('Companies.csv', '../attachments/Companies.csv');

	}

	public function export_all()
	{
		$post = $this->input->post(null, true);
		// check
		if( isset($post['ids']) && ! empty($post['ids']) ){
			// ids
			$ids = $post['ids'];

			// init
			$accts = new Company();

			// find in
			$accts->where_in('company_id', $ids)->get();

			$accts->csv_export('../attachments/'.$_SERVER['HTTP_HOST'].'/Companies.csv');

			$this->load->helper('download_helper');
			force_download('Companies.csv', '../attachments/'.$_SERVER['HTTP_HOST'].'/Companies.csv');
		}
		redirect( 'companies' );
	}
}

/* End of file companies.php */
/* Location: ./application/controllers/companies.php */