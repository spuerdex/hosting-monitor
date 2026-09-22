<?php

declare(strict_types=1);

namespace Digit\HostingAdmin\Hosting;

use Closure;
use DateTimeImmutable;
use Exception;
use RuntimeException;

final class MultiHostStatusRepository
{
    public function __construct(
        private string $registryPath,
        private string $cacheDir,
        private int $maxAgeSeconds = 180,
        private ?Closure $readFile = null
    ) {
    }

    public function all(?DateTimeImmutable $now = null): array
    {
        $now ??= new DateTimeImmutable();

        $registry = $this->readJson($this->registryPath);

        if (
            $registry === null
            || ($registry['schema_version'] ?? null) !== 1
            || !isset($registry['hosts'])
            || !is_array($registry['hosts'])
            || !array_is_list($registry['hosts'])
        ) {
            throw new RuntimeException('Invalid host registry.');
        }

        $result = [];
        $seenCodes = [];

        foreach ($registry['hosts'] as $host) {
            if (!is_array($host)) {
                throw new RuntimeException('Invalid host registry.');
            }

            $code = $host['code'] ?? null;
            $enabled = $host['enabled'] ?? null;

            if (
                !is_string($code)
                || preg_match('/^[a-z][a-z0-9_-]{0,31}$/D', $code) !== 1
                || in_array($code, ['status', 'metadata'], true)
                || !is_string($host['ip'] ?? null)
                || filter_var(
                    $host['ip'],
                    FILTER_VALIDATE_IP,
                    FILTER_FLAG_IPV4
                ) === false
                || !is_bool($enabled)
            ) {
                throw new RuntimeException('Invalid host registry.');
            }

            // Accept only the fields defined by Host Registry v1.
            $expectedFields = [
                'code',
                'enabled',
                'ip',
                'name',
                'ssh_user',
            ];

            $actualFields = array_keys($host);
            sort($actualFields);

            if (
                $actualFields !== $expectedFields
                || !is_string($host['name'])
                || trim($host['name']) === ''
                || $host['ssh_user'] !== 'hostingportal'
            ) {
                throw new RuntimeException('Invalid host registry.');
            }

            if (isset($seenCodes[$code])) {
                throw new RuntimeException('Duplicate host code.');
            }

            $seenCodes[$code] = true;

            if (!$enabled) {
                continue;
            }

            $record = [
                'code' => $code,
                'name' => $host['name'] ?? $code,
                'ip' => $host['ip'] ?? '',
                'fetch_state' => 'UNKNOWN',
                'display_state' => 'UNAVAILABLE',
                'last_attempt' => null,
                'last_success' => null,
                'status' => null,
                'account_count' => null,
            ];

            $metadataPath =
                $this->cacheDir . '/metadata/' . $code . '.json';

            $metadataRaw = $this->readRaw($metadataPath);
            $metadata = $this->decodeJson($metadataRaw);

            if (
                $metadata === null
                || ($metadata['host_code'] ?? null) !== $code
                || !$this->isIsoTimestamp(
                    $metadata['last_attempt'] ?? null
                )
                || !in_array(
                    $metadata['fetch_state'] ?? null,
                    ['SUCCESS', 'PUBLISHING', 'UNREACHABLE'],
                    true
                )
                || (
                    ($metadata['last_success'] ?? null) !== null
                    && !$this->isIsoTimestamp($metadata['last_success'])
                )
            ) {
                $result[$code] = $record;
                continue;
            }

            $record['fetch_state'] = $metadata['fetch_state'] ?? 'UNKNOWN';
            $record['last_attempt'] = $metadata['last_attempt'] ?? null;
            $record['last_success'] = $metadata['last_success'] ?? null;

            if ($record['fetch_state'] !== 'SUCCESS') {
                $record['display_state'] = $record['fetch_state'];
                $result[$code] = $record;
                continue;
            }

            if (!$this->isIsoTimestamp($record['last_success'])) {
                $result[$code] = $record;
                continue;
            }

            try {
                $lastSuccess = new DateTimeImmutable(
                    (string) $record['last_success']
                );
            } catch (Exception $e) {
                $result[$code] = $record;
                continue;
            }

            $age = $now->getTimestamp() - $lastSuccess->getTimestamp();

            if ($age < 0 || $age > $this->maxAgeSeconds) {
                $record['display_state'] = 'STALE';
                $result[$code] = $record;
                continue;
            }

            $cacheRaw = $this->readRaw(
                $this->cacheDir . '/' . $code . '.json'
            );

            $metadataAfter = $this->readRaw($metadataPath);

            if (
                $metadataRaw === null
                || $metadataAfter === null
                || !hash_equals($metadataRaw, $metadataAfter)
            ) {
                $result[$code] = $record;
                continue;
            }

            $status = $this->decodeJson($cacheRaw);

            if (
                $status === null
                || ($status['schema_version'] ?? null) !== 1
                || !array_key_exists('backup', $status)
                || !is_array($status['backup'])
                || !is_string($status['generated_at'] ?? null)
                || trim($status['generated_at']) === ''
                || !is_array($status['services'] ?? null)
                || !is_array($status['storage'] ?? null)
                || !is_array($status['summary'] ?? null)
                || !in_array(
                    $status['overall_status'] ?? null,
                    ['HEALTHY', 'WARNING'],
                    true
                )
                || !isset($status['students'])
                || !is_array($status['students'])
                || !array_is_list($status['students'])
            ) {
                $result[$code] = $record;
                continue;
            }

            // Reject malformed generated_at before exposing the cache.
            $generatedAt = $status['generated_at'];

            if (preg_match(
                '~^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(?:\.\d+)?(?:Z|[+-]\d{2}:\d{2})$~D',
                $generatedAt
            ) !== 1) {
                $result[$code] = $record;
                continue;
            }

            try {
                new DateTimeImmutable($generatedAt);
                $dateErrors = DateTimeImmutable::getLastErrors();

                if (
                    $dateErrors !== false
                    && (
                        $dateErrors['warning_count'] > 0
                        || $dateErrors['error_count'] > 0
                    )
                ) {
                    $result[$code] = $record;
                    continue;
                }
            } catch (Exception $e) {
                $result[$code] = $record;
                continue;
            }

            foreach ($status['students'] as $student) {
                if (!is_array($student)) {
                    $result[$code] = $record;
                    continue 2;
                }
            }

            $record['status'] = $status;
            $record['account_count'] = count($status['students']);
            $record['display_state'] = $status['overall_status'];

            $result[$code] = $record;
        }

        return $result;
    }

