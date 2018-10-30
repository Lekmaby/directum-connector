<?php

namespace Kins\DirectumConnector\Traits;


trait DirectumUser
{
    public function updateUserFromDirectum()
    {
        $dir_id = \DirectumSoap::runScript('FUAssignmentsGetWorkerIDByLogin', ['UserName' => $this->login]);
        if (!empty($dir_id) && $dir_id > 0) {
            $this->dir_id = $dir_id;
            $result = \DirectumSoap::GetEntityItem('РАБ', $dir_id);
        } else {
            return $this;
        }

        $name = self::split_name($result['Персона']['DisplayValue']);

        $this->surname = $name['last_name'];
        $this->name = $name['first_name'];
        $this->name_2 = $name['middle_name'];
        $this->dir_job_title = $result['ВидДолжности']['DisplayValue'];
        $this->dir_department = $result['Подразделение']['DisplayValue'];

        $this->setPhotoFromBase64($result['Текст']['Value']);

        $this->update();

        return $this;
    }

    public static function split_name($name)
    {
        $parts = preg_split('/[\s,]+/', $name);
        $name = [];
        $name['last_name'] = $parts[0];
        $name['first_name'] = $parts[1] ?? '';
        $name['middle_name'] = $parts[2] ?? '';

        return $name;
    }


    public function authenticateUserInDirectum($login, $password)
    {
        $expire_date = date('Y-m-d', strtotime('+1 years')) . 'T' . date('H:i:s');
        $result = \DirectumSoap::OpenUserToken($login, $password, $expire_date);

        $this->dir_token = $result;
        $this->dir_token_expire = $expire_date;

        $this->update();

        return $this;
    }

    public function logoutUserInDirectum()
    {
        \DirectumSoap::CloseUserToken($this->dir_token);

        $this->dir_token = NULL;
        $this->dir_token_expire = NULL;

        $this->update();

        return $this;
    }
}
