<?php namespace TxButton\App\Classes;

use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Bitcoin\Key\Deterministic\HierarchicalKeyFactory;
use BitWasp\Bitcoin\Key\Deterministic\HierarchicalKeySequence;
use BitWasp\Bitcoin\Key\Deterministic\MultisigHD;
use BitWasp\Bitcoin\Network\NetworkFactory;
use Exception;

class HdWallet
{
    protected $network = null;

    protected $xpub = null;

    protected $multisigXpubs = null;

    public function __construct($network = 'bitcoin')
    {
        $this->network = NetworkFactory::$network();
    }

    public function setXpub($xpub)
    {
        $this->xpub = $xpub;
    }

    public function setMultisigXpubs($xpubs)
    {
        $this->multisigXpubs = $xpubs;
    }

    public function addressArrayFromXpub($change = 0, $from = 0, $count = 1)
    {
        if ($this->xpub === '') {
            throw new Exception("XPUB key is not present!");
        }

        $results = [];

        $key = HierarchicalKeyFactory::fromExtended($this->xpub, $this->network);

        $paths = [];

        for ($k = 0; $k < $count; $k++){
            $paths[] = $change.'/'.($from + $k);
        }

        foreach ($paths as $path) {
            $childKey = $key->derivePath($path);

            $pubKey = $childKey->getPublicKey();

            $results[] = $pubKey->getAddress()->getAddress();
        }

        return $results;
    }

    public function addressFromXpub($path = '0/0')
    {
        if ($this->xpub === '') {
            throw new Exception("XPUB key is not present!");
        }

        $key = HierarchicalKeyFactory::fromExtended($this->xpub, $this->network);

        $childKey = $key->derivePath($path);

        $pubKey = $childKey->getPublicKey();

        return $pubKey->getAddress()->getAddress();
    }

    public function multisigAddressFromXpub($m, $path = '0/0')
    {
        if (count($this->multisigXpubs) < 2) {
            throw new Exception("XPUB keys are not present!");
        }

        $keys = [];

        foreach ($this->multisigXpubs as $xpub) {
            $keys[] = HierarchicalKeyFactory::fromExtended($xpub, $this->network);
        }

        $sequences = new HierarchicalKeySequence();
        $hd = new MultisigHD($m, 'm', $keys, $sequences, TRUE);

        $childKey = $hd->derivePath($path);

        return $childKey->getAddress()->getAddress();
    }
}
