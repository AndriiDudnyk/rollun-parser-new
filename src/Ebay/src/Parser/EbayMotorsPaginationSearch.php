<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace Ebay\Parser;

use Ebay\ConfigProvider;
use GuzzleHttp\Psr7\ServerRequest;
use phpQuery as PhpQuery;
use Psr\Log\LoggerInterface;
use rollun\callback\Callback\Interrupter\QueueFiller;
use rollun\callback\Queues\Message;
use rollun\callback\Queues\QueueClient;
use rollun\dic\InsideConstruct;
use rollun\parser\AbstractParser;

/**
 * Parse pagination links and create new task for parsing each link
 *
 * Class EbayMotorsPaginationSearch
 * @package Ebay\Parser
 */
final class EbayMotorsPaginationSearch extends AbstractParser
{
    const MAX_TOTAL_PRODUCT = 20000;

    /** @var QueueClient */
    protected $queueClient;

    public function __construct(
        LoggerInterface $logger = null,
        QueueClient $queueClient = null
    ) {
        InsideConstruct::setConstructParams([
            'queueClient' => ConfigProvider::__NAMESPACE__ . 'searchTaskQueue',
            'logger' => LoggerInterface::class,
        ]);
    }

    const PAGINATION_URI = 'https://www.ebay.com/sch/i.html?_from=R40&_sacat=0&LH_Sold=1&_sadis=15&_stpos=59456&_fss=1'
    . '&_fsradio=%26LH_SpecificSeller%3D1&_saslop=1&_sasl=eldinisport%2Cljdpowersports%2'
    . 'C1_avec_plaisir%2Ccascade_lakes_motorsports%2Cmadpower5%2Cmxpowerplay%2Cuniversalpowersportsllc%2'
    . 'Cthe-d-zone%2Crollunlc%2Cdualsportarmory%2Cgnarlymoto-x%2Ctoyotaktmusa%2Cvitvov%2Cxelementseller%2'
    . 'Cimbodenmotorsportsllc%2Cmach3motorsports%2Cunhingedatv%2Ccorneraddiction&_sop=13&_dmd=1&LH_Complete=1&_fosrp=1 '
    . ' &_skc=productLeft&rt=nc&_pgn=currentPage';

    public const PARSER_NAME = self::class;

    /**
     * Parse ebay search page.
     * Return list of all available pages for this search phrase
     *
     * @param string $data
     * @return array
     */
    public function parse(string $data): array
    {
        $document = PhpQuery::newDocument($data);
        $productCountPerPage = $document->find('#ListViewInner > li')->count();
        $totalProductListing = $document->find('.listingscnt')->text();
        [$totalProduct,] = explode(' ', $totalProductListing);
        $totalProduct = intval(trim(str_replace(',', '', $totalProduct)));
        $totalProduct = $totalProduct > self::MAX_TOTAL_PRODUCT ? self::MAX_TOTAL_PRODUCT : $totalProduct;
        $pageCount = ceil($totalProduct / $productCountPerPage);
        $result = [];

        for ($i = 1; $i <= $pageCount; $i++) {
            $uri = str_replace('productLeft', $productCountPerPage * ($i - 1), self::PAGINATION_URI);
            $uri = str_replace('currentPage', $i, $uri);
            $result[] = $uri;
        }

        return $result;
    }

    public function canParse(string $data): bool
    {
        $document = PhpQuery::newDocument($data);

        return boolval($document->find('#ListViewInner > li')->count());
    }

    /**
     * Add task for full (real) parsing ebay search page
     *
     * @param $uris
     * @throws \rollun\utils\Json\Exception
     */
    protected function saveResult($uris)
    {
        if (!$uris) {
            throw new \InvalidArgumentException("Invalid data after search parsing");
        }

        foreach ($uris as $uri) {
            $request = new ServerRequest('GET', $uri);
            $serializedData = QueueFiller::serializeMessage($request);
            $message = new Message($serializedData);
            $this->queueClient->addMessage($message);
        }
    }

    public function __wakeup()
    {
        InsideConstruct::initWakeup([
            'queueClient' => ConfigProvider::__NAMESPACE__ . 'searchTaskQueue',
            'logger' => LoggerInterface::class,
        ]);
    }
}
