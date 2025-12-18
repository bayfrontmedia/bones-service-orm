<?php

namespace Bayfront\BonesService\Orm\Traits;

use Bayfront\Bones\Application\Utilities\App;
use Bayfront\BonesService\Orm\Exceptions\InvalidFieldException;
use Bayfront\BonesService\Orm\Exceptions\UnexpectedException;
use Bayfront\Encryptor\DecryptException;
use Bayfront\Encryptor\EncryptException;
use Bayfront\Encryptor\Encryptor;
use Bayfront\Encryptor\InvalidCipherException;
use Bayfront\Sanitize\Sanitize;
use Bayfront\StringHelpers\Str;
use Bayfront\Validator\Rules\IsJson;

/**
 * Cast fields to another type.
 */
trait Castable
{

    /**
     * Cast array to JSON-encoded string.
     *
     * @param mixed $value
     * @return string
     */
    protected function jsonEncode(mixed $value): string
    {
        if (is_array($value)) {
            return json_encode($value);
        }

        return (string)$value;
    }

    /**
     * Cast JSON-encoded string to array.
     *
     * @param mixed $value
     * @return array
     */
    protected function jsonDecode(mixed $value): array
    {
        $json = new IsJson($value);

        if ($json->isValid()) {
            $value = json_decode($value, true);
            ksort($value);
            return $value;
        }

        return (array)$value;
    }

    /**
     * Escape strings and arrays using UTF-8 character encoding.
     *
     * @param mixed $value
     * @return string
     */
    protected function escapeString(mixed $value): string
    {
        if (is_string($value)) {
            return Sanitize::escape($value);
        }

        return (string)$value;
    }

    /**
     * Cast to boolean.
     *
     * @param mixed $value
     * @return bool
     */
    protected function boolean(mixed $value): bool
    {
        return Sanitize::cast($value, Sanitize::CAST_BOOL);
    }

    /**
     * Cast to integer.
     *
     * @param mixed $value
     * @return int
     */
    protected function integer(mixed $value): int
    {
        return Sanitize::cast($value, Sanitize::CAST_INT);
    }

    /**
     * Cast empty string to NULL.
     *
     * @param mixed $value
     * @return mixed
     */
    protected function nullify(mixed $value): mixed
    {
        if (is_string($value) && $value == '') {
            return null;
        }

        return $value;
    }

    /**
     * Cast to lowercase + kebab case (URL-friendly slug).
     *
     * @param mixed $value
     * @return string
     */
    protected function slug(mixed $value): string
    {
        if (is_string($value)) {
            return Str::kebabCase($value, true);
        }

        return (string)$value;
    }

    /**
     * Convert 16 byte binary string to UUID.
     *
     * @param string $binary
     * @return string
     */
    protected function binToUuid(string $binary): string
    {
        return Str::binToUuid($binary);
    }

    /**
     * Convert UUID to 16 byte binary string.
     *
     * @param string $uuid
     * @return string
     */
    protected function uuidToBin(string $uuid): string
    {
        return Str::uuidToBin($uuid);
    }

    /**
     * Cast non-null and non-empty values to censored string.
     *
     * @param mixed $value
     * @return string|null
     */
    protected function censor(mixed $value): ?string
    {
        if ($value !== null && $value !== '') {
            return '********';
        }

        return $value;
    }

    /**
     * Cast integer timestamp to date string.
     *
     * @param mixed $value
     * @return string (Date in Y-m-d format)
     */
    protected function date(mixed $value): string
    {
        if (is_int($value)) {
            return date('Y-m-d', $value);
        }

        return (string)$value;
    }

    /**
     * Cast integer timestamp to datetime string.
     *
     * @param mixed $value
     * @return string (Date in Y-m-d H:i:s format)
     */
    protected function datetime(mixed $value): string
    {
        if (is_int($value)) {
            return date('Y-m-d H:i:s', $value);
        }

        return (string)$value;
    }

    /**
     * Cast date/time string to timestamp.
     *
     * @param mixed $value
     * @return int
     */
    protected function timestamp(mixed $value): int
    {
        if (is_string($value)) {
            $timestamp = strtotime($value);
            if ($timestamp) {
                return $timestamp;
            }
        }

        return (int)$value;
    }

    /**
     * Cast to encrypted string using the Bones app key.
     *
     * @param mixed $value
     * @return string
     * @throws InvalidFieldException
     * @throws UnexpectedException
     */
    protected function encrypt(mixed $value): string
    {
        try {
            $encryptor = new Encryptor(App::getConfig('app.key', ''));
            return $encryptor->encrypt($value);
        } catch (EncryptException) {
            throw new InvalidFieldException('Unable to encrypt field');
        } catch (InvalidCipherException) {
            throw new UnexpectedException('Unable to encrypt field: Invalid cipher');
        }
    }

    /**
     * Cast to decrypted value using the Bones app key.
     *
     * @param mixed $value
     * @return string
     * @throws InvalidFieldException
     * @throws UnexpectedException
     */
    protected function decrypt(mixed $value): mixed
    {
        try {
            $encryptor = new Encryptor(App::getConfig('app.key', ''));
            return $encryptor->decrypt($value);
        } catch (DecryptException) {
            throw new InvalidFieldException('Unable to decrypt field');
        } catch (InvalidCipherException) {
            throw new UnexpectedException('Unable to decrypt field: Invalid cipher');
        }

    }

}