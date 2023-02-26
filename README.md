# Fractional Indexing


This is based on [Implementing Fractional Indexing
](https://observablehq.com/@dgreensp/implementing-fractional-indexing) by [David Greenspan
](https://github.com/dgreensp).

Fractional indexing is a technique to create an ordering that can be used for [Realtime Editing of Ordered Sequences](https://www.figma.com/blog/realtime-editing-of-ordered-sequences/).

This implementation includes variable-length integers, and the prepend/append optimization described in David's article.

This implementation was based on the [JS Implementation from rocicorp](https://github.com/rocicorp/fractional-indexing).

## Installation

```bash
$ composer install arcweave/fractional-indexing
```

## Other Languages

This is a PHP port of the original JavaScript implementation by [@rocicorp](https://github.com/rocicorp). That means that this implementation is byte-for-byte compatible with:

| Language   | Repo                                                 |
| ---------- | ---------------------------------------------------- |
| JavaScript | https://github.com/rocicorp/fractional-indexing      |
| Go         | https://github.com/rocicorp/fracdex                  |
| PHP        | https://github.com/rocicorp/fractional-indexing      |