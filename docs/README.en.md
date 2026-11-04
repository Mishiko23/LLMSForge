# LLMS Forge for MODX 3

LLMS Forge is a free GPL-3.0 extra that publishes `/llms.txt` and individual Markdown cards for selected site pages. Publisher: [Mishiko23](https://github.com/Mishiko23).

## Features

- Build the main file from selected MODX resources or edit its Markdown manually. Select up to 5000 resource IDs and organize links into sections.
- Create up to 200 individual cards at `/llms-txt/slug`. Automatic cards can include resource content, selected TVs, and miniShop3 fields: `price`, `old_price`, `article`, `weight`, `vendor`, and `made_in`.
- Search resources, create cards in bulk, preview and download documents, inspect installation checks, and view request statistics for known AI bots.
- Configure each MODX context separately. MODX serves the URLs dynamically; the extra does not write a physical file at the site root.

Publishing `llms.txt` does not guarantee AI indexing or citations. Bot names come from User-Agent strings and are not verified by IP.

## Requirements and installation

Requires MODX Revolution 3, PHP 8.1+, DOM, JSON, mbstring, and working Friendly URLs. The web server must route unknown `/llms.txt` and `/llms-txt/…` requests into MODX. An existing file or resource at these paths takes precedence. PHP needs write access to `core/components/llmsforge/var/visits` for request statistics.

1. Install the transport package with the MODX package manager.
2. Open **Components → LLMS Forge**, select a site context, and run the installation check.
3. Set the site title and summary. Select pages or edit Markdown manually; optionally specify TV and miniShop3 fields.
4. Preview, enable publication, and save.
5. Check `/llms.txt` and an individual card in an anonymous browser session.

Install a newer package over the existing version to update. Context settings remain in place.

## Access rules and limits

Automatic export excludes deleted, unpublished, non-searchable, resource-group protected, and inaccessible resources. Publication dates and ancestor resources are checked. Integrators can veto additional resources through `OnLLMSForgeResourceCheck`. HTML is converted to Markdown without executing MODX snippets.

Manual cards are independent public text and do not automatically disappear when a source page becomes private. Use an automatic resource card when access must follow the resource. miniShop3 fields appear in automatic cards, not next to links in the main file. WebLink and SymLink targets and StaticResource file contents are not expanded.

The request log retains up to 30 days and 5 MB per day. It stores neither IP addresses, URL query parameters, cookies, nor full User-Agent strings. It records requests that reach MODX, not confirmed reads or citations.

For source builds and tests, see the [Russian README](../README.md) and [release checklist](release-checklist.md). Changes are listed in [CHANGELOG](../CHANGELOG.md).
