<?php
/**
 * Controller for configure the extension
 * @autor vutreras (victor.utreras@continuum.cl)
 *
 * dir models: /opt/bitnami/opencart/admin/
 */
class ControllerExtensionPaymentTransbankOnepay extends Controller {

    //constant for keys configurations
    const PAYMENT_TRANSBANK_ONEPAY_ENVIRONMENT = 'payment_transbank_onepay_environment';
    const PAYMENT_TRANSBANK_ONEPAY_APIKEY_TEST = 'payment_transbank_onepay_apikey_test';
    const PAYMENT_TRANSBANK_ONEPAY_SHARED_SECRET_TEST = 'payment_transbank_onepay_shared_secret_test';
    const PAYMENT_TRANSBANK_ONEPAY_APIKEY_LIVE = 'payment_transbank_onepay_apikey_live';
    const PAYMENT_TRANSBANK_ONEPAY_SHARED_SECRET_LIVE = 'payment_transbank_onepay_shared_secret_live';
    const PAYMENT_TRANSBANK_ONEPAY_LOGO_URL = 'payment_transbank_onepay_logo_url';
    const PAYMENT_TRANSBANK_ONEPAY_STATUS = 'payment_transbank_onepay_status';
    const PAYMENT_TRANSBANK_ONEPAY_SORT_ORDER = 'payment_transbank_onepay_sort_order';
    const PAYMENT_TRANSBANK_ONEPAY_ORDER_STATUS_ID_PAID = 'payment_transbank_onepay_order_status_id_paid';
    const PAYMENT_TRANSBANK_ONEPAY_ORDER_STATUS_ID_FAILED = 'payment_transbank_onepay_order_status_id_failed';
    const PAYMENT_TRANSBANK_ONEPAY_ORDER_STATUS_ID_REJECTED = 'payment_transbank_onepay_order_status_id_rejected';
    const PAYMENT_TRANSBANK_ONEPAY_ORDER_STATUS_ID_CANCELLED = 'payment_transbank_onepay_order_status_id_cancelled';
    const PAYMENT_TRANSBANK_ONEPAY_ORDER_STATUS_CONFIGURED = 'payment_transbank_onepay_order_status_configured';

    private $error = array();
    private $transbankSdkOnepay = null;

    private function loadResources() {
        $this->load->language('extension/payment/transbank_onepay');
        $this->load->model('setting/setting'); //load model in: $this->model_setting_setting
        $this->load->model('localisation/order_status'); //load model in: $this->model_localisation_order_status
    }

    private function getTransbankSdkOnepay() {
        $this->loadResources();
        if (!class_exists('TransbankSdkOnepay')) {
            $this->load->library('TransbankSdkOnepay');
        }
        return new TransbankSdkOnepay($this->config);
    }

	public function index() {

        $this->transbankSdkOnepay = $this->getTransbankSdkOnepay();

        if (($this->request->server['REQUEST_METHOD'] == 'GET') && isset($this->request->get['diagnostic_pdf'])) {
            $this->transbankSdkOnepay->createDiagnosticPdf();
            return;
        }

        //load all order status
		$data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {

            //create un array of order status original with order status configured by user, used for show it in pdf of diagnostic
            $dataPost = $this->request->post;
            $keyBase = 'payment_transbank_onepay_order_status_id_';
            $orderStatusValues = array();

            foreach ($dataPost as $key => $value) {
                if (strpos($key, $keyBase) !== false) {
                    $orderStatusNameOriginal = substr($key, strlen($keyBase), strlen($key));
                    $orderStatusNameConfiguredByUser = $this->getOrderStatusName($data['order_statuses'], $value);
                    array_push($orderStatusValues, $orderStatusNameOriginal . '(' . $value . ',' . $orderStatusNameConfiguredByUser . ')');
                }
            }

            //add PAYMENT_TRANSBANK_ONEPAY_ORDER_STATUS_CONFIGURED to dataPost to save in config system
            $dataPost[self::PAYMENT_TRANSBANK_ONEPAY_ORDER_STATUS_CONFIGURED] = implode(',', $orderStatusValues);

			$this->model_setting_setting->editSetting('payment_transbank_onepay', $dataPost);
            $this->session->data['success'] = $this->language->get('text_success');
            $data['msg_success'] = $this->session->data['success'];
            $this->cache->delete('payment_transbank_onepay');
            $this->transbankSdkOnepay->logInfo('Configuracion guardada correctamente');
        }

        $this->document->setTitle($this->language->get('heading_title'));

		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}

