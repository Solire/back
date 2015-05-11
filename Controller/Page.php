<?php
/**
 * Gestionnaire de pages
 *
 * @author  dev <dev@solire.fr>
 * @license CC by-nc http://creativecommons.org/licenses/by-nc/3.0/fr/
 */

namespace Solire\Back\Controller;

/**
 * Gestionnaire de pages
 *
 * @author  dev <dev@solire.fr>
 * @license CC by-nc http://creativecommons.org/licenses/by-nc/3.0/fr/
 */
class Page extends Main
{
    /**
     * Page courrante
     *
     * @var \Solire\Lib\Model\gabaritPage
     */
    protected $page = null;

    /**
     * Tableau de pages
     *
     * @var \Solire\Lib\Model\gabaritPage[]
     */
    protected $pages = null;

    /**
     * Tableau de redirections
     *
     * @var array
     */
    protected $redirections = null;

    /**
     * Modifie les droits sur les pages
     *
     * @param \Solire\Lib\Model\gabaritPage[] $pages    Tableau de pages
     * @param \Solire\Lib\Model\gabarit[]     $gabarits Tableau de gabarits
     *
     * @return void
     * @hook back/ pagevisible Pour autoriser / interdire la modification de la
     * visibilité d'une page
     * @hook back/ pagedelete Pour autoriser / interdire la suppression d'une page
     * @hook back/ pageorder Pour autoriser / interdire la modification de l'ordre
     * de pages
     */
    public function checkPrivileges($pages, $gabarits)
    {
        $ids = [];
        foreach ($pages as $page) {
            $gabarit = $gabarits[$page->getMeta('id_gabarit')];

            $page->makeVisible = true;
            $page->makeHidden  = $gabarit['make_hidden'];
            $page->deletable   = $gabarit['deletable'];
            $page->sortable    = $gabarit['sortable'];

            $ids[] = $page->getMeta('id');
            $p[$page->getMeta('id')] = $page;
        }


        /*
         * On vérifie la possibilité de rendre invisble pour
         * chaque page
         */

        $hook = new \Solire\Lib\Hook();
        $hook->setSubdirName('back');

        $hook->permission     = null;
        $hook->utilisateur    = $this->utilisateur;
        $hook->ids            = $ids;
        $hook->id_version     = BACK_ID_VERSION;
        $hook->visible        = 0;

        $hook->exec('pagevisible');

        if ($hook->permission !== null) {
            if ($hook->permission === false) {
                foreach ($p as $id => $page) {
                    $page->makeHidden  = false;
                }
            } elseif (is_array($hook->permission)) {
                foreach ($hook->permission as $id => $permissions) {
                    $p[$id]->makeHidden  = $permission;
                }
            }
        }

        /*
         * On vérifie la possibilité de rendre visible pour
         * chaque page
         */

        $hook = new \Solire\Lib\Hook();
        $hook->setSubdirName('back');

        $hook->permission     = null;
        $hook->utilisateur    = $this->utilisateur;
        $hook->ids            = $ids;
        $hook->id_version     = BACK_ID_VERSION;
        $hook->visible        = 1;

        $hook->exec('pagevisible');

        if ($hook->permission !== null) {
            if ($hook->permission === false) {
                foreach ($p as $id => $page) {
                    $page->makeVisible  = false;
                }
            } elseif (is_array($hook->permission)) {
                foreach ($hook->permission as $id => $permissions) {
                    $p[$id]->makeVisible  = $permission;
                }
            }
        }


        /*
         * On vérifie la possibilité d'ordonner chaque page
         */

        $hook = new \Solire\Lib\Hook();
        $hook->setSubdirName('back');

        $hook->permission     = null;
        $hook->utilisateur    = $this->utilisateur;
        $hook->ids            = $ids;
        $hook->id_version     = BACK_ID_VERSION;

        $hook->exec('pageorder');

        if ($hook->permission !== null) {
            if ($hook->permission === false) {
                foreach ($p as $id => $page) {
                    $page->sortable  = false;
                }
            } elseif (is_array($hook->permission)) {
                foreach ($hook->permission as $id => $permissions) {
                    $p[$id]->sortable  = $permission;
                }
            }
        }


        /*
         * On vérifie la possibilité de supprimer chaque page
         */

        $hook = new \Solire\Lib\Hook();
        $hook->setSubdirName('back');

        $hook->permission     = null;
        $hook->utilisateur    = $this->utilisateur;
        $hook->ids            = $ids;
        $hook->id_version     = BACK_ID_VERSION;

        $hook->exec('pagedelete');

        if ($hook->permission !== null) {
            if ($hook->permission === false) {
                foreach ($p as $id => $page) {
                    $page->deletable  = false;
                }
            } elseif (is_array($hook->permission)) {
                foreach ($hook->permission as $id => $permissions) {
                    $p[$id]->deletable  = $permission;
                }
            }
        }
    }

