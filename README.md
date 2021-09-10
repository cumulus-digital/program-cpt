# program-cpt

Custom post type and Gutenberg blocks for a "Program" custom post type.

Code standards set to quasi-wordpress with custom php-cs-fixer rules.

Distributed with non-dev vendor libraries in build/composer, scoped with php-scoper. Wordpress core symbols are excluded via snippets from
jason-pomerleau/vscode-wordpress-toolbox, see refresh script in
.php-cs-fixer/refresh.sh

When including future libraries for distribution, run ./rescope.sh after to ensure they're committed to the plugin.

NOTE: Uses extended-cpts, which does not currently support hierarchical taxonomy permalinks. See libs/extended-cpts-hierarchical-post-links.php
for override filter.

https://github.com/johnbillion/extended-cpts/pull/104/files
