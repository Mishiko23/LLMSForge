# LLMS Forge — llms.txt generator for MODX 3

This free extra publishes a Markdown guide to your site for AI agents. Curate `/llms.txt` in the MODX manager and create separate Markdown descriptions for important pages.

Features: automatic generation from selected resources or manually edited Markdown; sections, search and bulk selection; individual `/llms-txt/…` pages for products and articles; preview; context-specific settings; a 30-day request log for recognized AI bot User-Agents.

The extra does not guarantee indexing or citation by AI systems. Bot names are inferred from User-Agent and can be spoofed. The log counts requests that reach MODX. Automatic content uses standard MODX resource fields; miniShop3 prices, stock and properties, template variables and snippet output are not included.

Requirements: MODX Revolution 3, PHP 8.1+, friendly URL routing into MODX, and a PHP-writable `core/components/llmsforge/var/visits` directory for the request log.

Install the transport package through the MODX package manager. Open Components → LLMS Forge, enter a site title and summary, select pages, preview the output, enable publication and save. Check the public `/llms.txt` URL and the installation diagnostics in the component.

Use cases: company documentation, product catalogues with detailed Markdown cards, article collections, and multilingual sites configured by context.
