<?php

declare(strict_types=1);

namespace App\View;

final class ShellView
{
    public function render(array $boot): string
    {
        $appName = htmlspecialchars((string) ($boot['appName'] ?? 'Community Site'), ENT_QUOTES, 'UTF-8');
        $jsonBoot = json_encode($boot, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return <<<HTML
<!DOCTYPE html>
<html lang="ko">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>{$appName}</title>
  <link rel="preconnect" href="https://cdn.jsdelivr.net">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="/assets/styles.css">
</head>
<body>
  <div id="app"></div>
  <script>
    window.APP_BOOT = {$jsonBoot};
  </script>
  <script src="https://cdn.jsdelivr.net/npm/vue@3.5.13/dist/vue.global.prod.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="/assets/app.js"></script>
</body>
</html>
HTML;
    }
}

