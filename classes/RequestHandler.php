<?php

namespace Grav\Plugin\Pushy;

use Exception;
use Grav\Common\Grav;
use Grav\Common\Uri;
use Grav\Plugin\Pushy\Data\ChangedItem;
use Grav\Plugin\Pushy\Data\ChangedItems;
use Grav\Plugin\Pushy\Data\GitActionResponse;

/**
 * Handles are Pushy requests.
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class RequestHandler
{
    private Grav $grav;
    private Uri $uri;

	protected PushyRepo $repo;

	protected string $admin_route = 'publish';

    public function __construct()
    {
        $this->grav = Grav::instance();
        $this->uri = $this->grav['uri'];
        $this->config = $this->grav['config'];

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
            case 'otherRequest':
                $response = $this->handleOtherTask();
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
        $gitIndex = $this->repo->statusSelect();

        $changedPages = new ChangedItems();

        if ($gitIndex) {
            foreach($gitIndex as $index) {
                $changedPages[$index['path']] = new ChangedItem($index);
            }
        }

        return $changedPages;
    }

    /**
     * Handle request to commit array of changed pages.
     */
    private function handlePublishTask(): GitActionResponse
    {
        $pages = json_decode(file_get_contents('php://input'), true);
        
        // TODO Handle commit

        return new GitActionResponse(
            true,
            'Items have been published.',
       );
    }

    /**
     * Revert a list changed pages in Git.
     */
    private function handleOtherTask(): GitActionResponse
    {
        $pages = json_decode(file_get_contents('php://input'), true);

        // TODO Handle other request

        return new GitActionResponse(
            true,
            'Request has been processed successfully',
       );
    }
}
