<?php
namespace OnPay\API\Util;

class Pagination
{
    /**
     * @internal Shall not be used outside library
     * Pagination constructor.
     * @param array $data
     */
    public function __construct(array $data)
    {
        $this->total = DataReader::intOrNull($data, 'total');
        $this->totalPages = DataReader::intOrNull($data, 'total_pages');
        $links = DataReader::arrayOr($data, 'links');
        $this->nextUrl = DataReader::stringOrNull($links, 'next');
        $this->previousUrl = DataReader::stringOrNull($links, 'previous');
    }

    /**
     * @var int|null
     */
    public $total;
    /**
     * @var int|null
     */
    public $totalPages;
    /**
     * @var string|null
     */
    public $nextUrl;
    /**
     * @var string|null
     */
    public $previousUrl;
}
