<?php namespace TxButton\App;

use Backend;
use System\Classes\PluginBase;

/**
 * App Plugin Information File
 */
class Plugin extends PluginBase
{
    /**
     * Returns information about this plugin.
     *
     * @return array
     */
    public function pluginDetails()
    {
        return [
            'name'        => 'App',
            'description' => 'No description provided yet...',
            'author'      => 'TxButton',
            'icon'        => 'icon-leaf'
        ];
    }

    /**
     * Register method, called when the plugin is first registered.
     *
     * @return void
     */
    public function register()
    {

    }

    /**
     * Boot method, called right before the request route.
     *
     * @return array
     */
    public function boot()
    {

    }

    /**
     * Registers any front-end components implemented in this plugin.
     *
     * @return array
     */
    public function registerComponents()
    {
        return [
            'TxButton\App\Components\Dashboard' => 'dashboard',
            'TxButton\App\Components\UserSettings' => 'userSettings',
            'TxButton\App\Components\WalletSettings' => 'walletSettings',
            'TxButton\App\Components\PosTerminal' => 'posTerminal',
        ];
    }

    /**
     * Registers any back-end permissions used by this plugin.
     *
     * @return array
     */
    public function registerPermissions()
    {
        return []; // Remove this line to activate

        return [
            'txbutton.app.some_permission' => [
                'tab' => 'App',
                'label' => 'Some permission'
            ],
        ];
    }

    /**
     * Registers back-end navigation items for this plugin.
     *
     * @return array
     */
    public function registerNavigation()
    {
        return []; // Remove this line to activate

        return [
            'app' => [
                'label'       => 'App',
                'url'         => Backend::url('txbutton/app/mycontroller'),
                'icon'        => 'icon-leaf',
                'permissions' => ['txbutton.app.*'],
                'order'       => 500,
            ],
        ];
    }
}
