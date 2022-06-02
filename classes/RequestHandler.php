<?php

namespace Grav\Plugin\Pushy;

use Exception;
use Grav\Common\Grav;
use Grav\Common\Page\Page;
use Grav\Common\Page\Pages;
use Grav\Common\Uri;
use Grav\Plugin\Pushy\Data\ChangedItem;
use Grav\Plugin\Pushy\Data\ChangedItems;
use Grav\Plugin\Pushy\Data\GitActionResponse;

/**
 * Handles all Pushy requests.
 */
class RequestHandler
{
    protected Grav $grav;
    protected Uri $uri;

    protected PushyRepo $repo;

    public function __construct()
    {
        $this->grav = Grav::instance();
        $this->uri = $this->grav['uri']; /** @phpstan-ignore-line */
        $this->repo = new PushyRepo();
    }

    /**
     * Handle async requests send by javascript injected by Pushy into Admin panel.
     *
     * @return GitActionResponse|ChangedItems|null
     * The response specific to each type of request, or null if request is not from Pushy.
     */
    public function handleRequest()
    {
        if (!$this->isPushyRequest()) {
            return null;
        }

        $task = $this->uri->param('pushy');

        switch ($task) {
            case 'readItems':
                $response = $this->handleReadTask();
                break;
            case 'publishItems':
                $response = $this->handlePublishTask();
                break;
            default:
                throw new Exception("Unkown request '$task'.");
        }

        return $response;
    }

    /**
     * Check if request originated from front-end javascript from Pushy.
     */
    private function isPushyRequest(): bool
    {
        return $this->uri->param('pushy') !== false;
    }

    /**
     * Read status of Git and return changed pages.
     */
    private function handleReadTask(): ChangedItems
    {
        /** @var Pages */
        $pages = $this->grav['pages'];
        $pages->enablePages();

        $adminRoute = $this->grav['config']->get('plugins.admin.route');

        $changedItems = new ChangedItems();

        $gitIndex = $this->repo->statusSelect();

        if ($gitIndex) {
            foreach ($gitIndex as $index) {
                if ($this->isPage($index)) {
                    $changedItems[$index['path']] = $this->createChangedItemPage($index, $pages, $adminRoute);
                } else {
                    $changedItems[$index['path']] = new ChangedItem($index);
                }
            }
        }

        return $changedItems;
    }

    /**
     * Check if git item is a Page
     * 
     * @param array{working: string, index: string, path: string} $gitItem
     * @return bool
     */
    private function isPage(array $gitItem): bool
    {
        return str_starts_with($gitItem['path'], 'pages/');
    }

    /**
     * Create ChangedItem for page
     * 
     * @param array{working: string, index: string, path: string} $gitItem
     * @param Pages $pages Contains all pages of site
     * @param string $adminRoute Url of Admin
     */
    private function createChangedItemPage(array $gitItem, Pages $pages, string $adminRoute): ChangedItem
    {
        $pageFilePath = GRAV_WEBROOT . DS . GRAV_USER_PATH . DS . $gitItem['path'];
        $pageFolderPath = implode('/', array_slice(explode('/', $pageFilePath), 0, -1));

        /** @var Page */
        $page = $pages->get($pageFolderPath);

        $pageTitle = $page->title();
        $pageAdminUrl = $pages->baseUrl() . "$adminRoute/pages{$page->rawRoute()}";
        $pageSiteUrl = $page->url();

        return new ChangedItem($gitItem, $pageTitle, $pageAdminUrl, $pageSiteUrl);
    }

    /**
     * Handle request to commit array of changed pages.
     */
    private function handlePublishTask(): GitActionResponse
    {
        $taskData = file_get_contents('php://input');

        if ($taskData === false) {
            return new GitActionResponse(
                false,
                "No valid data submitted for task 'Publish'",
            );
        }

        /** @var array{paths: string[], message: string} */
        $pages = json_decode($taskData, true);

        try {
            $paths = implode(' ', $pages['paths']);
            $this->repo->stageFiles($paths);
            $this->repo->commit($pages['message']);
        } catch (Exception $e) {
            return new GitActionResponse(
                false,
                "There was an error publishing: \"{$e->getMessage()}\"", // FIXME
            );
        }

        return new GitActionResponse(
            true,
            'Items have been published.',
        );
    }
}
