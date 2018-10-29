<?php

namespace Kins\DirectumConnector;

use Illuminate\Support\Facades\Log;

use SoapClient;
use SoapFault;

class DirectumService
{
    private $soap;

    public function __construct($uri)
    {
        try {
            $this->soap = new SoapClient($uri, array(
                'cache_wsdl' => WSDL_CACHE_NONE,
                'cache_ttl'  => 0,
                'trace'      => true,
                'exceptions' => true,
            ));
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
            $resp = $this->get()->RunScript(
                array(
                    'Name'       => $name,
                    'Parameters' => $params
                )
            );

            return self::runScriptPrepareResult($name, $resp->RunScriptResult);

        } catch (SoapFault $e) {
            $this->ExceptionHandler($e, 'Directum RunScript error');
        }

        return false;
    }

    public function GetEntityItem($ReferenceName, $RecordKey)
    {
        try {
            $resp = $this->get()->GetEntity(
                array(
                    'ReferenceName' => $ReferenceName,
                    'RecordKey'     => $RecordKey
                )
            );

            return $resp->GetEntityResult;

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
            $resp = $this->get()->CloseUserToken(
                array(
                    'Token' => $Token
                )
            );

            return $resp->CloseUserTokenResult;

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
                    'Value' => self::formatDateForRequest($data['dataS'])
                ];
                break;
            case 'FUAssignmentsInWorkForManager':
                $result['Parameter'][] = [
                    'Name'  => 'dataS',
                    'Value' => self::formatDateForRequest($data['dataS'])
                ];
                $result['Parameter'][] = [
                    'Name'  => 'dataE',
                    'Value' => self::formatDateForRequest($data['dataS'])
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
                $result = $data;
                break;
            case 'FUAssignmentsInWorkForManager':
                $result = $data;
                break;
            case 'FUAssignmentsGetWorkerIDByLogin':
                $result = (int)$data;
                break;
            default:
                $result = $data;
        }

        return $result;
    }
}
