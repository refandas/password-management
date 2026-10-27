<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= $title; ?></title>
<!-- #32 / ADR-0005: self-hosted Bootstrap 5 + Font Awesome — the shell
     makes no third-party requests. The shell's own files carry a
     filemtime version, so deploys bust browser caches without bumps. -->
<link rel="stylesheet" href="<?= base_url('assets/bootstrap/css/bootstrap.min.css'); ?>">
<link rel="stylesheet" href="<?= base_url('assets/fontawesome/css/all.min.css'); ?>">
<link rel="stylesheet" href="<?= base_url('assets/css/app.css'); ?>?v=<?= filemtime(FCPATH . 'assets/css/app.css'); ?>">
</head>
<body>
