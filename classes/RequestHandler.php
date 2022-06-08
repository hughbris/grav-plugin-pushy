<?php

namespace Grav\Plugin\Pushy;

use Exception;
use Grav\Common\Grav;
use Grav\Common\Inflector;
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

        $gitItems = $this->repo->statusSelect();

        if ($gitItems) {
            foreach ($gitItems as $item) {
                if ($this->isPage($item)) {
                    $changedItems[$item['path']] = $this->addChangedPage($item, $pages, $adminRoute);
                } else {
                    $changedItems[$item['path']] = $this->addChangedOther($item);
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
    private function addChangedPage(array $gitItem, Pages $pages, string $adminRoute): ChangedItem
    {
        $pageFilePath = GRAV_WEBROOT . DS . GRAV_USER_PATH . DS . $gitItem['path'];
        // Remove filename from path
        $pageFolderPath = implode('/', array_slice(explode('/', $pageFilePath), 0, -1));

        /** @var Page */
        $page = $pages->get($pageFolderPath);

        $isPage = true;
        $pageTitle = $page->title();
        $pageAdminUrl = $pages->baseUrl() . "$adminRoute/pages{$page->rawRoute()}";

        if ($page->isModule()) {
            $anchor = Inflector::hyphenize($page->menu());
            $pageSiteUrl = implode('/', array_slice(explode('/', $page->url()), 0, -1)) . "/#$anchor";
        } else {
            $pageSiteUrl = $page->url();
        }

        return new ChangedItem($gitItem, $isPage, $pageTitle, $pageAdminUrl, $pageSiteUrl);
    }

    /**
     * Create ChangedItem for anything other then Page
     * 
     * @param array{working: string, index: string, path: string} $gitItem
     */
    private function addChangedOther(array $gitItem): ChangedItem
    {
        if (str_starts_with($gitItem['path'], 'config/plugins')) {
            // Remove '/config'
            $itemFilePath = substr($gitItem['path'], 7);
        } else {
            $itemFilePath = $gitItem['path'];
        }

        // Remove file extension
        $itemAdminUrl = preg_replace("/^(.*)\.[^.]+$/", "$1", $itemFilePath);

        return new ChangedItem($gitItem, false, '', $itemAdminUrl);
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
