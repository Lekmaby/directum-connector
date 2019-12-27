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

    public function runScript($name, $data = null)
    {
        try {
            $params['Name'] = $name;
            $parameters = self::runScriptPrepareData($name, $data);
            if ($parameters !== null) {
                $params['Params'] = $parameters;
            }

            $resp = $this->get()->RunScript($params);

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

            return $this->get()->OpenUserToken($params)->OpenUserTokenResult;

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

    private static function runScriptPrepareData($name, $data): ?array
    {
        if ($data === null) {
            return null;
        }
        $result = [];
        switch ($name) {
            case 'FUAssignmentsStatisticsForManager':
                $result[] = [
                    'Key'   => 'dataS',
                    'Value' => self::formatDateForRequest($data['dataS'])
                ];
                $result[] = [
                    'Key'   => 'dataE',
                    'Value' => self::formatDateForRequest($data['dataE'])
                ];
                break;
            case 'FUAssignmentsInWorkForManager':
                $result[] = [
                    'Key'   => 'dataS',
                    'Value' => self::formatDateForRequest($data['dataS'])
                ];
                $result[] = [
                    'Key'   => 'dataE',
                    'Value' => self::formatDateForRequest($data['dataE'])
                ];
                $result[] = [
                    'Key'   => 'UserID',
                    'Value' => $data['UserID']
                ];
                break;
            case 'FUAssignmentsGetWorkerIDByLogin':
                $result[] = [
                    'Key'   => 'UserName',
                    'Value' => $data['UserName']
                ];
                break;
            default:
                foreach ($data as $key => $value) {
                    $result[] = [
                        'Key'   => $key,
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
        libxml_use_internal_errors(true);

        switch ($name) {
            case 'FUAssignmentsStatisticsForManager':
            case 'FUAssignmentsInWorkForManager':
            case 'FUAssigDetalesInWorkForManager':
                //$result = new SimpleXMLElement('<result>' . trim(preg_replace('/\s+/', ' ', $data)) . '</result>');
                $xmlstr = '<result>' . trim($data) . '</result>';
                $result = simplexml_load_string($xmlstr);
                $errors = libxml_get_errors();
                if ($errors && count($errors) > 0) {
                    $xml = explode("\n", $xmlstr);
                    foreach ($errors as $error) {
                        dump(self::display_xml_error($error, $xml));
                    }
                    dd($xml);
                }
                break;
            case 'FUAssignmentsGetAnalitics':
                $result = new SimpleXMLElement('<result>' . trim(preg_replace('/\s+/', ' ', $data)) . '</result>');
                $result = json_decode(json_encode($result), TRUE);
                $result = $result['Workers']['Worker'];
                break;
            case 'FUAssignmentsGetWorkerIDByLogin':
                $result = (int)$data;
                break;
            default:
                $result = $data;
        }

        libxml_clear_errors();

        return $result;
    }

    private static function getEntityPrepareResult($name, $data)
    {
        $result = [];
        $data = new SimpleXMLElement($data);
        switch ($name) {
            case 'ПОЛ':
                /**
                 * Может не работать, т.к. нигде не вызывается
                 * Структура XML может отличаться
                 */
                foreach ($data->Object->Record->Section as $section) {
                    if (isset($section->Requisites)) {
                        foreach ($section->Requisites->Requisite as $requisite) {
                            $Name = (string)$requisite->attributes()->Name;
                            $Data = ((array)$requisite->attributes())['@attributes'];
                            $Data['Value'] = (string)$requisite;
                            $result[$Name] = $Data;
                            if (!isset($result[$Name]['DisplayValue'])) {
                                $result[$Name]['DisplayValue'] = '';
                            }
                        }
                    }
                }
                break;
            case 'РАБ':
                foreach ($data->Object->Record->Section->Requisite as $requisite) {
                    $Name = (string)$requisite->attributes()->Name;
                    $Data = ((array)$requisite->attributes())['@attributes'];
                    $Data['Value'] = (string)$requisite;
                    $result[$Name] = $Data;
                    if (!isset($result[$Name]['DisplayValue'])) {
                        $result[$Name]['DisplayValue'] = '';
                    }
                }
                break;
            default:
                $result = $data;
        }

        return $result;
    }

    private static function display_xml_error($error, $xml)
    {
        $return = $xml[$error->line - 1] . "\n";
        $return .= str_repeat('-', $error->column) . "^\n";

        switch ($error->level) {
            case LIBXML_ERR_WARNING:
                $return .= "Warning $error->code: ";
                break;
            case LIBXML_ERR_ERROR:
                $return .= "Error $error->code: ";
                break;
            case LIBXML_ERR_FATAL:
                $return .= "Fatal Error $error->code: ";
                break;
        }

        $return .= trim($error->message) .
            "\n  Line: $error->line" .
            "\n  Column: $error->column";

        if ($error->file) {
            $return .= "\n  File: $error->file";
        }

        return $return;
    }
}
