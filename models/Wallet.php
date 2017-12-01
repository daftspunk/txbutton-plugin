<?php namespace TxButton\App\Models;

use Db;
use Model;
use TxButton\App\Classes\HdWallet;
use RainLab\User\Models\User as UserModel;

/**
 * Wallet Model
 */
class Wallet extends Model
{
    /**
     * @var string The database table used by the model.
     */
    public $table = 'txbutton_app_wallets';

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
        'user' => UserModel::class
    ];

    public static function findActive(UserModel $user)
    {
        return static::applyUser($user)->applyActive()->first();
    }

    public function scopeApplyUser($query, UserModel $user)
    {
        return $query->where('user_id', $user->id);
    }

    public function scopeApplyActive($query)
    {
        return $query->where('is_active', true);
    }

    public static function createForUser(UserModel $user, $xpub)
    {
        $wallet = static::firstOrCreate([
            'user_id' => $user->id,
            'xpub' => $xpub
        ]);

        $wallet->setRelation('user', $user);

        return $wallet;
    }

    public function beforeCreate()
    {
        $this->generateHash();
    }

    public function bumpAddressIndex()
    {
        Db::table('txbutton_app_wallets')->where('id', $this->id)->increment('last_address_index');

        return $this->last_address_index;
    }

    public function generateWalletAddress()
    {
        if ($sale = Sale::findReusableSale()) {
            $sale->is_reused = true;
            $sale->save();

            $addressIndex = $sale->address_index;
            $address = $sale->coin_address;
        }
        else {
            $addressIndex = $this->bumpAddressIndex();
            $address = $this->makeWalletAddress($addressIndex);
        }

        return [$address, $addressIndex];
    }

    protected function makeWalletAddress($index)
    {
        $wallet = new HdWallet;
        $wallet->setXpub($this->xpub);
        return $wallet->addressFromXpub('0/'.$index);
    }

    /**
     * Makes this model the active
     * @return void
     */
    public function makeActive()
    {
        $this->newQuery()->where('user_id', $this->user_id)->where('id', $this->id)->update(['is_active' => true]);
        $this->newQuery()->where('user_id', $this->user_id)->where('id', '<>', $this->id)->update(['is_active' => false]);
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
        return md5(uniqid('wallet', microtime()));
    }
}
