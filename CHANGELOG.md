# v0.1.0
##  07-04-2023

1. [](#new)
  * Detects and displays file renames in a clearer way
2. [](#improved)
  * Switched out PHP Git libraries [#11](https://github.com/hughbris/grav-plugin-pushy/issues/11)
3. [](#bugfix)
  * Solve git errors on renames and deletes [#24](https://github.com/hughbris/grav-plugin-pushy/issues/24)

# v0.1.1
## 10-04-2023

1. [](#new)
  * Front end strings are now translateable [#29](https://github.com/hughbris/grav-plugin-pushy/issues/29), English and Italian currently provided
2. [](#improved)
  * Logging for back end issues [64e71ef](https://github.com/hughbris/grav-plugin-pushy/commit/64e71ef779e86f38bb93af3bb095aa75e2046410)
  * Changed items list layout styling, table to grid [b07c2e2](https://github.com/hughbris/grav-plugin-pushy/commit/b07c2e223b9994802cd641d054e20f336d375e60)
  * Much better display when changes are not detected
3. [](#bugfix)
  * Change count badge in Admin menu now counts renames as single change, as per changed items listing [c52dc16](https://github.com/hughbris/grav-plugin-pushy/commit/c52dc16652545179a6009de3b37271648d5f199f)