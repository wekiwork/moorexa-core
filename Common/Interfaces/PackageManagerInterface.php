<?php
namespace Lightroom\Common\Interfaces;

use Lightroom\Core\{
    Payload, BootCoreEngine
};
/**
 * @package Package manager interface
 */
interface PackageManagerInterface
{
    /**
     * @method PackageManagerInterface register payload for package manager
     * @param Payload $payload
     * @param BootCoreEngine $engine
     */
    public function registerPayload(Payload &$payload, BootCoreEngine $engine);
}