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
    const STATUS_ABANDONED = 'abandoned';

    const SOURCE_POS = 'pos';
    const SOURCE_WEB = 'web';

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
     * @var array List of datetime attributes to convert to an instance of Carbon/DateTime objects.
     */
    protected $dates = ['abandon_at', 'checked_at', 'paid_at'];

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

    public function scopeApplyPendingBalances($query)
    {
        return $query
            ->where(function($q) {
                $q->where('is_paid', 0);
                $q->orWhereNull('is_paid');
            })
            ->where(function($q) {
                $q->where('is_abandoned', 0);
                $q->orWhereNull('is_abandoned');
            })
        ;
    }

    public function scopeFindReusableSale($query)
    {
        $query
            ->where(function($q) {
                $q->where('is_permanent', 0);
                $q->orWhereNull('is_permanent');
            })
            ->where(function($q) {
                $q->where('is_reused', 0);
                $q->orWhereNull('is_reused');
            })
            ->where('is_abandoned', 1)
        ;

        return $query->first();
    }

    public function scopeApplyIpnUnsent($query)
    {
        return $query
            ->where(function($q) {
                $q->where('is_ipn_sent', 0);
                $q->orWhereNull('is_ipn_sent');
            })
            ->where('is_paid', 1)
        ;
    }

    public static function raiseSale(UserModel $user, $options = [])
    {
        extract(array_merge([
            'coinPrice' => 0,
            'fiatPrice' => 0,
            'coinCurrency' => 'BCH',
            'fiatCurrency' => 'USD',
            'source'       => self::SOURCE_POS
        ], $options));

        if (!$wallet = Wallet::findActive($user)) {
            throw new ApplicationException('No active wallet found');
        }

        if (!$settings = UserSetting::findActive($user)) {
            throw new ApplicationException('No user settings found');
        }

        $saleIndex = $settings->bumpSaleIndex();
        list($address, $addressIndex) = $wallet->generateWalletAddress();
        $exchangeRate = $coinPrice / $fiatPrice;

        $needsIpn = $source == self::SOURCE_WEB;

        $sale = new self;
        $sale->coin_address = $address;
        $sale->address_index = $addressIndex;
        $sale->sale_index = $saleIndex;
        $sale->wallet_id = $wallet->id;
        $sale->user_id = $user->id;
        $sale->status_name = self::STATUS_EMPTY;
        $sale->source_name = $source;
        $sale->is_paid = false;
        $sale->is_permanent = false;
        $sale->is_ipn_sent = $needsIpn ? false : true;
        $sale->is_abandoned = false;
        $sale->coin_price = $coinPrice;
        $sale->fiat_price = $fiatPrice;
        $sale->exchange_rate = $exchangeRate;
        $sale->coin_currency = $coinCurrency;
        $sale->fiat_currency = $fiatCurrency;
        $sale->touchFromUser(false);
        $sale->save();

        return $sale;
    }

    public function touchFromUser($doSave = true)
    {
        $this->checked_at = $this->freshTimestamp();
        $this->abandon_at = $this->freshAbandonTimestamp();

        if ($doSave) {
            $this->save();
        }
    }

    public function freshAbandonTimestamp()
    {
        return $this->freshTimestamp()->addHours(2);
    }

    public function markAbandoned()
    {
        if (!$this->isAbandoned()) {
            return;
        }

        $this->status_name = self::STATUS_ABANDONED;
        $this->is_abandoned = true;
        $this->save();
    }

    public function isAbandoned()
    {
        if (!$this->abandon_at) {
            return false;
        }

        return $this->abandon_at->isPast();
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
            $this->abandon_at = $this->freshAbandonTimestamp();
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
