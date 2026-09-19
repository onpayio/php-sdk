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
        $this->total = DataReader::requireInt($data, 'total');
        $this->totalPages = DataReader::requireInt($data, 'total_pages');
        $links = DataReader::arrayOr($data, 'links');
        $this->nextUrl = DataReader::stringOrNull($links, 'next');
        $this->previousUrl = DataReader::stringOrNull($links, 'previous');
    }

    public int $total;

    public int $totalPages;

    public ?string $nextUrl = null;

    public ?string $previousUrl = null;
}
