<?php

/**
 * @package     Tide Table
 * @subpackage  plg_task_tidetable
 * @license     GNU General Public License version 2 or later
 */

namespace ChaoticFish\Plugin\Task\TideTable\Extension;

use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Component\Scheduler\Administrator\Event\ExecuteTaskEvent;
use Joomla\Component\Scheduler\Administrator\Task\Status;
use Joomla\Component\Scheduler\Administrator\Traits\TaskPluginTrait;
use Joomla\Event\SubscriberInterface;
use SquidForm\Component\SquidForm\Administrator\Api\Submissions;

\defined('_JEXEC') or die;

/**
 * "Tide Table: archive past events" (a Joomla scheduled task, daily).
 * Archives every Tide Table event dated before today (site time zone), less
 * the days to wait, whatever its status except unsent drafts.
 */
final class TideTableTask extends CMSPlugin implements SubscriberInterface
{
    use TaskPluginTrait;

    private const TASKS_MAP = [
        'tidetable.archive' => [
            'langConstPrefix' => 'PLG_TASK_TIDETABLE_ARCHIVE',
            'method'          => 'archive',
            'form'            => 'archive',
        ],
    ];

    protected $autoloadLanguage = true;

    public static function getSubscribedEvents(): array
    {
        return [
            'onTaskOptionsList'    => 'advertiseRoutines',
            'onExecuteTask'        => 'standardRoutineHandler',
            'onContentPrepareForm' => 'enhanceTaskItemForm',
        ];
    }

    private function archive(ExecuteTaskEvent $event): int
    {
        $days = max(1, min(365, (int) ($event->getArgument('params')->days ?? 1)));
        $n    = self::archivePast($days);

        if ($n < 0) {
            $this->logTask('SquidForm 1.0.3 or later, or the Tide Table: Events form, is missing.', 'warning');

            return Status::KNOCKOUT;
        }

        $this->logTask('Archived ' . $n . ' past event' . ($n === 1 ? '' : 's') . '.');

        return Status::OK;
    }

    /** Archive events dated before today minus ($days - 1). Returns how many, or -1 when there's nothing to work with. */
    public static function archivePast(int $days = 1): int
    {
        try {
            Factory::getApplication()->bootComponent('com_squidform');
        } catch (\Throwable) {
            return -1;
        }

        if (!class_exists(Submissions::class) || !($api = Submissions::form('tidetable-events'))) {
            return -1;
        }

        $tz     = new \DateTimeZone((string) Factory::getApplication()->get('offset', 'UTC') ?: 'UTC');
        $cutoff = (new \DateTimeImmutable('today', $tz))->modify('-' . ($days - 1) . ' days')->format('Y-m-d');
        $status = ['submitted', 'pending', 'approved', 'rejected', 'returned', 'withdrawn'];
        $n      = 0;

        do {
            $rows = $api->find(['status' => $status, 'test' => true, 'where' => [['f_date', '<', $cutoff]], 'limit' => 500]);
            $done = $api->archive(array_column($rows, 'id'));
            $n   += $done;
        } while (\count($rows) === 500 && $done > 0);

        return $n;
    }
}
