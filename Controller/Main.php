<?php
/**
 * Controleur principal du back
 *
 * @author  dev <dev@solire.fr>
 * @license CC by-nc http://creativecommons.org/licenses/by-nc/3.0/fr/
 */

namespace Solire\Back\Controller;

use Solire\Lib\Log;
use Solire\Lib\Session;
use Solire\Lib\Path;
use Solire\Lib\FrontController;
use Solire\Lib\Model\FileManager;
use Solire\Lib\Model\GabaritManager;
use Solire\Lib\Hook;
use Solire\Lib\Security\AntiBruteforce;
use Solire\Conf\Loader as ConfLoader;

/**
 * Controleur principal du back
 *
 * @author  dev <dev@solire.fr>
 * @license CC by-nc http://creativecommons.org/licenses/by-nc/3.0/fr/
 */
class Main extends \Solire\Lib\Controller
{

    /**
     * Session en cours
     *
     * @var \Solire\Lib\Session
     */
    public $utilisateur;

    /**
     * Api en cours
     *
     * @var array
     */
    public $api;

    /**
     * Manager des requetes liées aux pages
     *
     * @var \Solire\Lib\Model\GabaritManager
     */
    public $gabaritManager = null;

    /**
     * Manager fichiers
     *
     * @var \Solire\Lib\Model\FileManager
     */
    public $fileManager = null;

    /**
     * Manager fichiers
     *
     * @var \Solire\Lib\Log
     */
    public $log = null;

