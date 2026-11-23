<?php

/**
 * @package     Tide Table
 * @subpackage  mod_tidetable
 * @license     GNU General Public License version 2 or later
 */

namespace ChaoticFish\Module\TideTable\Site\Helper;

use Joomla\CMS\Factory;
use Joomla\CMS\Log\Log;
use Joomla\Registry\Registry;

\defined('_JEXEC') or die;

/**
 * Loads categories and events from SquidForm (the Tide Table forms, through
 * SquidForm's reader) or from a JSON, XML or CSV file, and gives every layout
 * the same shape:
 *
 *   category: key, label, color
 *   event:    uid, date (Y-m-d), time, sort, category, title, link, link_label,
 *             fee_youth, fee_adult, registration, badge, description, special
 *
 * Upcoming only (today on, site time zone), soonest first, then by the first
 * time in the time text. A switched-off category hides its events too.
 */
final class EventSource
{
    public const COLORS = ['teal', 'gold', 'purple', 'orange', 'blue', 'green', 'red', 'gray'];

    /** @var list<string> problems worth showing an admin */
    public array $problems = [];

    public function __construct(private Registry $params)
    {
    }

    /** @return array{0: list<object>, 1: list<object>} [categories, events] */
    public function load(): array
    {
        try {
            [$cats, $events] = $this->params->get('source', 'squidform') === 'file' ? $this->fromFile() : $this->fromSquidForm();
        } catch (\Throwable $e) {
            $this->problems[] = $e->getMessage();
            Log::add('mod_tidetable: ' . $e->getMessage(), Log::WARNING, 'mod_tidetable');

            return [[], []];
        }

        // Only these categories, if the module says so.
        $only = array_filter(array_map('trim', explode(',', strtolower((string) $this->params->get('categories', '')))));

        if ($only) {
            $cats = array_values(array_filter($cats, static fn ($c) => \in_array($c->key, $only, true)));
        }

        $byKey = [];

        foreach ($cats as $c) {
            $byKey[$c->key] = $c;
        }

        $tz    = new \DateTimeZone((string) Factory::getApplication()->get('offset', 'UTC') ?: 'UTC');
        $today = (new \DateTimeImmutable('today', $tz))->format('Y-m-d');
        $ahead = (int) $this->params->get('days_ahead', 0);
        $until = $ahead > 0 ? (new \DateTimeImmutable('today', $tz))->modify('+' . $ahead . ' days')->format('Y-m-d') : null;
        $out   = [];

        foreach ($events as $e) {
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $e->date) || $e->date < $today || ($until !== null && $e->date > $until) || $e->title === '') {
                continue;
            }

            // A category that's off (or not in this module's list) hides its events. Events
            // with no category, or one that isn't listed at all, show when nothing is limited.
            if ($e->category !== '' && !isset($byKey[$e->category]) && ($only || isset($this->off[$e->category]))) {
                continue;
            }

            if ($e->category === '' && $only) {
                continue;
            }

