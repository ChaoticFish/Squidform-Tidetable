<?php

/**
 * @package     Tide Table
 * @subpackage  mod_tidetable
 * @license     GNU General Public License version 2 or later
 *
 * Ship's log: a compact table (date, time, event, cost) for a sidebar.
 * On narrow screens each row stacks, with its column names.
 */

\defined('_JEXEC') or die;

use ChaoticFish\Module\TideTable\Site\Helper\Show;
use Joomla\CMS\Language\Text;

/** @var list<object> $categories @var list<object> $events @var int $moduleId @var int $h @var bool $filter @var string $addUrl @var string $copyBase @var list<string> $problems */

$cost = static function (object $e): string {
    if ($e->fee_youth === '' && $e->fee_adult === '') {
        return '';
    }

    $y = $e->fee_youth === '' ? '' : Show::price($e->fee_youth);
    $a = $e->fee_adult === '' ? '' : Show::price($e->fee_adult);

    if ($y === $a || $y === '' || $a === '') {
        return Show::e($a !== '' ? $a : $y);
    }

    return Text::sprintf('MOD_TIDETABLE_FEE_YOUTH', Show::e($y)) . '<br>' . Text::sprintf('MOD_TIDETABLE_FEE_ADULT', Show::e($a));
};
$anyCost = (bool) array_filter($events, static fn ($e) => $e->fee_youth !== '' || $e->fee_adult !== '');
?>
<div class="tidetable tt-log" id="tt<?php echo $moduleId; ?>" data-tt-root
	data-tt-showing="<?php echo Show::e(Text::_('MOD_TIDETABLE_SHOWING')); ?>" data-tt-one="<?php echo Show::e(Text::_('MOD_TIDETABLE_SHOWING_1')); ?>" data-tt-none="<?php echo Show::e(Text::_('MOD_TIDETABLE_NONE_FILTER')); ?>">
	<?php echo Show::problems($problems); ?>
	<?php echo $filter ? Show::filter($categories, $moduleId) : ''; ?>
	<?php if (!$events) : ?>
		<?php echo Show::none(); ?>
	<?php else : ?>
		<table class="tt-table" data-tt-group>
			<caption class="visually-hidden"><?php echo Text::_('MOD_TIDETABLE_LOG_CAPTION'); ?></caption>
			<thead>
				<tr>
					<th scope="col"><?php echo Text::_('MOD_TIDETABLE_DATE'); ?></th>
					<th scope="col"><?php echo Text::_('MOD_TIDETABLE_TIME'); ?></th>
					<th scope="col"><?php echo Text::_('MOD_TIDETABLE_EVENT'); ?></th>
					<?php if ($anyCost) : ?><th scope="col"><?php echo Text::_('MOD_TIDETABLE_COST'); ?></th><?php endif; ?>
				</tr>
			</thead>
			<tbody>
			<?php foreach ($events as $e) : ?>
				<tr class="tt-row tt-c-<?php echo Show::e($e->category_color); ?><?php echo $e->special ? ' tt-special' : ''; ?>" data-tt-cat="<?php echo Show::e($e->category); ?>">
					<td data-label="<?php echo Show::e(Text::_('MOD_TIDETABLE_DATE')); ?>"><time datetime="<?php echo Show::e($e->date); ?>"><span aria-hidden="true"><?php echo Show::e(Show::date($e->date, 'M j')); ?></span><span class="visually-hidden"><?php echo Show::e(Show::date($e->date, 'l, F j')); ?></span></time></td>
					<td data-label="<?php echo Show::e(Text::_('MOD_TIDETABLE_TIME')); ?>"><?php echo Show::e($e->time); ?></td>
					<th scope="row" data-label="<?php echo Show::e(Text::_('MOD_TIDETABLE_EVENT')); ?>">
						<?php if ($e->link !== '') : ?>
							<?php $ext = Show::external($e->link); ?>
							<a href="<?php echo Show::e($e->link); ?>"<?php echo $ext ? ' target="_blank" rel="noopener noreferrer"' : ''; ?>><?php echo Show::special($e) . Show::e($e->title); ?><?php if ($ext) : ?><span class="visually-hidden"> <?php echo Text::_('MOD_TIDETABLE_NEW_TAB'); ?></span><span class="tt-ext" aria-hidden="true">&#8599;</span><?php endif; ?></a>
						<?php else : ?>
							<?php echo Show::special($e) . Show::e($e->title); ?>
						<?php endif; ?>
						<?php if ($e->category_label !== '' || $e->badge !== '') : ?>
							<span class="tt-sub"><?php if ($e->category_label !== '') : ?><span class="tt-swatch" aria-hidden="true"></span><?php echo Show::e($e->category_label); ?><?php endif; ?><?php echo $e->badge !== '' ? ' ' . Show::badge($e) : ''; ?></span>
						<?php endif; ?>
						<?php echo Show::copy($e, $copyBase); ?>
					</th>
					<?php if ($anyCost) : ?><td data-label="<?php echo Show::e(Text::_('MOD_TIDETABLE_COST')); ?>"><?php echo $cost($e); ?></td><?php endif; ?>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
	<?php endif; ?>
	<?php echo Show::add($addUrl); ?>
</div>