    /**
     * Liste les gabarits
     *
     * @return void
     * @hook back/ list<indexConfig> Pour remplacer le chargement d'une config
     * particulière
     */
    public function listeAction()
    {
        $this->javascript->addLibrary('back/js/liste.js');
        $this->javascript->addLibrary('back/js/jquery/jquery.ajaxqueue.js');
        $this->javascript->addLibrary('back/js/jquery/jquery.scrollTo-min.js');

        $gabaritsList = [];
        $query = 'SELECT `gab_gabarit`.id, `gab_gabarit`.* '
               . 'FROM `gab_gabarit` '
               . 'WHERE `gab_gabarit`.`id_api` = ' . $this->api['id'];

        /*
         * Si on veut n'afficher que certains gabarits
         */
        if (isset($_GET['c']) && intval($_GET['c'])) {
            $indexConfig = intval($_GET['c']);
        } else {
            $indexConfig = 0;
        }

        /*
         * Récupération de la liste de la page et des droits utilisateurs
         */
        $currentConfigPageModule = $this->configPageModule[$indexConfig];
        $gabaritsListPage = $currentConfigPageModule['gabarits'];
        $configPageModule = $this->configPageModule[$this->utilisateur->gabaritNiveau];
        $gabaritsListUser = $configPageModule['gabarits'];

        /*
         * Option de blocage de l'affichage des gabarits enfants
         */
        $this->view->noChild = false;
        if (isset($currentConfigPageModule['noChild'])
            && $currentConfigPageModule['noChild'] === true
        ) {
            $this->view->noChild = true;
        }

        if (isset($currentConfigPageModule['urlRedir'])) {
            $this->view->urlRedir = $currentConfigPageModule['urlRedir'];
        }

        /*
         * Chargement du titre de la page
         */
        if (isset($currentConfigPageModule['label'])) {
            $this->view->label = $currentConfigPageModule['label'];
        }

        $this->view->urlAjax = 'back/page/children.html';
        if (isset($currentConfigPageModule['urlAjax'])) {
            $this->view->urlAjax = $currentConfigPageModule['urlAjax'];
        }

        $this->view->childName = '';
        if (isset($currentConfigPageModule['childName'])) {
            $this->view->childName = $currentConfigPageModule['childName'];
        }

        if (isset($currentConfigPageModule['noType'])
            && $currentConfigPageModule['noType'] === true
        ) {
            $this->view->noType = true;
        }

        unset($configPageModule);

        /*
         * Génération de la liste des gabarits à montrer
         */
        if ($gabaritsListPage == '*') {
            $gabaritsList = $gabaritsListUser;
        } else {
            if ($gabaritsListUser == '*') {
                $gabaritsList = $gabaritsListPage;
            } else {
                $gabaritsList = [];
                foreach ($gabaritsListPage as $gabId) {
                    if (in_array($gabId, $gabaritsListUser)) {
                        $gabaritsList[] = $gabId;
                    }
                    unset($gabId);
                }
            }
        }
        unset($gabaritsListPage, $gabaritsListUser);

        /*
         * Si on liste que certains gabarits
         */
        if ($gabaritsList != '*' && count($gabaritsList) > 0) {
            $query .= ' AND id IN ( ' . implode(', ', $gabaritsList) . ')';
            /*
             * Permet de séparer les différents gabarits
             */
            if (isset($_GET['gabaritByGroup'])) {
                $this->view->gabaritByGroup = true;
                foreach ($gabaritsList as $gabariId) {
                    $this->view->pagesGroup[$gabariId] = $this->gabaritManager->getList(
                        BACK_ID_VERSION,
                        $this->api['id'],
                        0,
                        $gabariId
                    );
                }
            } else {
                $hook = new \Solire\Lib\Hook();
                $hook->setSubdirName('back');

                $hook->gabaritManager = $this->gabaritManager;
                $hook->gabaritsList = $gabaritsList;
                $hook->idVersion = BACK_ID_VERSION;
                $hook->idApi = $this->api['id'];

                $hook->exec('list' . $indexConfig);

                /*
                 *  Chargement par défaut
                 */
                if (!isset($hook->list) || empty($hook->list)) {
                    $this->pages = $this->gabaritManager->getList(
                        BACK_ID_VERSION,
                        $this->api['id'],
                        0,
                        $gabaritsList
                    );
                } else {
                    $this->pages = $hook->list;
                }
                $this->view->pagesGroup[0] = 1;
            }
        } else {
            $this->pages = $this->gabaritManager->getList(
                BACK_ID_VERSION,
                $this->api['id'],
                0
            );
            $this->view->pagesGroup[0] = 1;
        }

        $this->gabarits = $this->db->query($query)->fetchAll(
            \PDO::FETCH_UNIQUE | \PDO::FETCH_ASSOC
        );
        $query  = 'SELECT `gab_gabarit`.id_parent, `gab_gabarit`.id'
                . ' FROM `gab_gabarit`'
                . ' WHERE `gab_gabarit`.id_parent > 0'
                . ' AND `gab_gabarit`.`id_api` = ' . $this->api['id'];
        $this->gabaritsChildren = $this->db->query($query)->fetchAll(
            \PDO::FETCH_GROUP | \PDO::FETCH_COLUMN
        );

        $this->getButton($currentConfigPageModule);

        $this->checkPrivileges($this->pages, $this->gabarits);

        $this->view->gabarits = $this->gabarits;
        $this->view->gabaritsChildren = $this->gabaritsChildren;
        $this->view->pages = $this->pages;

        $this->view->breadCrumbs[] = [
            'label' => $currentConfigPageModule['label'],
            'url'   => 'page/liste.html',
        ];
    }

    /**
     * Affichage des enfants d'une page
     *
     * @return void
     */
    public function childrenAction()
    {
        $gabaritsList = 0;

        /* Si on veut n'afficher que certains gabarits */
        if (isset($_GET['c']) && intval($_GET['c'])) {
            $indexConfig = intval($_GET['c']);
        } else {
            $indexConfig = 0;
        }

        /* Récupération de la liste de la page et des droits utilisateurs */
        $currentConfigPageModule = $this->configPageModule[$indexConfig];
        $gabaritsListPage = $currentConfigPageModule['gabarits'];
        $configPageModule = $this->configPageModule[$this->utilisateur->gabaritNiveau];
        $gabaritsListUser = $configPageModule['gabarits'];

        /* Option de blocage de l'affichage des gabarits enfants */
        $this->view->noChild = false;
        if (isset($currentConfigPageModule['noChild'])
            && $currentConfigPageModule['noChild'] === true
        ) {
            $this->view->noChild = true;
        }
        if (isset($currentConfigPageModule['urlRedir'])) {
            $this->view->urlRedir = $currentConfigPageModule['urlRedir'];
        }

        if (isset($currentConfigPageModule['urlAjax'])) {
            $this->view->urlAjax = $currentConfigPageModule['urlAjax'];
        }

        $this->view->childName = '';
        if (isset($currentConfigPageModule['childName'])) {
            $this->view->childName = $currentConfigPageModule['childName'];
        }

        if (isset($currentConfigPageModule['noType'])
            && $currentConfigPageModule['noType'] === true
        ) {
            $this->view->noType = true;
        }

        /* Génération de la liste des gabarits à montrer */
        if ($gabaritsListPage == '*') {
            $gabaritsList = $gabaritsListUser;
        } else {
            if ($gabaritsListUser == '*') {
                $gabaritsList = $gabaritsListPage;
            } else {
                $gabaritsList = [];
                foreach ($gabaritsListPage as $gabId) {
                    if (in_array($gabId, $gabaritsListUser)) {
                        $gabaritsList[] = $gabId;
                    }
                    unset($gabId);
                }
            }
        }
        unset($gabaritsListPage, $gabaritsListUser);

        if ($gabaritsList === '*') {
            $gabaritsList = 0;
        }

        $this->view->unsetMain();

        $hook = new \Solire\Lib\Hook();
        $hook->setSubdirName('back');

        $hook->gabaritManager = $this->gabaritManager;
        $hook->gabaritsList = $gabaritsList;
        $hook->idVersion = BACK_ID_VERSION;
        $hook->idApi = $this->api['id'];
        $hook->idParent = $_REQUEST['id_parent'];

        $hook->exec('list' . $indexConfig);

        /* Chargement par défaut */
        if (!isset($hook->list) || empty($hook->list)) {
            $this->pages = $this->gabaritManager->getList(
                BACK_ID_VERSION,
                $this->api['id'],
                $_GET['id_parent'],
                $gabaritsList
            );
        } else {
            $this->pages = $hook->list;
        }

        if (count($this->pages) == 0) {
            exit();
        }

        $query  = 'SELECT `gab_gabarit`.id, `gab_gabarit`.*'
                . ' FROM `gab_gabarit`'
                . ' WHERE `gab_gabarit`.`id_api` = ' . $this->api['id'];
        $this->gabarits = $this->db->query($query)->fetchAll(
            \PDO::FETCH_UNIQUE | \PDO::FETCH_ASSOC
        );

        $query  = 'SELECT `gab_gabarit`.id_parent, `gab_gabarit`.id'
                . ' FROM `gab_gabarit`'
                . ' WHERE `gab_gabarit`.id_parent > 0'
                . ' AND `gab_gabarit`.`id_api` = ' . $this->api['id'];
        $this->gabaritsChildren = $this->db->query($query)->fetchAll(
            \PDO::FETCH_GROUP | \PDO::FETCH_COLUMN
        );

        $this->checkPrivileges($this->pages, $this->gabarits);

        $this->view->pages = $this->pages;

        $this->view->gabarits = $this->gabarits;
        $this->view->gabaritsChildren = $this->gabaritsChildren;
    }

