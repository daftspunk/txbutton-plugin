<?php namespace TxButton\App\Classes;

use Mail;
use Event;
use TxButton\App\Models\Sale as SaleModel;
use TxButton\App\Classes\IpnManager;
use Carbon\Carbon;
use ApplicationException;
use Exception;

/**
 * Worker class, engaged by the automated worker
 */
class RobotWorker
{
    use \October\Rain\Support\Traits\Singleton;

    /**
     * @var TxButton\App\Classes\IpnManager
     */
    protected $ipnManager;

    /**
     * @var bool There should be only one task performed per execution.
     */
    protected $isReady = true;

    /**
     * @var string Processing message
     */
    protected $logMessage = 'There are no outstanding activities to perform.';

    /**
     * Initialize this singleton.
     */
    protected function init()
    {
        $this->ipnManager = IpnManager::instance();
    }

    /*
     * Process all tasks
     */
    public function process()
    {
        $this->isReady && $this->processIpnNotifications();
        $this->isReady && $this->processPendingBalances();

        return $this->logMessage;
    }

    /**
     * Check sale wallet balances, marks as paid or abandoned
     */
    public function processPendingBalances()
    {
        $sales = SaleModel::applyPendingBalances()
            ->orderBy('checked_at', 'asc')
            ->limit(10)
        ;

        $sales = $sales->get();

        /*
         * If already checked in the last 5 mins, don't check again
         */
        $checkThreshold = Carbon::now()->subMinutes(5);

        $sales = $sales->filter(function($sale) use ($checkThreshold) {
            $tooSoon = $sale->checked_at > $checkThreshold;
            return !$tooSoon;
        });

        /*
         * Check for something to do
         */
        $countAbandoned = 0;
        if (!$countChecked = $sales->count()) {
            return;
        }

        /*
         * Immediately mark as checked to prevent multiple threads
         */
        SaleModel::whereIn('id', $sales->lists('id'))
            ->update(['checked_at' => Carbon::now()])
        ;

        foreach ($sales as $sale) {
            if ($sale->isAbandoned()) {
                $sale->markAbandoned();
                $countAbandoned++;
            }
            else {
                try {
                    $sale->checkBalance();
                }
                catch (Exception $ex) {
                    traceLog('Check balance failed for sale #'.$sale->id);
                    traceLog($ex);
                }
            }
        }

        $this->logActivity(sprintf(
            'Checked %s sales, abandoned %s sales.',
            $countChecked,
            $countAbandoned
        ));
    }

    /**
     * Check sale wallet balances, marks as paid or abandoned
     */
    public function processIpnNotifications()
    {
        $sale = SaleModel::applyIpnUnsent()->first();

        /*
         * Check for something to do
         */
        if (!$sale) {
            return;
        }

        $sale->is_ipn_sent = true;
        $sale->save();

        $this->ipnManager->sendIpn($sale);

        $this->logActivity(sprintf(
            'Send IPN for sale #%s.',
            $sale->id
        ));
    }

    /**
     * Called when activity has been performed.
     */
    protected function logActivity($message)
    {
        $this->logMessage = $message;
        $this->isReady = false;
    }
}
