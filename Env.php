<?php

declare(strict_types=1);

namespace MaplePHP\Http;

use InvalidArgumentException;
use MaplePHP\DTO\Format;

class Env
{
    private $fileData = array();
    private $data = array();
    private $set = array();
    private $drop = array();

    public function __construct(?string $file = null)
    {
        if (!is_null($file) && is_file($file)) {
            $this->loadEnvFile($file);
        }
    }

    public function loadEnvFile(string $file): void
    {
        $this->fileData = parse_ini_file($file);
    }

    public function hasEnv(string $key): ?string
    {
        $key = $this->formatKey($key);
        return (isset($this->fileData[$key])) ? $key : null;
    }

    public function set(string $key, string $value): string
    {
        if ($keyB = $this->hasEnv($key)) {
            $this->fileData[$keyB] = $value;
        } else {
            $key = $this->formatKey($key);
            $this->set[$key] = $value;
        }
        return "{$key}={$value}";
    }

    public function get(string $key): string
    {
        $key = $this->formatKey($key);
        return $this->fileData[$key];
    }

    public function drop(string $key): void
    {
        $key = $this->formatKey($key);
        $this->drop[$key] = $key;
    }

    public function formatKey($key)
    {
        return Format\Str::value($key)->clearBreaks()->trim()->replaceSpecialChar()
                ->trimSpaces()->replaceSpaces("-")->toUpper()->get();
    }

    public function generateOutput(array $fromArr = ["data", "fileData", "set"])
    {
        $out = "";

        $data = array();
        $validData = ["data", "fileData", "set"];
        foreach ($validData as $d) {
            if (in_array($d, $fromArr)) {
                $data += $this->{$d};
            }
        }

        $length = count($data);
        foreach ($data as $key => $val) {
            if (empty($this->drop[$key])) {
                $key = $this->formatKey($key);
                $val = trim($val);
                if (!is_numeric($val) && ($val !== "true" || $val !== false)) {
                    $val = "'{$val}'";
                }
                $out .= "{$key}={$val}";
                if ($length > 1) {
                    $out .= "\n";
                }
            }
        }
        return $out;
    }

    public function putenv($key, $value): self
    {
        $this->data[$key] = $value;
        return $this;
    }

    public function putenvArray(array $array): self
    {
        foreach ($array as $prefix => $val) {
            $prefix = strtoupper($prefix);
            if (is_array($val)) {
                foreach ($val as $k1 => $v1) {
                    foreach ($v1 as $k2 => $v2) {
                        $newKey = strtoupper("{$k1}_{$k2}");
                        if (!isset($this->fileData[$newKey])) {
                            $this->data[$newKey] = $v2;
                        }
                    }
                }
            } else {
                $this->data[$prefix] = $val;
            }
        }
        $this->data = array_merge($this->data, $array);
        return $this;
    }

    private function put(array $data, bool $overwrite = false)
    {
        foreach ($data as $key => $value) {
            /*
            if (!$overwrite && getenv($key) !== false) {
                throw new InvalidArgumentException("The Environmental variable \"{$key}\" already exists. " .
                    "It's recommended to make every variable unique.", 1);
            }
            */
            $_ENV[$key] = $value;
            if (is_array($value)) {
                $value = json_encode($value);
            }
            putenv("{$key}={$value}");
        }
    }

    public function execute(bool $overwrite = false): void
    {
        if ($this->fileData) {
            $this->put($this->fileData, $overwrite);
        }
        if ($this->data) {
            $this->put($this->data, $overwrite);
        }
    }

    public function getData()
    {
        return $this->data + $this->fileData + $this->set;
    }
}
