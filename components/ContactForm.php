<?php namespace TxButton\App\Components;

use Mail;
use Validator;
use ValidationException;
use ApplicationException;
use Backend\Models\UserGroup;
use Cms\Classes\Theme;
use Cms\Classes\ComponentBase;

class ContactForm extends ComponentBase
{
    public function componentDetails()
    {
        return [
            'name'        => 'Contact Form',
            'description' => 'Allows users to submit feedback directly on the site'
        ];
    }

    public function defineProperties()
    {
        return [];
    }

    public function onLoadSubmitFeedbackForm()
    {
    }

    public function onSubmitFeedback()
    {
        $rules = [
            'name'     => 'required|min:2|max:64',
            'email'    => 'required|email|min:2|max:64',
            'comments' => 'required|min:5',
        ];

        $validation = Validator::make(post(), $rules);
        if ($validation->fails()) {
            throw new ValidationException($validation);
        }

        $adminGroup = 'contact-users';
        if (!$group = UserGroup::whereCode($adminGroup)->first()) {
            throw new ApplicationException('Action failed! Missing a valid notification group.');
        }

        $contacts = $group->users->lists('full_name', 'email');

        $params = [
            // 'site_name' => Theme::getActiveTheme()->site_name,
            'site_name' => 'TXButton',
        ];
        $params = array_merge($params, (array) post());

        Mail::sendTo($contacts, 'txbutton.app::mail.contact-form', $params, function($message) {
            $message->replyTo(post('email'), post('name'));
        });

        $this->page['success'] = true;
    }
}
