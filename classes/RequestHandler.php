<?php

namespace Grav\Plugin\Pushy;

use Exception;
use Grav\Common\Grav;
use Grav\Common\Uri;
use Grav\Plugin\Pushy\Data\ChangedPage;
use Grav\Plugin\Pushy\Data\ChangedPages;
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
     * @return GitActionResponse|ChangedPages|null
     * The response specific to each type of request, or null if request is not from Pushy.
     */
    public function handleRequest()
    {
        if (!$this->isPushyRequest()) {
            return null;
        }

        $request = $this->uri->param('pushy');

        switch ($request) {
            case 'readPages':
                $response = $this->handleReadPages();
                break;
            case 'commitPages':
                $response = $this->handleCommitPage();
                break;
            case 'revertPages':
                $response = $this->handleRevertPage();
                break;
            case 'otherRequest':
                $response = $this->handleOtherRequest();
                break;
            default:
                throw new Exception("Unkown request '$request'.");
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
    private function handleReadPages(): ChangedPages
    {
        $gitIndex = $this->repo->statusSelect();

        $changedPages = new ChangedPages();

        if ($gitIndex) {
            foreach($gitIndex as $index) {
                $changedPages[$index['path']] = new ChangedPage($index);
            }
        }

        return $changedPages;
    }

    /**
     * Handle request to commit array of changed pages.
     */
    private function handleCommitPage(): GitActionResponse
    {
        $pages = json_decode(file_get_contents('php://input'), true);
        
        // TODO Handle commit

        return new GitActionResponse(
            true,
            'Page has been committed',
       );
    }

    /**
     * Revert a list changed pages in Git.
     */
    private function handleRevertPage(): GitActionResponse
    {
        $pages = json_decode(file_get_contents('php://input'), true);

        // TODO Handle revert

        return new GitActionResponse(
            true,
            'Page has been reverted',
       );
    }

    /**
     * Revert a list changed pages in Git.
     */
    private function handleOtherRequest(): GitActionResponse
    {
        $pages = json_decode(file_get_contents('php://input'), true);

        // TODO Handle other request

        return new GitActionResponse(
            true,
            'Request has been processed successfully',
       );
    }
}
