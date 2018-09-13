<?php

namespace Kins\DirectumConnector\Traits;

use Illuminate\Support\Facades\App;
use Kins\DirectumConnector\DirectumService;

trait DirectumUser
{
    public function updateUserFromDirectum()
    {
        $api = App::make(DirectumService::class);
        if ($this->dir_id > 0) {
            $result = $api->GetEntityItem('РАБ', $this->dir_id);
        } else {
            $dir_id = $api->runScript('FUAssignmentsGetWorkerIDByLogin', ['UserName' => $this->login]);
            if (!empty($dir_id) && $dir_id > 0) {
                $this->dir_id = $dir_id;
                $result = $api->GetEntityItem('РАБ', $dir_id);
            } else {
                return $this;
            }
        }


        //todo получить информаци из директму
        $this->auth_type = 'directum';
        $this->surname = $result;
        $this->name = 'directum';
        $this->name_2 = 'directum';
        $this->email = 'directum';
        $this->gender = 'directum';
        $this->birthdate = 'directum';
        $this->dir_tab_num = 'directum';
        $this->dir_job_title = 'directum';
        $this->dir_department = 'directum';

        $this->update();

        return $this;
    }

    public function authenticateUserInDirectum($login, $password)
    {
        $api = App::make(DirectumService::class);
        $expire_date = date('Y-m-dTH:i:s', strtotime('+1 years'));
        $result = $api->OpenUserToken($login, $password, $expire_date);

        $this->dir_token = $result;
        $this->dir_token_expire = $expire_date;

        $this->update();

        return $this;
    }

    public function logoutUserInDirectum()
    {
        $api = App::make(DirectumService::class);
        $api->CloseUserToken($this->dir_token);

        $this->dir_token = NULL;
        $this->dir_token_expire = NULL;

        $this->update();

        return $this;
    }
}