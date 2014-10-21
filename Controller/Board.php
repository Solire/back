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
class Board extends Main
{
    /**
     * Toujours executé avant l'action.
     *
     * @return void
     */
    public function start()
    {
        parent::start();

        if (!$this->_appConfig->get('board', 'active')) {
            $this->pageNotFound();
        }
    }

    /**
     * Affichage du tableau de bord
     *
     * @return void
     */
    public function startAction()
    {
        $this->_view->action = 'board';

        /** Chargement du datatable en fonction des droits **/
        $configPageModule =
            $this->_configPageModule[$this->utilisateur->gabaritNiveau];
        $gabaritsListUser = $configPageModule['gabarits'];
        if ($gabaritsListUser == '*') {
            $this->boardDatatable();
        } else {
            $this->boardDatatable($this->utilisateur->gabaritNiveau);
        }

        $query  = 'SELECT `gab_gabarit`.id, count(DISTINCT gab_page.id) nbpages,'
                . ' `gab_gabarit`.*'
                . ' FROM `gab_gabarit`'
                . ' LEFT JOIN gab_page ON gab_page.id_gabarit = gab_gabarit.id'
                . ' AND gab_page.suppr = 0'
                . ' WHERE `gab_gabarit`.`id_api` = ' . $this->api['id']
                . ' AND `gab_gabarit`.id NOT IN (1,2)'
                . ' GROUP BY gab_gabarit.id'
                . ' ORDER BY gab_gabarit.id';
        $this->_gabarits2 = $this->_db->query($query)->fetchAll(
            \PDO::FETCH_UNIQUE | \PDO::FETCH_ASSOC
        );
        $pages = array();

        $colorWidget = array(
            'color-yellow',
            'color-red',
            'color-blue',
            'color-white',
            'color-orange',
            'color-green',
        );
        $indexColor = 0;
        $lastGabaritId = -1;

        foreach ($this->_gabarits2 as $gabarit) {
            $pagesMeta = $this->gabaritManager->getList(
                BACK_ID_VERSION,
                $this->api['id'],
                false,
                $gabarit['id'],
                false,
                'date_crea',
                'desc',
                0,
                3
            );

            if (count($pagesMeta) == 0) {
                continue;
            }

            $pages[$gabarit['id']]['gabarit'] = $gabarit;
            foreach ($pagesMeta as $pageMeta) {
                $page = $this->gabaritManager->getPage(
                    BACK_ID_VERSION,
                    BACK_ID_API,
                    $pageMeta->getMeta('id')
                );
                $pages[$gabarit['id']]['pages'][] = $page;
            }

            $pagesMeta = $this->gabaritManager->getList(
                BACK_ID_VERSION,
                $this->api['id'],
                false,
                $gabarit['id'],
                false,
                'date_modif',
                'desc',
                0,
                3
            );

            if (count($pagesMeta) == 0) {
                continue;
            }

            if ($gabarit['id_parent'] == $lastGabaritId) {
                $indexColor--;
            }
            $lastGabaritId = $gabarit['id'];

            $pages[$gabarit['id']]['gabarit'] = $gabarit;
            if (!isset($colorWidget[$indexColor])) {
                $indexColor = 0;
            }

            $pages[$gabarit['id']]['color'] = $colorWidget[$indexColor];

            $indexColor++;
            foreach ($pagesMeta as $pageMeta) {
                $page = $this->gabaritManager->getPage(
                    BACK_ID_VERSION,
                    BACK_ID_API,
                    $pageMeta->getMeta('id')
                );
                $pages[$gabarit['id']]['pages_mod'][] = $page;
            }
        }
        $this->_view->pages = $pages;

        $this->_view->breadCrumbs[] = array(
            'label' => 'Tableau de bord',
            'url' => 'back/board/start.html',
        );
    }

    /**
     * Génération du datatable des pages crées / éditées / supprimées
     *
     * @var string $opt Option qui s'ajoute au nom du fichier de configuration
     *
     * @return void
     */
    /**
     * Génération du datatable des pages crées / éditées / supprimées
     *
     * @param string $opt Option qui s'ajoute au nom du fichier de configuration
     *
     * @return void
     */
    private function boardDatatable($opt = null)
    {
        $configName = 'board';
        $gabarits = array();
        if (empty($opt)) {
            $gabarits = $this->_gabarits;
        } else {
            /* Récupération de la liste de la page et des droits utilisateurs */
            $configPageModule = $this->_configPageModule[$this->utilisateur->gabaritNiveau];
            $gabaritsListUser = $configPageModule['gabarits'];
            foreach ($this->_gabarits as $keyId => $gabarit) {
                if (in_array($gabarit['id'], $gabaritsListUser)) {
                    $gabarits[$keyId] = $gabarit;
                }
            }
            unset($configPageModule);
        }

        $configPath = \Slrfw\FrontController::search(
            'config/datatable/' . $configName . '.cfg.php'
        );

        $this->_gabarits = $gabarits;

        $datatableClassName = '\\App\\Back\\Datatable\\Board';
        /** @todo Chargement des fichiers des differentes app */
        try {
            $datatable = new $datatableClassName(
                $_GET, $configPath, $this->_db, './datatable/',
                './datatable/', 'img/datatable/');
        } catch (\Exception $exc) {
            $datatable = new \Slrfw\Datatable\Datatable(
                $_GET,
                $configPath,
                $this->_db,
                './datatable/',
                './datatable/',
                'img/datatable/'
            );
        }

        /** On cré notre object datatable */
        $datatable = new $datatableClassName($_GET, $configPath, $this->_db,
            '/back/css/datatable/', '/back/js/datatable/', 'img/datatable/');

        $datatable->setUtilisateur($this->utilisateur);
        $datatable->setGabarits($this->_gabarits);
        $datatable->setVersions($this->_versions);

        /** On cré un filtre pour les gabarits de l'api courante */
        $idsGabarit = array();
        foreach ($this->_gabarits as $gabarit) {
            $idsGabarit[] = $gabarit['id'];
        }
        $aqqQuery = 'id_gabarit IN (' . implode(',', $idsGabarit) . ')';
        $datatable->additionalWhereQuery($aqqQuery);

        $datatable->start();
        $datatable->setDefaultNbItems(
            $this->_appConfig->get('board', 'nb-content-default')
        );

        if (isset($_GET['json']) || (isset($_GET['nomain'])
            && $_GET['nomain'] == 1)
        ) {
            echo $datatable;
            exit();
        }

        $this->_view->datatableRender = $datatable;
    }
}
