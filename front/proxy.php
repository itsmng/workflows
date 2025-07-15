<?php

include("../../../inc/includes.php");

// Check if plugin is activated...
if (!(new Plugin())->isActivated('workflows')) {
    Html::displayNotFoundError();
} elseif (!PluginWorkflowsWorkflow::checkConnection()) {
    Html::displayErrorAndDie(__('Connection to BPMN engine failed', 'workflows'));
}

/** 
 * Check if the request is secure (HTTPS)
 * Port check is necessary to work with IIS
 * 
 * @return boolean
 */
function isSecure() {
    return
        (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || $_SERVER['SERVER_PORT'] == 443;
}

$path = $_GET['path'] ?? '/';
$proxySelf = (isSecure() ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'] . Plugin::getWebDir('workflows') . '/front/proxy.php';
$config = PluginWorkflowsConfig::getConfigValues();

$endpoint = $config['host'] . ':' . $config['port'] . $path;
$key = $config['key'];

$ch = curl_init();

$forwardedHeaders = [];
$allHeaders = getallheaders();
$headerBlacklist = ['host', 'content-length', 'content-type', 'cookie', 'accept-encoding'];

foreach ($allHeaders as $header => $value) {
    if (!in_array(strtolower($header), $headerBlacklist)) {
        $forwardedHeaders[] = "$header: $value";
    }
}

if ($key) {
    $forwardedHeaders[] = 'Authorization: Bearer ' . $key;
    $forwardedHeaders[] = 'x-api-key: ' . $key;
}

curl_setopt($ch, CURLOPT_URL, $endpoint);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
curl_setopt($ch, CURLOPT_HTTPHEADER, $forwardedHeaders);

$method = $_SERVER['REQUEST_METHOD'];
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);

if ($method === 'POST' || $method === 'PUT' || $method === 'PATCH') {
    $requestBody = file_get_contents('php://input');
    curl_setopt($ch, CURLOPT_POSTFIELDS, $requestBody);
    if (isset($_SERVER['CONTENT_TYPE'])) {
        curl_setopt($ch, CURLOPT_HTTPHEADER, array_merge($forwardedHeaders, ['Content-Type: ' . $_SERVER['CONTENT_TYPE']]));
    }
}

$response = curl_exec($ch);

if (curl_errno($ch)) {
    header('HTTP/1.1 502 Bad Gateway');
    echo '<h1>502 Bad Gateway</h1><p>The proxy server could not connect to the internal service.</p><p>Error: ' . curl_error($ch) . '</p>';
    curl_close($ch);
    exit;
}

$headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
$responseStatusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$responseHeadersRaw = substr($response, 0, $headerSize);
$responseBody = substr($response, $headerSize);
$responseContentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);

// Close the cURL session
curl_close($ch);

function resolve_relative_url(string $relativeUrl, string $basePath): string {
    // If it's already an absolute path, root-relative, or not a URL, return as is.
    if (parse_url($relativeUrl, PHP_URL_SCHEME) !== null || strpos($relativeUrl, '//') === 0 || $relativeUrl[0] === '/') {
        return $relativeUrl;
    }

    $baseDir = dirname($basePath);

    if (substr($basePath, -1) === '/') {
        $baseDir = rtrim($basePath, '/');
    }

    $fullPath = $baseDir . '/' . $relativeUrl;

    // Normalize the path (resolve '..' and '.')
    $pathParts = explode('/', $fullPath);
    $resolvedParts = [];
    foreach ($pathParts as $part) {
        if ($part === '.' || $part === '') {
            continue;
        }
        if ($part === '..') {
            array_pop($resolvedParts);
        } else {
            $resolvedParts[] = $part;
        }
    }

    return '/' . implode('/', $resolvedParts);
}

$rewriteCallback = function ($matches) use ($proxySelf, $path) {
    $originalUrl = $matches[3];
    
    if (preg_match('/^(https?|ftp):|\/\/|data:|mailto:|tel:|#|javascript:/i', $originalUrl)) {
        return $matches[0];
    }
    
    $resolvedPath = resolve_relative_url($originalUrl, $path);
    
    $newUrl = $proxySelf . '?path=' . urlencode($resolvedPath);
    
    // For HTML: $matches[1] = '<a href=', $matches[2] = '"', $matches[4] = '"'
    // For CSS:  $matches[1] = 'url(', $matches[2] = '"', $matches[4] = '")'
    return $matches[1] . $matches[2] . $newUrl . $matches[4];
};

if (strpos($responseContentType, 'text/html') !== false || $responseContentType === false) {
    $htmlRegex = '/(<(?:a|link|script|img|form|iframe|source|track)\s[^>]*?(?:href|src|action|poster|data)\s*=\s*)(["\'])([^"\']+)(["\'])/i';
    $responseBody = preg_replace_callback($htmlRegex, $rewriteCallback, $responseBody);
} elseif (strpos($responseContentType, 'text/css') !== false) {
    $cssRegex = '/(url\()(["\']?)([^)\'"]+)(["\']?\))/i';
    $responseBody = preg_replace_callback($cssRegex, $rewriteCallback, $responseBody);
}

http_response_code($responseStatusCode);

$headersArray = explode("\r\n", $responseHeadersRaw);
foreach ($headersArray as $header) {
    // Recalculate content-length if we modified the body.
    if (stripos($header, 'Content-Length:') !== false) {
        header('Content-Length: ' . strlen($responseBody));
        continue;
    }
    if (!empty($header) && stripos($header, 'Transfer-Encoding:') === false) {
        header($header, false);
    }
}

echo $responseBody;