    /**
     * Affichage du formulaire de création / d'édition d'une page
     *
     * @return void
     */
    public function displayAction()
    {
        $this->javascript->addLibrary('back/js/tinymce-4.0.5/tinymce.min.js');
        $this->javascript->addLibrary('back/js/tinymce-4.0.5/jquery.solire.tinymce.js');

        $this->javascript->addLibrary('back/js/autocomplete.js');
        $this->javascript->addLibrary('back/js/plupload/plupload.full.js');
        $this->javascript->addLibrary('back/js/plupload/jquery.pluploader.min.js');
        $this->javascript->addLibrary('back/js/formgabarit.js');
        $this->javascript->addLibrary('back/js/jquery/jquery.tipsy.js');
        $this->javascript->addLibrary('back/js/jquery/jquery.qtip.min.js');

        $this->javascript->addLibrary('back/js/gmap.js');
        $this->javascript->addLibrary('back/js/crop.js');
        $this->javascript->addLibrary('back/js/datafile.js');
        $this->javascript->addLibrary('back/js/affichegabarit.js');

        $this->javascript->addLibrary('back/js/jquery/jquery.autogrow.js');
        $this->javascript->addLibrary('back/js/datatable/jquery/jquery.dataTables.js');
        $this->javascript->addLibrary('back/js/jquery/jcrop/jquery.Jcrop.min.js');
        $this->javascript->addLibrary('back/js/jquery/ui.spinner.min.js');
        $this->javascript->addLibrary('back/js/autocomplete_multi/jquery.tokeninput.js');
        $this->javascript->addLibrary('back/js/autocomplete_multi.js');
        $this->javascript->addLibrary('back/js/compareversion.js');

        /*
         * Gmap
         */
        $this->javascript->addLibrary('http://maps.google.com/maps/api/js?sensor=false');
        $this->javascript->addLibrary('back/js/jquery/gmap3.min.js');

        $this->css->addLibrary('back/css/jcrop/jquery.Jcrop.min.css');
        $this->css->addLibrary('back/css/ui.spinner.css');
        $this->css->addLibrary('back/css/demo_table_jui.css');
        $this->css->addLibrary('back/css/tipsy.css');
        $this->css->addLibrary('back/css/jquery.qtip.min.css');
        $this->css->addLibrary('back/css/autocomplete_multi/token-input.css');
        $this->css->addLibrary('back/css/autocomplete_multi/token-input-facebook.css');
        $this->css->addLibrary('back/css/affichegabarit.css');

        $id_gab_page = isset($_GET['id_gab_page']) ? $_GET['id_gab_page'] : 0;
        $id_gabarit = isset($_GET['id_gabarit']) ? $_GET['id_gabarit'] : 1;

        $this->view->action = 'liste';

        $this->form            = '';
        $this->pages           = [];
        $this->redirections    = [];

        if ($id_gab_page) {
            $query  = 'SELECT *'
                    . ' FROM `version`'
                    . ' WHERE `id_api` = ' . $this->api['id'];
            $this->versions = $this->db->query($query)->fetchAll(
                \PDO::FETCH_ASSOC | \PDO::FETCH_UNIQUE
            );

            foreach ($this->versions as $id_version => $version) {
                $page = $this->gabaritManager->getPage(
                    $id_version,
                    BACK_ID_API,
                    $id_gab_page
                );

                $this->pages[$id_version] = $page;

                $hook = new \Solire\Lib\Hook();
                $hook->setSubdirName('back');

                $hook->permission     = null;
                $hook->utilisateur    = $this->utilisateur;
                $hook->visible        = $page->getMeta('visible') > 0 ? 0 : 1;
                $hook->ids            = $id_gab_page;
                $hook->id_version     = $id_version;

                $hook->exec('pagevisible');

                $page->makeVisible = true;

                if ($page->getGabarit()->getMakeHidden()) {
                    $page->makeHidden  = true;
                } else {
                    $page->makeHidden  = false;
                }

                if ($hook->permission === false) {
                    if ($hook->visible > 0) {
                        $page->makeVisible = false;
                    } else {
                        $page->makeHidden  = false;
                    }
                }

                $path   = $page->getMeta('rewriting')
                        . $page->getGabarit()->getExtension();
                foreach ($page->getParents() as $parent) {
                    $path = $parent->getMeta('rewriting') . '/' . $path;
                }

                if ($id_version == BACK_ID_VERSION) {
                    /* Cas de la page d'accueil */
                    if ($page->getMeta('id') == 1) {
                        $this->view->pagePath = '?mode_previsualisation=1';
                    } else {
                        $this->view->pagePath = $path
                                               . '?mode_previsualisation=1';
                    }
                }

                $query  = 'SELECT `old`'
                        . ' FROM `redirection`'
                        . ' WHERE `new` LIKE ' . $this->db->quote($path);
                $this->redirections[$id_version] = $this->db->query($query)
                    ->fetchAll(\PDO::FETCH_COLUMN);

                $query  = 'SELECT * '
                        . 'FROM `main_element_commun_author_google` '
                        . 'WHERE `id_version` = ' . $id_version;
                $this->authors[$id_version] = $this->db->query($query)
                    ->fetchAll(\PDO::FETCH_ASSOC);
            }
        } else {
            $query  = 'SELECT *'
                    . ' FROM `version`'
                    . ' WHERE `id` = ' . BACK_ID_VERSION;
            $this->versions = $this->db->query($query)->fetchAll(
                \PDO::FETCH_ASSOC | \PDO::FETCH_UNIQUE
            );

            $page = $this->gabaritManager->getPage(
                BACK_ID_VERSION,
                BACK_ID_API,
                0,
                $id_gabarit
            );
            $this->pages[BACK_ID_VERSION] = $page;
            $this->redirections[BACK_ID_VERSION] = [];

            $query  = 'SELECT * '
                    . 'FROM `main_element_commun_author_google` '
                    . 'WHERE `id_version` = ' . BACK_ID_VERSION;
            $this->authors[BACK_ID_VERSION] = $this->db->query($query)
                ->fetchAll(\PDO::FETCH_ASSOC);
        }

        $this->view->versions = $this->versions;
        $this->view->pages = $this->pages;
        $this->view->redirections = $this->redirections;
        $this->view->authors = $this->authors;

        /*
         * On recupere la sous rubrique de page a laquelle il appartient
         * pour le breadCrumbs et le lien retour
         */
        $found = false;
        foreach ($this->configPageModule as $index => $currentConfigPageModule) {
            /*
             * Si le gabarit courant appartien à un des groupes personnalisés
             */
            if ($currentConfigPageModule['gabarits'] == '*'
                || in_array($this->pages[BACK_ID_VERSION]->getGabarit()->getId(), $currentConfigPageModule['gabarits'])
            ) {
                $indexPageList = $index;
                $found = true;
                break;
            }

            if ($found) {
                break;
            }
        }

        if ($found) {
            $this->view->breadCrumbs[] = [
                'label' => $this->configPageModule[$indexPageList]['label'],
                'url'   => 'page/liste.html?c=' . $indexPageList,
            ];
        } else {
            $this->view->breadCrumbs[] = [
                'label' => 'Liste des pages',
                'url'   => 'page/liste.html',
            ];
        }

        $this->view->breadCrumbs[] = [
            'label' => 'Gestion des pages',
            'url'   => '',
        ];

        $this->getButton($currentConfigPageModule);
    }