    private function isIsoTimestamp(mixed $value): bool
    {
        if (
            !is_string($value)
            || preg_match(
                '~^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(?:\.\d+)?(?:Z|[+-]\d{2}:\d{2})$~D',
                $value
            ) !== 1
        ) {
            return false;
        }

        try {
            new DateTimeImmutable($value);

            $errors = DateTimeImmutable::getLastErrors();

            return $errors === false
                || (
                    $errors['warning_count'] === 0
                    && $errors['error_count'] === 0
                );
        } catch (Exception $e) {
            return false;
        }
    }

    private function readJson(string $path): ?array
    {
        return $this->decodeJson($this->readRaw($path));
    }

    private function decodeJson(?string $raw): ?array
    {
        if ($raw === null) {
            return null;
        }

        $data = json_decode($raw, true);

        return json_last_error() === JSON_ERROR_NONE
            && is_array($data)
            ? $data
            : null;
    }

    private function readRaw(string $path): ?string
    {
        if (is_link($path) || !is_file($path)) {
            return null;
        }

        $size = filesize($path);

        if ($size === false || $size > 1048576) {
            return null;
        }

        $raw = $this->readFile !== null
            ? ($this->readFile)($path)
            : file_get_contents($path);

        if (!is_string($raw) || strlen($raw) > 1048576) {
            return null;
        }

        return $raw;
    }
}
