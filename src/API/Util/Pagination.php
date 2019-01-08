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
        $this->total = isset($data['total']) ? $data['total'] :  null;
        $this->totalPages = isset($data['total_pages']) ? $data['total_pages'] : null;
        $this->nextUrl = isset($data['links']['next']) ? $data['links']['next'] : null;
        $this->previousUrl = isset($data['links']['previous']) ? $data['links']['previous'] : null;
    }
    /**
     * @var int
     */
    public $total;
    /**
     * @var int
     */
    public $totalPages;
    /**
     * @var string
     */
    public $nextUrl;
    /**
     * @var string
     */
    public $previousUrl;
}

