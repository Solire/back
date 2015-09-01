<?php
/**
 * Controller du tableau de bord
 *
 * @author  dev <dev@solire.fr>
 * @license CC by-nc http://creativecommons.org/licenses/by-nc/3.0/fr/
 */

namespace Solire\Back\Controller;

/**
 * Controller du tableau de bord
 *
 * @author  dev <dev@solire.fr>
 * @license CC by-nc http://creativecommons.org/licenses/by-nc/3.0/fr/
 */
class Board extends Datatable
{
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
