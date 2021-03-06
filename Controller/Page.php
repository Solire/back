<?php
/**
 * Gestionnaire de pages.
 *
 * @author  dev <dev@solire.fr>
 * @license CC by-nc http://creativecommons.org/licenses/by-nc/3.0/fr/
 */

namespace Solire\Back\Controller;

use Exception;
use PDO;
use Solire\Lib\Hook;
use Solire\Lib\Tools;
use stdClass;

/**
 * Gestionnaire de pages.
 *
 * @author  dev <dev@solire.fr>
 * @license CC by-nc http://creativecommons.org/licenses/by-nc/3.0/fr/
 */
class Page extends Main
{
    /**
     * Page courante.
     *
     * @var \Solire\Lib\Model\gabaritPage
     */
    protected $page = null;

    /**
     * Tableau de pages.
     *
     * @var \Solire\Lib\Model\gabaritPage[]
     */
    protected $pages = null;

    /**
     * Tableau de redirections.
     *
     * @var array
     */
    protected $redirections = null;

    /**
     * Modifie les droits sur les pages.
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
            $page->makeHidden = $gabarit['make_hidden'];
            $page->deletable = $gabarit['deletable'];
            $page->sortable = $gabarit['sortable'];

            $ids[] = $page->getMeta('id');
            $p[$page->getMeta('id')] = $page;
        }

        /*
         * On vérifie la possibilité de rendre invisble pour
         * chaque page
         */

        $hook = new Hook();
        $hook->setSubdirName('back');

        $hook->permission = null;
        $hook->utilisateur = $this->utilisateur;
        $hook->ids = $ids;
        $hook->id_version = BACK_ID_VERSION;
        $hook->visible = 0;

        $hook->exec('pagevisible');

        if ($hook->permission !== null) {
            if ($hook->permission === false) {
                foreach ($p as $id => $page) {
                    $page->makeHidden = false;
                }
            } elseif (is_array($hook->permission)) {
                foreach ($hook->permission as $id => $permissions) {
                    $p[$id]->makeHidden = $permission;
                }
            }
        }

        /*
         * On vérifie la possibilité de rendre visible pour
         * chaque page
         */

        $hook = new Hook();
        $hook->setSubdirName('back');

        $hook->permission = null;
        $hook->utilisateur = $this->utilisateur;
        $hook->ids = $ids;
        $hook->id_version = BACK_ID_VERSION;
        $hook->visible = 1;

        $hook->exec('pagevisible');

        if ($hook->permission !== null) {
            if ($hook->permission === false) {
                foreach ($p as $id => $page) {
                    $page->makeVisible = false;
                }
            } elseif (is_array($hook->permission)) {
                foreach ($hook->permission as $id => $permissions) {
                    $p[$id]->makeVisible = $permission;
                }
            }
        }

        /*
         * On vérifie la possibilité d'ordonner chaque page
         */

        $hook = new Hook();
        $hook->setSubdirName('back');

        $hook->permission = null;
        $hook->utilisateur = $this->utilisateur;
        $hook->ids = $ids;
        $hook->id_version = BACK_ID_VERSION;

        $hook->exec('pageorder');

        if ($hook->permission !== null) {
            if ($hook->permission === false) {
                foreach ($p as $id => $page) {
                    $page->sortable = false;
                }
            } elseif (is_array($hook->permission)) {
                foreach ($hook->permission as $id => $permissions) {
                    $p[$id]->sortable = $permission;
                }
            }
        }

        /*
         * On vérifie la possibilité de supprimer chaque page
         */

        $hook = new Hook();
        $hook->setSubdirName('back');

        $hook->permission = null;
        $hook->utilisateur = $this->utilisateur;
        $hook->ids = $ids;
        $hook->id_version = BACK_ID_VERSION;

        $hook->exec('pagedelete');

