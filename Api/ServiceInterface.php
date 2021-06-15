<?php

namespace Ingenico\Payment\Api;

interface ServiceInterface
{
    /**
     * Remove Alias.
     *
     * @api
     * @param string $alias
     * @return string
     */
    public function removeAlias($alias);
}
