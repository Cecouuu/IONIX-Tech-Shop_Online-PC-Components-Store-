<?php
declare(strict_types=1);

const SITE_NAME = 'IONIX Tech Shop';
const SITE_LOGO_PATH = 'assets/images/ionix-mark.svg';
const SITE_WORDMARK_PRIMARY = 'IONIX';
const SITE_WORDMARK_SECONDARY = 'TECH SHOP';

function site_name(): string
{
    return SITE_NAME;
}

function site_logo_path(): string
{
    return SITE_LOGO_PATH;
}

function site_wordmark_primary(): string
{
    return SITE_WORDMARK_PRIMARY;
}

function site_wordmark_secondary(): string
{
    return SITE_WORDMARK_SECONDARY;
}

function site_title(?string $pageTitle = null): string
{
    $pageTitle = trim((string)$pageTitle);
    if ($pageTitle === '') {
        return SITE_NAME;
    }

    return $pageTitle . ' | ' . SITE_NAME;
}
