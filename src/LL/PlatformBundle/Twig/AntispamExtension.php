<?php

namespace LL\PlatformBundle\Twig;

use LL\PlatformBundle\Antispam\Antispam;

class AntispamExtension extends \Twig_Extension
{
    /**
     * @var Antispam
     */
    private $llAntispam;

    public function __construct(Antispam $llAntispam)
    {
        $this->llAntispam = $llAntispam;
    }

    public function checkIfArgumentIsSpam($text)
    {
        return $this->llAntispam->isSpam($text);
    }

    public function getFunctions()
    {
        return array(
            new \Twig_SimpleFunction('checkIfSpam', array($this, 'checkIfArgumentIsSpam')),
        );
    }

    public function getName()
    {
        return 'AntiSpam';
    }
}