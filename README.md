<div style="text-align: center;" align="center">

![NAF](assets/naf-logo-small-square.png)

[![NAF CLI Plugin](https://github.com/nafphp/cli/actions/workflows/php.yml/badge.svg)](https://github.com/nafphp/cli/actions/workflows/php.yml)

</div>

[← Back to NAF](https://github.com/nafphp/framework)

---

# naf/cli

> **A minimal, developer-friendly command-line interface for your NAF application.**

This plugin gives you a clean CLI system with colored output, argument parsing, and auto-discovered commands. All without external dependencies.

> 🧩 Part of the official NAF plugin collection. Install it if you want powerful CLI tools for development, deployment, and automation.

## Documentation

**[Console commands →](https://nafphp.github.io/docs/console/)**

Everything about this package — what it does, how it is configured and what it needs — lives
in the [NAF documentation](https://nafphp.github.io/docs/). Not sure which packages you need?
[Start here](https://nafphp.github.io/docs/choosing-packages/).

## Install

```bash
composer require naf/cli
```

## License

MIT. Part of [NAF](https://github.com/nafphp/framework).


## Behavior notes

The binary resolves Composer autoload paths before locating the application bootstrap. Invoking vendor/bin/naf through a relative Composer proxy now finds the same host bootstrap as the absolute path.

## PHP code style

Source, tests and PHP templates follow the shared [NAF code style](https://github.com/nafphp/docs/blob/main/CODE_STYLE.md)
(PER Coding Style 3.0 with the Nafinity readability rules). After `composer install`, run
`composer style:check` to verify formatting or `composer style:fix` to apply it. The formatter
is a development dependency. Review template output and run the package checks after changes.
