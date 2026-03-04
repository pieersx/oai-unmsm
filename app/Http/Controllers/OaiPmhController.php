<?php

namespace App\Http\Controllers;

use App\Models\Publication;
use Illuminate\Http\Request;
use SimpleXMLElement;

class OaiPmhController extends Controller
{
    private const OAI_NAMESPACE = 'http://www.openarchives.org/OAI/2.0/';
    private const OAI_PMH_SCHEMA = 'http://www.openarchives.org/OAI/2.0/OAI-PMH.xsd';
    private const DC_NAMESPACE = 'http://purl.org/dc/elements/1.1/';
    private const OAI_DC_SCHEMA = 'http://www.openarchives.org/OAI/2.0/oai_dc.xsd';

    public function handle(Request $request)
    {
        $verb = $request->query('verb');

        if (!$verb) {
            return $this->errorResponse('badRequest', 'Falta parámetro verb');
        }

        return match ($verb) {
            'Identify' => $this->identify(),
            'ListMetadataFormats' => $this->listMetadataFormats($request),
            'ListSets' => $this->listSets(),
            'ListIdentifiers' => $this->listIdentifiers($request),
            'ListRecords' => $this->listRecords($request),
            'GetRecord' => $this->getRecord($request),
            default => $this->errorResponse('badVerb', "Verbo '$verb' no reconocido"),
        };
    }

    private function identify()
    {
        $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><OAI-PMH xmlns="' . self::OAI_NAMESPACE . '" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="' . self::OAI_NAMESPACE . ' ' . self::OAI_PMH_SCHEMA . '"/>');

        $xml->addChild('responseDate', now()->toAtomString());
        $request = $xml->addChild('request', route('oai.pmh'));
        $request->addAttribute('verb', 'Identify');

        $identify = $xml->addChild('Identify');
        $identify->addChild('repositoryName', 'UNMSM OAI-PMH Repository');
        $identify->addChild('baseURL', route('oai.pmh'));
        $identify->addChild('protocolVersion', '2.0');
        $identify->addChild('adminEmail', config('mail.from.address'));
        $identify->addChild('earliestDatestamp', Publication::min('created_at') ?? now()->toDateString());
        $identify->addChild('deletedRecord', 'no');
        $identify->addChild('granularity', 'YYYY-MM-DDThh:mm:ssZ');

        return response($xml->asXML(), 200, ['Content-Type' => 'application/xml']);
    }

    private function listMetadataFormats(Request $request)
    {
        $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><OAI-PMH xmlns="' . self::OAI_NAMESPACE . '"/>');

        $xml->addChild('responseDate', now()->toAtomString());
        $req = $xml->addChild('request', route('oai.pmh'));
        $req->addAttribute('verb', 'ListMetadataFormats');

        $formats = $xml->addChild('ListMetadataFormats');

        $format = $formats->addChild('metadataFormat');
        $format->addChild('metadataPrefix', 'oai_dc');
        $format->addChild('schema', self::OAI_DC_SCHEMA);
        $format->addChild('metadataNamespace', self::DC_NAMESPACE);

        return response($xml->asXML(), 200, ['Content-Type' => 'application/xml']);
    }

    private function listSets()
    {
        $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><OAI-PMH xmlns="' . self::OAI_NAMESPACE . '"/>');

        $xml->addChild('responseDate', now()->toAtomString());
        $req = $xml->addChild('request', route('oai.pmh'));
        $req->addAttribute('verb', 'ListSets');

        $sets = $xml->addChild('ListSets');
        $set = $sets->addChild('set');
        $set->addChild('setSpec', 'publications');
        $set->addChild('setName', 'Publicaciones UNMSM');

        return response($xml->asXML(), 200, ['Content-Type' => 'application/xml']);
    }

    private function listRecords(Request $request)
    {
        $prefix = $request->query('metadataPrefix', 'oai_dc');
        $perPage = 50;
        $page = (int) $request->query('page', 1);

        if ($prefix !== 'oai_dc') {
            return $this->errorResponse('cannotDisseminateFormat', "Formato '$prefix' no soportado");
        }

        $publications = Publication::paginate($perPage, ['*'], 'page', $page);
        $xml = $this->createBaseXml('ListRecords');

        if ($publications->count() > 0) {
            $listRecords = $xml->addChild('ListRecords');
            foreach ($publications as $pub) {
                $record = $listRecords->addChild('record');
                $this->addRecordMetadata($record, $pub);
            }
        }

        return response($xml->asXML(), 200, ['Content-Type' => 'application/xml']);
    }

