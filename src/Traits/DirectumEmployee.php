<?php

namespace Kins\DirectumConnector\Traits;


trait DirectumEmployee
{
    public function updateEmployeeFromDirectum()
    {
        if ($this->dir_id > 0) {
            $result = \DirectumSoap::GetEntityItem('РАБ', $this->dir_id);
        } else {
            return false;
        }

        $name = self::split_name($result['Персона']['DisplayValue']);

        $this->surname = $name['last_name'];
        $this->name = $name['first_name'];
        $this->name_2 = $name['middle_name'];
        $this->job_title = $result['Строка']['Value'];
        $this->department = $result['Подразделение']['DisplayValue'];


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
}
