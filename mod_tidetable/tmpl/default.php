<?php

/**
 * @package     Tide Table
 * @subpackage  mod_tidetable
 * @license     GNU General Public License version 2 or later
 *
 * Default: a list grouped by month, a date block beside each event.
 */

\defined('_JEXEC') or die;

use ChaoticFish\Module\TideTable\Site\Helper\Show;
use Joomla\CMS\Language\Text;

/** @var list<object> $categories @var list<object> $events @var int $moduleId @var int $h @var bool $filter @var string $addUrl @var string $copyBase @var list<string> $problems */

$hm = 'h' . $h;
$he = 'h' . ($h + 1);
?>
<div class="tidetable tt-default" id="tt<?php echo $moduleId; ?>" data-tt-root
	data-tt-showing="<?php echo Show::e(Text::_('MOD_TIDETABLE_SHOWING')); ?>" data-tt-one="<?php echo Show::e(Text::_('MOD_TIDETABLE_SHOWING_1')); ?>" data-tt-none="<?php echo Show::e(Text::_('MOD_TIDETABLE_NONE_FILTER')); ?>">
	<?php echo Show::problems($problems); ?>
	<?php echo $filter ? Show::filter($categories, $moduleId) : ''; ?>
	<?php if (!$events) : ?>
		<?php echo Show::none(); ?>
	<?php else : ?>
		<?php $month = null; foreach ($events as $e) : ?>
			<?php if (Show::month($e->date) !== $month) : ?>
				<?php if ($month !== null) : ?></ul></section><?php endif; ?>
				<?php $month = Show::month($e->date); ?>
				<section class="tt-month" data-tt-group aria-labelledby="<?php echo Show::id($moduleId, 'm' . $month); ?>">
				<<?php echo $hm; ?> class="tt-month-title" id="<?php echo Show::id($moduleId, 'm' . $month); ?>"><?php echo Show::e(Show::date($e->date, 'F Y')); ?></<?php echo $hm; ?>>
				<ul class="tt-list">
			<?php endif; ?>
			<li class="tt-event tt-c-<?php echo Show::e($e->category_color); ?><?php echo $e->special ? ' tt-special' : ''; ?>" data-tt-cat="<?php echo Show::e($e->category); ?>">
				<div class="tt-dateblock" aria-hidden="true">
					<span class="tt-dow"><?php echo Show::e(Show::date($e->date, 'D')); ?></span>
					<span class="tt-day"><?php echo (int) substr($e->date, 8, 2); ?></span>
				</div>
				<div class="tt-body">
					<<?php echo $he; ?> class="tt-title"><?php echo Show::special($e) . Show::e($e->title); ?></<?php echo $he; ?>>
					<p class="tt-when">
						<span class="visually-hidden"><?php echo Show::e(Show::date($e->date, 'l, F j')); ?>, </span><?php echo Show::e($e->time); ?>
						<?php if ($e->category_label !== '') : ?><span class="tt-cat"><span class="tt-swatch" aria-hidden="true"></span><?php echo Show::e($e->category_label); ?></span><?php endif; ?>
						<?php echo Show::badge($e); ?>
					</p>
					<?php echo Show::description($e) . Show::registration($e) . Show::fees($e); ?>
					<?php if ($e->link !== '' || ($copyBase !== '' && $e->uid !== '')) : ?>
						<p class="tt-actions"><?php echo Show::link($e, 'tt-link') . ' ' . Show::copy($e, $copyBase); ?></p>
					<?php endif; ?>
				</div>
			</li>
		<?php endforeach; ?>
		</ul></section>
	<?php endif; ?>
	<?php echo Show::add($addUrl); ?>
</div>
