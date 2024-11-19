<?php

namespace Grav\Plugin\Pushy;

use Exception;
use Grav\Common\Grav;
use Grav\Common\Page\Page;
use Grav\Common\Page\Pages;
use Grav\Common\Uri;
use Grav\Plugin\Pushy\Data\ChangedItem;
use Grav\Plugin\Pushy\Data\GitActionResponse;

abstract class GitItemType
{
    const Page = 'page';
    const Module = 'module';
    const Config = 'config';
    const Other = 'other';
}

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
        $this->uri = $this->grav['uri'];
        $this->repo = new PushyRepo();
    }

    /**
     * Handle async requests send by javascript injected by Pushy into Admin panel.
     *
     * The response specific to each type of request, or null if request is not from Pushy.
     * @return GitActionResponse|ChangedItem[]|null
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
     * 
     * @return ChangedItem[]
     */
    private function handleReadTask()
    {
        /** @var Pages */
        $pages = $this->grav['pages'];
        $pages->enablePages();

        $adminRoute = $this->grav['config']->get('plugins.admin.route');

        /** var ChangedItem[] */
        $changedItems = [];

        $statusItems = $this->repo->getChangedItems();

        if ($statusItems) {
            foreach ($statusItems as $item) {
                if ($this->isPage($item)) {
                    $changedItems[] = $this->addChangedPage($item, $pages, $adminRoute);
                } elseif ($this->isConfig($item)) {
                    $changedItems[] = $this->addChangedConfig($item);
                } else {
                    $changedItems[] = $this->addChangedOther($item, $pages->baseUrl());
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
        return str_starts_with($gitItem['path'], 'pages/') && str_ends_with($gitItem['path'], '.md');
    }

    /**
     * Check if git item is a Config file
     * 
     * @param array{working: string, index: string, path: string} $gitItem
     * @return bool
     */
    private function isConfig(array $gitItem): bool
    {
        return str_starts_with($gitItem['path'], 'config/');
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
        if ($gitItem['index'] === 'D') {
            return new ChangedItem($gitItem, GitItemType::Page);
        }

        $pageFilePath = GRAV_WEBROOT . DS . GRAV_USER_PATH . DS . $gitItem['path'];
        // Remove filename from path
        $pageFolderPath = implode('/', array_slice(explode('/', $pageFilePath), 0, -1));

        /** @var Page */
        $page = $pages->get($pageFolderPath);

        $pageTitle = $page->title();
        $pageAdminUrl = $pages->baseUrl() . "$adminRoute/pages{$page->rawRoute()}";
        $pageSiteUrl = $page->url();
        $type = GitItemType::Page;

        if ($page->isModule()) {
            $pageTitle .= ' (module)';
            $pageSiteUrl = $page->parent()->url();
            $type = GitItemType::Module;
        }

        return new ChangedItem($gitItem, $type, $pageTitle, $pageAdminUrl, $pageSiteUrl);
    }

    /**
     * Create ChangedItem for anything other than Page
     * 
     * @param array{working: string, index: string, path: string} $gitItem
     */
    private function addChangedConfig(array $gitItem): ChangedItem
    {
        if ($gitItem['index'] === 'D') {
            return new ChangedItem($gitItem, GitItemType::Config);
        }

        $itemFilePath = $gitItem['path'];
        $itemAdminUrl = $itemFilePath;

        if (str_starts_with($itemAdminUrl, 'config/plugins')) {
            // Remove leading 'config'
            $itemAdminUrl = substr($itemAdminUrl, 7);
        }

        // Remove file extension
        $itemAdminUrl = preg_replace("/^(.*)\.[^.]+$/", "$1", $itemAdminUrl);

        return new ChangedItem($gitItem, GitItemType::Config, $itemFilePath, $itemAdminUrl);
    }

    /**
     * Create ChangedItem for non specified type
     * 
     * @param array{working: string, index: string, path: string} $gitItem
     */
    private function addChangedOther(array $gitItem, string $siteBaseUrl): ChangedItem
    {
        $pathParts = explode('.', $gitItem['path']);
        $fileType = array_pop($pathParts);

        $siteUrl = '';

        // TODO: Should be a dynamic check + handle other media types
        if (in_array($fileType, ['jpg', 'jpe', 'jpeg', 'png', 'webp', 'avif'])) {
            $siteUrl = "$siteBaseUrl/user/{$gitItem['path']}";
        }

        return new ChangedItem($gitItem, GitItemType::Other, '', '', $siteUrl);
    }

    /**
     * Handle request to commit array of changed pages.
     */
    private function handlePublishTask(): GitActionResponse
    {
        $taskData = file_get_contents('php://input');

        if ($taskData === false) {
            $log = $this->grav['log'];
            $jsonTaskData = json_encode($taskData);
            $log->addCritical("Pushy - No valid data submitted: $jsonTaskData");

            return new GitActionResponse(
                false,
                $this->translate('PUBLISH_INVALID_DATA_SUBMITTED'),
            );
        }

        /** @var array{items: array<array{index: string, path: string, orig_path: string}>, message: string} */
        $pages = json_decode($taskData, true);

        try {
            foreach ($pages['items'] as $item) {
                if ($item['index'] === 'D') {
                    $this->repo->removeFile($item['path']);
                } else if ($item['index'] === 'R') {
                    $this->repo->removeFile($item['orig_path']);
                    $this->repo->addFile($item['path']);
                } else {
                    $this->repo->addFile($item['path']);
                }
            }

            $this->repo->commit($pages['message']);
        } catch (Exception $e) {
            $log = $this->grav['log'];
            $log->addCritical($e->getMessage() . ' - Trace: ' . $e->getTraceAsString());

            return new GitActionResponse(
                false,
                $this->translate('PUBLISH_EXCEPTION'), // FIXME
            );
        }

        return new GitActionResponse(
            true,
            $this->translate('PUBLISH_SUCCESS'),
        );
    }

    private function translate(string $key, ?string $arg = null) : string {
        $prefix = 'PLUGIN_PUSHY';

        $user = $this->grav['user'];
        $language = $user['language'];

		return $this->grav['language']->translate(["$prefix.$key", $arg], [$language]);
    }
}
