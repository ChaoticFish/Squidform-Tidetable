<?php

/**
 * @package     Tide Table
 * @subpackage  plg_squidform_tidetable
 * @license     GNU General Public License version 2 or later
 */

\defined('_JEXEC') or die;

use ChaoticFish\Plugin\SquidForm\TideTable\Extension\TideTable;
use Joomla\CMS\Extension\PluginInterface;
use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;

return new class () implements ServiceProviderInterface {
    public function register(Container $container): void
    {
        $container->set(
            PluginInterface::class,
            function (Container $container) {
                $plugin = new TideTable((array) PluginHelper::getPlugin('squidform', 'tidetable'));
                $plugin->setApplication(Factory::getApplication());

                return $plugin;
            }
        );
    }
};
