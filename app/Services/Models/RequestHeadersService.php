<?php

namespace App\Services\Models;

use App\Models\SearchModel;

/**
 * @see https://medium.com/@sadigrzazada20/the-accept-and-content-type-headers-in-http-requests-and-responses-serve-different-purposes-and-fe5c0b808ecf
 */
class RequestHeadersService
{
    /**
     * @return array<string, string>
     */
    public static function getContentTypeHeader(int $bodyType): array
    {
        $contentType = match ($bodyType) {
            SearchModel::BODY_TYPE_JSON => 'application/json',
            SearchModel::BODY_TYPE_TEXT => 'text/plain',
            SearchModel::BODY_TYPE_XML => 'application/xml',
            SearchModel::BODY_TYPE_HTML => 'text/html',
            SearchModel::BODY_TYPE_JAVASCRIPT => 'application/javascript',
            SearchModel::BODY_TYPE_FORM => 'application/x-www-form-urlencoded',
            default => 'application/json',
        };

        return [
            'Content-Type' => $contentType,
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function getBodyTypes(): array
    {
        return [
            SearchModel::BODY_TYPE_JSON => 'JSON',
            SearchModel::BODY_TYPE_TEXT => 'Text',
            SearchModel::BODY_TYPE_XML => 'XML',
            SearchModel::BODY_TYPE_HTML => 'HTML',
            SearchModel::BODY_TYPE_JAVASCRIPT => 'JavaScript',
            SearchModel::BODY_TYPE_FORM => 'Form',
        ];
    }
}
