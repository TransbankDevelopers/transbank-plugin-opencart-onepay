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

    const PLUGIN_VERSION = '1.0.0'; //version of plugin payment
    const PLUGIN_CODE = 'transbank_onepay'; //code of plugin for opencart
    const APP_KEY = '647E0914-DE74-11E7-80C1-9A214CF093AE'; //app key for opencart

    //constants for log handler
    const LOG_FILENAME = 'onepay-log.log'; //name of the log file
    const LOG_DEBUG_ENABLED = false; //enable or disable debug logs
    const LOG_INFO_ENABLED = true; //enable or disable info logs
    const LOG_ERROR_ENABLED = true; //enable or disable error logs

    //constants for keys configurations
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

    private $softwareVersion = '';

    public function __construct($config) {
        $this->config = $config;
        $this->log = new Log(self::LOG_FILENAME);
    }

    public function getEnvironment() {
        return $this->config->get(self::PAYMENT_TRANSBANK_ONEPAY_ENVIRONMENT);
    }

    public function getApiKey() {
        $environment = $this->getEnvironment();
        if ($environment == 'LIVE') {
            return $this->config->get(self::PAYMENT_TRANSBANK_ONEPAY_APIKEY_LIVE);
        } else {
            return $this->config->get(self::PAYMENT_TRANSBANK_ONEPAY_APIKEY_TEST);
        }
    }

    public function getSharedSecret() {
        $environment = $this->getEnvironment();
        if ($environment == 'LIVE') {
            return $this->config->get(self::PAYMENT_TRANSBANK_ONEPAY_SHARED_SECRET_LIVE);
        } else {
            return $this->config->get(self::PAYMENT_TRANSBANK_ONEPAY_SHARED_SECRET_TEST);
        }
    }

    public function getLogoUrl() {
        return $this->config->get(self::PAYMENT_TRANSBANK_ONEPAY_LOGO_URL);
    }

    public function getPluginVersion() {
        return self::PLUGIN_VERSION;
    }

    public function getSoftwareName() {
        return 'OpenCart';
    }

    public function setSoftwareVersion($softwareVersion) {
        $this->softwareVersion = $softwareVersion;
    }

    public function getSoftwareVersion() {
        return $this->softwareVersion;
    }

    public function getLogfileLocation() {
        return DIR_LOGS . self::LOG_FILENAME;
    }

    public function getOrderStatusIdPaid() {
        return $this->config->get(self::PAYMENT_TRANSBANK_ONEPAY_ORDER_STATUS_ID_PAID);
    }

    public function getOrderStatusIdFailed() {
        return $this->config->get(self::PAYMENT_TRANSBANK_ONEPAY_ORDER_STATUS_ID_FAILED);
    }

    public function getOrderStatusIdRejected() {
        return $this->config->get(self::PAYMENT_TRANSBANK_ONEPAY_ORDER_STATUS_ID_REJECTED);
    }

    public function getOrderStatusIdCancelled() {
        return $this->config->get(self::PAYMENT_TRANSBANK_ONEPAY_ORDER_STATUS_ID_CANCELLED);
    }

    public function getStatusConfigured() {
        return $this->config->get(self::PAYMENT_TRANSBANK_ONEPAY_ORDER_STATUS_CONFIGURED);
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
            $options->setAppKey(self::APP_KEY);
        }

        return $options;
    }

    /**
     * create a transaction in onepay
     */
    public function createTransaction($channel, $paymentMethod, $items, $callbackUrl) {

        if ($channel == null) {
            return $this->failCreate('Falta parámetro channel');
        }

        if ($paymentMethod != self::PLUGIN_CODE) {
            return $this->failCreate('Método de pago no es Transbank Onepay');
        }

        try {

            OnepayBase::setCallbackUrl($callbackUrl);

            $options = $this->getOnepayOptions();

            $cart = new ShoppingCart();

            foreach($items as $qItem) {
                $item = new Item($qItem['name'], intval($qItem['quantity']), intval($qItem['price']));
                $cart->add($item);
            }

            $transaction = Transaction::create($cart, $channel, $options);

            $amount = $cart->getTotal();
            $occ = $transaction->getOcc();
            $ott = $transaction->getOtt();
            $externalUniqueNumber = $transaction->getExternalUniqueNumber();
            $issuedAt = $transaction->getIssuedAt();

            $response = array(
                'amount' => $amount,
                'occ' => $occ,
                'ott' => $ott,
                'externalUniqueNumber' => $externalUniqueNumber,
                'issuedAt' => $issuedAt,
                'qrCodeAsBase64' => $transaction->getQrCodeAsBase64()
            );

            $this->logDebug('Transacción creada: ' . json_encode($response));

            return $response;

        } catch (TransbankException $transbankException) {
            return $this->failCreate($transbankException->getMessage());
        }
    }

    private function failCreate($message) {
        $this->logError('Transacción fallida: ' . $message);
        return array('error' => true, 'message' => $message);
    }

    /**
     * commit a transaction in onepay
     */
    public function commitTransaction($status, $occ, $externalUniqueNumber) {

        $options = $this->getOnepayOptions();

        $orderStatusPaid = $this->getOrderStatusIdPaid();
        $orderStatusFailed = $this->getOrderStatusIdFailed();
        $orderStatusRejected = $this->getOrderStatusIdRejected();
        $orderStatusCancelled = $this->getOrderStatusIdCancelled();

        $detail = "<b>Estado:</b> {$status}
                <br><b>OCC:</b> {$occ}
                <br><b>N&uacute;mero de carro:</b> {$externalUniqueNumber}";

        $metadata = array('status' => $status,
                        'occ' => $occ,
                        'externalUniqueNumber' => $externalUniqueNumber);

        if ($status == null || $occ == null || $externalUniqueNumber == null) {
            return $this->failCommit($orderStatusCancelled, 'Parametros inválidos', $detail, $metadata);
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

                    $detail = "<b>Detalles del pago con Onepay:</b>
                                <br><b>Fecha de Transacci&oacute;n:</b> {$dateTransaction}
                                <br><b>OCC:</b> {$occ}
                                <br><b>N&uacute;mero de carro:</b> {$externalUniqueNumber}
                                <br><b>C&oacute;digo de Autorizaci&oacute;n:</b> {$authorizationCode}
                                <br><b>Orden de Compra:</b> {$buyOrder}
                                <br><b>Estado:</b> {$description}
                                <br><b>Monto de la Compra:</b> {$amount}";

                    $installmentsNumber = $transactionCommitResponse->getInstallmentsNumber();

                    if ($installmentsNumber == 1) {

                        $detail = $detail . "<br><b>N&uacute;mero de cuotas:</b> Sin cuotas";

                    } else {

                        $installmentsAmount = $transactionCommitResponse->getInstallmentsAmount();

                        $detail = $detail . "<br><b>N&uacute;mero de cuotas:</b> {$installmentsNumber}
                                            <br><b>Monto cuota:</b> {$installmentsAmount}";
                    }

                    $metadata = array('orderStatusOriginal' => 'paid',
                                    'orderStatus' => $orderStatusPaid,
                                    'amount' => $amount,
                                    'authorizationCode' => $authorizationCode,
                                    'occ' => $occ,
                                    'externalUniqueNumber' => $externalUniqueNumber,
                                    'issuedAt' => $issuedAt);

                    return $this->successCommit($orderStatusPaid, 'Pago exitoso', $detail, $metadata);
                } else {
                    return $this->failCommit($orderStatusFailed, 'Tu pago ha fallado. Vuelve a intentarlo más tarde.', $detail, $metadata);
                }

            } catch (TransbankException $transbankException) {
                return $this->failCommit($orderStatusFailed, $transbankException->getMessage(), $detail, $metadata);
            }

        } else if($status == 'REJECTED') {
            return $this->failCommit($orderStatusRejected, 'Tu pago ha fallado. Pago rechazado.', $detail, $metadata);
        } else {
            return $this->failCommit($orderStatusCancelled, 'Tu pago ha fallado. Compra cancelada.', $detail, $metadata);
        }
    }

    private function successCommit($orderStatusId, $message, $detail, $metadata) {
        $this->logDebug('Transacción confirmada: orderStatusId: ' . $orderStatusId . ', ' . json_encode($metadata));
        return array('success' => true, 'orderStatusId' => $orderStatusId, 'message' => $message, 'detail' => $detail, 'metadata' => $metadata);
    }

    private function failCommit($orderStatusId, $message, $detail, $metadata) {
        $this->logError('Transacción no confirmada: orderStatusId: ' . $orderStatusId . ', ' . json_encode($metadata));
        return array('error' => true, 'orderStatusId' => $orderStatusId, 'message' => $message, 'detail' => $detail, 'metadata' => $metadata);
    }

    /**
     * refund a transaction in onepay
     */
    public function refundTransaction() {
        //not implemented
    }

    /**
     * create the diagnostic pdf
     */
    public function createDiagnosticPdf() {

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
     * print DEBUG log
     */
    public function logDebug($msg) {
        if (self::LOG_DEBUG_ENABLED) {
            $this->log->write('DEBUG: ' . $msg);
        }
    }

    /**
     * print INFO log
     */
    public function logInfo($msg) {
        if (self::LOG_INFO_ENABLED) {
            $this->log->write('INFO: ' . $msg);
        }
    }

    /**
     * print ERROR log
     */
    public function logError($msg) {
        if (self::LOG_ERROR_ENABLED) {
            $this->log->write('ERROR: ' . $msg);
        }
    }
}
?>
