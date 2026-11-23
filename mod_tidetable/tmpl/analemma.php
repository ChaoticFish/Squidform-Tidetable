<?php

/**
 * @package     Tide Table
 * @subpackage  mod_tidetable
 * @license     GNU General Public License version 2 or later
 *
 * Analemma: the Analemma site's timeline, with its old class names
 * (.analemma-events-module, .timeline-item, .ti-*), so its theme CSS still
 * applies. New: fees (.ti-fees), registration (.ti-reg), Copy (.ti-copy),
 * real headings and a list.
 */

\defined('_JEXEC') or die;

use ChaoticFish\Module\TideTable\Site\Helper\Show;
use Joomla\CMS\Language\Text;

/** @var list<object> $categories @var list<object> $events @var int $moduleId @var int $h @var bool $filter @var string $addUrl @var string $copyBase @var list<string> $problems */

$hm = 'h' . $h;
$he = 'h' . ($h + 1);
?>
<div class="tidetable analemma-events-module" id="tt<?php echo $moduleId; ?>" data-tt-root
	data-tt-showing="<?php echo Show::e(Text::_('MOD_TIDETABLE_SHOWING')); ?>" data-tt-one="<?php echo Show::e(Text::_('MOD_TIDETABLE_SHOWING_1')); ?>" data-tt-none="<?php echo Show::e(Text::_('MOD_TIDETABLE_NONE_FILTER')); ?>">
	<?php echo Show::problems($problems); ?>
	<?php echo $filter ? Show::filter($categories, $moduleId, 'filter-chip-bar', 'filter-chip') : ''; ?>
	<div class="timeline">
		<?php if (!$events) : ?>
			<?php echo Show::none('timeline-empty'); ?>
		<?php else : ?>
			<?php $month = null; foreach ($events as $e) : ?>
				<?php if (Show::month($e->date) !== $month) : ?>
					<?php if ($month !== null) : ?></ul></div><?php endif; ?>
					<?php $month = Show::month($e->date); ?>
					<div class="timeline-group" data-tt-group>
					<<?php echo $hm; ?> class="timeline-month" data-month-key="<?php echo Show::e($month); ?>"><?php echo Show::e(Show::date($e->date, 'F Y')); ?></<?php echo $hm; ?>>
					<ul class="timeline-list">
				<?php endif; ?>
				<li class="timeline-item<?php echo $e->special ? ' special' : ''; ?>" data-category="<?php echo Show::e($e->category); ?>" data-tt-cat="<?php echo Show::e($e->category); ?>">
					<div class="ti-row">
						<span class="ti-date"><?php echo Show::e(Show::date($e->date, 'D, F j')); ?></span>
						<?php if ($e->time !== '') : ?><span class="ti-time"><?php echo Show::e($e->time); ?></span><?php endif; ?>
						<?php echo Show::badge($e, 'ti-badge'); ?>
						<<?php echo $he; ?> class="ti-heading">
						<?php if ($e->link !== '') : ?>
							<?php $ext = Show::external($e->link); ?>
							<a class="ti-title ti-link" href="<?php echo Show::e($e->link); ?>"<?php echo $ext ? ' target="_blank" rel="noopener noreferrer"' : ''; ?>>
								<?php echo Show::special($e) . Show::e($e->title); ?>
								<span class="ti-link-icon" aria-hidden="true">&#8599;</span>
								<span class="ti-link-label"><?php echo Show::e($e->link_label !== '' ? $e->link_label : Text::_('MOD_TIDETABLE_DETAILS')); ?></span>
								<?php if ($ext) : ?><span class="visually-hidden"><?php echo Text::_('MOD_TIDETABLE_NEW_TAB'); ?></span><?php endif; ?>
							</a>
						<?php else : ?>
							<span class="ti-title"><?php echo Show::special($e) . Show::e($e->title); ?></span>
						<?php endif; ?>
						</<?php echo $he; ?>>
						<?php if ($e->description !== '') : ?><div class="ti-desc"><?php echo nl2br(Show::e($e->description), false); ?></div><?php endif; ?>
						<?php echo Show::registration($e, 'ti-reg') . Show::fees($e, 'ti-fees') . Show::copy($e, $copyBase, 'ti-copy'); ?>
					</div>
				</li>
			<?php endforeach; ?>
			</ul></div>
		<?php endif; ?>
	</div>
	<?php echo Show::add($addUrl, 'ti-add'); ?>
</div>
