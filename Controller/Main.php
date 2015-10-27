<?php
/**
 * Contrôleur principal du back
 *
 * @author  dev <dev@solire.fr>
 * @license CC by-nc http://creativecommons.org/licenses/by-nc/3.0/fr/
 */

namespace Solire\Back\Controller;

use Monolog\Logger;
use Solire\Lib\Monolog\Handler\PDOHandler;
use Solire\Lib\Controller;
use Solire\Lib\Registry;
use Solire\Lib\Session;
use Solire\Lib\FrontController;
use Solire\Lib\Model\FileManager;
use Solire\Lib\Model\GabaritManager;
use Solire\Lib\Hook;
use Solire\Lib\Security\AntiBruteforce\AntiBruteforce;
use Solire\Conf\Loader as ConfLoader;
use Solire\Lib\Loader\RequireJs;

/**
 * Contrôleur principal du back
 *
 * @author  dev <dev@solire.fr>
 * @license CC by-nc http://creativecommons.org/licenses/by-nc/3.0/fr/
 */
class Main extends Controller
{

    /**
     * Session en cours
     *
     * @var Session
     */
    public $utilisateur;

    /**
     * Api en cours
     *
     * @var array
     */
    public $api;

    /**
     * Manager des requêtes liées aux pages
     *
     * @var GabaritManager
     */
    public $gabaritManager = null;

    /**
     * Manager fichiers
     *
     * @var FileManager
     */
    public $fileManager = null;

    /**
     * Logger relatif aux actions de l'utilisateur
     *
     * @var Logger
     */
    public $userLogger = null;

    /**
     * Chargeur de script pour requireJS
     *
     * @var RequireJs
     */
    protected $requireJs = null;

    /**
     * Fonction appelé avant l'appel à la méthode du contrôleur
     *
     * @throws \Exception
     * @throws \Solire\Conf\Exception
     * @throws \Solire\Lib\Exception\HttpError
     * @throws \Solire\Lib\Exception\lib
     * @throws \Solire\Lib\Security\AntiBruteforce\Exception\InvalidIpException
     * @hook back/ start Ajouter facilement des traitements au start du back
     * @return void
     */
    public function start()
    {
        /* Antibruteforce */
        $securityConfigPath = FrontController::search('config/security.yml');
        $securityConfig     = ConfLoader::load($securityConfigPath);
        $antiBruteforce     = new AntiBruteforce($securityConfig->antibruteforce, $_SERVER['REMOTE_ADDR']);

        if ($antiBruteforce->isBlocking()) {
            header('HTTP/1.0 429 Too Many Requests');
            /*
             * On garde en session le temps restant pour s'en servir
             */
            $_SESSION['so_fail2ban'] = [
                'remainingTime' => $antiBruteforce->unblockRemainingTime()
            ];
            FrontController::run('Error', 'error429Fail2ban');
            die;
        }

        parent::start();

        /*
         * Système de log en BDD
         */
        $userLogger = new Logger('backUser');
        $userLogger->pushHandler(new PDOHandler($this->db));
        $this->userLogger = $userLogger;

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
                $login = filter_var($_POST['log'], FILTER_SANITIZE_STRING);
                $this->userLogger->addError(
                    'Connexion échouée',
                    [
                        'user' => [
                            'login' => $login,
                        ]
                    ]
                );
                throw $exc;
            }

            $this->userLogger->addInfo(
                'Connexion réussie',
                [
                    'user' => [
                        'id'    => $this->utilisateur->id,
                        'login' => $this->utilisateur->login,
                    ]
                ]
            );

            $message = 'Connexion réussie, vous allez être redirigé';

            exit(json_encode(['success' => true, 'message' => $message]));
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

        $query     = 'SELECT * '
            . 'FROM gab_api '
            . 'WHERE id = ' . $idApi . ' ';
        $this->api = $this->db->query($query)->fetch(\PDO::FETCH_ASSOC);

        $query      = 'SELECT * '
            . 'FROM gab_api ';
        $this->apis = $this->db->query($query)->fetchAll(
            \PDO::FETCH_UNIQUE | \PDO::FETCH_ASSOC
        )
        ;
        if (!defined('BACK_ID_API')) {
            define('BACK_ID_API', $this->api['id']);
        }

        $this->loadRessources();

