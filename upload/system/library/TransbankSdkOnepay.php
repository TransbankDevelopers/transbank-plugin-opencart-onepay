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
class TransbankSdkOnepay {

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

    /**
     * return onepay pre-configured instance
     */
    private function getOnepayOptions() {

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

    /**
     * create a transaction in onepay
     */
    public function createTransaction($channel, $payment_method, $items) {

        if ($channel == null) {
            return $this->failCreate('Falta parámetro channel');
        }

        if ($payment_method != 'transbank_onepay') {
            return $this->failCreate('Método de pago no es Transbank Onepay');
        }

        try {

            $options = $this->getOnepayOptions();

            $carro = new ShoppingCart();

            foreach($items as $qItem) {
                $item = new Item($qItem['name'], intval($qItem['quantity']), intval($qItem->['price']));
                $carro->add($item);
            }

            $this->logInfo('carro: ' . json_encode($carro));
            $this->logInfo('create: ' . json_encode($data));

            $transaction = Transaction::create($carro, $channel, $options);

            $amount = $carro->getTotal();
            $occ = $transaction->getOcc();
            $ott = $transaction->getOtt();
            $externalUniqueNumber = $transaction->getExternalUniqueNumber();
            $issuedAt = $transaction->getIssuedAt();
            $dateTransaction = date('Y-m-d H:i:s', $issuedAt);

            return array(
                'externalUniqueNumber' => $externalUniqueNumber,
                'amount' => $amount,
                'qrCodeAsBase64' => $transaction->getQrCodeAsBase64(),
                'issuedAt' => $issuedAt,
                'occ' => $occ,
                'ott' => $ott
            );

        } catch (TransbankException $transbank_exception) {
            return $this->failCreate($transbank_exception->getMessage());
        }
    }

    private function failCreate($message) {
        $this->logError('Creacion de transacción fallida: ' . $message);
        $response = array('error' => $message);
        return $response;
    }

    /**
     * commit a transaction in onepay
     */
    public function commitTransaction($status, $occ, $externalUniqueNumber) {

        $options = $this->getOnepayOptions();

        $orderStatusComplete = 'PROCESSING';
        $orderStatusCanceled = 'CANCELED';
        $orderStatusRejected = 'CLOSED';

        $metadata = "<br><b>Estado:</b> {$status}
                     <br><b>OCC:</b> {$occ}
                     <br><b>N&uacute;mero de carro:</b> {$externalUniqueNumber}";

        if ($status == null || $occ == null || $externalUniqueNumber == null) {
            return $this->failCommit($orderStatusCanceled, 'Parametros inválidos', $metadata);
        }

        if ($status == 'PRE_AUTHORIZED') {

            try {

                $options = $this->getOnepayOptions();

                $transactionCommitResponse = Transaction::commit($occ, $externalUniqueNumber, $options);

                if ($transactionCommitResponse->getResponseCode() == 'OK') {

                    $amount = $transactionCommitResponse->getAmount();
                    $buyOrder = $transactionCommitResponse->getBuyOrder();
                    $authorizationCode = $transactionCommitResponse->getAuthorizationCode();
                    $description = $transactionCommitResponse->getDescription();
                    $issuedAt = $transactionCommitResponse->getIssuedAt();
                    $dateTransaction = date('Y-m-d H:i:s', $issuedAt);

                    $message = "<h3>Detalles del pago con Onepay:</h3>
                                <br><b>Fecha de Transacci&oacute;n:</b> {$dateTransaction}
                                <br><b>OCC:</b> {$occ}
                                <br><b>N&uacute;mero de carro:</b> {$externalUniqueNumber}
                                <br><b>C&oacute;digo de Autorizaci&oacute;n:</b> {$authorizationCode}
                                <br><b>Orden de Compra:</b> {$buyOrder}
                                <br><b>Estado:</b> {$description}
                                <br><b>Monto de la Compra:</b> {$amount}";

                    $installmentsNumber = $transactionCommitResponse->getInstallmentsNumber();

                    if ($installmentsNumber == 1) {

                        $message = $message . "<br><b>N&uacute;mero de cuotas:</b> Sin cuotas";

                    } else {

                        $installmentsAmount = $transactionCommitResponse->getInstallmentsAmount();

                        $message = $message . "<br><b>N&uacute;mero de cuotas:</b> {$installmentsNumber}
                                                <br><b>Monto cuota:</b> {$installmentsAmount}";
                    }

                    $metadata2 = array('amount' => $amount,
                                    'authorizationCode' => $authorizationCode,
                                    'occ' => $occ,
                                    'externalUniqueNumber' => $externalUniqueNumber,
                                    'issuedAt' => $issuedAt);

                    return $this->successCommit($orderStatusComplete, $message, $metadata2);
                } else {
                    return $this->failCommit($orderStatusRejected, 'Tu pago ha fallado. Vuelve a intentarlo más tarde.', $metadata);
                }

            } catch (TransbankException $transbank_exception) {
                return $this->failCommit($orderStatusCanceled, $transbank_exception->getMessage(), $metadata);
            }

        } else if($status == 'REJECTED') {
            return $this->failCommit($orderStatusRejected, 'Tu pago ha fallado. Pago rechazado', $metadata);
        } else {
            return $this->failCommit($orderStatusCanceled, 'Tu pago ha fallado. Compra cancelada', $metadata);
        }
    }

    private function successCommit($orderStatus, $message, $metadata) {
        $this->logInfo('Confirmación de transacción exitosa: orderStatus: ' . $orderStatus . ', ' . $message);
        $response = array('orderStatus' => $orderStatus, 'success' => true);
        return $response;
    }

    private function failCommit($orderStatus, $message, $metadata) {
        $this->logError('Confirmación de transacción fallida: orderStatus: ' . $orderStatus . ', ' . $message);
        $response = array('error' => $message);
        return $response;
    }

    /**
     * refund a transaction in onepay
     */
    public function refundTransaction() {

        $options = $this->getOnepayOptions();

        $this->log->write('refundTransaction: ' . json_encode($options));
    }

    /**
     * create the diagnostic pdf
     */
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

    /**
     * print INFO log
     */
    public function logInfo($msg) {
        $this->log->write('INFO: ' . $msg);
    }

    /**
     * print ERROR log
     */
    public function logError($msg) {
        $this->log->write('ERROR: ' . $msg);
    }
}
?>
