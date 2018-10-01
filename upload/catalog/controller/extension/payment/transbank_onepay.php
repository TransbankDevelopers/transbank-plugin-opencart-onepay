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
        $this->load->model('setting/setting'); //load model in: $this->model_setting_setting
        $this->load->model('localisation/order_status'); //load model in: $this->model_localisation_order_status
        $this->load->model('checkout/order'); //load model in: $this->model_checkout_order
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

        $data['action_create'] = $this->url->link('extension/payment/transbank_onepay/createTransaction', '', true);
        $data['action_commit'] = $this->url->link('extension/payment/transbank_onepay/commitTransaction', '', true);
        $data['logo_url'] = $this->transbankSdkOnepay->getLogoUrl();

        return $this->load->view('extension/payment/transbank_onepay', $data);
    }

    public function createTransaction() {

        $channel = isset($this->request->post['channel']) ? $this->request->post['channel'] : null;

        $this->transbankSdkOnepay = $this->getTransbankSdkOnepay();

        $data = $this->session->data;

        $orderInfo = $this->model_checkout_order->getOrder($data['order_id']);

        if ($orderInfo) {

            $items = array();

            $paymentMethod = null;

            if (isset($data['payment_method']) && isset($data['payment_method']['code'])) {
                $paymentMethod = $data['payment_method']['code'];
            }

            if ($paymentMethod != null) {

                foreach ($this->cart->getProducts() as $product) {

                    $items[] = array(
                        'name' => htmlspecialchars($product['name']) . ' ' . htmlspecialchars($product['model']),
                        'quantity' => $product['quantity'],
                        'price' => $this->currency->format($product['price'], $orderInfo['currency_code'], false, false),
                    );
                }

                $shippingAmount = 0;

                if (isset($data['shipping_method']) && isset($data['shipping_method']['cost'])) {
                    $shippingAmount = $data['shipping_method']['cost'];
                }

                if ($shippingAmount != 0) {
                    $items[] = array(
                        'name'     => 'Costo por envio',
                        'price'    => $shippingAmount,
                        'quantity' => 1
                    );
                }
            }

            $response = $this->transbankSdkOnepay->createTransaction($channel, $paymentMethod, $items);
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($response));
    }

    public function commitTransaction() {

        $status = isset($this->request->get['status']) ? $this->request->get['status'] : null;
        $occ = isset($this->request->get['occ']) ? $this->request->get['occ'] : null;
        $externalUniqueNumber = isset($this->request->get['externalUniqueNumber']) ? $this->request->get['externalUniqueNumber'] : null;

        $this->transbankSdkOnepay = $this->getTransbankSdkOnepay();
        $response = $this->transbankSdkOnepay->commitTransaction($status, $occ, $externalUniqueNumber);

        $this->transbankSdkOnepay->logInfo('response: ' . json_encode($response));

        $data = $this->session->data;
        $metadata = $response['metadata'];
        $orderStatus = $this->getIdOrderStatus($response['orderStatus']);
        $this->model_checkout_order->addOrderHistory($data['order_id'], $orderStatus, $metadata, false);

        if (isset($response['error'])) {
            $this->session->data['error'] = $response['error'];
            $this->response->redirect($this->url->link('checkout/checkout', '', 'SSL'));
        } else {
            $this->response->redirect($this->url->link('checkout/success', 'language=' . $this->config->get('config_language'), 'SSL'));
        }
    }

    private function getIdOrderStatus($status) {

        $value = null;

        //load all order status
        $order_statuses = $this->model_localisation_order_status->getOrderStatuses();

        foreach ($order_statuses as $order_status) {
            $this->transbankSdkOnepay->logInfo($status . ' - check status: ' . json_encode($order_status));
            if (trim(strtolower($order_status['name'])) == $status) {
                $value = $order_status['order_status_id'];
                break;
            }
        }

        $this->transbankSdkOnepay->logInfo('return status: ' . $value);

        return $value;
    }
}
?>
