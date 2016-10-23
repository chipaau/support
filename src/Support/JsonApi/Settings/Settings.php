<?php 

namespace Support\JsonApi\Settings;

class Settings
{
    const SCHEMAS_PATH = 'schemas';

    /** Config key for json options section */
    const JSON = 'json';

    /** Config key for json_encode options */
    const JSON_OPTIONS = 'options';

    /** Default value for json_encode options */
    const JSON_OPTIONS_DEFAULT = 0;

    /** Config key for json_encode max depth */
    const JSON_DEPTH = 'depth';

    /** Default value for json_encode max depth */
    const JSON_DEPTH_DEFAULT = 512;

    /** If JSON API version should be shown in top-level 'jsonapi' section */
    const JSON_IS_SHOW_VERSION = 'showVer';

    /** Default value for 'show JSON API version' */
    const JSON_IS_SHOW_VERSION_DEFAULT = true;

    /** Config key for JSON API version meta information */
    const JSON_VERSION_META = 'verMeta';

    /** Config key for URL prefix that will be added to all document links which have $treatAsHref flag set to false */
    const JSON_URL_PREFIX = 'urlPrefix';
}
