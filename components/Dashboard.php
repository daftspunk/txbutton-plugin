<?php namespace Txbutton\App\Components;

use Auth;
use Flash;
use Cms\Classes\ComponentBase;
use TxButton\App\Models\Wallet as WalletModel;
use Txbutton\App\Models\UserSetting as UserSettingModel;

class Dashboard extends ComponentBase
{
    public function componentDetails()
    {
        return [
            'name'        => 'Dashboard Component',
            'description' => 'No description provided yet...'
        ];
    }

    public function defineProperties()
    {
        return [];
    }

    public function onRun()
    {
        $this->prepareVars();
    }

    protected function prepareVars()
    {
        $this->page['settings'] = $this->settings();
        $this->page['wallet'] = $this->wallet();

        if ($settings = $this->settings()) {
            $this->page['settings'] = $settings;
            $this->page['posConfigured'] = $settings->isPosConfigured();
            $this->page['posUrl'] = $this->pageUrl('pos', ['username' => $settings->pos_username]);
        }
    }

    public function user()
    {
        return Auth::getUser();
    }

    public function wallet()
    {
        if (!$user = $this->user()) {
            return null;
        }

        return WalletModel::applyUser($user)->applyActive()->first();
    }

    public function settings()
    {
        if (!$user = $this->user()) {
            return null;
        }

        return UserSettingModel::createForUser($user);
    }

    public function onSavePosSettings()
    {
        if (!$user = $this->user()) {
            throw new ApplicationException('Must be logged in!');
        }

        $settings = $this->settings();

        $settings->rules['pos_username'] .= '|required';
        $settings->rules['pos_pin'] .= '|required';

        $settings->fill(post());
        $settings->save();

        Flash::success('POS terminal ready to go!');

        $this->onLaunchPos();
    }

    public function onLaunchPos()
    {
        $this->prepareVars();
    }
}
