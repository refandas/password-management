<?php

declare(strict_types=1);

namespace Tests\Acceptance;

/**
 * One observed HTTP response: status line, headers, body.
 */
final class HttpResponse
{
    private function __construct(
        public readonly int $statusCode,
        /** @var array<string, string> Lowercased header name => last value. */
        public readonly array $headers,
        public readonly string $body,
    ) {
    }

    public static function fromRaw(int $statusCode, int $headerSize, string $raw): self
    {
        $headerBlock = substr($raw, 0, $headerSize);
        $body = substr($raw, $headerSize);

        $headers = [];
        foreach (explode("\r\n", $headerBlock) as $line) {
            $sep = strpos($line, ':');
            if ($sep === false) {
                continue;
            }
            $headers[strtolower(trim(substr($line, 0, $sep)))] = trim(substr($line, $sep + 1));
        }

        return new self($statusCode, $headers, $body);
    }

    public function header(string $name): ?string
    {
        return $this->headers[strtolower($name)] ?? null;
    }

    /** Absolute URL from the Location header, or null when not a redirect. */
    public function location(): ?string
    {
        return $this->header('Location');
    }
}
