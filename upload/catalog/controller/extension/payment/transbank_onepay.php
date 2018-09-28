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

        $this->transbankSdkOnepay->logInfo('cart: ' . json_encode($this->cart->getProducts()));

        $data = $this->session->data;

        $order_info = $this->model_checkout_order->getOrder($data['order_id']);

        if ($order_info) {

            $items = array();

            $payment_method = null;

            if (isset($data['payment_method']) && isset($data['payment_method']['code'])) {
                $payment_method = $data['payment_method']['code'];
            }

            if ($payment_method != null) {

                foreach ($this->cart->getProducts() as $product) {

                    $items[] = array(
                        'name'     => htmlspecialchars($product['name']),
                        'model'    => htmlspecialchars($product['model']),
                        'price'    => $this->currency->format($product['price'], $order_info['currency_code'], false, false),
                        'quantity' => $product['quantity']
                    );
                }

                $shipping_amount = 0;

                if (isset($data['shipping_method']) && isset($data['shipping_method']['cost'])) {
                    $shipping_amount = $data['shipping_method']['cost'];
                }

                if ($shipping_amount != 0) {
                    $items[] = array(
                        'name'     => 'Costo por envio',
                        'price'    => intval($shipping_amount),
                        'quantity' => $product['quantity']
                    );
                }
            }

            $response = $this->transbankSdkOnepay->createTransaction($channel, $payment_method, $items);
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

        if (isset($response['error'])) {
            $this->session->data['error'] = $response['error'];
            $this->response->redirect($this->url->link('checkout/checkout', '', 'SSL'));
        } else {
            //payment_transbank_onepay_failed_status_id
            //payment_transbank_onepay_completed_status_id

            //$order_status_id = $this->config->get('payment_transbank_onepay_order_status_id');
            //$this->model_checkout_order->addOrderHistory($this->session->data['order_id'], $order_status_id, 'Transacción en Onepay', false);
            //$this->transbankSdkOnepay->logInfo('status_id: ' . $order_status_id);

            //$order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);
            //$this->transbankSdkOnepay->logInfo('order_info: ' . json_encode($order_info));


            $this->response->redirect($this->url->link('checkout/success', '', 'SSL'));
        }
    }
}
?>