    /**
     * Page appelé pour la sauvegarde d'une page
     *
     * @return void
     * @hook back/ pagesaved Après la création / modification d'une page. Si les
     * données envoyés sont les mêmes que celles enregistrées en BDD, cette
     * évènement n'est pas déclenché
     */
    public function saveAction()
    {
        $this->view->unsetMain();
        $this->view->enable(false);

        if (isset($_GET['edit-front']) && $_GET['edit-front'] == 1) {
            /*
             * Sauvegarde partielle sur le middleoffice
             */

            $dataRaw = json_decode($_POST['content'], true);
            $data = [
                'id_version' => $dataRaw['id_version']['value'],
                'id_gab_page' => $dataRaw['id_gab_page']['value'],
                'id_api' => $dataRaw['id_api']['value'],
            ];
            $page = $this->gabaritManager->getPage(
                $dataRaw['id_version']['value'],
                $dataRaw['id_api']['value'],
                $dataRaw['id_gab_page']['value'],
                0
            );
            $pageSave = false;

            if (!$page || $page->getGabarit()->getEditable() == 0) {
                $this->pageNotFound();
            }

            foreach ($dataRaw as $k => $d) {
                $val = isset($d['value']) ? $d['value'] : false;
                if ($val === false) {
                    if (isset($d['attributes']['src'])) {
                        $filePathPart = explode('/', $d['attributes']['src']);
                        $val = $filePathPart[1];
                    }
                }

                if ($val !== false) {

                    if (strpos($k, '-') !== false) {
                        $fieldPart = explode('-', $k);
                        if (!isset($data[$fieldPart[0]])) {
                            $data[$fieldPart[0]] = [];
                        }


                        $blocTableName = $fieldPart[2];
                        $idBlocLine = $fieldPart[1];
                        $idChamp = substr($fieldPart[0], 5);

                        if (!isset($data['id_' . $blocTableName])) {
                            $data['id_' . $blocTableName] = [];
                        }
                        $data['id_' . $blocTableName][$idBlocLine] = $idBlocLine;

                        $data[$fieldPart[0]][] = $val;
                    } else {
                        if (substr($k, 0, 5) == 'champ') {
                            $pageSave = true;
                            $data[$k] = [
                                $val
                            ];
                        }
                    }
                }
            }

            $modif = false;

            if ($pageSave) {
                $modifTmp = $this->gabaritManager->savePage($page, $data, true);

                if (!$modif && $modifTmp) {
                    $modif = $modifTmp;
                }
            }

            $blocs = $page->getBlocs();
            foreach ($blocs as $bloc) {
                $modifTmp = $this->gabaritManager->saveBloc(
                    $bloc,
                    $dataRaw['id_gab_page']['value'],
                    $dataRaw['id_version']['value'],
                    $data,
                    true
                );

                if (!$modif && $modifTmp) {
                    $modif = $modifTmp;
                }
            }

            if ($modif) {
                $this->page = $this->gabaritManager->getPage(
                    $dataRaw['id_version']['value'],
                    $dataRaw['id_api']['value'],
                    $dataRaw['id_gab_page']['value'],
                    0
                );
            }

            $json = [
                'status' => 'success',
            ];
        } else {
            $modif = false;

            if ($_POST['id_gab_page'] > 0) {
                $updating = true;
                $typeSave = 'Modification';
            } else {
                $updating = false;
                $typeSave = 'Création';
            }

            $res = $this->gabaritManager->save($_POST);

            if ($res === null) {
                throw new Exception('Problème à l\'enregistrement');
            }

            if ($res === false) {
                /*
                 * Dans le cas d'une mise-à-jour où les données étaient les
                 * mêmes que celles préenregistrées en BDD.
                 */

                $modif = false;

                $json = [
                    'status'        => 'success',
                    'search'        => '?id_gab_page=' . $_POST['id_gab_page']
                                     . '&popup=more',
                    'id_gab_page'   => $_POST['id_gab_page'],
                ];
            } else {
                /*
                 * Création de page ou modification effective
                 */

                $modif = true;

                $this->page = $res;

                if ($this->appConfig->get('general', 'mail-notification')) {
                    /*
                     * Envoi de mail à solire
                     */

                    $subject    = $typeSave . ' de contenu sur '
                                . $this->mainConfig->get('project', 'name');

                    $contenu    = '<a href="' . \Solire\Lib\Registry::get('basehref')
                                . 'page/display.html?id_gab_page='
                                . $this->page->getMeta('id') . '">'
                                . $this->page->getMeta('titre') . '</a>';

                    $headers    = 'From: ' . \Solire\Lib\Registry::get('mail-contact') . "\r\n"
                                . 'Reply-To: ' . \Solire\Lib\Registry::get('mail-contact') . "\r\n"
                                . 'Bcc: contact@solire.fr ' . "\r\n"
                                . 'X-Mailer: PHP/' . phpversion();

                    \Solire\Lib\Tools::mail_utf8(
                        'Modif site <modif@solire.fr>',
                        $subject,
                        $contenu,
                        $headers,
                        'text/html'
                    );
                }

                $json = [
                    'status'        => 'success',
                    'search'        => '?id_gab_page=' . $this->page->getMeta('id')
                                     . '&popup=more',
                    'id_gab_page'   => $this->page->getMeta('id'),
                ];

                if (isset($_POST['id_temp']) && $_POST['id_temp']) {
                    /*
                     * Déplacement des fichiers utilisés dans la page.
                     */

                    $upload_path = $this->mainConfig->get('upload', 'path');

                    $tempDir    = './' . $upload_path . DIRECTORY_SEPARATOR . 'temp-' . $_POST['id_temp'];
                    $targetDir  = './' . $upload_path . DIRECTORY_SEPARATOR . $this->page->getMeta('id');

                    $succes = rename($tempDir, $targetDir);

                    $query  = 'UPDATE `media_fichier` SET'
                            . ' `id_gab_page` = ' . $this->page->getMeta('id') . ','
                            . ' `id_temp` = 0'
                            . ' WHERE `id_temp` = ' . $_POST['id_temp'];
                    $this->db->exec($query);
                }

                $flagName = strtolower(
                    $this->versions[$_POST['id_version']]['suf']
                );

                if ($json['status'] == 'error') {
                    $logTitle = $typeSave . 'de page échouée';
                    $logMessage = '<b>Id</b> : ' . $this->page->getMeta('id')
                                . '<br /><img src="public/default/back/img/flags/png/'
                                . $flagName . '.png" alt="'
                                . $this->versions[$_POST['id_version']]['nom']
                                . '" /></a><br />'
                                . '<span style="color:red;">Error</span>';
                } else {
                    $logTitle = $typeSave . 'de page réussie';
                    $logMessage = '<b>Id</b> : ' . $this->page->getMeta('id')
                                . '<br /><img src="public/default/back/img/flags/png/'
                                . $flagName . '.png" alt="'
                                . $this->versions[$_POST['id_version']]['nom']
                                . '" /></a>';
                }

                $this->log->logThis(
                    $logTitle,
                    $this->utilisateur->get('id'),
                    $logMessage
                );
            }
        }

        if ($modif) {
            /*
             * Si une création ou une modification a été effectuée,
             * on fait un hook
             */

            $hook = new \Solire\Lib\Hook();
            $hook->setSubdirName('back');

            $hook->page        = $this->page;
            $hook->utilisateur = $this->utilisateur;

            $hook->exec('pagesaved');
        }

        echo(json_encode($json));
    }

