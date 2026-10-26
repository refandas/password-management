<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= $title; ?></title>
<!-- #32 / ADR-0005: self-hosted Bootstrap 5 + Font Awesome — the shell
     makes no third-party requests. -->
<link rel="stylesheet" href="<?= base_url('assets/bootstrap/css/bootstrap.min.css'); ?>">
<link rel="stylesheet" href="<?= base_url('assets/fontawesome/css/all.min.css'); ?>">
<link rel="stylesheet" href="<?= base_url('assets/css/app.css'); ?>">
</head>
<body>
