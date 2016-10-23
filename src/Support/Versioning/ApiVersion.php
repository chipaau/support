<?php 

namespace Support\Versioning;

use Illuminate\Http\Request;
use Support\Versioning\VersionException;

class ApiVersion {

    const DEFAULT_VERSION = 'v1';

    protected $request;
    protected $version;
    protected $versions = array('v1');

    public function __construct(Request $request, array $versions = array())
    {
        $this->request = $request;
        $this->setVersions($versions);
        $this->loadVersion();
    }

    public function setVersions($versions = array())
    {
        if (empty($versions)) return;
        $this->versions = array_merge($versions, $this->versions);
    }

    /**
     * Resolve the requested api version.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return integer
     */
    private function loadVersion() {
        $headers = $this->request->header();
        if (isset($headers['api-version'])) {
            $rawVersion = array_shift($headers['api-version']);
            $version = strtolower($rawVersion);
            if (is_numeric($version)) {
                $this->version = 'v'.$version;
            } else {
                $this->version = $version;
            }
        } else {
            $this->version = self::DEFAULT_VERSION;
        }

        if (!in_array($this->version, $this->versions)) {
            throw new VersionException($rawVersion . " is an invalid version number.");
        }
    }

    /**
     * Resolve namespace for a api version
     *
     * @param integer $apiVersion
     * @return string
     */
    public function getVersion()
    {
        return $this->version;
    }

    public function getVersonNameSpace()
    {
        return strtoupper($this->version);
    }

}