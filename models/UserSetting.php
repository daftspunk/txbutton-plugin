<?php namespace Txbutton\App\Models;

use Db;
use Model;
use RainLab\User\Models\User as UserModel;

/**
 * UserSetting Model
 */
class UserSetting extends Model
{
    use \October\Rain\Database\Traits\Validation;

    /**
     * @var string The database table used by the model.
     */
    public $table = 'txbutton_app_user_settings';

    /**
     * Validation rules
     */
    public $rules = [
        'pos_username' => 'alpha_num|between:3,64|unique:txbutton_app_user_settings',
        'pos_pin' => 'numeric|digits_between:3,6',
    ];

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
        return static::applyUser($user)->first();
    }

    public function scopeApplyUser($query, UserModel $user)
    {
        return $query->where('user_id', $user->id);
    }

    public static function createForUser(UserModel $user)
    {
        $setting = static::firstOrCreate([
            'user_id' => $user->id,
        ], [
            'currency_symbol' => '$',
            'currency_code' => 'USD',
            'require_confirm' => 1,
        ]);

        $setting->setRelation('user', $user);

        return $setting;
    }

    public function beforeSave()
    {
        if ($this->isDirty('pos_pin')) {
            $this->pos_hash = md5(uniqid('pos', microtime()));
        }
    }

    public function bumpSaleIndex()
    {
        Db::table('txbutton_app_user_settings')->where('id', $this->id)->increment('total_sales');

        return $this->total_sales + 1;
    }

    /*
     * @todo Make sure this is unique
     */
    public function getWebId()
    {
        if (!$this->web_id) {
            $newId = crc32(uniqid('web', microtime()));

            if ($newId < 0) {
                $newId = $newId * -1;
            }

            $this->web_id = $newId;
            $this->forceSave();
        }

        return $this->web_id;
    }

    /*
     * @todo Make sure this is unique
     */
    public function getPosHash()
    {
        if (!$this->pos_hash) {
            $this->pos_hash = md5(uniqid('pos', microtime()));
            $this->forceSave();
        }

        return $this->pos_hash;
    }

    /**
     * Checks the given persist code.
     * @param string $posHash
     * @return bool
     */
    public function checkPosHash($posHash)
    {
        if (!$this->isPosConfigured()) {
            return false;
        }

        if (!$posHash || !$this->pos_hash) {
            return false;
        }

        return $posHash == $this->pos_hash;
    }

    public function isPosConfigured()
    {
        return strlen(trim($this->pos_username)) && strlen(trim($this->pos_pin));
    }
}
