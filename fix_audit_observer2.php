<?php

$file = 'app/Observers/AuditObserver.php';
$content = file_get_contents($file);

$content = str_replace('protected $sensitiveFields = [', "/** @var array<int, string> */\n    protected array \$sensitiveFields = [", $content);
$content = str_replace('protected $ignoredModels = [', "/** @var array<int, string> */\n    protected array \$ignoredModels = [", $content);
$content = str_replace('$user?->email ?? \'system\'', '$user->email ?? \'system\'', $content);
$content = str_replace('$user?->name ?? \'System\'', '$user->name ?? \'System\'', $content);
$content = str_replace('$config?->retention_days ?? 90', '$config->retention_days ?? 90', $content);

file_put_contents($file, $content);
