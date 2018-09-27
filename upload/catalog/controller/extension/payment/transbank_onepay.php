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

        $data['msg'] = 'Prueba catalogo';

		$this->response->setOutput($this->load->view('extension/payment/transbank_onepay', $data));
    }
}
?>
