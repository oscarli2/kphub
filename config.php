<?php
// config.php
if (!function_exists('app_config')) {
    function app_config() {
      static $cfg = null;
      if ($cfg !== null) return $cfg;
      $path = __DIR__ . '/settings.json';
      $cfg = ["storage_mode" => "gdrive"];
      if (file_exists($path)) {
        $json = json_decode(file_get_contents($path), true);
        if (is_array($json) && isset($json["storage_mode"])) {
          $cfg["storage_mode"] = $json["storage_mode"] === "local" ? "local" : "gdrive";
        }
      }
      return $cfg;
    }
}
