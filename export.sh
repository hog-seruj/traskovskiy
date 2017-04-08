#!/bin/bash
DIR_DEFAULT_CONTENT="modules/custom/custom_default_content/content"

rm -rf ${DIR_DEFAULT_CONTENT}

drush cex -y

drush dcer block_content --folder=${DIR_DEFAULT_CONTENT}

drush dcer node --folder=${DIR_DEFAULT_CONTENT}

drush dcer taxonomy_term --folder=${DIR_DEFAULT_CONTENT}

drush dcer menu_link_content 1 --folder=${DIR_DEFAULT_CONTENT}

rm -rf ${DIR_DEFAULT_CONTENT}/user/
