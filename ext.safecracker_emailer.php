<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Safecracker_emailer_ext {

	var $name       	= 'Safecracker Emailer';
	var $version        = '1.0';
	var $description    = 'Sends an email when an entry is updated. {short_tag} support for all settings.';
	var $settings_exist = 'y';
	var $docs_url       = '';

	var $settings       = array();

	function __construct($settings='') {
		$this->EE =& get_instance();
		$this->settings = $settings;
	}

	function activate_extension() {
		$data = array(
			'class'     => __CLASS__,
			'method'    => 'send_email',
			'hook'      => 'safecracker_submit_entry_end', //Use the hook following submission of data, to check if it was successful
			'settings'  => serialize($this->settings),
			'priority'  => 10,
			'version'   => $this->version,
			'enabled'   => 'y'
		);

		$this->EE->db->insert('extensions', $data);
	}

	function disable_extension() {
		$this->EE->db->where('class', __CLASS__);
		$this->EE->db->delete('extensions');
	}

	function settings() {
		$settings = array();
		$settings['channel_name'] = array('i', '', 'channel_name');
		$settings['email_address_from'] = array('i', '', '{email}');
		$settings['name_from'] = array('i', '', '{first_name} {last_name}');
		$settings['email_address_to'] = array('i', '', 'example@email.com');
		$settings['cc'] = array('i', '', '');
		$settings['bcc'] = array('i', '', '');
		$settings['subject'] = array('i', '', '');
		$settings['message'] = array('t', '', 'This is a test.');
		return $settings;
	}

	function send_email($data) {
		$channel_name = $this->parse_value('channel_name', $data);
		if (count($data->field_errors) == 0 && count($data->errors) == 0 && ($data->channel['channel_name'] == $channel_name || $channel_name == '')) {
			$email_address_from = $this->parse_value('email_address_from', $data);
			$name_from 			= $this->parse_value('name_from', $data);
			$email_address_to 	= $this->parse_value('email_address_to', $data);
			$name_to 			= $this->parse_value('name_to', $data);
			$cc 				= $this->parse_value('cc', $data);
			$bcc 				= $this->parse_value('bcc', $data);
			$subject 			= $this->parse_value('subject', $data);
			$message 			= $this->parse_value('message', $data);

			$this->EE->load->library('email');
			$this->EE->email->from($email_address_from, $name_from);
			$this->EE->email->to($email_address_to);
			$this->EE->email->cc($cc);
			$this->EE->email->bcc($bcc);
			$this->EE->email->subject($subject);
			$this->EE->email->message($message);

			$this->EE->email->send();
			/*
			//To output debugging info, use:
			$file = '/path/to/file.txt';
			$debug = $this->EE->email->print_debugger();
			file_put_contents($file, $debug, FILE_APPEND | LOCK_EX);
			*/
		}
	}

	function parse_value($item, $data) {
		if (isset($this->settings[$item])) {
			$item = $this->settings[$item];
			preg_match_all('/\{([A-Za-z_0-9-]*)\}/', $item, $output); //simple match for a {short_name} field
			if (count($output[0]) > 0) { //if {short_name} tags are detected, check the data object for either channel (e.g. channel_name) or entry (e.g. custom_field) arrays for matching keys
				foreach ($output[1] as $values) {
					$item = (isset($data->entry[$values])) ? str_replace('{'.$values.'}', $data->entry[$values], $item) : (isset($data->channel[$values])) ? str_replace('{'.$values.'}', $data->channel[$values], $item) : $item; //It works. Trust me, I am science
				}
			}
			return $item;
		}
	}
}

/* End of file ext.safecracker_emailer.php */
/* Location: ./control-panel/expressionengine/third_party/safecracker_emailer/ext.safecracker_emailer.php */