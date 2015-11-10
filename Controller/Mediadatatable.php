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

        $requestUrl = $config['driver']['conf']['requestUrl'] . '&id_gab_page=' . $_GET['id_gab_page']
            . '&id_temp=' . $_GET['id_temp'];

        $contextConfig = new Conf();
        $contextConfig->set($prefixUrl, 'prefixUrl');
        $config['columns']['fullpath']['format']->set($contextConfig, 'context');
        $config['driver']['conf']->set($requestUrl, 'requestUrl');
        $whereArray = [
            'm.id_gab_page = ' . (int) $_GET['id_gab_page'],
            'm.id_temp = ' . (int) $_GET['id_temp'],
        ];
        $config['source']['conf']->set($whereArray, 'where');

        return $config;
    }
}
