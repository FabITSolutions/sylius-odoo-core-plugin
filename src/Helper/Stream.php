<?php

/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Fab IT Sylius Odoo Core Plugin to newer
 * versions in the future.
 * Feel free to contact us once you face any issues or want to start
 * another great project.
 * You can find more information about us on https://www.fabitsolutions.in/ and write us
 * an email on contact@fabitsolutions.in
 *
 * @category  Fabitsolutions
 * @package   fabit/sylius-odoo-product-plugin
 * @author    contact@fabitsolutions.in
 * @copyright 2023 Fab IT Solutions
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Fabit\SyliusOdooCorePlugin\Helper;


use Ripcord\Client\Contracts\Transport;
use Ripcord\Exceptions\TransportException;
use Ripcord\Ripcord;

/**
 * This class implements the Ripcord_Transport interface using PHP streams.
 */
class Stream implements Transport
{
    /**
     * A list of stream context options.
     */
    private $options = [];

    /**
     * Contains the headers sent by the server.
     */
    public $responseHeaders = null;

    /**
     * This is the constructor for the Ripcord_Transport_Stream class.
     *
     * @param array $contextOptions Optional. An array with stream context options.
     */
    public function __construct($contextOptions = null)
    {
        if (isset($contextOptions)) {
            $this->options = $contextOptions;
        }
    }

    /**
     * This method posts the request to the given url.
     *
     * @param string $url     The url to post to.
     * @param string $request The request to post.
     *
     * @throws TransportException (ripcord::cannotAccessURL) when the given URL cannot be accessed for any reason.
     *
     * @return string The server response
     */
    public function post($url, $request)
    {
        $options = array_merge_recursive(
            $this->options,
            [
                'http' => [
                    'method'  => 'POST',
                    'header'  => 'Content-Type: text/xml',
                    'content' => $request,
                ],
                'wrapper' => ['notification' => 'callback'],
                "ssl" =>["verify_peer"=>false,"verify_peer_name"=>false,],
            ]
        );
        $context = stream_context_create($options);
        $result = file_get_contents($url, false, $context);
        //$this->responseHeaders = $http_response_header;
        if (!$result) {
            throw new TransportException(
                'Could not access '.$url,
                Ripcord::CANNOT_ACCESS_URL
            );
        }

        return $result;
    }
}