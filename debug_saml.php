<?php
require '/var/www/html/vendor/autoload.php';

$metadataUrl = getenv('IAM_PROXY_SAML2_IDP_METADATA_URL_INTERNAL') ?: getenv('IAM_PROXY_SAML2_IDP_METADATA_URL');
if (!$metadataUrl) {
    echo "ERROR: Metadata URL not set\n";
    exit(1);
}

echo "Metadata URL: $metadataUrl\n";

$ch = curl_init($metadataUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
$xml = curl_exec($ch);
$status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Status: $status\n";
if ($status != 200 || !$xml) {
    echo "ERROR: Failed to download metadata. Status: $status\n";
    // echo "Response: $xml\n";
    exit(1);
}

echo "Metadata downloaded successfully (" . strlen($xml) . " bytes)\n";

$doc = new DOMDocument();
if (!$doc->loadXML($xml)) {
    echo "ERROR: Failed to parse XML\n";
    exit(1);
}

$xp = new DOMXPath($doc);
$xp->registerNamespace('md', 'urn:oasis:names:tc:SAML:2.0:metadata');
$xp->registerNamespace('ds', 'http://www.w3.org/2000/09/xmldsig#');

$entityId = '';
$entityNode = $xp->query('/md:EntityDescriptor')->item(0);
if ($entityNode) {
    $entityId = $entityNode->getAttribute('entityID');
}
echo "EntityID: $entityId\n";

$ssoUrl = '';
$ssoNodes = $xp->query('//md:IDPSSODescriptor/md:SingleSignOnService[@Binding="urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect"]');
if ($ssoNodes->length > 0) {
    $ssoUrl = $ssoNodes->item(0)->getAttribute('Location');
}
echo "SSO URL: $ssoUrl\n";

$certNodes = $xp->query('//md:IDPSSODescriptor/md:KeyDescriptor[@use="signing"]//ds:X509Certificate');
if ($certNodes->length > 0) {
    echo "Certificate found (signing)\n";
} else {
    $certNodes = $xp->query('//md:IDPSSODescriptor//ds:X509Certificate');
    if ($certNodes->length > 0) {
        echo "Certificate found (fallback)\n";
    } else {
        echo "ERROR: Certificate not found\n";
    }
}

if ($entityId && $ssoUrl && $certNodes->length > 0) {
    echo "SUCCESS: Metadata valid for SAML Auth\n";
} else {
    echo "FAILURE: Missing required metadata fields\n";
}
