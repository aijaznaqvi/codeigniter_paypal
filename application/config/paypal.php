<?php
/** set your paypal credential **/

$config['client_id'] = 'AbqfolVlfz83oAeZmhbztPaZaBZV7uH62w5SYVtLpaeRhD_2IPKrQw2Sc3YRhr8PSGFjYSOoyVUG5tZI';
$config['secret'] = 'EKvSawiTVuOiHddWNuW-dTxkR01n5ZqZfvI08w3xohNXJljEVihrmkOGRt1TInQaSmTn7obxQwpxV8Dw';

/**
 * SDK configuration
 */
/**
 * Available option 'sandbox' or 'live'
 */
$config['settings'] = array(

    'mode' => 'sandbox',
    /**
     * Specify the max request time in seconds
     */
    'http.ConnectionTimeOut' => 1000,
    /**
     * Whether want to log to a file
     */
    'log.LogEnabled' => true,
    /**
     * Specify the file that want to write on
     */
    'log.FileName' => 'application/logs/paypal.log',
    /**
     * Available option 'FINE', 'INFO', 'WARN' or 'ERROR'
     *
     * Logging is most verbose in the 'FINE' level and decreases as you
     * proceed towards ERROR
     */
    'log.LogLevel' => 'FINE'
);
