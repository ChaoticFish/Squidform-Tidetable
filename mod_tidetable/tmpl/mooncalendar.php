<?php

/**
 * @package     Tide Table
 * @subpackage  mod_tidetable
 * @license     GNU General Public License version 2 or later
 *
 * Moon calendar: a month grid with a moon on each day that has events, and
 * that month's events listed under it. Days with events are links to their
 * events. With the script, one month shows at a time (Previous / Next);
 * without it, every month shows, one after another.
 */

\defined('_JEXEC') or die;

use ChaoticFish\Module\TideTable\Site\Helper\Show;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;

/** @var list<object> $categories @var list<object> $events @var int $moduleId @var int $h @var bool $filter @var string $addUrl @var string $copyBase @var list<string> $problems */

$hm    = 'h' . $h;
$hd    = 'h' . ($h + 1);
$he    = 'h' . min(6, $h + 2);
$first = (int) Factory::getApplication()->getLanguage()->getFirstDay();
$byDay = [];

foreach ($events as $e) {
    $byDay[substr($e->date, 0, 7)][$e->date][] = $e;
}
?>
<div class="tidetable tt-moon" id="tt<?php echo $moduleId; ?>" data-tt-root data-tt-calendar
	data-tt-showing="<?php echo Show::e(Text::_('MOD_TIDETABLE_SHOWING')); ?>" data-tt-one="<?php echo Show::e(Text::_('MOD_TIDETABLE_SHOWING_1')); ?>" data-tt-none="<?php echo Show::e(Text::_('MOD_TIDETABLE_NONE_FILTER')); ?>"
	data-tt-day-many="<?php echo Show::e(Text::_('MOD_TIDETABLE_DAY_EVENTS')); ?>" data-tt-day-one="<?php echo Show::e(Text::_('MOD_TIDETABLE_DAY_EVENTS_1')); ?>">
	<?php echo Show::problems($problems); ?>
	<?php echo $filter ? Show::filter($categories, $moduleId) : ''; ?>
	<?php if (!$events) : ?>
		<?php echo Show::none(); ?>
	<?php else : ?>
		<div class="tt-moon-nav" data-tt-nav hidden>
			<button type="button" class="tt-navbtn" data-tt-prev><span aria-hidden="true">&#8249;</span> <?php echo Text::_('MOD_TIDETABLE_PREV_MONTH'); ?></button>
			<button type="button" class="tt-navbtn" data-tt-next><?php echo Text::_('MOD_TIDETABLE_NEXT_MONTH'); ?> <span aria-hidden="true">&#8250;</span></button>
		</div>
		<?php foreach ($byDay as $ym => $days) : ?>
			<?php
			$start  = new DateTimeImmutable($ym . '-01');
			$inMon  = (int) $start->format('t');
			$lead   = ((int) $start->format('w') - $first + 7) % 7;
			$title  = Show::date($ym . '-01', 'F Y');
			$cap    = Show::id($moduleId, 'cap' . $ym);
			?>
			<section class="tt-moonmonth" data-tt-month aria-labelledby="<?php echo $cap; ?>">
				<<?php echo $hm; ?> class="tt-month-title" id="<?php echo $cap; ?>" tabindex="-1"><?php echo Show::e($title); ?></<?php echo $hm; ?>>
				<p class="visually-hidden"><?php echo Text::_('MOD_TIDETABLE_CAL_HELP'); ?></p>
				<table class="tt-grid">
					<thead>
						<tr>
							<?php for ($d = 0; $d < 7; $d++) : ?>
								<?php $wd = ($first + $d) % 7; $sample = $start->modify('+' . ((($wd - (int) $start->format('w')) + 7) % 7) . ' days')->format('Y-m-d'); ?>
								<th scope="col" abbr="<?php echo Show::e(Show::date($sample, 'l')); ?>"><?php echo Show::e(Show::date($sample, 'D')); ?></th>
							<?php endfor; ?>
						</tr>
					</thead>
					<tbody>
						<tr>
						<?php for ($c = 0; $c < $lead; $c++) : ?><td class="tt-empty"></td><?php endfor; ?>
						<?php for ($day = 1; $day <= $inMon; $day++) : ?>
							<?php
							$ymd  = $ym . '-' . sprintf('%02d', $day);
							$list = $days[$ymd] ?? [];
							$col  = ($lead + $day - 1) % 7;
							if ($day > 1 && $col === 0) {
								echo '</tr><tr>';
							}
							?>
							<td class="<?php echo $list ? 'tt-has' : ''; ?><?php echo array_filter($list, static fn ($x) => $x->special) ? ' tt-special' : ''; ?>">
							<?php if ($list) : ?>
								<?php $n = count($list); $label = Text::sprintf($n === 1 ? 'MOD_TIDETABLE_DAY_EVENTS_1' : 'MOD_TIDETABLE_DAY_EVENTS', Show::date($ymd, 'l, F j'), $n); ?>
								<a class="tt-daylink" href="#<?php echo Show::id($moduleId, 'd' . $ymd); ?>" aria-label="<?php echo Show::e($label); ?>" data-tt-day="<?php echo Show::e(Show::date($ymd, 'l, F j')); ?>"
									data-tt-cats="<?php echo Show::e(implode(' ', array_map(static fn ($x) => $x->category === '' ? '-' : $x->category, $list))); ?>">
									<span class="tt-num"><?php echo $day; ?></span>
									<span class="tt-moons" aria-hidden="true"><?php foreach ($list as $x) : ?><span class="tt-moondot tt-c-<?php echo Show::e($x->category_color); ?>" data-tt-cat="<?php echo Show::e($x->category); ?>"></span><?php endforeach; ?></span>
								</a>
								<span class="tt-num tt-plain" hidden><?php echo $day; ?></span>
							<?php else : ?>
								<span class="tt-num"><?php echo $day; ?></span>
							<?php endif; ?>
							</td>
						<?php endfor; ?>
						<?php for ($c = ($lead + $inMon) % 7; $c > 0 && $c < 7; $c++) : ?><td class="tt-empty"></td><?php endfor; ?>
						</tr>
					</tbody>
				</table>
				<div class="tt-days">
					<?php foreach ($days as $ymd => $list) : ?>
						<div class="tt-dayblock" id="<?php echo Show::id($moduleId, 'd' . $ymd); ?>" tabindex="-1" data-tt-group>
							<<?php echo $hd; ?> class="tt-daytitle"><?php echo Show::e(Show::date($ymd, 'l, F j')); ?></<?php echo $hd; ?>>
							<ul class="tt-list">
							<?php foreach ($list as $e) : ?>
								<li class="tt-event tt-c-<?php echo Show::e($e->category_color); ?><?php echo $e->special ? ' tt-special' : ''; ?>" data-tt-cat="<?php echo Show::e($e->category); ?>">
									<<?php echo $he; ?> class="tt-title"><?php echo Show::special($e) . Show::e($e->title); ?></<?php echo $he; ?>>
									<p class="tt-when"><?php echo Show::e($e->time); ?>
										<?php if ($e->category_label !== '') : ?><span class="tt-cat"><span class="tt-swatch" aria-hidden="true"></span><?php echo Show::e($e->category_label); ?></span><?php endif; ?>
										<?php echo Show::badge($e); ?></p>
									<?php echo Show::description($e) . Show::registration($e) . Show::fees($e); ?>
									<?php if ($e->link !== '' || ($copyBase !== '' && $e->uid !== '')) : ?>
										<p class="tt-actions"><?php echo Show::link($e) . ' ' . Show::copy($e, $copyBase); ?></p>
									<?php endif; ?>
								</li>
							<?php endforeach; ?>
							</ul>
						</div>
					<?php endforeach; ?>
				</div>
			</section>
		<?php endforeach; ?>
	<?php endif; ?>
	<?php echo Show::add($addUrl); ?>
</div>
