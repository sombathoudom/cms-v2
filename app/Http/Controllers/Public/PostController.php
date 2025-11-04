<?php

namespace App\Http\Controllers\Public;

use App\DTOs\ContentFilterData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Web\PostIndexRequest;
use App\Services\ContentDeliveryService;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class PostController extends Controller
{
    public function __construct(private readonly ContentDeliveryService $service)
    {
    }

    public function home(PostIndexRequest $request): View
    {
        $filters = ContentFilterData::fromArray($request->validated());
        $posts = $this->service->listPosts($filters, 6);
        $archives = $this->service->getArchiveSummary();

        Log::info('public.posts.home', [
            'filters' => $request->validated(),
            'count' => count($posts->items()),
        ]);

        return view('posts.index', [
            'posts' => $posts,
            'archives' => $archives,
            'title' => 'Latest posts',
            'searchTerm' => $filters->search,
        ]);
    }

    public function index(PostIndexRequest $request): View
    {
        $filters = ContentFilterData::fromArray($request->validated());
        $perPage = (int) ($request->validated()['per_page'] ?? 10);
        $posts = $this->service->listPosts($filters, $perPage);
        $archives = $this->service->getArchiveSummary();

        Log::info('public.posts.index', [
            'filters' => $request->validated(),
            'count' => count($posts->items()),
        ]);

        return view('posts.index', [
            'posts' => $posts,
            'archives' => $archives,
            'title' => 'Blog',
            'searchTerm' => $filters->search,
        ]);
    }

    public function archive(Request $request, int $year, ?int $month = null): View
    {
        if ($month !== null && ($month < 1 || $month > 12)) {
            throw new NotFoundHttpException('Invalid archive month.');
        }

        $filters = ContentFilterData::fromArchive($year, $month);
        $perPage = (int) min(50, max(1, (int) $request->query('per_page', 10)));
        $posts = $this->service->listPosts($filters, $perPage);
        $archives = $this->service->getArchiveSummary();

        Log::info('public.posts.archive', [
            'year' => $year,
            'month' => $month,
            'count' => count($posts->items()),
        ]);

        $title = $month
            ? sprintf('Archive %s %s', Str::ucfirst(Carbon::createFromDate($year, $month, 1)->translatedFormat('F')), $year)
            : sprintf('Archive %s', $year);

        return view('posts.index', [
            'posts' => $posts,
            'archives' => $archives,
            'title' => $title,
            'searchTerm' => null,
        ]);
    }

    public function show(Request $request, string $slug): View
    {
        $post = $this->service->getPostBySlug($slug);
        $this->service->recordView($post, $request, 'web');

        Log::info('public.posts.show', [
            'slug' => $slug,
            'content_id' => $post->id,
        ]);

        return view('posts.show', [
            'post' => $post,
        ]);
    }
}
