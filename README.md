# pf_pageparser

[![Latest Version on Packagist](https://img.shields.io/packagist/v/pforret/pf_pageparser.svg?style=flat-square)](https://packagist.org/packages/pforret/pf_pageparser)
[![Build Status](https://img.shields.io/travis/pforret/pf_pageparser/master.svg?style=flat-square)](https://travis-ci.org/pforret/pf_pageparser)
[![Quality Score](https://img.shields.io/scrutinizer/g/pforret/pf_pageparser.svg?style=flat-square)](https://scrutinizer-ci.com/g/pforret/pf_pageparser)
[![Total Downloads](https://img.shields.io/packagist/dt/pforret/pf_pageparser.svg?style=flat-square)](https://packagist.org/packages/pforret/pf_pageparser)

This is a HTML parser I've written because I scrape a lot of web sites to look for structured, repetitive data. 
This parser allows me to easily cleanup HTML, split it into chunks and find the right data in each chunk
It does not use a DOM parser, so it also works on partial or invalid HTML

## Installation

You can install the package via composer:

```bash
composer require pforret/pf_pageparser
```

## Usage

``` php
$pp=New PfPageparser(["CacheTime" => 300]);
$pp->load_from_url("http://www.example.com/products")
    ->trim("<table","</table>")
    ->split_chunks('</tr>')
    ->filter_chunks('product_id')
    ->parse_from_chunks('||',true);

$results=$pp->results();
```

### Testing

``` bash
composer test
```

### Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

### Security

If you discover any security related issues, please email peter@forret.com instead of using the issue tracker.

## Credits

- [Peter Forret](https://github.com/pforret)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

## PHP Package Boilerplate

This package was generated using the [PHP Package Boilerplate](https://laravelpackageboilerplate.com).
