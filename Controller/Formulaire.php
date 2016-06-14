<?php

namespace Solire\Back\Controller;

use Doctrine\DBAL\DriverManager;
use Solire\Conf\Loader as ConfLoader;
use Solire\Form\Formulaire as Form;
use Solire\Lib\Exception\User as UserException;
use Solire\Lib\FrontController;
use Solire\Lib\Hook;

/**
 * Description of Formsave.
 *
 * @author thansen
 */
class Formulaire extends Main
{
    public function saveAction()
    {
        $this->view->enable(false);

        $confName = filter_var(
            $_GET['name'],
            FILTER_SANITIZE_STRING,
            FILTER_FLAG_STRIP_LOW
        );

        if (isset($_REQUEST['id']) && $_REQUEST['id'] > 0) {
            $type = 'update';
        } else {
            $type = 'create';
        }

        // Si on n'a pas de conf spécifique pour la création ou mise à jour
        if (!$confPath = FrontController::search('config/form/' . $confName . '.' . $type . '.ini')) {
            $confPath = FrontController::search('config/form/' . $confName . '.ini');
        }

        if (!$confPath) {
            echo json_encode([
                'status' => 'error',
                'msg' => 'wrong',
            ]);

            return;
        }
        $formConf = ConfLoader::load($confPath);

        foreach ($formConf as $key => $conf) {
            if (!empty($_POST[$key])) {
                $conf->obligatoire = true;
            }
        }

        $form = new Form($formConf);

        try {
            $request = $form->run();
        } catch (UserException $uE) {
            echo json_encode([
                'status' => 'error',
                'msg' => $uE->getMessage(),
            ]);

            return;
        }

        // Si on n'a pas de conf spécifique pour la création ou mise à jour
        if (!$confPath = FrontController::search('config/form/save/' . $confName . '.' . $type . '.yml')) {
            $confPath = FrontController::search('config/form/save/' . $confName . '.yml');
        }

        if (!$confPath) {
            echo json_encode([
                'status' => 'error',
                'msg' => 'wrong',
            ]);

            return;
        }
        $saveConf = ConfLoader::load($confPath);

        $identifier = [];
        if (isset($request['cle'])) {
            $pks = explode('|', $request['cle']);

            foreach ($saveConf->cle as $cle) {
                $identifier[$cle] = array_shift($pks);
            }
        }

        $data = [];
        foreach ($saveConf->champs as $champ) {
            if (!isset($request[$champ])) {
                continue;
            }

            $data[$champ] = $request[$champ];
            $dataRaw[$champ] = $request[$champ];
        }

        if (isset($saveConf->timestamp)) {
            foreach ($saveConf->timestamp as $champ) {
                $data[$champ] = date('Y-m-d H:i:s');
            }
        }

        if (isset($saveConf->treatments)) {
            foreach ($saveConf->treatments as $champ => $callables) {
                if (empty($data[$champ])) {
                    continue;
                }

                foreach ($callables as $callable) {
                    if (is_object($callable)) {
                        $callable = array_values((array) $callable);
                    }

                    $data[$champ] = call_user_func($callable, $data[$champ]);
                }
            }
        }

        $doctrineConnection = DriverManager::getConnection([
            'pdo' => $this->db,
        ]);

        if (empty($identifier)) {
            $doctrineConnection->insert($saveConf->table, $data);
            $msg = 'Ajout enregistré';

            $data['id'] = $doctrineConnection->lastInsertId();

            $hook = new Hook();
            $hook->setSubdirName('Back');
            $hook->data = $data;
            $hook->dataRaw = $dataRaw;
            $hook->conf = $saveConf;
            $hook->confName = $confName;
            $hook->exec('Form' . ucfirst($confName) . 'Created');
        } else {
            $doctrineConnection->update($saveConf->table, $data, $identifier);
            $msg = 'Modifications enregistrées';

            $hook = new Hook();
            $hook->setSubdirName('Back');
            $hook->data = $data;
            $hook->dataRaw = $dataRaw;
            $hook->conf = $saveConf;
            $hook->confName = $confName;
            $hook->exec('Form' . ucfirst($confName) . 'Updated');
        }

        echo json_encode([
            'status' => 'success',
            'text' => $msg,
            'after' => [
                'modules/helper/noty',
            ],
        ]);
    }
}
