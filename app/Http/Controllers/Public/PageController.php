<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Services\ContentDeliveryService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PageController extends Controller
{
    public function __construct(private readonly ContentDeliveryService $service)
    {
    }

    public function show(Request $request, string $slug): View
    {
        $page = $this->service->getPageBySlug($slug);
        $this->service->recordView($page, $request, 'web');

        Log::info('public.pages.show', [
            'slug' => $slug,
            'content_id' => $page->id,
        ]);

        return view('pages.show', [
            'page' => $page,
        ]);
    }
}