        $this->view->site = Registry::get('project-name');

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
        )
        ;

        if (isset($_GET['id_version'])) {
            $id_version = $_GET['id_version'];
            $url        = '/' . Registry::get('baseroot');
            setcookie('id_version', $id_version, 0, $url);
            if (!defined('BACK_ID_VERSION')) {
                define('BACK_ID_VERSION', $id_version);
            }
        } elseif (isset($_POST['id_version'])) {
            $id_version = $_POST['id_version'];
            $url        = '/' . Registry::get('baseroot');
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
            $retour = [
                'success' => false,
                'message' => 'Veuillez renseigner l\'identifiant et le mot de passe'
            ];
            exit(json_encode($retour));
        }

        $this->view->utilisateur   = $this->utilisateur;
        $this->view->apis          = $this->apis;
        $this->view->api           = $this->api;
        $this->view->javascript    = $this->javascript;
        $this->view->requireJs     = $this->requireJs;
        $this->view->css           = $this->css;
        $this->view->mainVersions  = $this->versions;
        $query                     = 'SELECT `version`.id, `version`.* '
            . 'FROM `version` '
            . 'WHERE `version`.id_api = ' . $this->api['id'] . ' ';
        $this->view->mainVersions  = $this->db->query($query)->fetchAll(
            \PDO::FETCH_UNIQUE | \PDO::FETCH_ASSOC
        )
        ;
        $this->view->breadCrumbs   = [];
        $this->view->breadCrumbs[] = [
            'title' => '<i class="fa fa-home"></i>'
                . ' '
                . $this->view->site,
        ];

        /* On indique que l'on est dans une autre api **/
        if ($this->api['id'] != 1) {
            $this->view->breadCrumbs[] = [
                'title' => $this->api['label'],
            ];
        }

        $this->view->appConfig = $this->appConfig;

        /*
         * On récupère la configuration du module pages (Menu + liste)
         */
        $completConfig = [];
        $path          = FrontController::search(
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

        $this->configPageModule = $completConfig;
        unset($path, $config);
        $this->view->menuPage = [];
        foreach ($this->configPageModule as $configPage) {
            $this->view->menuPage[] = [
                'label'   => $configPage['label'],
                'display' => $configPage['display'],
            ];
        }

        $query          = 'SELECT gab_gabarit.id, gab_gabarit.* '
            . 'FROM gab_gabarit '
            . 'WHERE gab_gabarit.id_api = ' . $this->api['id'] . ' ';
        $this->gabarits = $this->db->query($query)->fetchAll(
            \PDO::FETCH_UNIQUE | \PDO::FETCH_ASSOC
        )
        ;

        $query = 'SELECT * '
            . 'FROM gab_page gp '
            . 'WHERE rewriting = "" '
            . ' AND gp.suppr = 0 '
            . ' AND id_api = ' . BACK_ID_API
            . ' AND id_version = ' . BACK_ID_VERSION;

        $this->view->pagesNonTraduites = $this->db->query($query)->fetchAll(
            \PDO::FETCH_ASSOC
        )
        ;

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
        $hook->setSubdirName('Back');

        $hook->controller = $this;

        $hook->exec('Shutdown');

        $title = 'Module de gestion du site ' . Registry::get('project-name');
        if (isset($this->breadCrumbs) && count($this->breadCrumbs) > 1) {
            foreach ($this->breadCrumbs as $iLink => $link) {
                if ($iLink == 0) {
                    continue;
                }

                if ($iLink == count($this->breadCrumbs) - 1) {
                    $title .= ' ' . $link['title'];
                } else {
                    $title .= ' ' . $link['title'] . ' > ';
                }
            }
        }
        $this->seo->setTitle($title);

        $this->view->backIdVersion = BACK_ID_VERSION;
    }

    /**
     * Initialisation et chargement des ressources
     *
     * @throws \Solire\Lib\Exception\Lib
     *
     * @return void;
     */
    protected function loadRessources()
    {
        /* Chargement de requireJS */
        $requireInitPath = $this->javascript->getPath('back/js/init.js');
        $this->javascript->addLibrary(
            'back/bower_components/requirejs/require.js',
            ['data-main' => $requireInitPath]
        );

        $this->requireJs = new RequireJs(FrontController::$publicDirs);

        /* Jquery */
        $this->requireJs->addLibrary(
            'back/bower_components/jquery/dist/jquery.min.js',
            ['name' => 'jquery']
        );

        /* Sortable */
        $this->requireJs->addLibrary(
            'back/bower_components/Sortable/Sortable.js',
            [
                'name' => 'sortable',
            ]
        );

        /* Jquery cookie */
        $this->requireJs->addLibrary(
            'back/bower_components/jquery.cookie/jquery.cookie.js',
            [
                'name' => 'jqueryCookie',
                'deps' => [
                    'jquery',
                ]
            ]
        );

        /* Bootstrap */
        $this->requireJs->addLibrary(
            'back/bower_components/bootstrap/dist/js/bootstrap.min.js',
            [
                'name' => 'bootstrap',
                'deps' => [
                    'jquery',
                ]
            ]
        );

        $this->css->addLibrary('back/bower_components/bootstrap/dist/css/bootstrap.min.css');

        /* Bootstrap meteriel design */
        $this->requireJs->addLibrary(
            'back/bower_components/bootstrap-material-design/dist/js/ripples.min.js',
            [
                'name' => 'ripples',
                'deps' => [
                    'bootstrap',
                ]
            ]
        );

        $this->requireJs->addLibrary(
            'back/bower_components/bootstrap-material-design/dist/js/material.min.js',
            [
                'name' => 'material',
                'deps' => [
                    'bootstrap',
                ]
            ]
        );

//        $this->css->addLibrary('back/bower_components/bootstrap-material-design/dist/css/roboto.min.css');
//        $this->css->addLibrary('back/bower_components/bootstrap-material-design/dist/css/material.min.css');
        $this->css->addLibrary('back/bower_components/bootstrap-material-design/dist/css/ripples.min.css');

//        $this->css->addLibrary('back/bower_components/bootstrap/dist/css/bootstrap-theme.min.css');
//        $this->css->addLibrary('back/css/bootstrap-theme/bootstrap.min.css');

        /* Bootstrap datepicker */
        $this->requireJs->addLibrary(
            'back/bower_components/bootstrap-datepicker/js/bootstrap-datepicker.js',
            [
                'name' => 'bootstrapDatepicker',
                'deps' => [
                    'bootstrap',
                ]
            ]
        );

        $this->css->addLibrary('back/bower_components/bootstrap-datepicker/css/datepicker3.css');

        // Fichier de traduction FR du datepicker
        $this->requireJs->addLibrary(
            'back/bower_components/bootstrap-datepicker/js/locales/bootstrap-datepicker.fr.js',
            [
                'name' => 'bootstrapDatepickerFr',
                'deps' => [
                    'bootstrapDatepicker',
                ]
            ]
        );

        /* Bootstrap autocomplete */
        $this->requireJs->addLibrary(
            'back/bower_components/select2/dist/js/select2.full.min.js',
            [
                'name' => 'autocomplete',
                'deps' => [
                    'jquery',
                ]
            ]
        );

        $this->css->addLibrary('back/bower_components/select2/dist/css/select2.min.css');
        $this->css->addLibrary('back/bower_components/select2-bootstrap-theme/dist/select2-bootstrap.min.css');

        $this->requireJs->addLibrary(
            'back/bower_components/select2/dist/js/i18n/fr.js',
            [
                'name' => 'autocompleteFr',
                'deps' => [
                    'autocomplete',
                ]
            ]
        );

        /* Bootstrap typeahead */
        $this->requireJs->addLibrary(
            'back/bower_components/typeahead.js/dist/typeahead.jquery.min.js',
            [
                'name' => 'typeahead',
                'deps' => [
                    'jquery',
                ]
            ]
        );

        /* Jcrop */
        $this->requireJs->addLibrary(
            'back/bower_components/Jcrop/js/Jcrop.js',
            [
                'name' => 'jcrop',
                'deps' => [
                    'jquery',
                ]
            ]
        );

        $this->css->addLibrary(
            'back/bower_components/Jcrop/css/Jcrop.min.css'
        );

        /* jquery.scrollTo */
        $this->requireJs->addLibrary(
            'back/bower_components/jquery.scrollTo/jquery.scrollTo.min.js',
            [
                'name' => 'jqueryScrollTo',
                'deps' => [
                    'jquery',
                ]
            ]
        );

        /* Youtube loading Bar */
        $this->requireJs->addLibrary(
            'back/bower_components/youtube-loading-bar/dist/js/youtubeLoadingBar.min.js',
            [
                'name' => 'youtubeLoadingBar',
                'deps' => [
                    'jquery',
                ]
            ]
        );

        $this->css->addLibrary(
            'back/bower_components/youtube-loading-bar/dist/css/youtubeLoadingBar.min.css'
        );

        /* Datatables */
        $this->requireJs->addLibrary(
            'back/bower_components/datatables/media/js/jquery.dataTables.js',
            [
                'name' => 'datatables',
                'deps' => [
                    'jquery',
                ]
            ]
        );

        $this->requireJs->addLibrary(
            'back/bower_components/datatables-responsive/js/dataTables.responsive.js',
            [
                'name' => 'datatablesResponsive',
                'deps' => [
                    'jquery',
                    'datatables',
                ]
            ]
        );

        $this->requireJs->addLibrary(
            'back/bower_components/datatables-material-design/dist/js/dataTables.materialdesign.min.js',
            [
                'name' => 'datatablesMaterialDesign',
                'deps' => [
                    'datatables',
                    'youtubeLoadingBar',
                ]
            ]
        );

        $this->css->addLibrary(
            'back/bower_components/datatables-material-design/dist/css/dataTables.materialdesign.min.css'
        );

        $this->requireJs->addLibrary(
            'back/bower_components/datatables-light-columnfilter/dist/dataTables.lightColumnFilter.min.js',
            [
                'name' => 'datatablesLightColumnfilter',
                'deps' => [
                    'datatables',
                ]
            ]
        );

        /* Plupload */
        $this->requireJs->addLibrary(
            'back/bower_components/plupload/js/plupload.full.min.js',
            [
                'name' => 'plupload'
            ]
        );
        $this->requireJs->addLibrary(
            'back/bower_components/jquery-pluploader/dist/jquery.pluploader.min.js',
            [
                'name' => 'jqueryPluploader',
                'deps' => [
                    'plupload',
                ]
            ]
        );

        /* Noty */
        $this->requireJs->addLibrary(
            'back/bower_components/noty/js/noty/packaged/jquery.noty.packaged.min.js',
            [
                'name' => 'noty',
                'deps' => [
                    'jquery',
                ]
            ]
        );

        /* SoModal */
        $this->requireJs->addLibrary(
            'back/bower_components/jquery.transit/jquery.transit.js',
            ['name' => 'jqueryTransit']
        );

        $this->requireJs->addLibrary(
            'back/bower_components/jquery-somodal/dist/js/jquery.somodal.min.js',
            [
                'name' => 'jquerySoModal',
                'deps' => [
                    'jquery',
                    'jqueryTransit',
                ]
            ]
        );

        $this->css->addLibrary('back/bower_components/jquery-somodal/dist/css/jquery.somodal.min.css');

        /* JSTREE */
        $this->requireJs->addLibrary(
            'back/bower_components/jstree/dist/jstree.min.js',
            ['name' => 'jsTree']
        );

        // Thème Bootstrap de jstree
        $this->css->addLibrary('back/bower_components/jstree-bootstrap-theme/dist/themes/proton/style.min.css');

        /* tinyMCE */
        $this->requireJs->addLibrary(
            'back/bower_components/tinymce/tinymce.min.js',
            ['name' => 'tinyMCE_source']
        );

        $this->requireJs->addLibrary(
            'back/bower_components/bower-tinymce-amd/tinyMCE.js',
            ['name' => 'tinyMCE']
        );

        /* Jquery Form controle */
        $this->requireJs->addLibrary(
            'back/bower_components/jquery-controle/jquery.controle.min.js',
            [
                'name' => 'jqueryControle',
                'deps' => [
                    'jquery',
                ]
            ]
        );

        $this->css->addLibrary('back/bower_components/x-editable/dist/bootstrap3-editable/css/bootstrap-editable.css');
        $this->requireJs->addLibrary(
            'back/bower_components/x-editable/dist/bootstrap3-editable/js/bootstrap-editable.min.js',
            [
                'name' => 'xEditable',
                'deps' => [
                    'jquery',
                ]
            ]
        );


        /* Modules Solire */
        $requireJsModules = [
            'modules/page/liste',
            'modules/page/affichegabarit',
            'modules/page/listefichiers',
            'modules/page/signin',
            'modules/helper/amd',
            'modules/helper/dialog',
            'modules/helper/ajaxDialog',
            'modules/helper/wysiwyg',
            'modules/helper/datepicker',
            'modules/helper/search',
            'modules/helper/autocomplete',
            'modules/helper/crop',
            'modules/helper/cropDialog',
            'modules/helper/autocompleteFile',
            'modules/helper/autocompleteJoin',
            'modules/helper/confirm',
            'modules/helper/message',
            'modules/config/noty',
            'modules/helper/noty',
            'modules/helper/datatable',
            'modules/helper/editable',
            'modules/helper/ajaxform',
            'modules/helper/uploader',
            'modules/helper/sortable',
            'modules/page/upload',
            'modules/page/simpleupload',
            'modules/helper/ajaxcall',
            'modules/helper/zoom',
            'modules/helper/tour',
            'modules/render/visible',
            'modules/render/delete',
            'modules/render/beforeloadpage',
            'modules/render/aftersavepage',
            'modules/render/aftersavepassword',
            'modules/render/afterdeletepage',
            'modules/page/block',
            'modules/page/apichange',
        ];

        $this->requireJs->setModuleDir('back/js');
        $this->requireJs->addModules($requireJsModules);

        /* font-awesome */
        $this->css->addLibrary('back/bower_components/font-awesome/css/font-awesome.min.css');

        /* Flags */
        $this->css->addLibrary('back/bower_components/flag-icon-css/css/flag-icon.min.css');

        /* Librairies Solire */
        $this->css->addLibrary('back/css/style.css');
    }
}
