<?php namespace Txbutton\App\Models;

use Model;
use RainLab\User\Models\User as UserModel;
use TxButton\App\Classes\AddressWatcher;
use ApplicationException;

/**
 * Sale Model
 */
class Sale extends Model
{
    const STATUS_EMPTY = 'empty';
    const STATUS_PARTIAL = 'partial';
    const STATUS_UNCONFIRMED = 'unconfirmed';
    const STATUS_CONFIRMED = 'confirmed';
    const STATUS_VOID = 'void';

    /**
     * @var string The database table used by the model.
     */
    public $table = 'txbutton_app_sales';

    /**
     * @var array Guarded fields
     */
    protected $guarded = [];

    /**
     * @var array Fillable fields
     */
    protected $fillable = [];

    /**
     * @var array Relations
     */
    public $belongsTo = [
        'user' => UserModel::class,
        'wallet' => Wallet::class,
    ];

    public function beforeCreate()
    {
        $this->generateHash();
    }

    public function scopeApplyUser($query, UserModel $user)
    {
        return $query->where('user_id', $user->id);
    }

    public static function raiseSale(UserModel $user, $options = [])
    {
        extract(array_merge([
            'coinPrice' => 0,
            'fiatPrice' => 0,
            'coinCurrency' => 'BCH',
            'fiatCurrency' => 'USD',
        ], $options));

        if (!$wallet = Wallet::findActive($user)) {
            throw new ApplicationException('No active wallet found');
        }

        if (!$settings = UserSetting::findActive($user)) {
            throw new ApplicationException('No user settings found');
        }

        $saleIndex = $settings->bumpSaleIndex();
        $addressIndex = $wallet->bumpAddressIndex();
        $address = $wallet->generateWalletAddress($addressIndex);
        $exchangeRate = $coinPrice / $fiatPrice;

        $sale = new self;
        $sale->coin_address = $address;
        $sale->address_index = $addressIndex;
        $sale->sale_index = $saleIndex;
        $sale->wallet_id = $wallet->id;
        $sale->user_id = $user->id;
        $sale->status_name = self::STATUS_EMPTY;
        $sale->is_paid = false;
        $sale->is_permanent = false;
        $sale->coin_price = $coinPrice;
        $sale->fiat_price = $fiatPrice;
        $sale->exchange_rate = $exchangeRate;
        $sale->coin_currency = $coinCurrency;
        $sale->fiat_currency = $fiatCurrency;
        $sale->save();

        return $sale;
    }

    public function checkBalance()
    {
        list($balance, $unconfirmed) = AddressWatcher::instance()->getBalance($this->coin_address);

        $confirmed = $balance - $unconfirmed;

        $toSave = false;

        if (!$this->is_permanent && $balance) {
            $this->is_permanent = true;
            $toSave = true;
        }

        if (!$this->is_paid) {
            if ($balance != $this->coin_balance) {
                $this->coin_balance = $balance;
                $toSave = true;
            }

            if ($confirmed != $this->coin_confirmed) {
                $this->coin_confirmed = $confirmed;
                $toSave = true;
            }

            if ($balance > 0 && $balance < $this->coin_price) {
                $this->status_name = self::STATUS_PARTIAL;
                $toSave = true;
            }
            elseif ($balance >= $this->coin_price) {
                $this->status_name = self::STATUS_UNCONFIRMED;
                $toSave = true;
            }

            if ($confirmed >= $this->coin_price) {
                $this->status_name = self::STATUS_CONFIRMED;
                $this->is_paid = true;
                $this->paid_at = $this->freshTimestamp();
                $toSave = true;
            }
        }

        if ($toSave) {
            $this->save();
        }
    }

    /**
     * Internal helper, and set generate a unique hash for this invoice.
     * @return string
     */
    protected function generateHash()
    {
        $this->hash = $this->createHash();
        while ($this->newQuery()->where('hash', $this->hash)->count() > 0) {
            $this->hash = $this->createHash();
        }
    }

    /**
     * Internal helper, create a hash for this invoice.
     * @return string
     */
    protected function createHash()
    {
        return md5(uniqid('sale', microtime()));
    }
}
