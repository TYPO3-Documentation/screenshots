#TYPO3 extension "Screenshots"

This extension is producing automated screenshots and gathering code-snippets from documentation.
The kind of the documentation and the screenshots can be configured, so that also unknown documents can be processed.
By default a list of the important TYPO3 documentation is used and can also be downloaded automatically. Included are also the documentations for the extensions `extension_builder` and `styleguide`.

##Version
This version is intended to run on TYPO3 v11.

##Requirements

 - The command `jq` has to be available in the Operating System and can be installed with `sudo apt-get install jq` if it's missing.
 - cUrl has to be available (@TODO: define more precisely if cUrl in general or php-curl).
 - @TODO: selenium, ...
