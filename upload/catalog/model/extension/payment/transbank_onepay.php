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
        $to = new TransbankSdkOnepay();
        $to->init($this->config);
        return $to;
    }

    public function getMethod($address, $total) {

        $this->transbankSdkOnepay = $this->getTransbankSdkOnepay();
        $this->transbankSdkOnepay->logInfo('Model cargado');

		$method_data = array(
            'code' => 'transbank_onepay',
            'title' => $this->language->get('text_title'),
            'terms' => ''
        );
		return $method_data;
	}
}
?>
