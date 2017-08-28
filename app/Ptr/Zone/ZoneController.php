<?php

namespace Packages\Rdns\App\Ptr\Zone;

use App\Api;
use Illuminate\Http\Request;
use Packages\Rdns\App\Ptr\PtrService;
use Packages\Rdns\App\Ptr\PtrFilterService;
use Packages\Rdns\App\Ptr\PtrRepository;

/**
 * Handle HTTP requests regarding PTR Zone.
 */
class ZoneController
    extends Api\Controller
{
    /**
     * @var PtrFilterService
     */
    private $filter;

    /**
     * @var PtrRepository
     */
    private $items;

    /**
     * @var Api\ApiAuthService
     */
    private $auth;

    /**
     * @var PtrService
     */
    private $ptr;

    /**
     * ZoneController constructor.
     *
     * @param PtrFilterService $filter
     * @param PtrRepository $items
     * @param Api\ApiAuthService $auth
     * @param PtrService $ptr
     */
    public function __construct(
        PtrFilterService $filter,
        PtrRepository $items,
        Api\ApiAuthService $auth,
        PtrService $ptr
    ) {
        $this->filter = $filter;
        $this->items = $items;
        $this->auth = $auth;
        $this->ptr = $ptr;
    }

    public function store(Request $request)
    {
        $this->filter->viewable($this->items->query());

        $this->auth->only([
            'admin',
            'integration',
        ]);

        $file = $request->file('file')->openFile();
        $ptrs = 0;
        $findPtr = '/([0-9]+)\s+IN\s+PTR\s+(.+)/S';
        $findOrigin = '/\$ORIGIN ([0-9]+).([0-9]+).([0-9]+).in-addr.arpa/S';
        $zoneIpStart = null;

        while (!$file->eof()) {
            $line = $file->fgets();

            if (!$zoneIpStart && preg_match($findOrigin, $line, $matches)) {
                $zoneIpStart = implode([
                    $matches[3],
                    $matches[2],
                    $matches[1],
                    '',
                ], '.');
            }

            if ($zoneIpStart && preg_match($findPtr, $line, $matches)) {
                $ptrs++;
                $ip = $zoneIpStart . $matches[1];
                $this->ptr->create($ip, $matches[2]);
            }
        }

        if (!$zoneIpStart) {
            return response()->error('Could not find $ORIGIN line. Please make sure the $ORIGIN is included.');
        }

        return response()->success(sprintf(
            'Zone imported: %d PTRs added.',
            $ptrs
        ));
    }
}
