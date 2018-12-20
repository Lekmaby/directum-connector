<?php

namespace Kins\DirectumConnector\Traits;

use Illuminate\Support\Str;

trait DirectumUser
{
    public static function updateUsersFromDirectum()
    {
        $dirUsers = \DirectumSoap::runScript('FUAssignmentsGetAnalitics');

        foreach ($dirUsers as $dirUser) {
            $user = self::where('dir_id', $dirUser['UserID'])->first();
            if (!$user) {
                $user = new self();
                $user->uuid = (string)Str::uuid();
                $user->dir_id = $dirUser['UserID'];
            }

            $user->sync_source = 'directum';
            $user->active = true;
            $user->surname = !is_array($dirUser['SurName']) ? $dirUser['SurName'] : null;
            $user->name = !is_array($dirUser['FirstName']) ? $dirUser['FirstName'] : null;
            $user->name_2 = !is_array($dirUser['SecondName']) ? $dirUser['SecondName'] : null;
            $user->login = !is_array($dirUser['Login']) ? $dirUser['Login'] : $user->uuid;


            switch ($dirUser['Autorithation']) {
                case 'доменная':
                    $user->auth_type = 'directum_domain';
                    break;
                case 'внутренняя':
                    $user->auth_type = 'directum_inner';
                    break;
                default:
                    $user->auth_type = 'directum_inner';
            }

            $user->save();

            if (array_key_exists('Photo', $dirUser) && $dirUser['Photo'] > 0 !== '') {
                $user->setPhotoFromBase64($dirUser['Photo']);
            }
        }

        return true;
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
