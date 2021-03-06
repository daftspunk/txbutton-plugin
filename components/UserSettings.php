<?php namespace Txbutton\App\Components;

use Auth;
use Flash;
use Cms\Classes\ComponentBase;
use Responsiv\Currency\Models\Currency as CurrencyModel;
use TxButton\App\Models\Wallet as WalletModel;
use Txbutton\App\Models\UserSetting as UserSettingModel;
use ApplicationException;
use ValidationException;
use Exception;

class UserSettings extends ComponentBase
{
    public function componentDetails()
    {
        return [
            'name'        => 'UserSettings Component',
            'description' => 'No description provided yet...'
        ];
    }

    public function defineProperties()
    {
        return [];
    }

    public function onRun()
    {
        $this->page['currencies'] = $this->currencies();
        $this->page['settings'] = $this->settings();
        $this->page['wallet'] = $this->wallet();
    }

    public function user()
    {
        return Auth::getUser();
    }

    public function settings()
    {
        if (!$user = $this->user()) {
            return null;
        }

        return UserSettingModel::createForUser($user);
    }

    public function wallet()
    {
        if (!$user = $this->user()) {
            return null;
        }

        return WalletModel::findActive($user);
    }

    public function currencies()
    {
        return CurrencyModel::listEnabled();
    }

    public function onSaveSettings()
    {
        if (!$user = $this->user()) {
            throw new ApplicationException('Must be logged in!');
        }

        $settings = $this->settings();

        $settings->fill(post());

        $currency = CurrencyModel::where('currency_code', post('currency_code'))->first();
        if ($currency) {
            $settings->currency_symbol = $currency->currency_symbol;
        }

        $settings->save();

        Flash::success('Settings saved!');
    }
}
