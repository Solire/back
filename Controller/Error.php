<?php
/**
 * Contrôleur des erreurs.
 *
 * @author  dev <dev@solire.fr>
 * @license CC by-nc http://creativecommons.org/licenses/by-nc/3.0/fr/
 */

namespace Solire\Back\Controller;

use Solire\Lib\Controller;
use Solire\Lib\Registry;

/**
 * Contrôleur des erreurs.
 *
 * @author  dev <dev@solire.fr>
 * @license CC by-nc http://creativecommons.org/licenses/by-nc/3.0/fr/
 */
class Error extends Controller
{
    /**
     * Méthode toujours exécuté.
     *
     * @return void
     */
    public function start()
    {
        parent::start();
        $this->view->site = Registry::get('project-name');
        $this->view->code = null;
        $this->view->title = 'OOPS! - Une erreur est survenue';
        $this->view->btn = [
            'content' => 'Retour',
            'href' => 'javascript:history.back();',
        ];

        $this->view->setMainPath('error/errorMain');
    }

    /**
     * Action de page non trouvée.
     *
     * @return void
     */
    public function error404Action()
    {
        $this->view->code = 404;
        $this->view->title = 'OOPS! - Page non trouvée';
    }

    /**
     * Action de page Trop de requêtes.
     *
     * @return void
     */
    public function error429Action()
    {
        $this->view->code = 429;
        $this->view->title = 'OOPS! - Trop de requêtes';
        $this->view->message = ' Merci de réitérer ultérieurement.';

        $this->view->btn = [
            'content' => 'Rafraîchir la page',
            'href' => 'javascript:location.reload();',
        ];
    }

    /**
     * Action de page Trop de requêtes.
     *
     * @return void
     */
    public function error429Fail2banAction()
    {
        $timeRemaining = null;
        if (isset($_SESSION['so_fail2ban'])
            && isset($_SESSION['so_fail2ban']['remainingTime'])
        ) {
            $timeRemaining = $_SESSION['so_fail2ban']['remainingTime'];
        }

        $this->view->code = 429;
        $this->view->title = 'OOPS! - Trop de tentatives d\'authentification infructueuses';
        $this->view->timeRemaining = $timeRemaining;
        $this->view->message = 'La protection de l\'authentification contre les'
                . ' attaques par force brute a été activée.';
        if ($timeRemaining == null) {
            $this->view->message .= ' Merci de réitérer ultérieurement.';
        } else {
            $this->view->message .= '<br>La prochaine tentative'
                    . ' d\'authentification sera possible dans : ';
        }

        $this->view->btn = [
            'content' => 'Rafraîchir la page',
            'href' => 'javascript:location.reload();',
        ];
    }
}
