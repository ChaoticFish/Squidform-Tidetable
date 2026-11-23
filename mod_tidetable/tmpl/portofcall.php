<?php

/**
 * @package     Tide Table
 * @subpackage  mod_tidetable
 * @license     GNU General Public License version 2 or later
 *
 * Port of call: cards in a grid, a big date on each, the link as a
 * full-width button at the bottom. Good on a home page with a limit set.
 */

\defined('_JEXEC') or die;

use ChaoticFish\Module\TideTable\Site\Helper\Show;
use Joomla\CMS\Language\Text;

/** @var list<object> $categories @var list<object> $events @var int $moduleId @var int $h @var bool $filter @var string $addUrl @var string $copyBase @var list<string> $problems */

$he = 'h' . $h;
?>
<div class="tidetable tt-port" id="tt<?php echo $moduleId; ?>" data-tt-root
	data-tt-showing="<?php echo Show::e(Text::_('MOD_TIDETABLE_SHOWING')); ?>" data-tt-one="<?php echo Show::e(Text::_('MOD_TIDETABLE_SHOWING_1')); ?>" data-tt-none="<?php echo Show::e(Text::_('MOD_TIDETABLE_NONE_FILTER')); ?>">
	<?php echo Show::problems($problems); ?>
	<?php echo $filter ? Show::filter($categories, $moduleId) : ''; ?>
	<?php if (!$events) : ?>
		<?php echo Show::none(); ?>
	<?php else : ?>
		<ul class="tt-cards" data-tt-group>
		<?php foreach ($events as $i => $e) : ?>
			<li class="tt-cardwrap" data-tt-cat="<?php echo Show::e($e->category); ?>">
				<article class="tt-pcard tt-c-<?php echo Show::e($e->category_color); ?><?php echo $e->special ? ' tt-special' : ''; ?>" aria-labelledby="<?php echo Show::id($moduleId, 'e' . $i); ?>">
					<div class="tt-pdate">
						<span class="tt-pmon"><?php echo Show::e(Show::date($e->date, 'M')); ?></span>
						<span class="tt-pday"><?php echo (int) substr($e->date, 8, 2); ?></span>
						<span class="tt-pdow"><?php echo Show::e(Show::date($e->date, 'D')); ?></span>
					</div>
					<div class="tt-pbody">
						<p class="tt-tags">
							<?php if ($e->category_label !== '') : ?><span class="tt-cat"><span class="tt-swatch" aria-hidden="true"></span><?php echo Show::e($e->category_label); ?></span><?php endif; ?>
							<?php echo Show::badge($e); ?>
						</p>
						<<?php echo $he; ?> class="tt-title" id="<?php echo Show::id($moduleId, 'e' . $i); ?>"><?php echo Show::special($e) . Show::e($e->title); ?></<?php echo $he; ?>>
						<p class="tt-when"><time datetime="<?php echo Show::e($e->date); ?>"><?php echo Show::e(Show::date($e->date, 'l, F j, Y')); ?></time><?php echo $e->time !== '' ? '<br>' . Show::e($e->time) : ''; ?></p>
						<?php echo Show::description($e) . Show::registration($e) . Show::fees($e); ?>
					</div>
					<div class="tt-pfoot">
						<?php echo Show::link($e, 'tt-btn'); ?>
						<?php echo Show::copy($e, $copyBase); ?>
					</div>
				</article>
			</li>
		<?php endforeach; ?>
		</ul>
	<?php endif; ?>
	<?php echo Show::add($addUrl); ?>
</div>
