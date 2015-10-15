<?php

namespace Solire\Back\Controller;

use Doctrine\DBAL\DriverManager;
use Solire\Conf\Loader as ConfLoader;
use Solire\Lib\FrontController;

/**
 * Description of Formsave
 *
 * @author thansen
 */
class Formulaire extends Main
{
    public function saveAction()
    {
        $this->view->enable(false);

        $confName = filter_var($_GET['name'], FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW);

        $confPath = FrontController::search('config/form/' . $confName . '.ini');
        if (!$confPath) {
            echo json_encode([
                'status' => 'error',
                'msg' => 'wrong',
            ]);
            return;
        }
        $formConf = ConfLoader::load($confPath);
        $form = new \Solire\Form\Formulaire($formConf);
        $request = $form->run();

        $confPath = FrontController::search('config/form/save/' . $confName . '.yml');
        if (!$confPath) {
            echo json_encode([
                'status' => 'error',
                'msg' => 'wrong',
            ]);
            return;
        }
        $saveConf = ConfLoader::load($confPath);

        $identifier = [];
        $pks = explode('|', $request['cle']);
        foreach ($saveConf->cle as $cle) {
            $identifier[$cle] = array_shift($pks);
        }

        $data = [];
        foreach ($saveConf->champs as $champ) {
            $data[$champ] = $request[$champ];
        }

        $doctrineConnection = DriverManager::getConnection([
            'pdo' => $this->db,
        ]);
        $doctrineConnection->update($saveConf->table, $data, $identifier);

        echo json_encode([
            'status' => 'success',
        ]);
    }
}
