<?php

/**
 * @package     Tide Table
 * @subpackage  plg_squidform_tidetable
 * @license     GNU General Public License version 2 or later
 */

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Installer\InstallerAdapter;
use Joomla\CMS\Installer\InstallerScriptInterface;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;

/**
 * On install and update: make the Event categories and Events forms if
 * they aren't there yet (an existing form, and its answers, is never touched).
 */
return new class () implements InstallerScriptInterface {
    public function install(InstallerAdapter $adapter): bool
    {
        return true;
    }

    public function update(InstallerAdapter $adapter): bool
    {
        return true;
    }

    public function uninstall(InstallerAdapter $adapter): bool
    {
        return true;
    }

    public function preflight(string $type, InstallerAdapter $adapter): bool
    {
        if ($type === 'uninstall') {
            return true;
        }

        if (!class_exists(\SquidForm\Component\SquidForm\Administrator\Api\Submissions::class) && !$this->boot()) {
            Factory::getApplication()->enqueueMessage('Tide Table needs SquidForm 1.0.3 or later. Install or update SquidForm first.', 'error');

            return false;
        }

        return true;
    }

    public function postflight(string $type, InstallerAdapter $adapter): bool
    {
        if ($type === 'uninstall' || !$this->boot()) {
            return true;
        }

        $dir  = __DIR__ . '/forms/';
        $made = [];

        // SquidForm's own messages ("version published") in words, not codes.
        Factory::getApplication()->getLanguage()->load('com_squidform', JPATH_ADMINISTRATOR . '/components/com_squidform');
        Factory::getApplication()->getLanguage()->load('com_squidform', JPATH_ADMINISTRATOR);

        foreach ([
            ['tidetable-categories', 'Tide Table: Event categories', 3, 'Event categories for Tide Table: name, short code, colour, order, on or off.'],
            ['tidetable-events', 'Tide Table: Events', 1, 'Events for Tide Table. Visitors\' events wait for an admin\'s check.'],
        ] as [$alias, $title, $access, $desc]) {
            if ($this->formId($alias)) {
                continue;
            }

            $line = \SquidForm\Component\SquidForm\Administrator\Engine\FormTransfer::create($title, $alias, $desc, (string) file_get_contents($dir . $alias . '.json'), 'Made by Tide Table');

            if (!$line['ok'] || $line['alias'] !== $alias) {
                Factory::getApplication()->enqueueMessage('Tide Table couldn\'t make the form "' . $title . '": ' . ($line['message'] ?: 'the alias ' . $alias . ' is taken') . '.', 'warning');
                continue;
            }

            // Published, for everyone (events) or Special (categories: staff only).
            $db = Factory::getContainer()->get(DatabaseInterface::class);
            $id = (int) $line['id'];
            $db->setQuery($db->createQuery()->update($db->quoteName('#__squidform_forms'))->set('state = 1')->set('access = :a')
                ->where('id = :id')->bind(':a', $access, ParameterType::INTEGER)->bind(':id', $id, ParameterType::INTEGER))->execute();
            $made[] = $title;
        }

        if ($made) {
            Factory::getApplication()->enqueueMessage('Tide Table made the forms ' . implode(' and ', $made) . '. Add your categories first, then events.', 'message');
        }

        return true;
    }

    private function boot(): bool
    {
        try {
            Factory::getApplication()->bootComponent('com_squidform');
        } catch (\Throwable) {
            return false;
        }

        return class_exists(\SquidForm\Component\SquidForm\Administrator\Api\Submissions::class);
    }

    private function formId(string $alias): int
    {
        $db = Factory::getContainer()->get(DatabaseInterface::class);

        return (int) $db->setQuery($db->createQuery()->select('id')->from($db->quoteName('#__squidform_forms'))->where('alias = :a')->bind(':a', $alias))->loadResult();
    }
};
