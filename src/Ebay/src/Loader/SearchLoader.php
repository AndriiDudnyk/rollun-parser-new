<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace Ebay\Loader;

use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Cookie\SetCookie;
use GuzzleHttp\Exception\RequestException;
use Psr\Http\Message\ServerRequestInterface;

class SearchLoader extends BaseLoader
{
    const REDIRECT_URI = 'https://www.ebay.com/sch/FindingCustomization/'
    . '?_fcdm=1&_fcss=12&_fcps=3&_fcippl=2&_fcso=1&_fcpd=1&_fcsbm=1&_pppn=v3'
    . '&_fcpe=7%7C5%7C3%7C2%7C4&_fcie=1%7C36&_fcse=10%7C42%7C43&_fcsp=';

    public function __invoke(ServerRequestInterface $request)
    {
        $proxy = $this->proxyDataStore->read(self::RANDOM_PROXY_ID);

        if (!$proxy) {
            throw new \RuntimeException("Can't fetch proxies");
        }

        $request = $this->withUserAgent($request);
        $response = $this->getClient()->send($request, ['proxy' => $proxy['proxy']]);
        $uri = self::REDIRECT_URI . urlencode($request->getUri()->__toString());
        $cookies = [];

        foreach ($response->getHeader('Set-Cookie') as $cookie) {
            $cookie = SetCookie::fromString($cookie);
            $cookies[$cookie->getName()] = $cookie->getValue();
        }

        $startTime = new \DateTime();

        try {
            $this->logger->debug('Sent http request using Guzzlehttp', [
                'uri' => $request->getUri()->__toString(),
                'proxy' => $proxy,
                'start_time' => date('d.m H:i:s'),
            ]);

            $request = $this->withUserAgent($request);
            $response = $this->getClient()->request('GET', $uri, [
                'cookies' => CookieJar::fromArray($cookies, '.ebay.com'),
                'proxy' => $proxy['proxy']
            ]);

            $this->logger->debug('Fetching http response using Guzzlehttp', [
                'uri' => $request->getUri()->__toString(),
                'proxy' => $proxy,
                'end_time' => date('d.m H:i:s'),
            ]);
        } catch (RequestException $e) {
            $response = $e->getResponse();
            $this->logger->error('Failed to fetch http response using Guzzlehttp', [
                'exception' => $e,
                'uri' => $request->getUri()->__toString(),
                'proxy' => $proxy,
            ]);
        }

        $endTime = new \DateTime();
        $proxy['rating'] = $this->createRating($response, $startTime, $endTime);
        $this->proxyDataStore->update($proxy);

        if ($this->responseValidator->isValid($response)) {
            $this->saveDocument($response, $request);
        } else {
            throw new \RuntimeException("Response is not valid. {$this->responseValidator->getMessages()}");
        }
    }
}
