<?php
namespace DuplicatorPro\Guzzle\Http\Message\Header;

defined("ABSPATH") or die("");

/**
 * Interface for creating headers
 */
interface HeaderFactoryInterface
{
    /**
     * Create a header from a header name and a single value
     *
     * @param string $header Name of the header to create
     * @param string $value  Value to set on the header
     *
     * @return HeaderInterface
     */
    public function createHeader($header, $value = null);
}