    /**
     * Autocomplete des pages
     *
     * @return void
     * @deprecated ??? utiliser autocompleteJoinAction à la place
     * @see Page::autocompleteJoinAction()
     */
    public function autocompleteAction()
    {
        $this->view->enable(false);
        $this->view->unsetMain();

        $json = [];
        $dejaLiees = is_array($_REQUEST['deja']) ? $_REQUEST['deja'] : [];

        if (!isset($_REQUEST['id_gabarit'])
            || !is_numeric($_REQUEST['id_gabarit'])
        ) {
            exit(json_encode($json));
        }

        $pages = $this->gabaritManager->getSearch(BACK_ID_VERSION, $_GET['term'], $_REQUEST['id_gabarit']);
        foreach ($pages as $page) {
            if (!in_array($page->getMeta('id'), $dejaLiees)) {
                $json[] = [
                    'value' => $page->getMeta('id'),
                    'label' => $page->getMeta('titre'),
                    'visible' => $page->getMeta('titre')
                ];
            }
        }

        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
        header('Content-type: application/json');
        echo json_encode($json);
    }

    /**
     * Recherche de page
     *
     * @return void
     */
    public function autocompleteJoinAction()
    {
        $this->view->enable(false);
        $this->view->unsetMain();

        $idChamp    = $_GET['id_champ'];
        $idVersion  = $_GET['id_version'];
        $idGabPage  = $_GET['id_gab_page'];
        $term       = $_GET['term'];
        $response   = [];

        $query  = 'SELECT code_champ_param, value'
                . ' FROM gab_champ_param_value'
                . ' WHERE id_champ = ' . $idChamp;
        $params = $this->db->query($query)->fetchAll(
            \PDO::FETCH_UNIQUE | \PDO::FETCH_COLUMN
        );

        $idField = $params['TABLE.FIELD.ID'];
        if (isset($params['TYPE.GAB.PAGE'])) {
            $typeGabPage = $params['TYPE.GAB.PAGE'];
        } else {
            $typeGabPage = 0;
        }

        if (isset($params['QUERY.FILTER'])) {
            $queryFilter = str_replace('[ID]', $idGabPage, $params['QUERY.FILTER']);
            $queryFilter = str_replace('[ID_VERSION]', $idVersion, $params['QUERY.FILTER']);
        } else {
            $queryFilter = '';
        }


        $table          = $params['TABLE.NAME'];
        $labelField     = $params['TABLE.FIELD.LABEL'];
        $gabPageJoin    = '';


        $filterVersion = '`' . $table . '`.id_version = ' . $idVersion;
        if ($table == 'gab_page'
            || !$typeGabPage
        ) {
            $filterVersion = 1;
        } else {
            $gabPageJoin = ' INNER JOIN gab_page ON visible = 1'
                         . ' AND suppr = 0'
                         . ' AND gab_page.id = `' . $table . '`.`' . $idField . '` ';

            if ($filterVersion != 1) {
                $gabPageJoin .= 'AND gab_page.id_version = ' . $idVersion;
            }
        }

        if (substr($labelField, 0, 9) != 'gab_page.') {
            $labelField = '`' . $table . '`.`' . $labelField . '`';
        }

        $quotedTerm = $this->db->quote('%' . $term . '%');
        $query = 'SELECT `' . $table . '`.`' . $idField . '` id,'
               . ' ' . $labelField . ' `label`';

        /*
         * Si gab_page
         */
        if ($gabPageJoin != '' || $table == 'gab_page') {
            $query .= ',gab_gabarit.label gabarit_label';
        }

        $query .= ' FROM `' . $table . '`'
                . $gabPageJoin;

        /*
         * Si gab_page
         */
        if ($gabPageJoin != '' || $table == 'gab_page') {
            $query .= ' INNER JOIN gab_gabarit ON gab_gabarit.id = gab_page.id_gabarit';
        }

        $query .= ' WHERE ' . $filterVersion . ' '
                . ' AND ' . $labelField . '  LIKE ' . $quotedTerm;

        if ($queryFilter != '') {
            $query .= ' AND (' . $queryFilter . ')';
        }

        if (isset($_GET['ids'])
            && is_array($_GET['ids'])
            && count($_GET['ids']) > 0
        ) {
            $ids = $_GET['ids'];
            $query .= ' AND `' . $table . '`.`' . $idField . '`'
                    . ' NOT IN (' . implode(',', $ids) . ')';
        }

        $pagesFound = $this->db->query($query)->fetchAll(\PDO::FETCH_ASSOC);

        $pages = [];
        foreach ($pagesFound as $page) {
            if (isset($page['gabarit_label'])) {
                $gabaritLabel = $page['gabarit_label'];
            } else {
                $gabaritLabel = '';
            }

            $pages[] = [
                'label' => $page['label'],
                'id' => $page['id'],
                'gabarit_label' => $gabaritLabel,
            ];
        }

        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
        header('Content-type: application/json');
        echo json_encode($pages);
    }

