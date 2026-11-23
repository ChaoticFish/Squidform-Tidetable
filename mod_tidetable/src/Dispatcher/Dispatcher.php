<?php

/**
 * @package     Tide Table
 * @subpackage  mod_tidetable
 * @license     GNU General Public License version 2 or later
 */

namespace ChaoticFish\Module\TideTable\Site\Dispatcher;

use ChaoticFish\Module\TideTable\Site\Helper\EventSource;
use Joomla\CMS\Dispatcher\AbstractModuleDispatcher;
use Joomla\CMS\Router\Route;

\defined('_JEXEC') or die;

final class Dispatcher extends AbstractModuleDispatcher
{
    protected function getLayoutData(): array
    {
        $data   = parent::getLayoutData();
        $params = $data['params'];
        $app    = $this->getApplication();
        $user   = $app->getIdentity();
        $src    = new EventSource($params);

        [$data['categories'], $data['events']] = $src->load();

        // Problems only for people who can change modules; visitors just see "no events".
        $data['problems'] = $user && $user->authorise('core.edit', 'com_modules') ? $src->problems : [];
        $data['moduleId'] = (int) $this->module->id;
        $data['h']        = max(2, min(5, (int) $params->get('heading', 3)));
        $data['filter']   = (bool) $params->get('show_filter', 1) && \count($data['categories']) > 1;

        // Adding and copying events (SquidForm only).
        $page             = (int) $params->get('add_page', 0);
        $squid            = $params->get('source', 'squidform') === 'squidform';
        $data['addUrl']   = $squid && $page && $params->get('show_add', 1) ? Route::_('index.php?Itemid=' . $page) : '';
        $groups           = array_map('intval', (array) $params->get('copy_groups', [7, 8]));
        $data['copyBase'] = $squid && $page && $user && $user->id && array_intersect($groups, $user->getAuthorisedGroups())
            ? 'index.php?Itemid=' . $page . '&tt_copy=' : '';

        $wa = $app->getDocument()->getWebAssetManager();
        $layout = basename((string) $params->get('layout', '_:default'));
        $layout = str_contains($layout, ':') ? substr($layout, strpos($layout, ':') + 1) : $layout;

        $wa->registerAndUseStyle('mod_tidetable.' . ($layout === 'analemma' ? 'analemma' : 'tidetable'), 'mod_tidetable/' . ($layout === 'analemma' ? 'analemma' : 'tidetable') . '.min.css');
        $wa->registerAndUseScript('mod_tidetable', 'mod_tidetable/tidetable.js', [], ['defer' => true]);

        return $data;
    }
}
