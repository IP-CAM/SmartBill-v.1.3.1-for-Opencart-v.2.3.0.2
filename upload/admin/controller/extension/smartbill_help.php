<?php

class ControllerExtensionSmartbillHelp extends Controller {
	public function index() {   // Default function
        $this->_labels($data);
        $this->_breadcrumbs($data);
	    $this->document->setTitle($this->language->get('heading_title')); // Set the title of the page to the heading title in the Language file i.e., SmartBill

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');
		$this->response->setOutput($this->load->view('extension/module/smartbill_help', $data));
	}

	private function _labels(&$data) {
	    $this->load->language('extension/module/smartbill'); // Loading the language file of smartbill
	    $data['heading_title'] = $this->language->get('heading_title');
	}

	private function _breadcrumbs(&$data) {
	    $data['breadcrumbs'] = array(
            array(
                'text'      => $this->language->get('text_home'),
                'href'      => $this->url->link('common/home', 'user_token=' . $this->session->data['user_token'], 'SSL'),
                'separator' => false
            ),
            array(
                'text'      => $this->language->get('text_module'),
                'href'      => $this->url->link('module/smartbill', 'user_token=' . $this->session->data['user_token'], 'SSL'),
                'separator' => ' :: '
            ),
            array(
                'text'      => $this->language->get('heading_title'),
                'href'      => $this->url->link('smartbill/help', 'user_token=' . $this->session->data['user_token'], 'SSL'),
                'separator' => ' :: '
            ),
        );
	}
}
