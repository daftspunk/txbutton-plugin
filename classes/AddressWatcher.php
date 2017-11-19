<?php namespace TxButton\App\Classes;

use Http;
use Exception;
use SystemException;

/**
 * TxButton\App\Classes\AddressWatcher::instance()->getBalance('...');
 */
class AddressWatcher
{
    use \October\Rain\Support\Traits\Singleton;

    protected $blockExplorers = [
        'https://cashexplorer.bitcoin.com/insight-api/addr/%s',
        'https://blockdozer.com/insight-api/addr/%s',
        'https://bccblock.info/api/addr/%s',
        'https://api.blockchair.com/bitcoin-cash/dashboards/address/%s',

        // 'https://api.explorer.cash/%s/balance', # bogus data provided
        // 'https://api.blocktrail.com/v1/bcc/address/%s?api_key=MY_APIKEY', # needs key
        // 'https://bch-bitcore2.trezor.io/api/addr/%s', # scripts not allowed
        // 'https://bitcoincash.blockexplorer.com/api/addr/%s', # scripts not allowed
    ];

    /**
     * If address starts with C or H, use the bitpay server
     */
    public function getBalance($address)
    {
        return [0, 0]; // Testing

        $apiUrl = $this->blockExplorers[array_rand($this->blockExplorers)];

        $response = null;
        try {
            $response = Http::get(sprintf($apiUrl, $address));
            $body = (string) $response;
        }
        catch (Exception $ex) { }

        if (!strlen($body)) {
            throw new SystemException('Error loading the block explorer feed: '. $apiUrl);
        }

        $result = json_decode($body, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new SystemException('Error decoding the block explorer feed: '. $apiUrl);
        }

        /*
         * Balance
         */
        $balance = 0;

        if (array_key_exists('balance', $result)) {
            $balance = $result['balance'];
        }
        // BlockChair
        elseif (isset($result['data'][0])) {
            $blockchair = $result['data'][0];
            if (array_key_exists('sum_value_unspent', $blockchair)) {
                $balance = $blockchair['sum_value_unspent'] / 100000000;
            }
        }

        /*
         * Unconfirmed balance
         */
        if (array_key_exists('unconfirmedBalance', $result)) {
            $unconfirmedBalance = $result['unconfirmedBalance'];
        }
        else {
            $unconfirmedBalance = $balance;
        }

        return [$balance, $unconfirmedBalance];
    }
}
