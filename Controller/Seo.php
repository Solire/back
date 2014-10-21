<?php
/**
 * Controlleur Seo
 *
 * @author  dev <dev@solire.fr>
 * @license CC by-nc http://creativecommons.org/licenses/by-nc/3.0/fr/
 */

namespace Solire\Back\Controller;

/**
 * Controlleur Seo
 *
 * @author  dev <dev@solire.fr>
 * @license CC by-nc http://creativecommons.org/licenses/by-nc/3.0/fr/
 */
class Seo extends Main
{
    /**
     * Empêche la redirection en cas de non connexion
     *
     * @var boolean
     */
    protected $noRedirect = true;

    /**
     * Récupère les redirections 301
     *
     * @return void
     */
    public function get301Action()
    {
        $this->_view->enable(false);
        $url301 = $this->_db->select('redirection', false, ['*']);
        echo json_encode($url301);
    }
}
