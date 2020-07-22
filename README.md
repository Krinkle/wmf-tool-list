[![Build Status](https://travis-ci.com/Krinkle/wmf-tool-list.svg?branch=master)](https://travis-ci.com/Krinkle/wmf-tool-list)

Wikimedia Mailing list utilities
=============

Redirect service for [Wikimedia mailing lists](https://lists.wikimedia.org/mailman/listinfo).

## Usage

Query parameters:

* `list`: Name of the mailing list
* `action`: One of:
  * `index` ([example](https://list.toolforge.org/?list=wikitech-l&action=index)): Redirect to the archive index
  * `thismonth` ([example](https://list.toolforge.org/?list=wikitech-l&action=thismonth)): Redirect to this month's archive
  * `lastentry` ([example](https://list.toolforge.org/?list=wikitech-l&action=lastentry)): Redirect to the most recent post on the list

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
3. Expose `wmf-tool-list/public_html/` from a web server that supports PHP.

For local development, run `composer serve` and open <http://localhost:4000>. Example query: <http://localhost:4000/?list=wikitech-l&action=thismonth>.
