<?php

require DIR_APPLICATION.'../admin/model/extension/smartbill_rest.php';

class ControllerExtensionModuleSmartbill extends Controller {

	public function install() {

		// Check version
		if (version_compare(VERSION , '3.0.0.0' , '<')) {
			die('You must have at least Open Cart 3.0.0.0 installed.');
		}

		// Alter order table
		$schema = $this->db->query("SHOW COLUMNS FROM `" . DB_PREFIX . "order`");
		if ( !empty($schema->rows) ) {
			if ( !$this->inFields('smartbill_document_url', $schema->rows) ) {
				$this->db->query("ALTER TABLE `" . DB_PREFIX . "order` ADD `smartbill_document_url` VARCHAR( 255 ) NULL ");
			}
			if ( !$this->inFields('smartbill_public_invoice', $schema->rows) ) {
				$this->db->query("ALTER TABLE `" . DB_PREFIX . "order` ADD `smartbill_public_invoice` longtext NULL ");
			}
			if ( !$this->inFields('smartbill_invoice_log', $schema->rows) ) {
				$this->db->query("ALTER TABLE `" . DB_PREFIX . "order` ADD `smartbill_invoice_log` longtext NULL ");
			}
		}
			$this->db->query("INSERT INTO `" . DB_PREFIX . "setting` ( `store_id`, `code`, `key`, `value`, `serialized`) VALUES
( 0, 'SMARTBILL', 'module_smartbill_status', '1', 0);");
		// Add user permissions
		$this->load->model('user/user_group');
		$user_groups = $this->model_user_user_group->getUserGroups();
		$admin_user_group_id = null;
		foreach ($user_groups as $user_group) {
			if ($user_group['name'] === 'Administrator') {
				$admin_user_group_id = $user_group['user_group_id'];
				break;
			}
		}
		if (!is_null($admin_user_group_id)) {
			$this->model_user_user_group->addPermission(
				$admin_user_group_id,
				"access",
				"module/smartbill"
			);
			$this->model_user_user_group->addPermission(
				$admin_user_group_id,
				"modify",
				"module/smartbill"
			);
			$this->model_user_user_group->addPermission(
				$admin_user_group_id,
				"access",
				"extension/smartbill_document"
			);
			$this->model_user_user_group->addPermission(
				$admin_user_group_id,
				"modify",
				"extension/smartbill_document"
			);
			$this->model_user_user_group->addPermission(
				$admin_user_group_id,
				"access",
				"extension/smartbill_settings"
			);
			$this->model_user_user_group->addPermission(
				$admin_user_group_id,
				"modify",
				"extension/smartbill_settings"
			);
			$this->model_user_user_group->addPermission(
				$admin_user_group_id,
				"access",
				"extension/smartbill_help"
			);
			$this->model_user_user_group->addPermission(
				$admin_user_group_id,
				"modify",
				"extension/smartbill_help"
			);
		}
	}

	private function inFields($field, $fields) {
		if ( is_array($fields) ) {
			foreach ($fields as $item) {
				if ( $field == $item['Field'] ) {
					return true;
				}
			}
		}

		return false;
	}

	// Show login screen
	public function index() {
		// Load language
		$this->load->language('extension/module/smartbill');

		// Load rest class and models
	    $this->load->model('setting/setting');
		$this->load->model('extension/smartbill');

        $this->model_extension_smartbill->validateSettingsValues();

        $this->_labels($data);
        $this->_breadcrumbs($data);
	    $this->document->setTitle($this->language->get('heading_title')); // Set the title of the page to the heading title in the Language file i.e., SmartBill

        if ( !empty($this->request->post['smartbill_login']) ) {
        	try {
        		$this->saveUser();
        	} catch (Exception $ex) {
        		$data['warning'] = $ex->getMessage();
        	}

			if ( (!isset($data['warning']) || empty($data['warning'])) && $this->model_extension_smartbill->isConnected() ) {
				$data['success'] = $this->language->get('success_connected');
			}
        }

        $data += $this->model_extension_smartbill->getSettings();

		$data['header'] = $this->load->controller('common/header');
		$data['module_version'] = SMRT_VERSION;
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');
		$this->response->setOutput($this->load->view('extension/module/smartbill_login', $data));
	}

	private function _labels(&$data) {
		// Load language
	    $this->load->language('extension/module/smartbill');

		$data['warning'] = '';
		$data['success'] = '';

	    $data['heading_title'] = $this->language->get('heading_title');

	    $data['button_login'] = $this->language->get('button_login');
	    $data['button_save'] = $this->language->get('button_save');
	    $data['button_cancel'] = $this->language->get('button_cancel');
	    $data['button_add_module'] = $this->language->get('button_add_module');
	    $data['button_remove'] = $this->language->get('button_remove');

	    $data['action'] = $this->url->link('extension/module/smartbill', 'user_token=' . $this->session->data['user_token'], 'SSL'); // URL to be directed when the save button is pressed
	    $data['cancel'] = $this->url->link('extension/module/smartbill', 'user_token=' . $this->session->data['user_token'], 'SSL'); // URL to be redirected when cancel button is pressed
	}

	private function _breadcrumbs(&$data) {
	    $data['breadcrumbs'] = array(
	    	array(
		        'text'      => $this->language->get('text_home'),
		        'href'      => $this->url->link('common/home', 'user_token=' . $this->session->data['user_token'], 'SSL'),
		        'separator' => false,
		    ),
		    array(
		        'text'      => $this->language->get('text_module'),
		        'href'      => $this->url->link('module/smartbill', 'user_token=' . $this->session->data['user_token'], 'SSL'),
		        'separator' => ' :: ',
		    ),
		    array(
		        'text'      => $this->language->get('heading_title'),
		        'href'      => $this->url->link('extension/module/smartbill', 'user_token=' . $this->session->data['user_token'], 'SSL'),
		        'separator' => ' :: ',
		    )
	    );
	}

    private function saveUser() {
		$this->model_extension_smartbill->saveFields(['SMARTBILL_USER', 'SMARTBILL_API_TOKEN', 'SMARTBILL_CIF']);

        if ( !empty($this->request->post[strtolower('SMARTBILL_CIF')]) ) {
        	try {
				$credentials = $this->model_extension_smartbill->getFields(['SMARTBILL_USER', 'SMARTBILL_API_TOKEN', 'SMARTBILL_CIF']);
        		$this->model_extension_smartbill->validateConnection($credentials);
        	} catch (Exception $ex) {
        		throw $ex;
        	}
        }
	}

}