    /**
     * Recherche d'anciennes url (en cas de refonte de site)
     *
     * @return void
     */
    public function autocompleteOldLinksAction()
    {
        $this->view->enable(false);
        $this->view->unsetMain();

        $json = [];
        $term = $_GET['term'];
        $table = 'old_link';
        $labelField = '`' . $table . '`.`link`';

        $quotedTerm = $this->db->quote('%' . $term . '%');

        $sql    = 'SELECT `' . $labelField . '` label'
                . ' FROM `' . $table . '`'
                . ' WHERE `' . $labelField . '` LIKE ' . $quotedTerm;

        $json = $this->db->query($sql)->fetchAll(\PDO::FETCH_ASSOC);

        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
        header('Content-type: application/json');
        echo json_encode($json);
    }

    /**
     * Moteur de recherche des pages
     *
     * @return void
     */
    public function liveSearchAction()
    {
        $this->view->enable(false);

        $pages = [];


        $qSearch = isset($_GET['term']) ? $_GET['term'] : '';

        /*
         * Traitement de la chaine de recherche
         */

        $searchTab = [];

        /*
         * Variable qui contient la chaine de recherche
         */
        $this->filter = new \stdClass();
        $stringSearch = strip_tags(trim($qSearch));
        $this->filter->stringSearch = $stringSearch;

        /*
         * Si un seul mot
         */
        if (strpos($stringSearch, ' ') === false) {
            $searchTab[0] = $stringSearch;
        } else {
            /*
             * Si plusieurs  mots on recupere un tableau de mots
             */
            $searchTab = preg_split('#[ ]+#', $stringSearch);
        }

        /*
         * Tableau de mot(s)
         */
        $this->filter->words = $searchTab;

        /*
         * On teste si un mot est supérieurs à 3 caractères
         */
        $this->filter->errors['len_words'] = true;
        for ($i = 0, $I = count($this->filter->words); $i < $I; $i++) {
            if (trim($this->filter->words[$i]) != '' && strlen(trim($this->filter->words[$i])) >= 2) {
                $this->filter->errors['len_words'] = false;
            }
        }

        if ($this->filter->errors['len_words']) {
            echo json_encode(null);
            return;
        }

        /*
         * Pour chaque mot ou essaie de mettre au singulier ou pluriel
         * + Traitement de la chaine de recherche (elimine mot trop court
         */
        $mode[] = 's';
        $mode[] = 'p';
        $i = 0;
        foreach ($this->filter->words as $t1) {
            foreach ($mode as $m) {
                if (strlen($t1) >= 2) {
                    if ($m == 's') {
                        $this->filter->wordsAdvanced[$i++] = $this->singulier($t1);
                    } else {
                        $this->filter->wordsAdvanced[$i++] = $this->pluriel($t1);
                    }
                }
            }
        }

        /*
         * Tri des mots par strlen
         */
        if (is_array($this->filter->wordsAdvanced)) {
            usort($this->filter->wordsAdvanced, [$this, 'lengthCmp']);
        }

        if ($qSearch != null) {
            $quotedSearch = $this->db->quote(
                '%' . $this->filter->stringSearch . '%'
            );
            $filterWords[] = 'CONCAT(" ", gab_page.titre, " ") LIKE '
                           . $quotedSearch;

            if (isset($this->filter->wordsAdvanced)
                && is_array($this->filter->wordsAdvanced)
                && count($this->filter->wordsAdvanced) > 0
            ) {
                foreach ($this->filter->wordsAdvanced as $word) {
                    $quotedWord = $this->db->quote('%' . $word . '%');
                    $filterWords[] = 'CONCAT(" ", gab_page.titre, " ") LIKE '
                                   . $quotedWord;
                }
            }

            foreach ($filterWords as $filterWord) {
                $orderBy[] = 'IF(' . $filterWord . ' , 0, 1)';
            }
        }

        $query  = 'SELECT `gab_page`.`id` id, gab_page.titre label,'
                . ' gab_page.titre visible, gab_gabarit.label gabarit_label,'
                . ' CONCAT("page/display.html?id_gab_page=", `gab_page`.`id`) url'
                . ' FROM `gab_page`'
                . ' LEFT JOIN `gab_gabarit`'
                . ' ON `gab_page`.id_gabarit = `gab_gabarit`.id'
                . ' AND `gab_gabarit`.editable = 1'
                . ' WHERE `gab_page`.`id_version` = ' . BACK_ID_VERSION
                . ' AND `gab_gabarit`.`id_api` = ' . $this->api['id']
                . ' AND `gab_page`.`suppr` = 0 '
                . (isset($filterWords) ? ' AND (' . implode(' OR ', $filterWords) . ')' : '')
                . ' ORDER BY ' . implode(',', $orderBy) . ' LIMIT 10';

        $pagesFound = $this->db->query($query)->fetchAll(\PDO::FETCH_ASSOC);

        foreach ($pagesFound as $page) {
            $highlight = \Solire\Lib\Tools::highlightedSearch(
                $page['label'],
                $this->filter->wordsAdvanced,
                true
            );

            $pages[] = [
                'label' => $highlight,
                'id' => $page['id'],
                'gabarit_label' => $page['gabarit_label'],
                'url' => $page['url'],
            ];
        }

        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
        header('Content-type: application/json');
        echo json_encode($pages);
    }

