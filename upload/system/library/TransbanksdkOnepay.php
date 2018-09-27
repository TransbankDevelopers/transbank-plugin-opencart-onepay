<?php

require_once(DIR_SYSTEM.'library/transbank-sdk-php/init.php');
require_once(DIR_SYSTEM.'library/DiagnosticPDF.php');

use \Transbank\Onepay\OnepayBase;
use \Transbank\Onepay\ShoppingCart;
use \Transbank\Onepay\Item;
use \Transbank\Onepay\Transaction;
use \Transbank\Onepay\Options;
use \Transbank\Onepay\Refund;
use \Transbank\Onepay\Exceptions\TransbankException;
use \Transbank\Onepay\Exceptions\TransactionCreateException;
use \Transbank\Onepay\Exceptions\TransactionCommitException;
use \Transbank\Onepay\Exceptions\RefundCreateException;

/**
 * Helper for load onepay sdk and expose to opencart
 * @autor vutreras (victor.utreras@continuum.cl)
 */
class TransbanksdkOnepay {

    const plugin_version = '1.0.0'; //version of plugin payment
    const app_key = '647E0914-DE74-11E7-80C1-9A214CF093AE'; //app key for opencart
    const log_filename = 'onepay-log.log';

    //constant for keys configurations
    const payment_transbank_onepay_environment = 'payment_transbank_onepay_environment';
    const payment_transbank_onepay_apikey_test = 'payment_transbank_onepay_apikey_test';
    const payment_transbank_onepay_shared_secret_test = 'payment_transbank_onepay_shared_secret_test';
    const payment_transbank_onepay_apikey_live = 'payment_transbank_onepay_apikey_live';
    const payment_transbank_onepay_shared_secret_live = 'payment_transbank_onepay_shared_secret_live';
    const payment_transbank_onepay_logo_url = 'payment_transbank_onepay_logo_url';
    const payment_transbank_onepay_status = 'payment_transbank_onepay_status';

    public function __construct() {
        $this->log = new Log(self::log_filename);
    }

    public function init($config_) {
        $this->config = $config_;
    }

    public function getEnvironment() {
        return $this->config->get(self::payment_transbank_onepay_environment);
    }

    public function getApiKey() {
        $environment = $this->getEnvironment();
        if ($environment == 'LIVE') {
            return $this->config->get(self::payment_transbank_onepay_apikey_live);
        } else {
            return $this->config->get(self::payment_transbank_onepay_apikey_test);
        }
    }

    public function getSharedSecret() {
        $environment = $this->getEnvironment();
        if ($environment == 'LIVE') {
            return $this->config->get(self::payment_transbank_onepay_shared_secret_live);
        } else {
            return $this->config->get(self::payment_transbank_onepay_shared_secret_test);
        }
    }

    public function getLogoUrl() {
        return $this->config->get(self::payment_transbank_onepay_logo_url);
    }

    public function getPluginVersion() {
        return self::plugin_version;
    }

    public function getSoftwareName() {
        return 'Opencart';
    }

    public function getSoftwareVersion() {
        return '3.x'; //TODO not implemented
    }

    public function getLogfileLocation() {
        return DIR_LOGS . self::log_filename;
    }

    public function getConfigArray() {
        return array(
                    'environment' => $this->getEnvironment(),
                    'apiKey' => $this->getApiKey(),
                    'sharedSecret' => $this->getSharedSecret(),
                    'logoUrl' => $this->getLogoUrl(),
                    'moduleVersion' => $this->getPluginVersion(),
                    'softwareVersion' => $this->getSoftwareVersion(),
                    'logfileLocation' => $this->getLogfileLocation()
                );
    }

    private function initOnepay() {

        $apiKey = $this->getApiKey();
        $sharedSecret = $this->getSharedSecret();
        $environment = $this->getEnvironment();

        $environment = $environment != null ? $environment : 'TEST';

        OnepayBase::setApiKey($apiKey);
        OnepayBase::setSharedSecret($sharedSecret);
        OnepayBase::setCurrentIntegrationType($environment);

        $options = new Options($apiKey, $sharedSecret);

        if ($environment == 'LIVE') {
            $options->setAppKey(self::app_key);
        }

        return $options;
    }

    public function createTransaction() {

        $options = $this->initOnepay();

	    $this->log->write('createTransaction: ' . json_encode($options));
    }

    public function commitTransaction() {

        $options = $this->initOnepay();

        $this->log->write('commitTransaction: ' . json_encode($options));
    }

    public function refundTransaction() {

        $options = $this->initOnepay();

        $this->log->write('refundTransaction: ' . json_encode($options));
    }

    public function createDiagnosticPdf() {
        //phpinfo();
        $pdf = new DiagnosticPDF($this);

        $pdf->AliasNbPages();
        $pdf->AddPage();
        $pdf->SetFont('Times','',12);

        // Add a title for the section
        $pdf->Cell(60,15,utf8_decode('Server summary'),0,0,'L');
        $pdf->Ln(15);
        // Add php version
        $pdf->addPHPVersion();
        // Add server software
        $pdf->addServerApi();
        // Add addEcommerceInfo and plugin info
        $pdf->addEcommerceInfo();
        // Add merchant info
        $pdf->addMerchantInfo();
        //Add extension info
        $pdf->addExtensionsInfo();
        $pdf->addLogs();

        $pdf->Output();
    }
}
?>
