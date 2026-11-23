<?php

/**
 * @package     Tide Table
 * @subpackage  plg_squidform_tidetable
 * @license     GNU General Public License version 2 or later
 */

namespace ChaoticFish\Plugin\SquidForm\TideTable\Extension;

use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Event\Event;
use Joomla\Event\SubscriberInterface;
use SquidForm\Component\SquidForm\Administrator\Api\Submissions;

\defined('_JEXEC') or die;

/**
 * Tide Table's side of SquidForm:
 *  - the two forms as builder starters (onSquidformStarters);
 *  - {tidetable:f_title} and friends: the event named by ?tt_copy=<uid>, so the
 *    Copy link opens a new event filled in from an old one (date left empty);
 *  - "tidetable.categories": the switched-on categories as a choice list.
 */
final class TideTable extends CMSPlugin implements SubscriberInterface
{
    public const CATEGORIES = 'tidetable-categories';
    public const EVENTS     = 'tidetable-events';

    /** Fields a copy carries over. Not the date (it's a new one) and never the contact email. */
    public const COPY = ['f_title', 'f_time', 'f_category', 'f_special', 'f_badge', 'f_description', 'f_link', 'f_link_label', 'f_fee_youth', 'f_fee_adult', 'f_registration'];

    /** @var array<string, mixed>|null|false the event being copied (false: none) */
    private array|null|false $copy = null;

    public static function getSubscribedEvents(): array
    {
        return [
            'onSquidformStarters'      => 'starters',
            'onSquidformPlaceholders'  => 'placeholders',
            'onSquidformOptionSources' => 'optionSources',
        ];
    }

    public function starters(Event $event): void
    {
        $dir = \dirname(__DIR__, 2) . '/forms/';
        $reg = $event->getArgument('registry');
        $reg['tidetable-categories'] = ['title' => 'Tide Table: Event categories', 'description' => 'Name, short code, colour, order, and a switch to hide a category.', 'json' => (string) file_get_contents($dir . self::CATEGORIES . '.json')];
        $reg['tidetable-events']     = ['title' => 'Tide Table: Events', 'description' => 'One event per date, checked by an admin unless an admin adds it.', 'json' => (string) file_get_contents($dir . self::EVENTS . '.json')];
    }

    public function placeholders(Event $event): void
    {
        $event->getArgument('registry')['tidetable'] = [
            'title' => 'Tide Table (Copy link)',
            'items' => array_combine(self::COPY, array_map(static fn ($k) => 'The copied event\'s ' . substr($k, 2), self::COPY)),
            'value' => fn (string $name, array $ctx): ?string => $this->copied($name),
        ];
    }

    public function optionSources(Event $event): void
    {
        $event->getArgument('registry')['tidetable.categories'] = [
            'title'   => 'Tide Table: event categories (switched on)',
            'options' => static function (): array {
                $api = Submissions::form(self::CATEGORIES);

                if ($api === null) {
                    return [];
                }

                return array_map(static fn ($r) => ['value' => (string) ($r['data']['f_key'] ?? ''), 'label' => (string) ($r['data']['f_label'] ?? '')],
                    $api->find(['where' => [['f_on', '=', true]], 'order' => [['f_order', 'asc'], ['f_label', 'asc']]]));
            },
        ];
    }

    /** A field of the event named by ?tt_copy=, or null. */
    private function copied(string $name): ?string
    {
        if (!\in_array($name, self::COPY, true)) {
            return null;
        }

        if ($this->copy === null) {
            $this->copy = false;
            $uid        = (string) Factory::getApplication()->getInput()->getCmd('tt_copy', '');
            $api        = preg_match('/^[0-9a-f-]{36}$/', $uid) ? Submissions::form(self::EVENTS) : null;
            // Only events that went up (sent or approved), archived ones too: last year's
            // event is the usual one to copy. Never someone's draft or one awaiting a check.
            $row = $api?->get($uid);

            if ($row && \in_array($row['status'], Submissions::FINAL, true)) {
                $this->copy = $row['data'];
            }
        }

        if ($this->copy === false) {
            return null;
        }

        $v = $this->copy[$name] ?? null;

        return $v === null ? null : (\is_array($v) ? implode(', ', $v) : (string) $v);
    }
}
