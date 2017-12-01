<?php namespace TxButton\App\Classes;

use Exception;

class IpnManager
{
    use \October\Rain\Support\Traits\Singleton;

    public function sendIpn($sale)
    {
        traceLog('Sending IPN for sale #' . $sale->id);
    }
}
