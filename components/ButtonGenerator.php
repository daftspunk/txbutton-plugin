<?php namespace Txbutton\App\Components;

use Auth;
use Flash;
use Cms\Classes\ComponentBase;
use Responsiv\Currency\Models\Currency as CurrencyModel;
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
    }

    public function user()
    {
        return Auth::getUser();
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
