[![Build Status](https://travis-ci.com/Krinkle/wmf-tool-list.svg?branch=master)](https://travis-ci.com/Krinkle/wmf-tool-list)

Wikimedia Mailing Lists utilities
=============

Redirect service for [Wikimedia mailing lists](https://lists.wikimedia.org/mailman/listinfo).

## Usage

Query parameters:

* `name`: Name of the mailing list
* `action`: One of:
  * `index` ([example](https://tools.wmflabs.org/list/?name=wikitech-l&action=index)): Redirect to the archive index
  * `thismonth` ([example](https://tools.wmflabs.org/list/?name=wikitech-l&action=thismonth)): Redirect to this month's archive
  * `lastentry` ([example](https://tools.wmflabs.org/list/?name=wikitech-l&action=lastentry)): Redirect to the most recent post on the list

##  Short urls

* wikitech-l:
  * https://bit.ly/wikitechLatest
  * https://bit.ly/wikitechMonth
* toolserver-l:
  * https://bit.ly/toolserverLatest
  * https://bit.ly/toolserverMonth
* commons-l:
  * https://bit.ly/commonsLatest
  * https://bit.ly/commonsMonth
* cvn-l:
  * https://bit.ly/cvnLatest
  * https://bit.ly/cvnMonth

## Install

1. Clone this repository.
2. Run `composer update --no-dev` for deployment (_or `composer update` for local development_).
3. View `your-server/wmf-tool-list/public_html/` via a PHP-capable server (e.g. run `php -S` in public_html locally, or symlink public_html to a pre-existing public path).
