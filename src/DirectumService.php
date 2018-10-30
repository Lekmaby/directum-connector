<?php

namespace Kins\DirectumConnector;

use Illuminate\Support\Facades\Log;

use SimpleXMLElement;
use SoapClient;
use SoapFault;

class DirectumService
{
    private $soap;

    public function __construct($uri)
    {
        try {
            $this->soap = new SoapClient($uri, [
                'cache_wsdl' => WSDL_CACHE_NONE,
                'cache_ttl'  => 0,
                'trace'      => true,
                'exceptions' => true,
            ]);
        } catch (SoapFault $e) {
            $this->ExceptionHandler($e, 'Directum connection error');
        }
    }

    private function ExceptionHandler($e, $error = 'Directum SOAP Error')
    {
        Log::error($e->getMessage());
        if (env('APP_DEBUG', false)) {
            abort(500, $e->getMessage());
        }
        abort(500, $error);
    }

    public function get()
    {
        return $this->soap;
    }

    public function runScript($name, $data)
    {
        try {
            $params = self::runScriptPrepareData($name, $data);
            $resp = $this->get()->RunScript([
                'Name'       => $name,
                'Parameters' => $params
            ]);

            return self::runScriptPrepareResult($name, $resp->RunScriptResult);

        } catch (SoapFault $e) {
            $this->ExceptionHandler($e, 'Directum RunScript error');
        }

        return false;
    }

    public function GetEntityItem($ReferenceName, $RecordKey)
    {
        try {
            $resp = $this->get()->GetEntity([
                'ReferenceName' => $ReferenceName,
                'RecordKey'     => $RecordKey
            ]);

            return self::getEntityPrepareResult($ReferenceName, $resp->GetEntityResult);

        } catch (SoapFault $e) {
            $this->ExceptionHandler($e, 'Directum GetEntity error');
        }

        return false;
    }

    public function OpenUserToken($UserName, $Password, $ExpirationDate = null)
    {
        try {
            $params = [
                'UserName' => $UserName,
                'Password' => $Password,
            ];
            if ($ExpirationDate !== null) {
                $params['ExpirationDate'] = $ExpirationDate;
            }
            $resp = $this->get()->OpenUserToken($params);

            return $resp->OpenUserTokenResult;

        } catch (SoapFault $e) {
            $this->ExceptionHandler($e, 'Directum OpenUserToken error');
        }

        return false;
    }

    public function CloseUserToken($Token)
    {
        try {
            $resp = $this->get()->CloseUserToken([
                'Token' => $Token
            ]);

            return $resp;

        } catch (SoapFault $e) {
            $this->ExceptionHandler($e, 'Directum CloseUserToken error');
        }

        return false;
    }

    private static function runScriptPrepareData($name, $data): array
    {
        $result = [];
        switch ($name) {
            case 'FUAssignmentsStatisticsForManager':
                $result['Parameter'][] = [
                    'Name'  => 'dataS',
                    'Value' => self::formatDateForRequest($data['dataS'])
                ];
                $result['Parameter'][] = [
                    'Name'  => 'dataE',
                    'Value' => self::formatDateForRequest($data['dataE'])
                ];
                break;
            case 'FUAssignmentsInWorkForManager':
                $result['Parameter'][] = [
                    'Name'  => 'dataS',
                    'Value' => self::formatDateForRequest($data['dataS'])
                ];
                $result['Parameter'][] = [
                    'Name'  => 'dataE',
                    'Value' => self::formatDateForRequest($data['dataE'])
                ];
                $result['Parameter'][] = [
                    'Name'  => 'UserID',
                    'Value' => $data['UserID']
                ];
                break;
            case 'FUAssignmentsGetWorkerIDByLogin':
                $result['Parameter'][] = [
                    'Name'  => 'UserName',
                    'Value' => $data['UserName']
                ];
                break;
            default:
                foreach ($data as $key => $value) {
                    $result['Parameter'][] = [
                        'Name'  => $key,
                        'Value' => $value
                    ];
                }
        }

        return $result;
    }

    private static function formatDateForRequest($date)
    {
        return date('d.m.Y', strtotime($date));
    }

    private static function runScriptPrepareResult($name, $data)
    {
        switch ($name) {
            case 'FUAssignmentsStatisticsForManager':
                $result = new SimpleXMLElement('<result>' . $data . '</result>');
                break;
            case 'FUAssignmentsInWorkForManager':
                $result = new SimpleXMLElement('<result>' . $data . '</result>');
                break;
            case 'FUAssignmentsGetWorkerIDByLogin':
                $result = (int)$data;
                break;
            default:
                $result = $data;
        }

        return $result;
    }

    private static function getEntityPrepareResult($name, $data)
    {
        $result = [];
        switch ($name) {
            case 'ПОЛ':
                foreach ($data->References->Reference->Records->Record->Sections->Section as $section) {
                    if (isset($section->Requisites)) {
                        foreach ($section->Requisites->Requisite as $requisite) {
                            $result[$requisite->Name] = (array)$requisite;
                            if (!isset($result[$requisite->Name]['DisplayValue'])) {
                                $result[$requisite->Name]['DisplayValue'] = '';
                            }
                        }
                    }
                }
                break;
            case 'РАБ':
                foreach ($data->References->Reference->Records->Record->Sections->Section->Requisites->Requisite as $requisite) {
                    $result[$requisite->Name] = (array)$requisite;
                    if (!isset($result[$requisite->Name]['DisplayValue'])) {
                        $result[$requisite->Name]['DisplayValue'] = '';
                    }
                }
                break;
            default:
                $result = $data;
        }

        return $result;
    }
}
