<?php
/**
 * Controller for configure the extension
 * @autor vutreras (victor.utreras@continuum.cl)
 *
 * dir models: /opt/bitnami/opencart/admin/
 */
class ControllerExtensionPaymentTransbankOnepay extends Controller {

    //constant for keys configurations
    const payment_transbank_onepay_environment = 'payment_transbank_onepay_environment';
    const payment_transbank_onepay_apikey_test = 'payment_transbank_onepay_apikey_test';
    const payment_transbank_onepay_shared_secret_test = 'payment_transbank_onepay_shared_secret_test';
    const payment_transbank_onepay_apikey_live = 'payment_transbank_onepay_apikey_live';
    const payment_transbank_onepay_shared_secret_live = 'payment_transbank_onepay_shared_secret_live';
    const payment_transbank_onepay_logo_url = 'payment_transbank_onepay_logo_url';
    const payment_transbank_onepay_status = 'payment_transbank_onepay_status';

	private $error = array();

	public function index() {

		$this->load->language('extension/payment/transbank_onepay');
		$this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('setting/setting');

        if (($this->request->server['REQUEST_METHOD'] == 'GET') && isset($this->request->get['diagnostic_pdf'])) {
            $this->createDiagnosticPdf();
            return;
        }

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
			$this->model_setting_setting->editSetting('payment_transbank_onepay', $this->request->post);
            $this->session->data['success'] = $this->language->get('text_success');
            $data['msg_success'] = $this->session->data['success'];
            $this->cache->delete('payment_transbank_onepay');
            //this line return to market/extension page on after save config
            //$this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true));
            //return;
        }

		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}

		if (isset($this->error[self::payment_transbank_onepay_environment])) {
			$data['error_transbank_onepay_environment'] = $this->error[self::payment_transbank_onepay_environment];
		} else {
			$data['error_transbank_onepay_environment'] = '';
		}

		if (isset($this->error[self::payment_transbank_onepay_apikey_test])) {
			$data['error_transbank_onepay_apikey_test'] = $this->error[self::payment_transbank_onepay_apikey_test];
		} else {
			$data['error_transbank_onepay_apikey_test'] = '';
		}

		if (isset($this->error[self::payment_transbank_onepay_shared_secret_test])) {
			$data['error_transbank_onepay_shared_secret_test'] = $this->error[self::payment_transbank_onepay_shared_secret_test];
		} else {
			$data['error_transbank_onepay_shared_secret_test'] = '';
        }

        if (isset($this->error[self::payment_transbank_onepay_apikey_live])) {
			$data['error_transbank_onepay_apikey_live'] = $this->error[self::payment_transbank_onepay_apikey_live];
		} else {
			$data['error_transbank_onepay_apikey_live'] = '';
		}

		if (isset($this->error[self::payment_transbank_onepay_shared_secret_live])) {
			$data['error_transbank_onepay_shared_secret_live'] = $this->error[self::payment_transbank_onepay_shared_secret_live];
		} else {
			$data['error_transbank_onepay_shared_secret_live'] = '';
		}

		if (isset($this->error[self::payment_transbank_onepay_logo_url])) {
			$data['error_transbank_onepay_logo_url'] = $this->error[self::payment_transbank_onepay_logo_url];
		} else {
			$data['error_transbank_onepay_logo_url'] = '';
        }

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_extension'),
			'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('extension/payment/transbank_onepay', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['action'] = $this->url->link('extension/payment/transbank_onepay', 'user_token=' . $this->session->data['user_token'], true);

		$data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true);

        $data['action_diagnostic_pdf'] = $this->url->link('extension/payment/transbank_onepay', 'user_token=' . $this->session->data['user_token'] . '&diagnostic_pdf=true', true);

		if (isset($this->request->post[self::payment_transbank_onepay_environment])) {
			$data[self::payment_transbank_onepay_environment] = $this->request->post[self::payment_transbank_onepay_environment];
		} else {
			$data[self::payment_transbank_onepay_environment] = $this->config->get(self::payment_transbank_onepay_environment);
		}

		if (isset($this->request->post[self::payment_transbank_onepay_apikey_test])) {
			$data[self::payment_transbank_onepay_apikey_test] = $this->request->post[self::payment_transbank_onepay_apikey_test];
		} else {
			$data[self::payment_transbank_onepay_apikey_test] = $this->config->get(self::payment_transbank_onepay_apikey_test);
		}

		if (isset($this->request->post[self::payment_transbank_onepay_shared_secret_test])) {
			$data[self::payment_transbank_onepay_shared_secret_test] = $this->request->post[self::payment_transbank_onepay_shared_secret_test];
		} else {
			$data[self::payment_transbank_onepay_shared_secret_test] = $this->config->get(self::payment_transbank_onepay_shared_secret_test);
		}

        if (isset($this->request->post[self::payment_transbank_onepay_apikey_live])) {
			$data[self::payment_transbank_onepay_apikey_live] = $this->request->post[self::payment_transbank_onepay_apikey_live];
		} else {
			$data[self::payment_transbank_onepay_apikey_live] = $this->config->get(self::payment_transbank_onepay_apikey_live);
		}

		if (isset($this->request->post[self::payment_transbank_onepay_shared_secret_live])) {
			$data[self::payment_transbank_onepay_shared_secret_live] = $this->request->post[self::payment_transbank_onepay_shared_secret_live];
		} else {
			$data[self::payment_transbank_onepay_shared_secret_live] = $this->config->get(self::payment_transbank_onepay_shared_secret_live);
		}

        if (isset($this->request->post[self::payment_transbank_onepay_logo_url])) {
			$data[self::payment_transbank_onepay_logo_url] = $this->request->post[self::payment_transbank_onepay_logo_url];
		} else {
			$data[self::payment_transbank_onepay_logo_url] = $this->config->get(self::payment_transbank_onepay_logo_url);
		}

		if (isset($this->request->post[self::payment_transbank_onepay_status])) {
			$data[self::payment_transbank_onepay_status] = $this->request->post[self::payment_transbank_onepay_status];
		} else {
			$data[self::payment_transbank_onepay_status] = $this->config->get(self::payment_transbank_onepay_status);
		}

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/payment/transbank_onepay', $data));
    }

	protected function validate() {

		if (!$this->user->hasPermission('modify', 'extension/payment/transbank_onepay')) {
			$this->error['warning'] = $this->language->get('error_permission');
        }

		if (!$this->request->post[self::payment_transbank_onepay_environment]) {
			$this->error[self::payment_transbank_onepay_environment] = $this->language->get('error_transbank_onepay_environment');
		}

		if (!$this->request->post[self::payment_transbank_onepay_apikey_test]) {
			$this->error[self::payment_transbank_onepay_apikey_test] = $this->language->get('error_transbank_onepay_apikey_test');
		}
		if (!$this->request->post[self::payment_transbank_onepay_shared_secret_test]) {
			$this->error[self::payment_transbank_onepay_shared_secret_test] = $this->language->get('error_transbank_onepay_shared_secret_test');
		}

		if (!$this->request->post[self::payment_transbank_onepay_apikey_live]) {
			$this->error[self::payment_transbank_onepay_apikey_live] = $this->language->get('error_transbank_onepay_apikey_live');
		}
		if (!$this->request->post[self::payment_transbank_onepay_shared_secret_live]) {
			$this->error[self::payment_transbank_onepay_shared_secret_live] = $this->language->get('error_transbank_onepay_shared_secret_live');
        }

		return !$this->error;
    }

    private function createDiagnosticPdf() {
        if (!class_exists('TransbanksdkOnepay')) {
            $this->load->library('TransbanksdkOnepay');
        }
        $to = new TransbanksdkOnepay();
        $to->init($this->config);
        $to->createDiagnosticPdf();
    }
}
?>
