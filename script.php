<?php

/**
 * @package     Tide Table
 * @license     GNU General Public License version 2 or later
 */

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Installer\InstallerAdapter;
use Joomla\CMS\Installer\InstallerScriptInterface;
use Joomla\Database\DatabaseInterface;

/**
 * Before: SquidForm 1.0.3 or later must be there.
 * After: switch both plugins on (once, at install) and add the daily
 * "Tide Table: archive past events" scheduled task if there isn't one.
 */
return new class () implements InstallerScriptInterface {
    private const MIN_SQUIDFORM = '1.0.3';

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

        if (version_compare(PHP_VERSION, '8.2', '<') || version_compare(JVERSION, '5.0', '<')) {
            Factory::getApplication()->enqueueMessage('Tide Table needs Joomla 5 or 6 and PHP 8.2 or later.', 'error');

            return false;
        }

        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $manifest = (string) $db->setQuery(
            $db->createQuery()->select('manifest_cache')->from($db->quoteName('#__extensions'))
                ->where($db->quoteName('element') . ' = ' . $db->quote('com_squidform'))->where($db->quoteName('type') . ' = ' . $db->quote('component'))
        )->loadResult();
        $version = (string) (json_decode($manifest, true)['version'] ?? '');

        if ($version === '' || version_compare($version, self::MIN_SQUIDFORM, '<')) {
            Factory::getApplication()->enqueueMessage('Tide Table needs SquidForm ' . self::MIN_SQUIDFORM . ' or later' . ($version !== '' ? ' (this site has ' . $version . ')' : '') . '. Install or update SquidForm first.', 'error');

            return false;
        }

        return true;
    }

    public function postflight(string $type, InstallerAdapter $adapter): bool
    {
        if ($type === 'uninstall') {
            return true;
        }

        $db = Factory::getContainer()->get(DatabaseInterface::class);

        if ($type === 'install') {
            $db->setQuery(
                $db->createQuery()->update($db->quoteName('#__extensions'))->set($db->quoteName('enabled') . ' = 1')
                    ->where($db->quoteName('type') . ' = ' . $db->quote('plugin'))->where($db->quoteName('element') . ' = ' . $db->quote('tidetable'))
                    ->whereIn($db->quoteName('folder'), ['squidform', 'task'], \Joomla\Database\ParameterType::STRING)
            )->execute();
        }

        $have = (int) $db->setQuery(
            $db->createQuery()->select('COUNT(*)')->from($db->quoteName('#__scheduler_tasks'))->where($db->quoteName('type') . ' = ' . $db->quote('tidetable.archive'))
        )->loadResult();

        if ($have === 0) {
            try {
                \Joomla\CMS\Plugin\PluginHelper::importPlugin('task');
                $model = Factory::getApplication()->bootComponent('com_scheduler')->getMVCFactory()->createModel('Task', 'Administrator', ['ignore_request' => true]);
                $ok    = $model->save([
                    'id'              => 0,
                    'title'           => 'Tide Table: archive past events',
                    'type'            => 'tidetable.archive',
                    'state'           => 1,
                    'execution_rules' => ['rule-type' => 'interval-hours', 'interval-hours' => '24', 'exec-day' => gmdate('d'), 'exec-time' => '05:00'],
                    'params'          => ['days' => 1, 'individual_log' => false, 'log_file' => '', 'notifications' => ['success_mail' => '0', 'failure_mail' => '1', 'fatal_failure_mail' => '1', 'orphan_mail' => '1']],
                    'note'            => 'Added by Tide Table. Archives events the day after their date.',
                ]);

                if (!$ok) {
                    throw new \RuntimeException((string) $model->getError());
                }

                Factory::getApplication()->enqueueMessage('Tide Table added a daily scheduled task, "Tide Table: archive past events" (System → Scheduled Tasks).', 'message');
            } catch (\Throwable $e) {
                Factory::getApplication()->enqueueMessage('Tide Table couldn\'t add its scheduled task (' . $e->getMessage() . '). Add it in System → Scheduled Tasks → New → "Tide Table: archive past events".', 'warning');
            }
        }

        return true;
    }
};
