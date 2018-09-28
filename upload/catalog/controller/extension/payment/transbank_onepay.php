<?php
/**
 * Controller for handle the checkout
 * @autor vutreras (victor.utreras@continuum.cl)
 *
 */
class ControllerExtensionPaymentTransbankOnepay extends Controller {

    private $error = array();
    private $transbankSdkOnepay = null;

    private function loadResources() {
        $this->load->language('extension/payment/transbank_onepay');
        $this->load->model('setting/setting');
        $this->load->model('checkout/order');
    }

    private function getTransbankSdkOnepay() {
        $this->loadResources();
        if (!class_exists('TransbankSdkOnepay')) {
            $this->load->library('TransbankSdkOnepay');
        }
        $to = new TransbankSdkOnepay();
        $to->init($this->config);
        return $to;
    }

	public function index() {

		$this->transbankSdkOnepay = $this->getTransbankSdkOnepay();
        $this->transbankSdkOnepay->logInfo('Controller catalog cargado');

        $data['action_create'] = $this->url->link('extension/payment/transbank_onepay/createTransaction', '', true);
        $data['action_commit'] = $this->url->link('extension/payment/transbank_onepay/commitTransaction', '', true);
        $data['logo_url'] = $this->transbankSdkOnepay->getLogoUrl();

        return $this->load->view('extension/payment/transbank_onepay', $data);
    }

    public function createTransaction() {

        $channel = isset($this->request->post['channel']) ? $this->request->post['channel'] : null;

        $this->transbankSdkOnepay = $this->getTransbankSdkOnepay();
        $response = $this->transbankSdkOnepay->createTransaction($channel, $this->session->data);

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($response));
    }

    public function commitTransaction() {

        $status = isset($this->request->get['status']) ? $this->request->get['status'] : null;
        $occ = isset($this->request->get['occ']) ? $this->request->get['occ'] : null;
        $externalUniqueNumber = isset($this->request->get['externalUniqueNumber']) ? $this->request->get['externalUniqueNumber'] : null;

        $this->transbankSdkOnepay = $this->getTransbankSdkOnepay();
        $response = $this->transbankSdkOnepay->commitTransaction($status, $occ, $externalUniqueNumber);

        if (isset($response['error'])) {
            $this->session->data['error'] = $response['error'];
            $this->response->redirect($this->url->link('checkout/checkout', '', 'SSL'));
        } else {
            //payment_transbank_onepay_failed_status_id
            //payment_transbank_onepay_completed_status_id

            //$order_status_id = $this->config->get('payment_transbank_onepay_order_status_id');
            //$this->model_checkout_order->addOrderHistory($this->session->data['order_id'], $order_status_id, 'Transacción en Onepay', false);
            //$this->transbankSdkOnepay->logInfo('status_id: ' . $order_status_id);

            $this->response->redirect($this->url->link('checkout/success', '', 'SSL'));
        }
    }
}
?>
