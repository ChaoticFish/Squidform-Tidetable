<?php

/**
 * @package     Tide Table
 * @subpackage  mod_tidetable
 * @license     GNU General Public License version 2 or later
 */

namespace ChaoticFish\Module\TideTable\Site\Helper;

use Joomla\CMS\Date\Date;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

\defined('_JEXEC') or die;

/**
 * The pieces every layout shares, escaped. Layouts decide where they go.
 */
final class Show
{
    public static function e(?string $s): string
    {
        return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
    }

    /**
     * A Y-m-d date in the site's language. Formats: Joomla Date letters
     * (l Monday, D Mon, F November, M Nov, j 21, Y 2026).
     */
    public static function date(string $ymd, string $format): string
    {
        // Noon UTC, formatted without a time zone shift: the date stays the date.
        return (new Date($ymd . ' 12:00:00', 'UTC'))->format($format, false, true);
    }

    /** "Nov 2026"-style month key for grouping. */
    public static function month(string $ymd): string
    {
        return substr($ymd, 0, 7);
    }

    /** id="" safe for this module. */
    public static function id(int $module, string $part): string
    {
        return 'tt' . $module . '-' . preg_replace('/[^a-z0-9-]/', '', strtolower($part));
    }

    /** A price as written, or "Free" for $0, 0.00 and the like. */
    public static function price(string $v): string
    {
        return preg_match('/^\D{0,3}\s?0+(?:[.,]0+)?$/u', trim($v)) ? Text::_('MOD_TIDETABLE_FREE') : $v;
    }

    /** Youth and adult fees: "Youth $13 · Adult $15", or one price when they match. '' when neither. */
    public static function fees(object $e, string $class = 'tt-fees'): string
    {
        $y = $e->fee_youth === '' ? '' : self::price($e->fee_youth);
        $a = $e->fee_adult === '' ? '' : self::price($e->fee_adult);

        if ($y === '' && $a === '') {
            return '';
        }

        if ($y !== '' && $a !== '' && $y === $a) {
            $txt = $y === Text::_('MOD_TIDETABLE_FREE') ? self::e($y) : Text::sprintf('MOD_TIDETABLE_FEE_ALL', self::e($y));
        } else {
            $parts = [];

            if ($y !== '') {
                $parts[] = Text::sprintf('MOD_TIDETABLE_FEE_YOUTH', self::e($y));
            }

            if ($a !== '') {
                $parts[] = Text::sprintf('MOD_TIDETABLE_FEE_ADULT', self::e($a));
            }

            $txt = implode(' <span aria-hidden="true">·</span> ', $parts);
        }

        return '<p class="' . $class . '">' . $txt . '</p>';
    }

    public static function registration(object $e, string $class = 'tt-reg'): string
    {
        return $e->registration === '' ? '' : '<p class="' . $class . '">' . self::e($e->registration) . '</p>';
    }

    public static function description(object $e, string $class = 'tt-desc'): string
    {
        return $e->description === '' ? '' : '<p class="' . $class . '">' . nl2br(self::e($e->description), false) . '</p>';
    }

    public static function badge(object $e, string $class = 'tt-badge'): string
    {
        return $e->badge === '' ? '' : '<span class="' . $class . '">' . self::e($e->badge) . '</span>';
    }

    /** True for a link to another site (opens in a new tab). */
    public static function external(string $url): bool
    {
        return (bool) preg_match('#^https?://#i', $url) && !str_starts_with(strtolower($url), strtolower(\Joomla\CMS\Uri\Uri::root()));
    }

    /**
     * The event's link as a button-ish link: its link text, and the title for
     * screen readers ("Details & Registration: Night Sky Tour"), so a list of
     * them still says which is which. '' when there's no link.
     */
    public static function link(object $e, string $class = 'tt-link'): string
    {
        if ($e->link === '') {
            return '';
        }

        $label = $e->link_label !== '' ? $e->link_label : Text::_('MOD_TIDETABLE_DETAILS');
        $ext   = self::external($e->link);

        return '<a class="' . $class . '" href="' . self::e($e->link) . '"' . ($ext ? ' target="_blank" rel="noopener noreferrer"' : '') . '>'
            . self::e($label) . '<span class="visually-hidden">: ' . self::e($e->title) . ($ext ? ' ' . Text::_('MOD_TIDETABLE_NEW_TAB') : '') . '</span>'
            . ($ext ? '<span class="tt-ext" aria-hidden="true">&#8599;</span>' : '') . '</a>';
    }

    /** "Copy" for people allowed to (module setting), or ''. */
    public static function copy(object $e, string $base, string $class = 'tt-copy'): string
    {
        if ($base === '' || $e->uid === '') {
            return '';
        }

        return '<a class="' . $class . '" href="' . self::e(Route::_($base . rawurlencode($e->uid))) . '">' . Text::_('MOD_TIDETABLE_COPY')
            . '<span class="visually-hidden"> ' . self::e($e->title) . ', ' . self::e(self::date($e->date, 'j F Y')) . '</span></a>';
    }

    /** "Special" for screen readers when an event is highlighted (the look carries it for everyone else). */
    public static function special(object $e): string
    {
        return $e->special ? '<span class="visually-hidden">' . Text::_('MOD_TIDETABLE_SPECIAL') . ': </span>' : '';
    }

    /**
     * The category buttons. Hidden until the script runs (without it every event
     * shows, which is right). One group, aria-pressed on each, and a polite
     * status line saying how many show.
     *
     * @param  list<object>  $cats
     */
    public static function filter(array $cats, int $module, string $class = 'tt-filter', string $chip = 'tt-chip'): string
    {
        $out = '<div class="' . $class . '" role="group" aria-label="' . self::e(Text::_('MOD_TIDETABLE_FILTER_LABEL')) . '" data-tt-filters hidden>'
            . '<button type="button" class="' . $chip . ' active" aria-pressed="true" data-tt-filter="">' . Text::_('MOD_TIDETABLE_ALL') . '</button>';

        foreach ($cats as $c) {
            $out .= '<button type="button" class="' . $chip . ' tt-c-' . self::e($c->color) . '" aria-pressed="false" data-tt-filter="' . self::e($c->key) . '">'
                . '<span class="tt-swatch" aria-hidden="true"></span>' . self::e($c->label) . '</button>';
        }

        return $out . '</div><p class="visually-hidden" role="status" data-tt-status></p>';
    }

    public static function add(string $url, string $class = 'tt-add'): string
    {
        return $url === '' ? '' : '<p class="' . $class . '"><a href="' . self::e($url) . '">' . Text::_('MOD_TIDETABLE_ADD') . '</a></p>';
    }

    /** @param list<string> $problems */
    public static function problems(array $problems): string
    {
        return $problems ? '<div class="alert alert-warning tt-problem">' . Text::_('MOD_TIDETABLE_PROBLEM') . ' ' . self::e(implode(' ', $problems)) . '</div>' : '';
    }

    public static function none(string $class = 'tt-none'): string
    {
        return '<p class="' . $class . '">' . Text::_('MOD_TIDETABLE_NONE') . '</p>';
    }
}