    /**
     * Rendre une page visible / invisible
     *
     * @return void
     * @hook back/ pagevisible Pour autoriser / interdire la modification de la
     * visibilité d'une page
     *
     * @todo Vérifier les droits selon le gabarit de page
     */
    public function visibleAction()
    {
        $this->view->unsetMain();
        $this->view->enable(false);

        $json = [
            'status' => 'error',
        ];
        $idVersion = BACK_ID_VERSION;

        if (isset($_POST['id_version']) && $_POST['id_version'] > 0) {
            $idVersion = intval($_POST['id_version']);
        }

        $hook = new \Solire\Lib\Hook();
        $hook->setSubdirName('back');

        $hook->permission = null;
        $hook->utilisateur = $this->utilisateur;
        $hook->visible = $_POST['visible'];
        $hook->ids = $_POST['id_gab_page'];
        $hook->id_version = BACK_ID_VERSION;

        $hook->exec('pagevisible');

        /*
         * On récupère la permission du hook,
         * on interdit uniquement si la variable a été modifié à false.
         */
        if ($hook->permission === false) {
            $permission = false;
        } else {
            $permission = true;
        }

        if ($permission
            && is_numeric($_POST['id_gab_page'])
            && is_numeric($_POST['visible'])
        ) {
            if ($_POST['visible'] == 1) {
                $type = 'Page rendu visible';
            } else {
                $type = 'Page rendu invisible';
            }

            $success = $this->gabaritManager->setVisible(
                $idVersion,
                BACK_ID_API,
                $_POST['id_gab_page'],
                $_POST['visible']
            );

            if ($success) {
                $title = $type . ' avec succès';
                $message = '<b>Id</b> : ' . $_POST['id_gab_page'] . '<br />'
                         . '<img src="public/default/back/img/flags/png/'
                         . strtolower($this->versions[$idVersion]['suf'])
                         . '.png" alt="' . $this->versions[$idVersion]['nom']
                         . '" />';

                $json['status'] = 'success';
            } else {
                $title = $type . ' échouée';
                $message = '<b>Id</b> : ' . $_POST['id_gab_page'] . '<br />'
                         . '<img src="public/default/back/img/flags/png/'
                         . strtolower($this->versions[$idVersion]['suf'])
                         . '.png" alt="' . $this->versions[$idVersion]['nom']
                         . '" /><br /><span style="color:red;">Error</span>';
            }

            $this->log->logThis(
                $title,
                $this->utilisateur->get('id'),
                $message
            );
        }

        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
        header('Content-type: application/json');
        echo json_encode($json);
    }

    /**
     * Suppression d'une page (suppression logique en base)
     *
     * @return void
     * @hook back/ pagedelete Pour autoriser / interdire la suppression d'une page
     *
     * @todo Vérifier les droits selon le gabarit de page
     */
    public function deleteAction()
    {
        $this->view->unsetMain();
        $this->view->enable(false);

        $json = [
            'status' => 'error',
        ];

        $hook = new \Solire\Lib\Hook();
        $hook->setSubdirName('back');

        $hook->permission     = null;
        $hook->utilisateur    = $this->utilisateur;
        $hook->ids            = $_POST['id_gab_page'];
        $hook->id_version     = BACK_ID_VERSION;

        $hook->exec('pagedelete');

        /*
         * On récupère la permission du hook,
         * on interdit uniquement si la variable a été modifié à false.
         */
        if ($hook->permission === false) {
            $permission = false;
        } else {
            $permission = true;
        }

        if ($permission
            && is_numeric($_POST['id_gab_page'])
        ) {
            $delete = $this->gabaritManager->delete($_POST['id_gab_page']);

            if ($delete) {
                $logTitle = 'Suppression de page réussie';
                $logMessage = '<b>Id</b> : ' . $_POST['id_gab_page'];
                $json['status'] = 'success';
            } else {
                $logTitle = 'Suppression de page échouée';
                $logMessage = '<b>Id</b> : ' . $_POST['id_gab_page']
                            . '<br /><span style="color:red;">Error</span>';
            }

            $this->log->logThis(
                $logTitle,
                $this->utilisateur->get('id'),
                $logMessage
            );
        }

        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
        header('Content-type: application/json');
        echo json_encode($json);
    }

