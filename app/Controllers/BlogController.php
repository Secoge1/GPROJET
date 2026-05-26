<?php
/**
 * GLOBALO - Blog SEO (liste et article par slug)
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Router;
use App\Core\Auth;
use App\Models\BlogPostModel;
use App\Models\BlogCategoryModel;
use App\Models\BlogTagModel;
use App\Services\SeoService;

class BlogController extends Controller
{
    private BlogPostModel $postModel;
    private BlogCategoryModel $categoryModel;
    private BlogTagModel $tagModel;

    public function __construct(Router $router)
    {
        parent::__construct($router);
        $this->postModel = new BlogPostModel();
        $this->categoryModel = new BlogCategoryModel();
        $this->tagModel = new BlogTagModel();
    }

    public function index(): void
    {
        $categoryId = isset($_GET['category']) ? (int) $_GET['category'] : null;
        if ($categoryId <= 0) {
            $categoryId = null;
        }
        $posts = $this->postModel->getPublishedList($categoryId, 20);
        $categories = $this->categoryModel->getAll();
        $baseUrl = rtrim(BASE_URL ?? '', '/');
        $this->render('index', [
            'pageTitle' => 'Blog — GLOBALO',
            'seo' => SeoService::forPage('default', [
                'title' => 'Blog — Conseils et actualités | GLOBALO',
                'description' => 'Conseils développement, recrutement d\'experts, astuces. Le blog Globalo.',
                'canonical' => $baseUrl . '/blog',
            ]),
            'user' => Auth::check() ? ['id' => Auth::id(), 'role' => Auth::role()] : null,
            'posts' => $posts,
            'categories' => $categories,
            'filtre_category' => $categoryId,
        ]);
    }

    public function show(string $slug): void
    {
        $post = $this->postModel->getBySlug($slug);
        if (!$post) {
            header('HTTP/1.0 404 Not Found');
            $this->render('errors/404', ['pageTitle' => 'Article introuvable']);
            return;
        }
        $tags = $this->postModel->getTagsForPost((int) $post['id']);
        $related = [];
        if (!empty($post['category_id'])) {
            $related = $this->postModel->getRelatedByCategory((int) $post['category_id'], (int) $post['id'], 3);
        }
        $baseUrl = rtrim(BASE_URL ?? '', '/');
        $pageUrl = $baseUrl . '/blog/' . $slug;
        \App\Models\GrowthPageViewModel::recordView('blog', (int) $post['id']);
        $this->render('show', [
            'pageTitle' => $post['title'] . ' — Blog GLOBALO',
            'seo' => SeoService::forPage('blog_post', [
                'title' => $post['title'],
                'meta_description' => $post['meta_description'] ?? mb_substr(strip_tags($post['body']), 0, 160),
                'description' => $post['meta_description'] ?? strip_tags($post['body']),
                'canonical' => $pageUrl,
                'image' => null,
                'published_at' => $post['published_at'] ?? null,
            ]),
            'user' => Auth::check() ? ['id' => Auth::id(), 'role' => Auth::role()] : null,
            'post' => $post,
            'tags' => $tags,
            'related' => $related,
            'pageUrl' => $pageUrl,
        ]);
    }
}
