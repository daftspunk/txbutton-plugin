<?php namespace Txbutton\App\Components;

use Flash;
use Currency;
use Redirect;
use Cms\Classes\ComponentBase;
use TxButton\App\Classes\PosAuthManager;
use ValidationException;

class PosTerminal extends ComponentBase
{
    protected $authManager;

    public function componentDetails()
    {
        return [
            'name'        => 'PosTerminal Component',
            'description' => 'No description provided yet...'
        ];
    }

    public function defineProperties()
    {
        return [];
    }

    public function init()
    {
        $this->authManager = PosAuthManager::instance();
    }

    public function onRun()
    {
        $this->prepareVars();
    }

    protected function prepareVars()
    {
        $this->page['posUsername'] = $this->posUsername();
        $this->page['posUser'] = $this->posUser();
        $this->page['slideMode'] = $this->detectSlideMode();
    }

    protected function detectSlideMode()
    {
        if (!$this->posUsername()) {
            return 'username';
        }

        if (!$this->posUser()) {
            return 'auth';
        }

        return 'amount';
    }

    public function posUsername()
    {
        return post('username', $this->param('username'));
    }

    public function posUser()
    {
        return $this->authManager->getUser();
    }

    public function onCheckUsername()
    {
        $username = post('username');

        if (!$username) {
            throw new ValidationException(['username' => 'The username field is required']);
        }

        $user = $this->authManager->findUserByLogin($username);

        if (!$user) {
            throw new ValidationException(['username' => 'Unable to find that account']);
        }

        return Redirect::to($this->currentPageUrl(['username' => $username]));
    }

    public function onCheckPin()
    {
        $pin = post('keypad_value');

        $username = $this->posUsername();

        $this->authManager->authenticate(['username' => $username, 'pin' => $pin]);

        Flash::success('Authentication successful');

        $this->prepareVars();
    }

    public function onRevertAmount()
    {
        $this->prepareVars();
    }

    public function onConfirmAmount()
    {
        $this->prepareVars();
        $this->setAmountsFromKeyPadValue();

        $this->page['screenMode'] = 'confirm';
    }

    public function onSubmitAmount()
    {
        $this->prepareVars();
        $this->setAmountsFromKeyPadValue();

        $this->page['address'] = '...';
        $this->page['slideMode'] = 'transaction';
    }

    protected function setAmountsFromKeyPadValue()
    {
        $fiatAmount = post('keypad_value');
        $fiatAmount = $fiatAmount / 100;

        if (!$fiatAmount) {
            throw new ValidationException(['keypad_value' => 'No amount entered']);
        }

        $coinAmount = Currency::format($fiatAmount, ['from' => 'AUD', 'to' => 'BCH', 'decimals' => 8]);
        $coinAmount = rtrim($coinAmount, '0');

        $this->page['amount'] = $fiatAmount;
        $this->page['amountCoin'] = $coinAmount;
    }
}