    /**
     * Modification de l'ordre de pages
     *
     * @return void
     * @hook back/ pageorder Pour autoriser / interdire la modification de l'ordre
     * de pages
     *
     * @todo Vérifier les droits selon le gabarit de page
     */
    public function orderAction()
    {
        $ok = true;

        $this->view->unsetMain();
        $this->view->enable(false);

        $json = [
            'status' => 'error',
        ];

        $hook = new \Solire\Lib\Hook();
        $hook->setSubdirName('back');

        $hook->permission     = null;
        $hook->utilisateur    = $this->utilisateur;
        $hook->ids            = array_keys($_POST['positions']);
        $hook->id_version     = BACK_ID_VERSION;

        $hook->exec('pageorder');

        /*
         * On récupère la permission du hook,
         * on interdit uniquement si la variable a été modifié à false.
         */
        if ($hook->permission === false) {
            $permission = false;
        } else {
            $permission = true;
        }

        if ($permission) {
            $query  = 'UPDATE `gab_page` SET `ordre` = :ordre WHERE `id` = :id';
            $prepStmt = $this->db->prepare($query);
            foreach ($_POST['positions'] as $id => $ordre) {
                $prepStmt->bindValue(':ordre', $ordre, \PDO::PARAM_INT);
                $prepStmt->bindValue(':id', $id, \PDO::PARAM_INT);
                $tmp = $prepStmt->execute();
                if ($ok) {
                    $ok = $tmp;
                }
            }

            if ($ok) {
                $logTitle = 'Changement d\'ordre réalisé avec succès';
                $logMessage = '<b>Id</b> : ' . $id . '<br />'
                            . '<b>Ordre</b> : ' . $ordre . '<br />';

                $json['status'] = 'success';
            } else {
                $logTitle = 'Changement d\'ordre échoué';
                $logMessage = '<b>Id</b> : ' . $id . ''
                            . '<b>Ordre</b> : ' . $ordre . '<br />'
                            . '<br /><span style="color:red;">Error</span>';
            }

            $this->log->logThis(
                $logTitle,
                $this->utilisateur->get('id'),
                $logMessage
            );
        }

        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
        header('Content-type: application/json');
        echo json_encode($json);
    }

    /**
     * Génère les boutons de création de page
     *
     * @param array $currentConfigPageModule Configurations des boutons
     *
     * @return void
     */
    protected function getButton($currentConfigPageModule)
    {
        /*
         * Liste des début de label à regrouper pour les boutons de création
         */
        $groupIdentifications = [
            'Rubrique ',
            'Sous rubrique ',
            'Page ',
        ];

        $groups = [];
        if (isset($currentConfigPageModule['boutons'])
            && isset($currentConfigPageModule['boutons']['groups'])
        ) {
            $groups = $currentConfigPageModule['boutons']['groups'];
        }

        $this->view->gabaritsBtn = [];

        /*
         * Si on a un regroupement des boutons personnalisés dans le
         * fichier de config et que l'on veut garder l'ordre défini
         */
        if (isset($currentConfigPageModule['boutons'])
                && isset($currentConfigPageModule['boutons']['groups'])
                && isset($currentConfigPageModule['sort'])
                && $currentConfigPageModule['sort']
        ) {
            foreach ($groups as $customGroup) {
                $gabaritsGroup = [
                    'label' => $customGroup['label'],
                ];
                $key = md5($gabaritsGroup['label']);
                $this->view->gabaritsBtn[$key] = $gabaritsGroup;
            }
        }

        foreach ($this->gabarits as $gabarit) {
            $found = false;

            $gabaritsGroup = [
                'label' => $gabarit['label'],
            ];

            /*
             * Si utilisateur standart à le droit de créer ce type de gabarit
             * ou si utilisateur solire
             */
            if ($gabarit['creable']
                || $this->utilisateur->get('niveau') == 'solire'
            ) {
                /*
                 * Si on a un regroupement des boutons personnalisés dans le
                 * fichier de config
                 */
                if (isset($currentConfigPageModule['boutons'])
                    && isset($currentConfigPageModule['boutons']['groups'])
                ) {
                    $groups = $currentConfigPageModule['boutons']['groups'];

                    foreach ($groups as $customGroup) {
                        /*
                         * Si le gabarit courant appartien à un des groupes
                         * personnalisés
                         */
                        $gabarits = $customGroup['gabarits'];
                        if (in_array($gabarit['id'], $gabarits)) {
                            $found = true;
                        } else {
                            if (isset($gabarits[$gabarit['id']])
                                && is_array($gabarits[$gabarit['id']])
                            ) {
                                $found = true;
                                $gabarit['label'] = $gabarits[$gabarit['id']]['label'];
                            }
                        }

                        if ($found) {
                            $gabaritsGroup = [
                                'label' => $customGroup['label'],
                            ];
                            break;
                        }
                    }
                }

                /*
                 * On parcourt les Début de label à regrouper
                 */
                if ($found == false) {
                    foreach ($groupIdentifications as $groupIdentification) {
                        $mask = '/^' . $groupIdentification . '/';

                        if (preg_match($mask, $gabarit['label'])) {
                            $gabaritsGroup = [
                                'label' => $groupIdentification,
                            ];
                            $gabarit['label'] = preg_replace(
                                '#^' . $groupIdentification . '#',
                                '',
                                $gabarit['label']
                            );
                            $gabarit['label'] = trim($gabarit['label']);
                            $gabarit['label'] = ucfirst($gabarit['label']);
                            $found = true;
                            break;
                        }
                    }
                }

                $gabaritsGroup['gabarit'][] = $gabarit;
                if (!$found) {
                    $gabaritsGroup['label'] = '';
                    $this->view->gabaritsBtn[] = $gabaritsGroup;
                } else {
                    $key = md5($gabaritsGroup['label']);

                    if (isset($this->view->gabaritsBtn[$key])) {
                        $this->view->gabaritsBtn[$key]['gabarit'][] = $gabarit;
                    } else {
                        $this->view->gabaritsBtn[$key] = $gabaritsGroup;
                    }
                }
            }
        }
    }

    /**
     * Met un mot au singulier
     *
     * @param string $mot Mot
     *
     * @return string
     */
    protected function singulier($mot)
    {
        if (substr($mot, -1) == 's') {
            return substr($mot, 0, -1);
        }

        return $mot;
    }

    /**
     * Met un mot au pluriel
     *
     * @param string $mot Mot
     *
     * @return string
     */
    protected function pluriel($mot)
    {
        if (substr($mot, -1) == 's') {
            return $mot;
        }

        return $mot . 's';
    }

    /**
     * Compare la longueur de deux chaînes de caractères
     *
     * @param string $a Première chaîne de caractère
     * @param string $b Deuxième chaîne de caractère
     *
     * @return int la diffèrence de longueur des chaînes de caractères
     */
    protected function lengthCmp($a, $b)
    {
        return strlen($b) - strlen($a);
    }
}