    /**
     * Always execute before other method in controller
     *
     * @return void
     * @hook back/ start Ajouter facilement des traitements au start du back
     */
    public function start()
    {
        /* Antibruteforce */
        $securityConfigPath = FrontController::search('config/security.yml');
        $securityConfig = ConfLoader::load($securityConfigPath);
        $antiBruteforce = new AntiBruteforce($securityConfig->antibruteforce, $_SERVER['REMOTE_ADDR']);

        if ($antiBruteforce->isBlocking()) {
            header('HTTP/1.0 429 Too Many Requests');
            /*
             * On garde en session le temps restant pour s'en servir
             */
            $_SESSION['so_fail2ban'] = array(
                'remainingTime' => $antiBruteforce->unblockRemainingTime()
            );
            FrontController::run('Error', 'error429Fail2ban');
            die;
        }

        parent::start();

        /*
         * Système de log en BDD
         */
        $this->log = new Log($this->db, '', 0, 'back_log');

        /*
         * Utilisateur connecté ?
         */
        $this->utilisateur = new Session('back');

        if (isset($_POST['log']) && isset($_POST['pwd'])
            && !empty($_POST['log']) && !empty($_POST['pwd'])
        ) {
            try {
                $this->utilisateur->connect(
                    $_POST['log'],
                    $_POST['pwd']
                );
            } catch (\Exception $exc) {
                $log = 'Identifiant : ' . $_POST['log'];
                $this->log->logThis('Connexion échouée', 0, $log);
                throw $exc;
            }

            $this->log->logThis('Connexion réussie', $this->utilisateur->id);

            $message = 'Connexion réussie, vous allez être redirigé';

            exit(json_encode(array('success' => true, 'message' => $message)));
        }

        if (!$this->utilisateur->isConnected()
            && (!isset($this->noRedirect) || $this->noRedirect === false)
        ) {
            $this->simpleRedirect('back/sign/start.html', true);
        }

        if (isset($_COOKIE['api'])) {
            $nameApi = $_COOKIE['api'];
        } else {
            $nameApi = 'main';
        }

        $query = 'SELECT id '
               . 'FROM gab_api '
               . 'WHERE name = ' . $this->db->quote($nameApi) . ' ';

        $idApi = $this->db->query($query)->fetch(\PDO::FETCH_COLUMN);

        if (intval($idApi) == 0) {
            $idApi = 1;
        }

        $query = 'SELECT * '
               . 'FROM gab_api '
               . 'WHERE id = ' . $idApi . ' ';
        $this->api = $this->db->query($query)->fetch(\PDO::FETCH_ASSOC);

        $query = 'SELECT * '
               . 'FROM gab_api ';
        $this->apis = $this->db->query($query)->fetchAll(
            \PDO::FETCH_UNIQUE | \PDO::FETCH_ASSOC
        );
        if (!defined('BACK_ID_API')) {
            define('BACK_ID_API', $this->api['id']);
        }

        $this->javascript->addLibrary('back/js/jquery/jquery-1.8.0.min.js');
        $this->javascript->addLibrary('back/js/jquery/jquery-ui-1.8.23.custom.min.js');
        $this->javascript->addLibrary('back/js/main.js');
        $this->javascript->addLibrary('back/js/jquery/jquery.cookie.js');
        $this->javascript->addLibrary('back/js/jquery/sticky.js');
        $this->javascript->addLibrary('back/js/jquery/jquery.livequery.min.js');

        $this->javascript->addLibrary('back/js/jquery/jquery.stickyPanel.min.js');

        $this->javascript->addLibrary('back/js/newstyle.js');
        $this->css->addLibrary('back/css/jquery-ui-1.8.7.custom.css');

        $this->css->addLibrary('back/css/jquery-ui/custom-theme/jquery-ui-1.8.22.custom.css');

        /* Inclusion Bootstrap twitter */
        $this->javascript->addLibrary('back/js/bootstrap/bootstrap.min.js');
        $this->css->addLibrary('back/css/bootstrap/bootstrap.min.css');
        $this->css->addLibrary('back/css/bootstrap/bootstrap-responsive.min.css');

        /* font-awesome */
        $this->css->addLibrary('back/css/font-awesome/css/font-awesome.min.css');

        $this->css->addLibrary('back/css/newstyle-1.3.css');
        $this->css->addLibrary('back/css/sticky.css');

        $this->view->site = \Solire\Lib\Registry::get('project-name');

        if (isset($_GET['controller'])) {
            $this->view->controller = $_GET['controller'];
        } else {
            $this->view->controller = '';
        }

        if (isset($_GET['action'])) {
            $this->view->action = $_GET['action'];
        } else {
            $this->view->action = '';
        }

        $className = FrontController::searchClass('Model\GabaritManager');
        if ($className !== false) {
            $this->gabaritManager = new $className();
        } else {
            $this->gabaritManager = new GabaritManager();
        }

        $this->fileManager = new FileManager();

        $query = 'SELECT `version`.id, `version`.* '
               . 'FROM `version` '
               . 'WHERE `version`.`id_api` = ' . $this->api['id'] . ' ';

        $this->versions = $this->db->query($query)->fetchAll(
            \PDO::FETCH_UNIQUE | \PDO::FETCH_ASSOC
        );

        if (isset($_GET['id_version'])) {
            $id_version = $_GET['id_version'];
            $url = '/' . \Solire\Lib\Registry::get('baseroot');
            setcookie('id_version', $id_version, 0, $url);
            if (!defined('BACK_ID_VERSION')) {
                define('BACK_ID_VERSION', $id_version);
            }
        } elseif (isset($_POST['id_version'])) {
            $id_version = $_POST['id_version'];
            $url = '/' . \Solire\Lib\Registry::get('baseroot');
            setcookie('back_id_version', $id_version, 0, $url);
            if (!defined('BACK_ID_VERSION')) {
                define('BACK_ID_VERSION', $id_version);
            }
        } elseif (isset($_COOKIE['back_id_version'])
            && isset($this->versions[$_COOKIE['back_id_version']])
        ) {
            if (!defined('BACK_ID_VERSION')) {
                define('BACK_ID_VERSION', $_COOKIE['back_id_version']);
            }
        } else {
            if (!defined('BACK_ID_VERSION')) {
                define('BACK_ID_VERSION', 1);
            }
        }

        if (isset($_POST['log']) && isset($_POST['pwd'])
            && ($_POST['log'] == '' || $_POST['pwd'] == '')
        ) {
            $retour = array(
                'success' => false,
                'message' => 'Veuillez renseigner l\'identifiant et le mot de passe'
            );
            exit(json_encode($retour));
        }

        $this->view->utilisateur = $this->utilisateur;
        $this->view->apis = $this->apis;
        $this->view->api = $this->api;
        $this->view->javascript = $this->javascript;
        $this->view->css = $this->css;
        $this->view->mainVersions = $this->versions;
        $query = 'SELECT `version`.id, `version`.* '
               . 'FROM `version` '
               . 'WHERE `version`.id_api = ' . $this->api['id'] . ' ';
        $this->view->mainVersions = $this->db->query($query)->fetchAll(
            \PDO::FETCH_UNIQUE | \PDO::FETCH_ASSOC
        );
        $this->view->breadCrumbs = array();
        $this->view->breadCrumbs[] = array(
            'label' => $this->view->img->output('back/img/gray_dark/home_12x12.png')
                    . $this->view->site,
        );

        /* On indique que l'on est dans une autre api **/
        if ($this->api['id'] != 1) {
            $this->view->breadCrumbs[] = array(
                    'label' => $this->api['label'],
            );
        }

        $this->view->appConfig = $this->appConfig;

        /*
         * On recupere la configuration du module pages (Menu + liste)
         */
        $completConfig = [];
        $path = FrontController::search(
            'config/page-' . BACK_ID_API . '.cfg.php'
        );
        if ($path !== false) {
            include $path;
            $completConfig = $config;
        } else {
            $path = FrontController::search('config/page.cfg.php');
            if ($path !== false) {
                include $path;
                $completConfig = $config;
            }
        }

//        $completConfig = [];
//        $appList = \Solire\Lib\FrontController::getAppDirs();
//        unset($config);
//        foreach ($appList as $app) {
//           /**
//            * On recupere la configuration du module pages (Menu + liste)
//            *  En cherchant si une configuration a été définie pour l'api courante
//            * Sinon on récupère le fichier de configuration générale
//            */
//            $path = new Path(
//                $app['dir'] . Path::DS . 'back/config/page-' . BACK_ID_API . '.cfg.php',
//                Path::SILENT
//            );
//            if ($path->get() == false) {
//                $path = new Path(
//                    $app['dir'] . Path::DS . 'back/config/page.cfg.php',
//                    Path::SILENT
//                );
//            }
//
//            if ($path->get() == false) {
//                continue;
//            }
//            include $path->get();
//
//            if (!isset($config)) {
//                $exc = new \Exception('fichier de config erroné [' . $path->get() . ']');
//                throw $exc;
//            }
//
//            $completConfig = $completConfig + $config;
//
//            unset($config, $key, $value);
//        }

        $this->configPageModule = $completConfig;
        unset($path, $config);
        $this->view->menuPage = [];
        foreach ($this->configPageModule as $configPage) {
            $this->view->menuPage[] = [
                'label' => $configPage['label'],
                'display' => $configPage['display'],
            ];
        }

        $query = 'SELECT gab_gabarit.id, gab_gabarit.* '
               . 'FROM gab_gabarit '
               . 'WHERE gab_gabarit.id_api = ' . $this->api['id'] . ' ';
        $this->gabarits = $this->db->query($query)->fetchAll(
            \PDO::FETCH_UNIQUE | \PDO::FETCH_ASSOC
        );

        $query = 'SELECT * '
               . 'FROM gab_page gp '
               . 'WHERE rewriting = "" '
               . ' AND gp.suppr = 0 '
               . ' AND id_api = ' . BACK_ID_API
               . ' AND id_version = ' . BACK_ID_VERSION;

        $this->view->pagesNonTraduites = $this->db->query($query)->fetchAll(
            \PDO::FETCH_ASSOC
        );

        $hook = new Hook();
        $hook->setSubdirName('back');

        $hook->controller = $this;

        $hook->exec('start');
    }

    /**
     * Fonction éxécutée après l'execution de la fonction relative à la page en cours
     *
     * @return void
     * @hook back/ shutdown Avant l'inclusion de la vue
     */
    public function shutdown()
    {
        parent::shutdown();

        $hook = new Hook();
        $hook->setSubdirName('back');

        $hook->controller = $this;

        $hook->exec('shutdown');
    }
}
