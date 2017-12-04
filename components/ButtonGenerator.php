<?php namespace Txbutton\App\Components;

use Auth;
use Flash;
use Cms\Classes\ComponentBase;
use Responsiv\Currency\Models\Currency as CurrencyModel;
use Txbutton\App\Models\UserSetting as UserSettingModel;
use ApplicationException;
use ValidationException;
use Exception;

class ButtonGenerator extends ComponentBase
{
    public function componentDetails()
    {
        return [
            'name'        => 'ButtonGenerator Component',
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

    public function currencies()
    {
        return CurrencyModel::listEnabled();
    }

    public function onCreateButton()
    {
        Flash::error('Sorry this feature is not available yet!');
    }
}
