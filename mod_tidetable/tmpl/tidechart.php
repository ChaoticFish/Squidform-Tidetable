<?php

/**
 * @package     Tide Table
 * @subpackage  mod_tidetable
 * @license     GNU General Public License version 2 or later
 *
 * Tide chart: a timeline down the middle, events either side on wide
 * screens (one side on phones); special events get a gold star on the line.
 */

\defined('_JEXEC') or die;

use ChaoticFish\Module\TideTable\Site\Helper\Show;
use Joomla\CMS\Language\Text;

/** @var list<object> $categories @var list<object> $events @var int $moduleId @var int $h @var bool $filter @var string $addUrl @var string $copyBase @var list<string> $problems */

$hm = 'h' . $h;
$he = 'h' . ($h + 1);
?>
<div class="tidetable tt-chart" id="tt<?php echo $moduleId; ?>" data-tt-root
	data-tt-showing="<?php echo Show::e(Text::_('MOD_TIDETABLE_SHOWING')); ?>" data-tt-one="<?php echo Show::e(Text::_('MOD_TIDETABLE_SHOWING_1')); ?>" data-tt-none="<?php echo Show::e(Text::_('MOD_TIDETABLE_NONE_FILTER')); ?>">
	<?php echo Show::problems($problems); ?>
	<?php echo $filter ? Show::filter($categories, $moduleId) : ''; ?>
	<?php if (!$events) : ?>
		<?php echo Show::none(); ?>
	<?php else : ?>
		<div class="tt-line">
		<?php $month = null; foreach ($events as $e) : ?>
			<?php if (Show::month($e->date) !== $month) : ?>
				<?php if ($month !== null) : ?></ol></section><?php endif; ?>
				<?php $month = Show::month($e->date); ?>
				<section class="tt-month" data-tt-group>
				<<?php echo $hm; ?> class="tt-month-title"><span><?php echo Show::e(Show::date($e->date, 'F Y')); ?></span></<?php echo $hm; ?>>
				<ol class="tt-tides">
			<?php endif; ?>
			<li class="tt-tide tt-c-<?php echo Show::e($e->category_color); ?><?php echo $e->special ? ' tt-special' : ''; ?>" data-tt-cat="<?php echo Show::e($e->category); ?>">
				<span class="tt-mark" aria-hidden="true"><?php echo $e->special ? '&#9733;' : ''; ?></span>
				<div class="tt-card">
					<p class="tt-when"><time datetime="<?php echo Show::e($e->date); ?>"><?php echo Show::e(Show::date($e->date, 'l, F j')); ?></time><?php echo $e->time !== '' ? ' <span aria-hidden="true">·</span> ' . Show::e($e->time) : ''; ?></p>
					<<?php echo $he; ?> class="tt-title"><?php echo Show::special($e) . Show::e($e->title); ?></<?php echo $he; ?>>
					<p class="tt-tags">
						<?php if ($e->category_label !== '') : ?><span class="tt-cat"><span class="tt-swatch" aria-hidden="true"></span><?php echo Show::e($e->category_label); ?></span><?php endif; ?>
						<?php echo Show::badge($e); ?>
					</p>
					<?php echo Show::description($e) . Show::registration($e) . Show::fees($e); ?>
					<?php if ($e->link !== '' || ($copyBase !== '' && $e->uid !== '')) : ?>
						<p class="tt-actions"><?php echo Show::link($e) . ' ' . Show::copy($e, $copyBase); ?></p>
					<?php endif; ?>
				</div>
			</li>
		<?php endforeach; ?>
		</ol></section>
		</div>
	<?php endif; ?>
	<?php echo Show::add($addUrl); ?>
</div>
