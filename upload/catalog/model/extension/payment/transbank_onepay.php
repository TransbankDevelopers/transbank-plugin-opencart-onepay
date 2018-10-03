<?php
/**
 * Model for handle the checkout
 * @autor vutreras (victor.utreras@continuum.cl)
 *
 */
class ModelExtensionPaymentTransbankOnepay extends Model {

    private $transbankSdkOnepay = null;

    private function loadResources() {
        $this->load->language('extension/payment/transbank_onepay');
        $this->load->model('setting/setting');
    }

    private function getTransbankSdkOnepay() {
        $this->loadResources();
        if (!class_exists('TransbankSdkOnepay')) {
            $this->load->library('TransbankSdkOnepay');
        }
        return new TransbankSdkOnepay($this->config);
    }

    public function getMethod($address, $total) {

        $this->transbankSdkOnepay = $this->getTransbankSdkOnepay();

        $status = false;

        if (intval($total) > 0) {
			$status = true;
		}

        $method_data = array();

		if ($status) {
            $method_data = array(
                'code' => TransbankSdkOnepay::PLUGIN_CODE,
                'title' => $this->language->get('text_title'),
                'terms' => '',
                'sort_order' => $this->config->get(TransbankSdkOnepay::PAYMENT_TRANSBANK_ONEPAY_SORT_ORDER)
            );
        }

		return $method_data;
	}
}
?>
