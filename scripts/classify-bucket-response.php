<?php

declare(strict_types=1);

if ($argc < 4) {
    fwrite(STDERR, "Usage: php classify-bucket-response.php <s3|gcs> <http-code> <body-file>\n");
    exit(2);
}

$service = $argv[1];
$httpCode = (int) $argv[2];
$bodyPath = $argv[3];

if (!is_readable($bodyPath)) {
    fwrite(STDERR, "Body file is not readable: {$bodyPath}\n");
    exit(2);
}

$body = file_get_contents($bodyPath);

if ($httpCode >= 200 && $httpCode < 300) {
    exit(0);
}

if ($service === 's3') {
    if (409 === $httpCode) {
        $previous = libxml_use_internal_errors(true);
        $xml = simplexml_load_string($body);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        if (false !== $xml && 'BucketAlreadyOwnedByYou' === (string) $xml->Code) {
            exit(0);
        }
    }

    fwrite(STDERR, sprintf("S3 bucket create failed (HTTP %d):\n%s\n", $httpCode, $body));
    exit(1);
}

if ($service === 'gcs') {
    if (409 === $httpCode) {
        $json = json_decode($body, true);
        if (is_array($json)
            && isset($json['error']['code'])
            && 409 === (int) $json['error']['code']
        ) {
            $errors = $json['error']['errors'] ?? [];
            if (is_array($errors)) {
                foreach ($errors as $error) {
                    if (is_array($error) && 'conflict' === ($error['reason'] ?? '')) {
                        exit(0);
                    }
                }
            }
        }
    }

    fwrite(STDERR, sprintf("GCS bucket create failed (HTTP %d):\n%s\n", $httpCode, $body));
    exit(1);
}

fwrite(STDERR, sprintf("Unknown service %s\n", $service));
exit(2);