        if ($hook->permission !== null) {
            if ($hook->permission === false) {
                foreach ($p as $id => $page) {
                    $page->deletable = false;
                }
            } elseif (is_array($hook->permission)) {
                foreach ($hook->permission as $id => $permissions) {
                    $p[$id]->deletable = $permission;
                }
            }
        }
    }

    /**
     * Liste les gabarits.
     *
     * @return void
     * @hook back/ list<indexConfig> Pour remplacer le chargement d'une config
     * particulière
     */
    public function listeAction()
    {
        //        $this->javascript->addLibrary('back/js/liste.js');
//        $this->javascript->addLibrary('back/js/jquery/jquery.ajaxqueue.js');
//        $this->javascript->addLibrary('back/js/jquery/jquery.scrollTo-min.js');

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
                if (is_array($_GET['gabaritByGroup'])) {
                    $this->view->gabaritByGroup = $_GET['gabaritByGroup'];
                } else {
                    $this->view->gabaritByGroup = true;
                }
                foreach ($gabaritsList as $gabariId) {
                    $this->view->pagesGroup[$gabariId] = $this->gabaritManager->getList(
                        BACK_ID_VERSION,
                        $this->api['id'],
                        0,
                        $gabariId
                    );
                }
            } else {
                $hook = new Hook();
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
            PDO::FETCH_UNIQUE | PDO::FETCH_ASSOC
        );
        $query = 'SELECT `gab_gabarit`.id_parent, `gab_gabarit`.id'
                . ' FROM `gab_gabarit`'
                . ' WHERE `gab_gabarit`.id_parent > 0'
                . ' AND `gab_gabarit`.`id_api` = ' . $this->api['id'];
        $this->gabaritsChildren = $this->db->query($query)->fetchAll(
            PDO::FETCH_GROUP | PDO::FETCH_COLUMN
        );

        $this->getButton($currentConfigPageModule);

        if (is_array($this->pages)) {
            $this->checkPrivileges($this->pages, $this->gabarits);
        }

        $this->view->gabarits = $this->gabarits;
        $this->view->gabaritsChildren = $this->gabaritsChildren;
        $this->view->pages = $this->pages;
        $this->view->currentMenuPage = $indexConfig;

        $this->view->breadCrumbs[] = [
            'title' => $currentConfigPageModule['label'],
            'url' => 'page/liste.html',
        ];
    }

    /**
     * Affichage des enfants d'une page.
     *
     * @return void
     */
    public function childrenAction()
    {
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

        $hook = new Hook();
        $hook->setSubdirName('Back');

        $hook->gabaritManager = $this->gabaritManager;
        $hook->gabaritsList = $gabaritsList;
        $hook->idVersion = BACK_ID_VERSION;
        $hook->idApi = $this->api['id'];
        $hook->idParent = $_REQUEST['id_parent'];

        $hook->exec('List' . $indexConfig);

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

        $query = 'SELECT `gab_gabarit`.id, `gab_gabarit`.*'
            . ' FROM `gab_gabarit`'
            . ' WHERE `gab_gabarit`.`id_api` = ' . $this->api['id'];
        $this->gabarits = $this->db->query($query)->fetchAll(
            PDO::FETCH_UNIQUE | PDO::FETCH_ASSOC
        )
        ;

        $query = 'SELECT `gab_gabarit`.id_parent, `gab_gabarit`.id'
            . ' FROM `gab_gabarit`'
            . ' WHERE `gab_gabarit`.id_parent > 0'
            . ' AND `gab_gabarit`.`id_api` = ' . $this->api['id'];
        $this->gabaritsChildren = $this->db->query($query)->fetchAll(
            PDO::FETCH_GROUP | PDO::FETCH_COLUMN
        )
        ;

        $this->checkPrivileges($this->pages, $this->gabarits);

        $this->view->pages = $this->pages;

        $this->view->gabarits = $this->gabarits;
        $this->view->gabaritsChildren = $this->gabaritsChildren;
    }

    /**
     * Affichage du formulaire de création / d'édition d'une page.
     *
     * @return void
     */
    public function displayAction()
    {
        $gabPageId = isset($_GET['id_gab_page']) ? $_GET['id_gab_page'] : 0;
        $gabaritId = isset($_GET['id_gabarit']) ? $_GET['id_gabarit'] : 1;

        $this->view->action = 'display';

        $this->form = '';
        $this->pages = [];
        $this->redirections = [];

        if ($gabPageId) {
            foreach ($this->versions as $versionId => $version) {
                $page = $this->gabaritManager->getPage(
                    $versionId,
                    BACK_ID_API,
                    $gabPageId
                );

                $this->pages[$versionId] = $page;

                $hook = new Hook();
                $hook->setSubdirName('back');

                $hook->permission = null;
                $hook->utilisateur = $this->utilisateur;
                $hook->visible = $page->getMeta('visible') > 0 ? 0 : 1;
                $hook->ids = $gabPageId;
                $hook->id_version = $versionId;

                $hook->exec('pagevisible');

                $page->makeVisible = true;

                if ($page->getGabarit()->getMakeHidden()) {
                    $page->makeHidden = true;
                } else {
                    $page->makeHidden = false;
                }

                if ($hook->permission === false) {
                    if ($hook->visible > 0) {
                        $page->makeVisible = false;
                    } else {
                        $page->makeHidden = false;
                    }
                }

                $path = $page->getMeta('rewriting')
                    . $page->getGabarit()->getExtension();
                foreach ($page->getParents() as $parent) {
                    $path = $parent->getMeta('rewriting') . '/' . $path;
                }

                if ($versionId == BACK_ID_VERSION) {
                    /* Cas de la page d'accueil */
                    if ($page->getMeta('id') == 1) {
                        $this->view->pagePath = '?mode_previsualisation=1';
                    } else {
                        $this->view->pagePath = $path
                            . '?mode_previsualisation=1';
                    }
                }

                $query = 'SELECT `old`'
                    . ' FROM `redirection`'
                    . ' WHERE `new` LIKE ' . $this->db->quote($path);
                $this->redirections[$versionId] = $this->db->query($query)
                    ->fetchAll(PDO::FETCH_COLUMN)
                ;

                $query = 'SELECT * '
                    . 'FROM `main_element_commun_author_google` '
                    . 'WHERE `id_version` = ' . $versionId;
                $this->authors[$versionId] = $this->db->query($query)
                    ->fetchAll(PDO::FETCH_ASSOC)
                ;
            }
        } else {
            $page = $this->gabaritManager->getPage(
                BACK_ID_VERSION,
                BACK_ID_API,
                0,
                $gabaritId
            );
            $this->pages[BACK_ID_VERSION] = $page;
            $this->redirections[BACK_ID_VERSION] = [];

            $query = 'SELECT * '
                . 'FROM `main_element_commun_author_google` '
                . 'WHERE `id_version` = ' . BACK_ID_VERSION;
            $this->authors[BACK_ID_VERSION] = $this->db->query($query)
                ->fetchAll(PDO::FETCH_ASSOC)
            ;
        }

        $this->view->versions = $this->versions;
        $this->view->pages = $this->pages;
        $this->view->redirections = $this->redirections;
        $this->view->authors = $this->authors;
        $this->view->popup = isset($_GET['popup']) ? $_GET['popup'] : null;

        /*
         * On recupere la sous rubrique de page a laquelle il appartient
         * pour le breadCrumbs et le lien retour
         */
        $found = false;
        foreach ($this->configPageModule as $index => $currentConfigPageModule) {
            /*
             * Si le gabarit courant appartient à un des groupes personnalisés
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
                'title' => $this->configPageModule[$indexPageList]['label'],
                'url' => 'back/page/liste.html?c=' . $indexPageList,
            ];
        } else {
            $this->view->breadCrumbs[] = [
                'title' => 'Liste des pages',
                'url' => 'back/page/liste.html',
            ];
        }

        $this->view->breadCrumbs[] = [
            'title' => 'Gestion des pages',
            'url' => '',
        ];

        $this->getButton($currentConfigPageModule);
    }

    /**
     * Page appelé pour la sauvegarde d'une page.
     *
     * @return void
     *
     * @throws Exception
     * @hook back/ pagesaved Après la création / modification d'une page. Si les
     * données envoyés sont les mêmes que celles enregistrées en BDD, cette
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

                        if (!isset($data['id_' . $blocTableName])) {
                            $data['id_' . $blocTableName] = [];
                        }
                        $data['id_' . $blocTableName][$idBlocLine] = $idBlocLine;

                        $data[$fieldPart[0]][] = $val;
                    } else {
                        if (substr($k, 0, 5) == 'champ') {
                            $pageSave = true;
                            $data[$k] = [
                                $val,
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

            $jsonResponse = [
                'status' => 'success',
            ];
        } else {
            if ($_POST['id_gab_page'] > 0) {
                $typeSave = 'Modification';
            } else {
                $typeSave = 'Création';
            }

            $res = $this->gabaritManager->save($_POST);

            if ($res === null) {
                throw new Exception('Problème à l\'enregistrement');
            }

            if ($res === false) {
                /*
                 * Dans le cas d'une mise-à-jour où les données étaient les
                 * mêmes que celles pré-enregistrées en BDD.
                 */

                $modif = false;

                $jsonResponse = [
                    'status' => 'success',
                    'search' => '?id_gab_page=' . $_POST['id_gab_page']
                        . '&popup=more',
                    'id_gab_page' => $_POST['id_gab_page'],
                ];
            } else {
                /*
                 * Création de page ou modification effective
                 */

                $modif = true;

                $this->page = $res;

                $jsonResponse = [
                    'status' => 'success',
                    'search' => '?id_gab_page=' . $this->page->getMeta('id')
                        . '&popup=more',
                    'id_gab_page' => $this->page->getMeta('id'),
                    'text' => 'La page a été enregistrée avec succès',
                    'after' => [
                        'modules/helper/noty',
                        'modules/render/aftersavepage',
                    ],
                ];

                if (isset($_POST['id_temp']) && $_POST['id_temp']) {
                    /*
                     * Déplacement des fichiers utilisés dans la page.
                     */
                    $upload_path = $this->mainConfig->get('upload', 'path');

                    $tempDir = './' . $upload_path . DIRECTORY_SEPARATOR . 'temp-' . $_POST['id_temp'];
                    $targetDir = './' . $upload_path . DIRECTORY_SEPARATOR . $this->page->getMeta('id');

                    rename($tempDir, $targetDir);

                    $query = 'UPDATE `media_fichier` SET'
                        . ' `id_gab_page` = ' . $this->page->getMeta('id') . ','
                        . ' `id_temp` = 0'
                        . ' WHERE `id_temp` = ' . $_POST['id_temp'];
                    $this->db->exec($query);
                }

                if ($jsonResponse['status'] == 'error') {
                    $this->userLogger->addError(
                        $typeSave . 'de page échouée',
                        [
                            'user' => [
                                'id' => $this->utilisateur->id,
                                'login' => $this->utilisateur->login,
                            ],
                            'page' => [
                                'id' => $this->page->getMeta('id'),
                                'version' => [
                                    'id' => (int) $_POST['id_version'],
                                    'name' => $this->versions[$_POST['id_version']]['nom'],
                                ],
                            ],
                        ]
                    );
                } else {
                    $this->userLogger->addInfo(
                        $typeSave . 'de page réussie',
                        [
                            'user' => [
                                'id' => $this->utilisateur->id,
                                'login' => $this->utilisateur->login,
                            ],
                            'page' => [
                                'id' => $this->page->getMeta('id'),
                                'version' => [
                                    'id' => (int) $_POST['id_version'],
                                    'name' => $this->versions[$_POST['id_version']]['nom'],
                                ],
                            ],
                        ]
                    );
                }
            }
        }

        if ($modif) {
            /*
             * Si une création ou une modification a été effectuée,
             * on fait un hook
             */

            $hook = new Hook();
            $hook->setSubdirName('back');

            $hook->page = $this->page;
            $hook->utilisateur = $this->utilisateur;

            $hook->exec('pagesaved');
        }

        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
        header('Content-type: application/json');
        echo json_encode($jsonResponse);
    }

    /**
     * Autocomplete des pages.
     *
     * @return void
     *
     * @deprecated ??? utiliser autocompleteJoinAction à la place
     * @see        Page::autocompleteJoinAction()
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
                    'visible' => $page->getMeta('titre'),
                ];
            }
        }

        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
        header('Content-type: application/json');
        echo json_encode($json);
    }

    /**
     * Recherche de page.
     *
     * @return void
     */
    public function autocompleteJoinAction()
    {
        $this->view->enable(false);
        $this->view->unsetMain();

        $idChamp = $_GET['id_champ'];
        $idVersion = $_GET['id_version'];
        $idGabPage = $_GET['id_gab_page'];
        $ids = isset($_GET['ids']) ? $_GET['ids'] : [];
        $term = isset($_GET['term']) ? $_GET['term'] : '';

        $hook = new Hook();
        $hook->setSubdirName('Back');

        $hook->idChamp = $idChamp;
        $hook->ids = $ids;
        $hook->idVersion = $idVersion;
        $hook->idGabPage = $idGabPage;
        $hook->term = $term;

        $hook->exec('AutocompleteJoin' . $idChamp);

        /* Chargement par défaut */
        if (!isset($hook->results)) {
            $query = 'SELECT code_champ_param, value'
                . ' FROM gab_champ_param_value'
                . ' WHERE id_champ = ' . $idChamp;
            $params = $this->db->query($query)->fetchAll(
                PDO::FETCH_UNIQUE | PDO::FETCH_COLUMN
            )
            ;

            $idField = $params['TABLE.FIELD.ID'];
            if (isset($params['TYPE.GAB.PAGE'])) {
                $typeGabPage = $params['TYPE.GAB.PAGE'];
            } else {
                $typeGabPage = 0;
            }

            if (isset($params['QUERY.FILTER'])) {
                $queryFilter = str_replace('[ID]', $idGabPage, $params['QUERY.FILTER']);
                $queryFilter = str_replace('[ID_VERSION]', $idVersion, $queryFilter);
            } else {
                $queryFilter = '';
            }

            $table = $params['TABLE.NAME'];
            $labelField = $params['TABLE.FIELD.LABEL'];
            $gabPageJoin = '';

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

            $pagesFound = $this->db->query($query)->fetchAll(PDO::FETCH_ASSOC);

            $results = [];
            foreach ($pagesFound as $page) {

                // Si on a une valeur pour optgroup et qu'il n'existe pas encore
                if (!empty($page['optgroup']) && !isset($results[$page['optgroup']])) {
                    $results[$page['optgroup']] = [
                        'text' => $page['optgroup'],
                        'children' => [],
                    ];
                }

                $page = [
                    'text' => $page['label'],
                    'id' => $page['id'],
                ];

                // On a un optgroup
                if (!empty($page['optgroup'])) {
                    $results[$page['optgroup']]['children'][] = $page;
                } else {
                    $results[] = $page;
                }
            }
        } else {
            $results = $hook->results;
        }

        $json = [
            'items' => $results,
        ];

        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
        header('Content-type: application/json');
        echo json_encode($json);
    }

    /**
     * Recherche d'anciennes url (en cas de refonte de site).
     *
     * @return void
     */
    public function autocompleteOldLinksAction()
    {
        $this->view->enable(false);
        $this->view->unsetMain();

        $term = $_GET['term'];
        $table = 'old_link';
        $labelField = '`' . $table . '`.`link`';

        $quotedTerm = $this->db->quote('%' . $term . '%');

        $sql = 'SELECT ' . $labelField . ' value'
            . ' FROM `' . $table . '`'
            . ' WHERE ' . $labelField . ' LIKE ' . $quotedTerm;

        $json = $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);

        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
        header('Content-type: application/json');
        echo json_encode($json);
    }

    /**
     * Moteur de recherche des pages.
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
        $this->filter = new stdClass();
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
            if (trim($this->filter->words[$i]) != '' && strlen(trim($this->filter->words[$i])) >= 1) {
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
                if (strlen($t1) >= 1) {
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

        $query = 'SELECT `gab_page`.`id` id, gab_page.titre label,'
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

        $pagesFound = $this->db->query($query)->fetchAll(PDO::FETCH_ASSOC);

        foreach ($pagesFound as $page) {
            $highlight = Tools::highlightedSearch(
                $page['label'],
                $this->filter->wordsAdvanced
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
     * Rendre une page visible / invisible.
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

        $jsonResponse = [
            'status' => 'error',
        ];
        $idVersion = BACK_ID_VERSION;

        if (isset($_POST['id_version']) && $_POST['id_version'] > 0) {
            $idVersion = intval($_POST['id_version']);
        }

        if (isset($_POST['visible'])) {
            $visible = intval(!$_POST['visible']);
        }

        $hook = new Hook();
        $hook->setSubdirName('back');

        $hook->permission = null;
        $hook->utilisateur = $this->utilisateur;
        $hook->visible = $visible;
        $hook->ids = $_POST['id'];
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
            && is_numeric($_POST['id'])
            && is_numeric($visible)
        ) {
            if ($visible == 1) {
                $htmlResponse = 'La page a été rendu visible avec succès';
            } else {
                $htmlResponse = 'La page a été rendu invisible avec succès';
            }

            $success = $this->gabaritManager->setVisible(
                $idVersion,
                BACK_ID_API,
                $_POST['id'],
                $visible
            );

            if ($success) {
                $this->userLogger->addInfo(
                    $htmlResponse,
                    [
                        'user' => [
                            'id' => $this->utilisateur->id,
                            'login' => $this->utilisateur->login,
                        ],
                        'page' => [
                            'id' => (int) $_POST['id'],
                            'versionId' => (int) $idVersion,
                        ],
                    ]
                );

                $jsonResponse = [
                    'status' => 'success',
                    'text' => $htmlResponse,
                    'visible' => $visible,
                    'after' => [
                        'modules/helper/noty',
                        'modules/render/visible',
                    ],
                ];
            } else {
                $this->userLogger->addError(
                    $htmlResponse,
                    [
                        'user' => [
                            'id' => $this->utilisateur->id,
                            'login' => $this->utilisateur->login,
                        ],
                        'page' => [
                            'id' => (int) $_POST['id'],
                            'versionId' => (int) $idVersion,
                        ],
                    ]
                );
            }
        }

        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
        header('Content-type: application/json');
        echo json_encode($jsonResponse);
    }

    /**
     * Suppression d'une page (suppression logique en base).
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

        $jsonResponse = [
            'status' => 'error',
        ];

        $hook = new Hook();
        $hook->setSubdirName('back');

        $hook->permission = null;
        $hook->utilisateur = $this->utilisateur;
        $hook->ids = $_POST['id'];
        $hook->id_version = BACK_ID_VERSION;

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
            && is_numeric($_POST['id'])
        ) {
            $page = $this->gabaritManager->getPage(BACK_ID_VERSION, BACK_ID_API, $_POST['id']);

            $delete = $this->gabaritManager->delete($_POST['id']);

            if ($delete) {
                $this->userLogger->addInfo(
                    'Suppression de page réussie',
                    [
                        'user' => [
                            'id' => $this->utilisateur->id,
                            'login' => $this->utilisateur->login,
                        ],
                        'page' => [
                            'id' => (int) $_POST['id'],
                        ],
                    ]
                );

                $jsonResponse['status'] = 'success';
                $jsonResponse['page'] = [
                    'id' => (int) $_POST['id'],
                    'type' => $page->getGabarit()->getLabel(),
                ];
                $jsonResponse['after'] = [
                    'modules/render/afterdeletepage',
                ];
            } else {
                $this->userLogger->addError(
                    'Suppression de page échouée',
                    [
                        'user' => [
                            'id' => $this->utilisateur->id,
                            'login' => $this->utilisateur->login,
                        ],
                        'page' => [
                            'id' => (int) $_POST['id_gab_page'],
                        ],
                    ]
                );
                $htmlResponse = '&laquo; ' . $_POST['elementTitle'] . ' &raquo; a été supprimé avec succès';
                $jsonResponse = [
                    'status' => 'success',
                    'text' => $htmlResponse,
                    'after' => [
                        'modules/helper/noty',
                        'modules/render/delete',
                    ],
                ];
            }
        }

        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
        header('Content-type: application/json');
        echo json_encode($jsonResponse);
    }

    /**
     * Modification de l'ordre de pages.
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

        $hook = new Hook();
        $hook->setSubdirName('back');

        $hook->permission = null;
        $hook->utilisateur = $this->utilisateur;
        $hook->ids = array_keys($_POST['positions']);
        $hook->id_version = BACK_ID_VERSION;

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
            $query = 'UPDATE `gab_page` SET `ordre` = :ordre WHERE `id` = :id';
            $prepStmt = $this->db->prepare($query);
            foreach ($_POST['positions'] as $ordre => $id) {
                $prepStmt->bindValue(':ordre', $ordre, PDO::PARAM_INT);
                $prepStmt->bindValue(':id', $id, PDO::PARAM_INT);
                $tmp = $prepStmt->execute();
                if ($ok) {
                    $ok = $tmp;
                }
            }

            if ($ok) {
                $this->userLogger->addInfo(
                    'Changement d\'ordre réalisé avec succès',
                    [
                        'user' => [
                            'id' => $this->utilisateur->id,
                            'login' => $this->utilisateur->login,
                        ],
                        'page' => [
                            'id' => (int) $id,
                            'order' => (int) $ordre,
                        ],
                    ]
                );
                $json['status'] = 'success';
            } else {
                $this->userLogger->addError(
                    'Changement d\'ordre échoué',
                    [
                        'user' => [
                            'id' => $this->utilisateur->id,
                            'login' => $this->utilisateur->login,
                        ],
                        'page' => [
                            'id' => (int) $id,
                            'order' => (int) $ordre,
                        ],
                    ]
                );
            }
        }

        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
        header('Content-type: application/json');
        echo json_encode($json);
    }

    /**
     * Génère les boutons de création de page.
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

            if (isset($currentConfigPageModule['boutons'])
                && isset($currentConfigPageModule['boutons']['gabarit'])
                && isset($currentConfigPageModule['boutons']['gabarit'][$gabarit['id']]['label'])
            ) {
                $gabarit['label'] = $currentConfigPageModule['boutons']['gabarit'][$gabarit['id']]['label'];
            }
            $gabaritsGroup = [
                'label' => $gabarit['label'],
            ];

            /*
             * Si utilisateur standart à le droit de créer ce type de gabarit
             * ou si utilisateur solire
             */
            if ($gabarit['creable']
                || $this->utilisateur->niveau == 'super administrateur'
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
     * Met un mot au singulier.
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
     * Met un mot au pluriel.
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
     * Compare la longueur de deux chaînes de caractères.
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
