<?php
/**
 * Controller du tableau de bord
 *
 * @author  dev <dev@solire.fr>
 * @license CC by-nc http://creativecommons.org/licenses/by-nc/3.0/fr/
 */

namespace Solire\Back\Controller;
use Solire\Conf\Conf;

/**
 * Controller du tableau de bord
 *
 * @author  dev <dev@solire.fr>
 * @license CC by-nc http://creativecommons.org/licenses/by-nc/3.0/fr/
 */
class Board extends Datatable
{
    /**
     * Charge et renvoi la configuration
     *
     * @param $name Nom du fichier de configuration
     * @return Conf
     */
    protected function getConfig($name)
    {
        $config = parent::getConfig($name);

        $contextConfig = new Conf();
        $contextConfig->set($this->utilisateur, 'user');
        $config['columns']['action']['format']->set($contextConfig, 'context');

        return $config;
    }

    /**
     * Toujours executé avant l'action.
     *
     * @return void
     */
    public function start()
    {
        parent::start();

        if (!$this->appConfig->get('board', 'active')) {
            $this->pageNotFound();
        }
    }
}
