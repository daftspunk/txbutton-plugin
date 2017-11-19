<?php namespace TxButton\App\Components;

use Auth;
use Flash;
use TxButton\App\Classes\HdWallet;
use TxButton\App\Models\Wallet as WalletModel;
use Cms\Classes\ComponentBase;
use ApplicationException;
use ValidationException;
use Exception;

class WalletSettings extends ComponentBase
{
    public function componentDetails()
    {
        return [
            'name'        => 'WalletSettings Component',
            'description' => 'No description provided yet...'
        ];
    }

    public function defineProperties()
    {
        return [];
    }

    public function onRun()
    {
        $this->page['wallet'] = $wallet = $this->wallet();

        $this->page['xpub'] = $wallet ? $wallet->xpub : null;
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

        return WalletModel::findActive($user);
    }

    public function onReplaceKey()
    {
        $this->pageCycle();
        $this->page['replaceMode'] = true;
    }

    public function onSaveKey()
    {
        if (!$user = $this->user()) {
            throw new ApplicationException('Must be logged in!');
        }

        try {
            $xpub = $this->xpubFromPost();

            $this->validateXpub($xpub);

            $wallet = WalletModel::createForUser($user, $xpub);

            $wallet->makeActive();

            $this->page['wallet'] = $wallet;
        }
        catch (Exception $ex) {
            throw new ValidationException(['xpub' => $ex->getMessage()]);
        }
    }

    public function onCheckKey()
    {
        try {
            $xpub = $this->xpubFromPost();

            $addresses = $this->validateXpub($xpub, 0, 4);

            $this->page['addresses'] = $addresses;
            $this->page['isChecked'] = true;
        }
        catch (Exception $ex) {
            throw new ValidationException(['xpub' => $ex->getMessage()]);
        }

        Flash::success('Key looks good!');
    }

    protected function xpubFromPost()
    {
        return $this->page['xpub'] = trim(post('xpub'));
    }

    protected function validateXpub($xpub, $from = 0, $count = 1)
    {
        $wallet = new HdWallet;
        $wallet->setXpub($xpub);
        $addresses = $wallet->addressArrayFromXpub(0, 0, $count);
        return $addresses;
    }
}
