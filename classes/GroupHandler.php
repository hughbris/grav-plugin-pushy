<?php

namespace Grav\Plugin\Pushy;

use Exception;
use Grav\Common\Grav;
use Grav\Common\Filesystem\Folder;
use Grav\Common\Utils;
use RocketTheme\Toolbox\File\File;
use Symfony\Component\Yaml\Yaml;

/**
 * Handles creation of group 'publisher'.
 */
class GroupHandler
{
    protected Grav $grav;
    protected string $admin_route = 'publish';

    public function __construct()
    {
        $this->grav = Grav::instance();
    }

    /**
     * Add groups which are authorized to use Pushy in Admim
     */
    public function createGroups()
    {
        $groupNames = $this->grav['config']->get('plugins.pushy.authorization.groups', []);

        if (is_string($groupNames)) {
            $groupNames = [$groupNames];
        }

        if (count($groupNames) === 0) {
            return;
        }

        $existingGroups = $this->getGroups();
        $newGroups = [];

        foreach ($groupNames as $name) {
            $this->getGroups();
            if ($this->hasConflictingName($name, $existingGroups)) {
                throw new Exception("Pushy: Group '$name' has conflict existing group.");
            }

            if (isset($existingGroups[$name])) {
                continue;
            }

            $newGroups[$name] = [
                'access' => [
                    'site' => [
                        'login' => true,
                    ],
                    'admin' => [
                        'publisher' => true,
                    ]
                ],
                'description' => 'Group granted access to use Pushy',
                'enabled' => true,
            ];
        }

        $this->saveGroups($newGroups);
    }

    /**
     * Get array of groups that have been defined.
     * 
     * @return array
     */
    private function getGroups(): array
    {
        $filename = $this->getGroupFilePath();
        $file = File::instance($filename);

        if (file_exists($filename)) {
            return Yaml::parse($file->content()) ?? [];
        }

        return [];
    }

    /**
     * Check if group exsist that has not been created with access.admin.publisher.
     */
    private function hasConflictingName(string $name, array $groups)
    {
        return isset($groups[$name]) && !isset($groups[$name]['access']['admin']['publisher']);
    }

    /**
     * Get path to file config://groups.yaml
     */
    private function getGroupFilePath()
    {
        return Utils::fullPath('config://') . '/groups.yaml';
    }

    /**
     * Save group 'pushy' into config://groups.yaml
     */
    public function saveGroups(array $newGroups): bool
    {
        $filename = $this->getGroupFilePath();
        $file = File::instance($filename);

        $groups = [];

        if (file_exists($filename)) {
            $groups = Yaml::parse($file->content()) ?? [];
        }

        foreach ($newGroups as $name => $details) {
            $groups[$name] = $details;
        }

        $yaml = Yaml::dump($groups, 5, 4);
        $file->save($yaml);

        return true;
    }
}