		if (isset($this->error[self::PAYMENT_TRANSBANK_ONEPAY_ENVIRONMENT])) {
			$data['error_transbank_onepay_environment'] = $this->error[self::PAYMENT_TRANSBANK_ONEPAY_ENVIRONMENT];
		} else {
			$data['error_transbank_onepay_environment'] = '';
		}

		if (isset($this->error[self::PAYMENT_TRANSBANK_ONEPAY_APIKEY_TEST])) {
			$data['error_transbank_onepay_apikey_test'] = $this->error[self::PAYMENT_TRANSBANK_ONEPAY_APIKEY_TEST];
		} else {
			$data['error_transbank_onepay_apikey_test'] = '';
		}

		if (isset($this->error[self::PAYMENT_TRANSBANK_ONEPAY_SHARED_SECRET_TEST])) {
			$data['error_transbank_onepay_shared_secret_test'] = $this->error[self::PAYMENT_TRANSBANK_ONEPAY_SHARED_SECRET_TEST];
		} else {
			$data['error_transbank_onepay_shared_secret_test'] = '';
        }

        if (isset($this->error[self::PAYMENT_TRANSBANK_ONEPAY_APIKEY_LIVE])) {
			$data['error_transbank_onepay_apikey_live'] = $this->error[self::PAYMENT_TRANSBANK_ONEPAY_APIKEY_LIVE];
		} else {
			$data['error_transbank_onepay_apikey_live'] = '';
		}

		if (isset($this->error[self::PAYMENT_TRANSBANK_ONEPAY_SHARED_SECRET_LIVE])) {
			$data['error_transbank_onepay_shared_secret_live'] = $this->error[self::PAYMENT_TRANSBANK_ONEPAY_SHARED_SECRET_LIVE];
		} else {
			$data['error_transbank_onepay_shared_secret_live'] = '';
		}

