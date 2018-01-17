<?php

namespace LL\UserBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class LLUserBundle extends Bundle
{
    public function getParent()
    {
        return 'FOSUserBundle';
    }
}
