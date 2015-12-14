<?php
/**
 * Controller du datatable des fichiers
 *
 * @author  dev <dev@solire.fr>
 * @license CC by-nc http://creativecommons.org/licenses/by-nc/3.0/fr/
 */

namespace Solire\Back\Controller;
use Solire\Conf\Conf;

/**
 * Controller du datatable des fichiers
 *
 * @author  dev <dev@solire.fr>
 * @license CC by-nc http://creativecommons.org/licenses/by-nc/3.0/fr/
 */
class Mediadatatable extends Datatable
{
    protected function getConfig($name)
    {
        $config = parent::getConfig($name);

        $prefixUrl = null;

        if ($this->mainConfig->get('upload', 'prefixUrl')) {
            $prefixUrl = $this->mainConfig->get('upload', 'prefixUrl') . '/';
        }

        if (isset($_REQUEST['prefix_url'])) {
            $prefixUrl = $_REQUEST['prefix_url'] . DIRECTORY_SEPARATOR;
        }

        $contextConfig = new Conf();
        $contextConfig->set($prefixUrl, 'prefixUrl');
        $config['columns']['fullpath']['format']->set($contextConfig, 'context');

        return $config;
    }
}