		if (isset($this->error[self::PAYMENT_TRANSBANK_ONEPAY_LOGO_URL])) {
			$data['error_transbank_onepay_logo_url'] = $this->error[self::PAYMENT_TRANSBANK_ONEPAY_LOGO_URL];
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

		if (isset($this->request->post[self::PAYMENT_TRANSBANK_ONEPAY_ENVIRONMENT])) {
			$data[self::PAYMENT_TRANSBANK_ONEPAY_ENVIRONMENT] = $this->request->post[self::PAYMENT_TRANSBANK_ONEPAY_ENVIRONMENT];
		} else {
			$data[self::PAYMENT_TRANSBANK_ONEPAY_ENVIRONMENT] = $this->config->get(self::PAYMENT_TRANSBANK_ONEPAY_ENVIRONMENT);
		}

		if (isset($this->request->post[self::PAYMENT_TRANSBANK_ONEPAY_APIKEY_TEST])) {
			$data[self::PAYMENT_TRANSBANK_ONEPAY_APIKEY_TEST] = $this->request->post[self::PAYMENT_TRANSBANK_ONEPAY_APIKEY_TEST];
		} else {
			$data[self::PAYMENT_TRANSBANK_ONEPAY_APIKEY_TEST] = $this->config->get(self::PAYMENT_TRANSBANK_ONEPAY_APIKEY_TEST);
		}

		if (isset($this->request->post[self::PAYMENT_TRANSBANK_ONEPAY_SHARED_SECRET_TEST])) {
			$data[self::PAYMENT_TRANSBANK_ONEPAY_SHARED_SECRET_TEST] = $this->request->post[self::PAYMENT_TRANSBANK_ONEPAY_SHARED_SECRET_TEST];
		} else {
			$data[self::PAYMENT_TRANSBANK_ONEPAY_SHARED_SECRET_TEST] = $this->config->get(self::PAYMENT_TRANSBANK_ONEPAY_SHARED_SECRET_TEST);
		}

        if (isset($this->request->post[self::PAYMENT_TRANSBANK_ONEPAY_APIKEY_LIVE])) {
			$data[self::PAYMENT_TRANSBANK_ONEPAY_APIKEY_LIVE] = $this->request->post[self::PAYMENT_TRANSBANK_ONEPAY_APIKEY_LIVE];
		} else {
			$data[self::PAYMENT_TRANSBANK_ONEPAY_APIKEY_LIVE] = $this->config->get(self::PAYMENT_TRANSBANK_ONEPAY_APIKEY_LIVE);
		}

		if (isset($this->request->post[self::PAYMENT_TRANSBANK_ONEPAY_SHARED_SECRET_LIVE])) {
			$data[self::PAYMENT_TRANSBANK_ONEPAY_SHARED_SECRET_LIVE] = $this->request->post[self::PAYMENT_TRANSBANK_ONEPAY_SHARED_SECRET_LIVE];
		} else {
			$data[self::PAYMENT_TRANSBANK_ONEPAY_SHARED_SECRET_LIVE] = $this->config->get(self::PAYMENT_TRANSBANK_ONEPAY_SHARED_SECRET_LIVE);
		}

        if (isset($this->request->post[self::PAYMENT_TRANSBANK_ONEPAY_LOGO_URL])) {
			$data[self::PAYMENT_TRANSBANK_ONEPAY_LOGO_URL] = $this->request->post[self::PAYMENT_TRANSBANK_ONEPAY_LOGO_URL];
		} else {
			$data[self::PAYMENT_TRANSBANK_ONEPAY_LOGO_URL] = $this->config->get(self::PAYMENT_TRANSBANK_ONEPAY_LOGO_URL);
		}

		if (isset($this->request->post[self::PAYMENT_TRANSBANK_ONEPAY_STATUS])) {
			$data[self::PAYMENT_TRANSBANK_ONEPAY_STATUS] = $this->request->post[self::PAYMENT_TRANSBANK_ONEPAY_STATUS];
		} else {
			$data[self::PAYMENT_TRANSBANK_ONEPAY_STATUS] = $this->config->get(self::PAYMENT_TRANSBANK_ONEPAY_STATUS);
        }

        if (isset($this->request->post[self::PAYMENT_TRANSBANK_ONEPAY_SORT_ORDER])) {
			$data[self::PAYMENT_TRANSBANK_ONEPAY_SORT_ORDER] = intval($this->request->post[self::PAYMENT_TRANSBANK_ONEPAY_SORT_ORDER]);
		} else {
			$data[self::PAYMENT_TRANSBANK_ONEPAY_SORT_ORDER] = intval($this->config->get(self::PAYMENT_TRANSBANK_ONEPAY_SORT_ORDER));
        }

        if (isset($this->request->post[self::PAYMENT_TRANSBANK_ONEPAY_ORDER_STATUS_ID_PAID])) {
			$data[self::PAYMENT_TRANSBANK_ONEPAY_ORDER_STATUS_ID_PAID] = $this->request->post[self::PAYMENT_TRANSBANK_ONEPAY_ORDER_STATUS_ID_PAID];
		} else {
			$data[self::PAYMENT_TRANSBANK_ONEPAY_ORDER_STATUS_ID_PAID] = $this->config->get(self::PAYMENT_TRANSBANK_ONEPAY_ORDER_STATUS_ID_PAID);
        }

        if (isset($this->request->post[self::PAYMENT_TRANSBANK_ONEPAY_ORDER_STATUS_ID_FAILED])) {
			$data[self::PAYMENT_TRANSBANK_ONEPAY_ORDER_STATUS_ID_FAILED] = $this->request->post[self::PAYMENT_TRANSBANK_ONEPAY_ORDER_STATUS_ID_FAILED];
		} else {
			$data[self::PAYMENT_TRANSBANK_ONEPAY_ORDER_STATUS_ID_FAILED] = $this->config->get(self::PAYMENT_TRANSBANK_ONEPAY_ORDER_STATUS_ID_FAILED);
        }

        if (isset($this->request->post[self::PAYMENT_TRANSBANK_ONEPAY_ORDER_STATUS_ID_REJECTED])) {
			$data[self::PAYMENT_TRANSBANK_ONEPAY_ORDER_STATUS_ID_REJECTED] = $this->request->post[self::PAYMENT_TRANSBANK_ONEPAY_ORDER_STATUS_ID_REJECTED];
		} else {
			$data[self::PAYMENT_TRANSBANK_ONEPAY_ORDER_STATUS_ID_REJECTED] = $this->config->get(self::PAYMENT_TRANSBANK_ONEPAY_ORDER_STATUS_ID_REJECTED);
        }

        if (isset($this->request->post[self::PAYMENT_TRANSBANK_ONEPAY_ORDER_STATUS_ID_CANCELLED])) {
			$data[self::PAYMENT_TRANSBANK_ONEPAY_ORDER_STATUS_ID_CANCELLED] = $this->request->post[self::PAYMENT_TRANSBANK_ONEPAY_ORDER_STATUS_ID_CANCELLED];
		} else {
			$data[self::PAYMENT_TRANSBANK_ONEPAY_ORDER_STATUS_ID_CANCELLED] = $this->config->get(self::PAYMENT_TRANSBANK_ONEPAY_ORDER_STATUS_ID_CANCELLED);
        }

        //if not seted PAYMENT_TRANSBANK_ONEPAY_ORDER_STATUS_ID_PAID, choose the default value for status processing
        if (intval($data[self::PAYMENT_TRANSBANK_ONEPAY_ORDER_STATUS_ID_PAID]) <= 0) {
            $data[self::PAYMENT_TRANSBANK_ONEPAY_ORDER_STATUS_ID_PAID] = $this->getOrderStatusId($data['order_statuses'], 'processing');
        }

        //if not seted PAYMENT_TRANSBANK_ONEPAY_ORDER_STATUS_ID_FAILED, choose the default value for status failed
        if (intval($data[self::PAYMENT_TRANSBANK_ONEPAY_ORDER_STATUS_ID_FAILED]) <= 0) {
            $data[self::PAYMENT_TRANSBANK_ONEPAY_ORDER_STATUS_ID_FAILED] = $this->getOrderStatusId($data['order_statuses'], 'failed');
        }

        //if not seted PAYMENT_TRANSBANK_ONEPAY_ORDER_STATUS_ID_REJECTED, choose the default value for status denied
        if (intval($data[self::PAYMENT_TRANSBANK_ONEPAY_ORDER_STATUS_ID_REJECTED]) <= 0) {
            $data[self::PAYMENT_TRANSBANK_ONEPAY_ORDER_STATUS_ID_REJECTED] = $this->getOrderStatusId($data['order_statuses'], 'denied');
        }

        //if not seted PAYMENT_TRANSBANK_ONEPAY_ORDER_STATUS_ID_CANCELLED, choose the default value for status canceled
        if (intval($data[self::PAYMENT_TRANSBANK_ONEPAY_ORDER_STATUS_ID_CANCELLED]) <= 0) {
            $data[self::PAYMENT_TRANSBANK_ONEPAY_ORDER_STATUS_ID_CANCELLED] = $this->getOrderStatusId($data['order_statuses'], 'canceled');
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

		if (!$this->request->post[self::PAYMENT_TRANSBANK_ONEPAY_ENVIRONMENT]) {
			$this->error[self::PAYMENT_TRANSBANK_ONEPAY_ENVIRONMENT] = $this->language->get('error_transbank_onepay_environment');
		}

		if (!$this->request->post[self::PAYMENT_TRANSBANK_ONEPAY_APIKEY_TEST]) {
			$this->error[self::PAYMENT_TRANSBANK_ONEPAY_APIKEY_TEST] = $this->language->get('error_transbank_onepay_apikey_test');
		}
		if (!$this->request->post[self::PAYMENT_TRANSBANK_ONEPAY_SHARED_SECRET_TEST]) {
			$this->error[self::PAYMENT_TRANSBANK_ONEPAY_SHARED_SECRET_TEST] = $this->language->get('error_transbank_onepay_shared_secret_test');
		}

		if (!$this->request->post[self::PAYMENT_TRANSBANK_ONEPAY_APIKEY_LIVE]) {
			$this->error[self::PAYMENT_TRANSBANK_ONEPAY_APIKEY_LIVE] = $this->language->get('error_transbank_onepay_apikey_live');
		}
		if (!$this->request->post[self::PAYMENT_TRANSBANK_ONEPAY_SHARED_SECRET_LIVE]) {
			$this->error[self::PAYMENT_TRANSBANK_ONEPAY_SHARED_SECRET_LIVE] = $this->language->get('error_transbank_onepay_shared_secret_live');
        }

		return !$this->error;
    }

    private function getOrderStatusId($orderStatuses, $statusName) {
        foreach ($orderStatuses as $orderStatus) {
            if (trim(strtolower($orderStatus['name'])) == $statusName) {
                return $orderStatus['order_status_id'];
            }
        }
        return 0;
    }

    private function getOrderStatusName($orderStatuses, $statusId) {
        foreach ($orderStatuses as $orderStatus) {
            if (intval($orderStatus['order_status_id']) == intval($statusId)) {
                return $orderStatus['name'];
            }
        }
        return '';
    }
}
?>