            $c                  = $byKey[$e->category] ?? null;
            $e->category_label  = $c->label ?? '';
            $e->category_color  = $c->color ?? 'teal';
            $e->sort            = self::sortTime($e->time);
            $out[]              = $e;
        }

        usort($out, static fn ($a, $b) => [$a->date, $a->sort, $a->title] <=> [$b->date, $b->sort, $b->title]);

        $limit = (int) $this->params->get('limit', 0);

        if ($limit > 0) {
            $out = \array_slice($out, 0, $limit);
        }

        // Only categories that have something to show get a filter button.
        $used = array_flip(array_map(static fn ($e) => $e->category, $out));
        $cats = array_values(array_filter($cats, static fn ($c) => isset($used[$c->key])));

        return [$cats, $out];
    }

    /** "8:30 PM" → "20:30"; "1:17 – 2:26 PM" → "13:17"; "After 9:00 PM" → "21:00"; "19:30" → "19:30"; none → "99:99" (last). */
    public static function sortTime(string $t): string
    {
        if (!preg_match_all('/(\d{1,2})(?::([0-5]\d))?(\s*(?:a\.?m\.?|p\.?m\.?)(?![a-z]))?/i', $t, $m, PREG_SET_ORDER)) {
            return '99:99';
        }

        $first  = $m[0];
        $h      = (int) $first[1];
        $min    = (int) ($first[2] ?? 0);
        $marker = trim((string) ($first[3] ?? ''));
        $borrow = $marker === '' && isset($m[1]) && trim((string) ($m[1][3] ?? '')) !== '';

        // "1:17 – 2:26 PM": the first time takes the AM/PM after the second.
        if ($borrow) {
            $marker = trim($m[1][3]);
        }

        if ($marker !== '' && $h >= 1 && $h <= 12) {
            $pm = stripos($marker, 'p') === 0;
            $h  = $h % 12 + ($pm ? 12 : 0);

            // "11:30 – 1:00 PM": the first is still morning.
            if ($borrow && $pm && (int) $first[1] !== 12 && (int) $first[1] > (int) $m[1][1]) {
                $h -= 12;
            }

            return \sprintf('%02d:%02d', $h, $min);
        }

        if (($first[2] ?? '') !== '' && $h <= 23) {
            return \sprintf('%02d:%02d', $h, $min);
        }

        return '99:99';
    }

    public static function color(string $c): string
    {
        $c = strtolower(trim($c));

        return \in_array($c, self::COLORS, true) ? $c : 'teal';
    }

    /** @var array<string, true> categories that are switched off */
    private array $off = [];

    /** @return array{0: list<object>, 1: list<object>} */
    private function fromSquidForm(): array
    {
        $api = '\\SquidForm\\Component\\SquidForm\\Administrator\\Api\\Submissions';

        try {
            Factory::getApplication()->bootComponent('com_squidform');
        } catch (\Throwable) {
        }

        if (!class_exists($api)) {
            throw new \RuntimeException('SquidForm 1.0.3 or later isn\'t installed. Install it, or switch the module to a file.');
        }

        $catForm = $api::form('tidetable-categories');
        $evForm  = $api::form('tidetable-events');

        if (!$catForm || !$evForm) {
            throw new \RuntimeException('The Tide Table forms are missing. Install the Tide Table package again to make them.');
        }

        $cats = [];

        foreach ($catForm->find(['order' => [['f_order', 'asc'], ['f_label', 'asc']], 'limit' => 200]) as $r) {
            $d   = $r['data'];
            $key = strtolower(trim((string) ($d['f_key'] ?? '')));

            if ($key === '' || isset($cats[$key]) || isset($this->off[$key])) {
                continue;
            }

            if (($d['f_on'] ?? '') !== '1') {
                $this->off[$key] = true;
                continue;
            }

            $cats[$key] = (object) ['key' => $key, 'label' => (string) ($d['f_label'] ?? $key), 'color' => self::color((string) ($d['f_color'] ?? ''))];
        }

        $tz    = new \DateTimeZone((string) Factory::getApplication()->get('offset', 'UTC') ?: 'UTC');
        $today = (new \DateTimeImmutable('today', $tz))->format('Y-m-d');
        $events = [];

        foreach ($evForm->find(['where' => [['f_date', '>=', $today]], 'order' => [['f_date', 'asc']], 'limit' => 1000]) as $r) {
            $d        = $r['data'];
            $events[] = self::event([
                'uid'          => $r['uid'],
                'date'         => $d['f_date'] ?? '',
                'time'         => $d['f_time'] ?? '',
                'category'     => $d['f_category'] ?? '',
                'title'        => $d['f_title'] ?? '',
                'link'         => $d['f_link'] ?? '',
                'link_label'   => $d['f_link_label'] ?? '',
                'fee_youth'    => $d['f_fee_youth'] ?? '',
                'fee_adult'    => $d['f_fee_adult'] ?? '',
                'registration' => $d['f_registration'] ?? '',
                'badge'        => $d['f_badge'] ?? '',
                'description'  => $d['f_description'] ?? '',
                'special'      => ($d['f_special'] ?? '') === '1',
            ]);
        }

        return [array_values($cats), $events];
    }

    /** @return array{0: list<object>, 1: list<object>} */
    private function fromFile(): array
    {
        $path = trim((string) $this->params->get('file_path', ''));

        if ($path === '') {
            throw new \RuntimeException('No data file is set.');
        }

        // A path from the site root ("files/events.json") or the full server path.
        $full = str_starts_with($path, '/') || preg_match('/^[A-Za-z]:[\\\\\/]/', $path) ? $path : JPATH_ROOT . '/' . ltrim($path, '/');
        $ext  = strtolower(pathinfo($full, PATHINFO_EXTENSION));

        if (!\in_array($ext, ['json', 'xml', 'csv'], true) || !is_file($full) || !is_readable($full)) {
            throw new \RuntimeException('The data file "' . $path . '" isn\'t a readable .json, .xml or .csv file.');
        }

        if (filesize($full) > 5 * 1024 * 1024) {
            throw new \RuntimeException('The data file is over 5 MB.');
        }

        $format = (string) $this->params->get('file_format', 'auto');
        $format = \in_array($format, ['json', 'xml', 'csv'], true) ? $format : $ext;

        return match ($format) {
            'xml'   => $this->xml($full),
            'csv'   => $this->csv($full),
            default => $this->json($full),
        };
    }

    /** @return array{0: list<object>, 1: list<object>} */
    private function json(string $path): array
    {
        $j = json_decode((string) file_get_contents($path), true);

        if (!\is_array($j) || !\is_array($j['events'] ?? null)) {
            throw new \RuntimeException('The JSON file needs an "events" list (and usually "categories").');
        }

        $cats = $this->cats(array_map(static fn ($c) => \is_array($c) ? $c : [], (array) ($j['categories'] ?? [])));

        return [$cats, array_map(static fn ($e) => self::fromRow(\is_array($e) ? $e : []), $j['events'])];
    }

    /** @return array{0: list<object>, 1: list<object>} */
    private function xml(string $path): array
    {
        $prev = libxml_use_internal_errors(true);
        // No entities or network: LIBXML_NONET, and simplexml never expands external entities by default (PHP 8).
        $x = simplexml_load_string((string) file_get_contents($path), 'SimpleXMLElement', LIBXML_NONET);
        libxml_use_internal_errors($prev);

        if ($x === false) {
            throw new \RuntimeException('The XML file can\'t be read.');
        }

        $attrs = static function (\SimpleXMLElement $n): array {
            $a = [];

            foreach ($n->attributes() as $k => $v) {
                $a[(string) $k] = (string) $v;
            }

            foreach ($n->children() as $k => $v) {
                $a[(string) $k] = (string) $v;
            }

            return $a;
        };

        $cats = [];

        foreach ($x->categories->category ?? [] as $c) {
            $cats[] = $attrs($c);
        }

        $events = [];

        foreach ($x->events->event ?? [] as $e) {
            $events[] = self::fromRow($attrs($e));
        }

        return [$this->cats($cats), $events];
    }

    /** @return array{0: list<object>, 1: list<object>} */
    private function csv(string $path): array
    {
        $h = fopen($path, 'r');

        if ($h === false) {
            throw new \RuntimeException('The CSV file can\'t be opened.');
        }

        $head = fgetcsv($h, null, ',', '"', '');

        if (!$head) {
            fclose($h);

            throw new \RuntimeException('The CSV file has no header row.');
        }

        $head   = array_map(static fn ($c) => strtolower(trim((string) preg_replace('/^\xEF\xBB\xBF/', '', (string) $c))), $head);
        $cats   = [];
        $events = [];

        while (($row = fgetcsv($h, null, ',', '"', '')) !== false) {
            if ($row === [null] || \count($row) !== \count($head)) {
                continue;
            }

            $r = array_combine($head, $row);
            $k = strtolower(trim((string) ($r['category_key'] ?? '')));

            if ($k !== '' && !isset($cats[$k])) {
                $cats[$k] = ['catkey' => $k, 'label' => $r['category_label'] ?? $k, 'color' => $r['category_color'] ?? 'teal', 'disabled' => $r['category_disabled'] ?? ''];
            }

            $events[] = self::fromRow($r);
        }

        fclose($h);

        return [$this->cats(array_values($cats)), $events];
    }

    /** @param list<array<string, mixed>> $rows @return list<object> */
    private function cats(array $rows): array
    {
        $out = [];

        foreach ($rows as $c) {
            $key = strtolower(trim((string) ($c['catkey'] ?? $c['key'] ?? '')));

            if ($key === '' || isset($out[$key])) {
                continue;
            }

            if (self::truthy($c['disabled'] ?? false)) {
                $this->off[$key] = true;
                continue;
            }

            $out[$key] = (object) ['key' => $key, 'label' => (string) ($c['label'] ?? $key), 'color' => self::color((string) ($c['color'] ?? ''))];
        }

        return array_values($out);
    }

    /** One event from a file row (the old Analemma names). */
    private static function fromRow(array $r): object
    {
        return self::event([
            'uid'          => '',
            'date'         => $r['event_date'] ?? '',
            'time'         => $r['event_time'] ?? '',
            'category'     => $r['category_key'] ?? '',
            'title'        => $r['title'] ?? '',
            'link'         => $r['link'] ?? '',
            'link_label'   => $r['link_label'] ?? '',
            'fee_youth'    => $r['enrollment_fee_youth'] ?? $r['fee_youth'] ?? '',
            'fee_adult'    => $r['enrollment_fee_adult'] ?? $r['fee_adult'] ?? '',
            'registration' => $r['registration_info'] ?? $r['registration'] ?? '',
            'badge'        => $r['badge'] ?? '',
            'description'  => $r['description'] ?? '',
            'special'      => self::truthy($r['special'] ?? false),
        ]);
    }

    private static function event(array $e): object
    {
        $s = static fn ($v, int $max) => mb_substr(trim(\is_array($v) ? implode(', ', $v) : (string) $v), 0, $max);
        $link = $s($e['link'], 500);

        // Only web links (and site-relative ones): never javascript: or data:.
        if ($link !== '' && !preg_match('#^(https?://|/(?!/)|mailto:)#i', $link)) {
            $link = '';
        }

        return (object) [
            'uid'          => $s($e['uid'], 36),
            'date'         => $s($e['date'], 10),
            'time'         => $s($e['time'], 100),
            'sort'         => '',
            'category'     => strtolower($s($e['category'], 40)),
            'title'        => $s($e['title'], 200),
            'link'         => $link,
            'link_label'   => $s($e['link_label'], 120),
            'fee_youth'    => $s($e['fee_youth'], 40),
            'fee_adult'    => $s($e['fee_adult'], 40),
            'registration' => $s($e['registration'], 500),
            'badge'        => $s($e['badge'], 60),
            'description'  => $s($e['description'], 2000),
            'special'      => (bool) $e['special'],
        ];
    }

    private static function truthy(mixed $v): bool
    {
        return $v === true || \in_array(strtolower(trim((string) (\is_scalar($v) ? $v : ''))), ['1', 'true', 'yes', 'on'], true);
    }
}
