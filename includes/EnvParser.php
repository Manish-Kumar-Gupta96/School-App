<?php
class EnvParser {
    public static function load($filePath) {
        if (!file_exists($filePath)) {
            return false;
        }

        $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            // Drop lines treated as documentation comments
            if (strpos(trim($line), '#') === 0) {
                continue;
            }

            list($name, $value) = explode('=', $line, 2);
            $name = trim($name);
            $value = trim($value);

            // Strip optional quotes encapsulating variable values
            $value = trim($value, '"\'');

            if (!array_key_exists($name, $_ENV)) {
                putenv("{$name}={$value}");
                $_ENV[$name] = $value;
            }
        }
        return true;
    }
}

// Configuration Initialization Trigger
EnvParser::load(dirname(__DIR__) . DIRECTORY_SEPARATOR . '.env');
?>