    private function listIdentifiers(Request $request)
    {
        $prefix = $request->query('metadataPrefix', 'oai_dc');
        $perPage = 50;
        $page = (int) $request->query('page', 1);

        if ($prefix !== 'oai_dc') {
            return $this->errorResponse('cannotDisseminateFormat', "Formato '$prefix' no soportado");
        }

        $publications = Publication::paginate($perPage, ['*'], 'page', $page);
        $xml = $this->createBaseXml('ListIdentifiers');

        if ($publications->count() > 0) {
            $listIdentifiers = $xml->addChild('ListIdentifiers');
            foreach ($publications as $pub) {
                $header = $listIdentifiers->addChild('header');
                $header->addChild('identifier', 'oai:unmsm:' . $pub->id);
                $header->addChild('datestamp', $pub->created_at->toDateString());
            }
        }

        return response($xml->asXML(), 200, ['Content-Type' => 'application/xml']);
    }

    private function getRecord(Request $request)
    {
        $identifier = $request->query('identifier');
        $prefix = $request->query('metadataPrefix', 'oai_dc');

        if (!$identifier) {
            return $this->errorResponse('badArgument', 'Identificador requerido');
        }

        if ($prefix !== 'oai_dc') {
            return $this->errorResponse('cannotDisseminateFormat', "Formato '$prefix' no soportado");
        }

        $id = str_replace('oai:unmsm:', '', $identifier);
        $publication = Publication::find($id);

        if (!$publication) {
            return $this->errorResponse('idDoesNotExist', 'Identificador no existe');
        }

        $xml = $this->createBaseXml('GetRecord');
        $record = $xml->addChild('GetRecord')->addChild('record');
        $this->addRecordMetadata($record, $publication);

        return response($xml->asXML(), 200, ['Content-Type' => 'application/xml']);
    }

    private function addRecordMetadata($record, $publication)
    {
        $header = $record->addChild('header');
        $header->addChild('identifier', 'oai:unmsm:' . $publication->id);
        $header->addChild('datestamp', $publication->created_at->toDateString());

        $metadata = $record->addChild('metadata');
        $dc = $metadata->addChild('oai_dc:dc');
        $dc->addAttribute('xmlns:oai_dc', 'http://www.openarchives.org/OAI/2.0/oai_dc/');
        $dc->addAttribute('xmlns:dc', self::DC_NAMESPACE);

        if ($publication->title) {
            $dc->addChild('dc:title', htmlspecialchars($publication->title));
        }
        if ($publication->author) {
            $dc->addChild('dc:creator', htmlspecialchars($publication->author));
        }
        if ($publication->description) {
            $dc->addChild('dc:description', htmlspecialchars($publication->description));
        }
        if ($publication->date) {
            $dc->addChild('dc:date', $publication->date->toDateString());
        }
        if ($publication->identifier) {
            $dc->addChild('dc:identifier', $publication->identifier);
        }
        if ($publication->subject) {
            $dc->addChild('dc:subject', htmlspecialchars($publication->subject));
        }
        $dc->addChild('dc:type', $publication->type ?? 'Article');
        $dc->addChild('dc:language', $publication->language ?? 'es');
        $dc->addChild('dc:publisher', $publication->publisher ?? 'UNMSM');
    }

    private function createBaseXml($element)
    {
        $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><OAI-PMH xmlns="' . self::OAI_NAMESPACE . '" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="' . self::OAI_NAMESPACE . ' ' . self::OAI_PMH_SCHEMA . '"/>');

        $xml->addChild('responseDate', now()->toAtomString());
        $req = $xml->addChild('request', route('oai.pmh'));
        $req->addAttribute('verb', $element);

        return $xml;
    }

    private function errorResponse($code, $message)
    {
        $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><OAI-PMH xmlns="' . self::OAI_NAMESPACE . '"/>');
        $xml->addChild('responseDate', now()->toAtomString());
        $req = $xml->addChild('request', route('oai.pmh'));
        $error = $xml->addChild('error', $message);
        $error->addAttribute('code', $code);

        return response($xml->asXML(), 400, ['Content-Type' => 'application/xml']);
    }
}
