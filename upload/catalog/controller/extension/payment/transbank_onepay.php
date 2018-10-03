<?php
/**
 * Controller for handle the checkout
 * @autor vutreras (victor.utreras@continuum.cl)
 *
 */
class ControllerExtensionPaymentTransbankOnepay extends Controller {

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

    /**
     * method default for handle the config of onepay modal checkout
     */
	public function index() {

		$this->transbankSdkOnepay = $this->getTransbankSdkOnepay();

        //create link to this controller method createTransaction
        $data['action_create'] = $this->url->link('extension/payment/transbank_onepay/createTransaction', '', 'SSL');
        //create link to this controller method commitTransaction
        $data['action_commit'] = $this->url->link('extension/payment/transbank_onepay/commitTransaction', '', 'SSL');
        $data['logo_url'] = $this->transbankSdkOnepay->getLogoUrl();

        return $this->load->view('extension/payment/transbank_onepay', $data);
    }

    /**
     * method for handle the transaction create
     */
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

    /**
     * method for handle the transaction commit
     */
    public function commitTransaction() {

        $status = isset($this->request->get['status']) ? $this->request->get['status'] : null;
        $occ = isset($this->request->get['occ']) ? $this->request->get['occ'] : null;
        $externalUniqueNumber = isset($this->request->get['externalUniqueNumber']) ? $this->request->get['externalUniqueNumber'] : null;

        $this->transbankSdkOnepay = $this->getTransbankSdkOnepay();
        $response = $this->transbankSdkOnepay->commitTransaction($status, $occ, $externalUniqueNumber);

        $data = $this->session->data;
        $orderId = $data['order_id'];
        $message = $response['message'];
        $detail = $response['detail'];
        $metadata = json_encode($response['metadata']);
        $orderStatusId = $response['orderStatusId'];
        $orderComment = $message . '<hr>' . $detail . '<span style="display:none;">json::' . $metadata . '</span>';
        $orderNotifyToUser = true;

        $this->model_checkout_order->addOrderHistory($orderId, $orderStatusId, $orderComment, $orderNotifyToUser);

        $this->session->data['transbank_onepay_result'] = '<div class="alert alert-success">' . $detail . '</div>';

        if (isset($response['error'])) {
            $this->response->redirect($this->url->link('checkout/failure', 'language=' . $this->config->get('config_language'), 'SSL'));
        } else {
            $this->response->redirect($this->url->link('checkout/success', 'language=' . $this->config->get('config_language'), 'SSL'));
        }
    }
}
?>
