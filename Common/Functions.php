<?php
namespace Lightroom\Common\Functions;

use Exception;
use Lightroom\Common\CsrfRequestManager;

/**
 * CsrfRequestManager csrf
 * @return string
 * @throws Exception
 */
function csrf() : string 
{
    return CsrfRequestManager::loadFromDefault();
}

/**
 * CsrfRequestManager csrf_error
 * @return string
 */
function csrf_error() : string 
{
    return CsrfRequestManager::loadDefaultError();
}

/**
 * CsrfRequestManager csrf_verified
 * @return bool
 */
function csrf_verified() : bool 
{
    return CsrfRequestManager::csrfVerified();
}