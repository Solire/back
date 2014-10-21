<?php
/**
 * Controleur des Erreurs
 *
 * @author  dev <dev@solire.fr>
 * @license CC by-nc http://creativecommons.org/licenses/by-nc/3.0/fr/
 */

namespace Solire\Back\Controller;

/**
 * Controleur des Erreurs
 *
 * @author  dev <dev@solire.fr>
 * @license CC by-nc http://creativecommons.org/licenses/by-nc/3.0/fr/
 */
class Error extends Main
{
    /**
     * Méthode toujours exécuté
     *
     * @return void
     */
    public function start()
    {
        parent::start();
    }

    /**
     * Action de page non trouvée
     *
     * @return void
     */
    public function error404Action()
    {
    }
}
